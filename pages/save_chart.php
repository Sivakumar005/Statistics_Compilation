<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Get the JSON data from the request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log the received data for debugging
error_log("Received data: " . print_r($data, true));

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid data: ' . json_last_error_msg()]);
    exit();
}

// Validate required fields
if (!isset($data['dataset_id'], $data['chart_type'], $data['title'], $data['config'], $data['chart_data'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

try {
    // Insert the chart into the database
    $query = "INSERT INTO charts (dataset_id, chart_type, title, config, chart_data, created_at) 
              VALUES (?, ?, ?, ?, ?, NOW())";

    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }

    $stmt->bind_param("issss", 
        $data['dataset_id'],
        $data['chart_type'],
        $data['title'],
        $data['config'],
        $data['chart_data']
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'chart_id' => $mysqli->insert_id]);
} catch (Exception $e) {
    error_log("Error saving chart: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 