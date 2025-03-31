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
$dataset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Debug log
error_log("get_chart_data.php - Parameters: dataset_id=$dataset_id, start_date=$start_date, end_date=$end_date");

try {
    // First check if dataset belongs to user
    $check_query = "SELECT * FROM datasets WHERE id = ? AND user_id = ?";
    $check_stmt = $mysqli->prepare($check_query);
    $check_stmt->bind_param("ii", $dataset_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Dataset not found or unauthorized");
    }

    $dataset = $result->fetch_assoc();

    // Check if dataset falls within date range
    if ($start_date && $end_date) {
        if (strtotime($dataset['upload_date']) < strtotime($start_date) || 
            strtotime($dataset['upload_date']) > strtotime($end_date)) {
            // Dataset not in range
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'chart' => [
                    'type' => 'bar',
                    'title' => $dataset['dataset_name'],
                    'data' => [
                        'labels' => [],
                        'data' => []
                    ]
                ]
            ]);
            exit();
        }
    }

    // Get chart data
    $query = "SELECT * FROM charts WHERE dataset_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $dataset_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // No chart exists yet, return empty data
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'chart' => [
                'type' => 'bar',
                'title' => $dataset['dataset_name'],
                'data' => [
                    'labels' => [],
                    'data' => []
                ]
            ]
        ]);
        exit();
    }

    $chart = $result->fetch_assoc();
    $chart_data = json_decode($chart['chart_data'], true);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'chart' => [
            'type' => $chart['chart_type'],
            'title' => $chart['title'],
            'data' => $chart_data
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_chart_data.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 