<?php
// Start output buffering to catch any unintended output
ob_start();

// Disable error display (already in your code, but ensuring it’s early)
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

    // Define UPLOAD_DIR if not already defined
    if (!defined('UPLOAD_DIR')) {
        define('UPLOAD_DIR', __DIR__ . '/../uploads/');
    }

    // Check if user is logged in using auth function (assuming isLoggedIn exists)
    if (!isset($_SESSION['user_id']) || !isLoggedIn()) {
        throw new Exception('Unauthorized', 401);
    }

    // Get dataset ID from request
    $dataset_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($dataset_id === false || $dataset_id === null || $dataset_id <= 0) {
        throw new Exception('Invalid or missing dataset ID', 400);
    }

    // Verify dataset belongs to user and get its data
    $user_id = (int)$_SESSION['user_id'];
    $check_query = "SELECT dataset_name, file_path FROM datasets WHERE id = ? AND user_id = ?";
    $check_stmt = $mysqli->prepare($check_query);
    if (!$check_stmt) {
        throw new Exception('Database prepare error: ' . $mysqli->error, 500);
    }
    $check_stmt->bind_param("ii", $dataset_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $dataset = $result->fetch_assoc();
    $check_stmt->close();

    if (!$dataset) {
        throw new Exception('Dataset not found or unauthorized', 404);
    }

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

    // Prepare chart data
    $labels = array_column($data, 0);
    $values = array_column($data, 1);

    // Get chart type from request or use default
    $allowed_chart_types = ['bar', 'line', 'pie', 'scatter'];
    $chart_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING) ?: 'bar';
    if (!in_array($chart_type, $allowed_chart_types)) {
        $chart_type = 'bar'; // Fallback to default
    }

    // Set colors based on chart type
    if ($chart_type === 'pie') {
        $backgroundColor = array_map(function() {
            return sprintf('rgba(%d, %d, %d, 0.2)', rand(0, 255), rand(0, 255), rand(0, 255));
        }, array_fill(0, count($values), null));
        $borderColor = array_map(function($color) {
            return str_replace('0.2', '1', $color);
        }, $backgroundColor);
    } else {
        $backgroundColor = 'rgba(54, 162, 235, 0.2)';
        $borderColor = 'rgba(54, 162, 235, 1)';
    }

    // Apply date filters if provided
    $date_start = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_STRING);
    $date_end = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_STRING);
    
    if ($date_start && $date_end) {
        $start_time = strtotime($date_start);
        $end_time = strtotime($date_end);
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
        
        if (!empty($filtered_data)) {
            $filtered_data = array_values($filtered_data); // Re-index array
            $labels = array_column($filtered_data, 0);
            $values = array_column($filtered_data, 1);
        }
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

    // Ensure the response is valid JSON
    $json_response = json_encode($response);
    if ($json_response === false) {
        throw new Exception('Failed to encode JSON response: ' . json_last_error_msg(), 500);
    }

    echo $json_response;

} catch (Exception $e) {
    // Clear any output buffer content to prevent accidental output
    ob_clean();

    http_response_code($e->getCode() >= 400 && $e->getCode() <= 599 ? $e->getCode() : 500);
    $error_response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    $json_error_response = json_encode($error_response);
    if ($json_error_response === false) {
        // Fallback in case JSON encoding fails
        $json_error_response = json_encode([
            'success' => false,
            'error' => 'Internal server error: Failed to encode error response'
        ]);
    }
    echo $json_error_response;
}

// End output buffering and ensure no additional output
ob_end_flush();