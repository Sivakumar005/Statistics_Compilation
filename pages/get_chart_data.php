<?php
// Start output buffering to catch any unintended output
ob_start();

// Disable error display
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON content type header
header('Content-Type: application/json');

try {
    // Clear any output buffer content to prevent accidental output
    ob_clean();

    session_start();
    require_once '../includes/config.php';
    require_once '../includes/auth.php';
    require_once '../includes/db.php';

    // Check if user is logged in
    if (!isLoggedIn()) {
        throw new Exception('Not authenticated', 401);
    }

    // Define UPLOAD_DIR if not already defined
    if (!defined('UPLOAD_DIR')) {
        define('UPLOAD_DIR', __DIR__ . '/../uploads/');
    }

    // Get parameters
    $dataset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    $chart_type = isset($_GET['type']) ? $_GET['type'] : 'bar';

    // Debug log
    error_log("get_chart_data.php - Parameters: dataset_id=$dataset_id, start_date=$start_date, end_date=$end_date, chart_type=$chart_type");

    // Validate dataset ID
    if ($dataset_id <= 0) {
        throw new Exception('Invalid dataset ID', 400);
    }

    // Check if dataset belongs to user
    $check_query = "SELECT dataset_name, file_path, upload_date FROM datasets WHERE id = ? AND user_id = ?";
    $check_stmt = $mysqli->prepare($check_query);
    if (!$check_stmt) {
        throw new Exception('Database prepare error: ' . $mysqli->error, 500);
    }
    $check_stmt->bind_param("ii", $dataset_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Dataset not found or unauthorized', 404);
    }

    $dataset = $result->fetch_assoc();
    $check_stmt->close();

    // Check if dataset falls within date range (based on upload_date)
    if ($start_date && $end_date) {
        $upload_date = strtotime($dataset['upload_date']);
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);

        if ($upload_date < $start_time || $upload_date > $end_time) {
            // Dataset not in range, return empty chart data
            echo json_encode([
                'success' => true,
                'type' => 'bar',
                'labels' => [],
                'values' => [],
                'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'label' => $dataset['dataset_name']
            ]);
            exit();
        }
    }

    // Try to get existing chart data from the charts table
    $query = "SELECT * FROM charts WHERE dataset_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $mysqli->error, 500);
    }
    $stmt->bind_param("i", $dataset_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $labels = [];
    $values = [];
    $backgroundColor = 'rgba(54, 162, 235, 0.2)';
    $borderColor = 'rgba(54, 162, 235, 1)';
    $chart_type = 'bar'; // Default chart type

    if ($result->num_rows > 0) {
        // Chart exists, use its data
        $chart = $result->fetch_assoc();
        $chart_data = json_decode($chart['chart_data'], true);
        if ($chart_data && isset($chart_data['labels']) && isset($chart_data['data'])) {
            $labels = $chart_data['labels'];
            $values = $chart_data['data'];
            $chart_type = $chart['chart_type'];
        }
    }

    // If no chart data exists or chart data is invalid, fetch from the dataset file
    if (empty($labels) || empty($values)) {
        // Read the file content
        $file_path = UPLOAD_DIR . $dataset['file_path'];
        if (!file_exists($file_path) || !is_readable($file_path)) {
            throw new Exception('Dataset file not found or inaccessible', 404);
        }

        // Read and parse the file based on its extension
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $data = [];

        switch ($extension) {
            case 'csv':
                if (($handle = fopen($file_path, "r")) === false) {
                    throw new Exception('Failed to open CSV file', 500);
                }
                // Skip header row
                fgetcsv($handle);
                while (($row = fgetcsv($handle)) !== false) {
                    if (isset($row[0]) && isset($row[1]) && $row[0] !== '' && $row[1] !== '') {
                        $data[] = [$row[0], floatval($row[1])];
                    }
                }
                fclose($handle);
                break;

            case 'json':
                $json_content = file_get_contents($file_path);
                if ($json_content === false) {
                    throw new Exception('Failed to read JSON file', 500);
                }
                $json_data = json_decode($json_content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON format: ' . json_last_error_msg(), 400);
                }
                if (!is_array($json_data)) {
                    throw new Exception('JSON data must be an array', 400);
                }
                foreach ($json_data as $row) {
                    if (isset($row[0]) && isset($row[1]) && $row[0] !== '' && $row[1] !== '') {
                        $data[] = [$row[0], floatval($row[1])];
                    }
                }
                break;

            case 'txt':
                $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines === false) {
                    throw new Exception('Failed to read TXT file', 500);
                }
                // Skip header row
                array_shift($lines);
                foreach ($lines as $line) {
                    $row = explode("\t", trim($line));
                    if (isset($row[0]) && isset($row[1]) && $row[0] !== '' && $row[1] !== '') {
                        $data[] = [$row[0], floatval($row[1])];
                    }
                }
                break;

            default:
                throw new Exception('Unsupported file format: ' . $extension, 400);
        }

        if (empty($data)) {
            throw new Exception('No valid data points found in dataset', 400);
        }

        // Apply date filtering to the data points
        if ($start_date && $end_date) {
            $start_time = strtotime($start_date);
            $end_time = strtotime($end_date);
            if ($start_time === false || $end_time === false) {
                throw new Exception('Invalid date format', 400);
            }
            if ($start_time > $end_time) {
                throw new Exception('Start date must be before end date', 400);
            }

            $filtered_data = array_filter($data, function($row) use ($start_time, $end_time) {
                $date = strtotime($row[0]);
                return $date !== false && $date >= $start_time && $date <= $end_time;
            });

            $data = array_values($filtered_data); // Re-index array
        }

        // Prepare chart data
        $labels = array_column($data, 0);
        $values = array_column($data, 1);
    }

    // Set colors based on chart type
    if ($chart_type === 'pie') {
        $backgroundColor = array_map(function() {
            return sprintf('rgba(%d, %d, %d, 0.2)', rand(0, 255), rand(0, 255), rand(0, 255));
        }, array_fill(0, count($values), null));
        $borderColor = array_map(function($color) {
            return str_replace('0.2', '1', $color);
        }, $backgroundColor);
    }

    // Prepare response
    $response = [
        'success' => true,
        'type' => $chart_type,
        'labels' => $labels,
        'values' => $values,
        'backgroundColor' => $backgroundColor,
        'borderColor' => $borderColor,
        'label' => $dataset['dataset_name']
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in get_chart_data.php: " . $e->getMessage());
    http_response_code($e->getCode() >= 400 && $e->getCode() <= 599 ? $e->getCode() : 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();