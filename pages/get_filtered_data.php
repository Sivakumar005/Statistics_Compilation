<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Get parameters
$dataset_id = isset($_GET['dataset_id']) ? intval($_GET['dataset_id']) : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Debug log
error_log("Received parameters - dataset_id: $dataset_id, start_date: $start_date, end_date: $end_date");

// Validate parameters
if (!$dataset_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Dataset ID is required']);
    exit();
}

try {
    // First check if the dataset falls within the date range
    if ($start_date && $end_date) {
        $check_query = "SELECT COUNT(*) as count 
                       FROM datasets 
                       WHERE id = ? 
                       AND user_id = ? 
                       AND DATE(upload_date) BETWEEN ? AND ?";
        
        $check_stmt = $mysqli->prepare($check_query);
        if (!$check_stmt) {
            throw new Exception("Prepare check failed: " . $mysqli->error);
        }

        $check_stmt->bind_param("iiss", $dataset_id, $_SESSION['user_id'], $start_date, $end_date);
        if (!$check_stmt->execute()) {
            throw new Exception("Execute check failed: " . $check_stmt->error);
        }

        $check_result = $check_stmt->get_result();
        $row = $check_result->fetch_assoc();

        if ($row['count'] == 0) {
            // Dataset not in date range
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'empty' => true,
                'message' => 'Dataset was not uploaded in the selected date range',
                'labels' => [],
                'values' => []
            ]);
            exit();
        }
    }

    // If we get here, either no date filter or dataset is in range
    $query = "SELECT dd.value, dd.label 
              FROM dataset_data dd 
              JOIN datasets d ON dd.dataset_id = d.id 
              WHERE dd.dataset_id = ? AND d.user_id = ?";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }

    $stmt->bind_param("ii", $dataset_id, $_SESSION['user_id']);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Get result failed: " . $stmt->error);
    }

    // Fetch and format data
    $labels = [];
    $values = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['label'];
        $values[] = floatval($row['value']);
    }

    // Debug log
    error_log("Data fetched - labels count: " . count($labels));
    error_log("Data fetched - values count: " . count($values));

    // Check if we got any data
    if (empty($labels) || empty($values)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'empty' => true,
            'message' => 'No data found for this dataset',
            'labels' => [],
            'values' => []
        ]);
        exit();
    }

    // Return data
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'empty' => false,
        'labels' => $labels,
        'values' => $values
    ]);

} catch (Exception $e) {
    error_log("Error in get_filtered_data.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 