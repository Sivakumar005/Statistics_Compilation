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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Debug log
error_log("get_filtered_datasets.php - Parameters: start_date=$start_date, end_date=$end_date");

try {
    // Base query without date filtering
    $query = "SELECT d.*, COUNT(c.id) as chart_count 
              FROM datasets d 
              LEFT JOIN charts c ON d.id = c.dataset_id 
              WHERE d.user_id = ?";

    $params = [$_SESSION['user_id']];
    $types = "i";

    // Add date filtering if dates are provided
    if ($start_date && $end_date) {
        $query .= " AND DATE(d.upload_date) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }

    $query .= " GROUP BY d.id ORDER BY d.upload_date DESC";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $datasets = [];
    while ($row = $result->fetch_assoc()) {
        $datasets[] = [
            'id' => $row['id'],
            'dataset_name' => $row['dataset_name'],
            'upload_date' => $row['upload_date'],
            'chart_count' => $row['chart_count']
        ];
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'datasets' => $datasets
    ]);

} catch (Exception $e) {
    error_log("Error in get_filtered_datasets.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 