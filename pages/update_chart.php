<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (!isset($data['dataset_id']) || !isset($data['chart_type']) || !isset($data['title']) || !isset($data['config']) || !isset($data['chart_data'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

try {
    // Update the chart in the database
    $query = "UPDATE charts SET 
              chart_type = ?, 
              title = ?, 
              config = ?, 
              chart_data = ?,
              updated_at = NOW()
              WHERE dataset_id = ?";
              
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ssssi", 
        $data['chart_type'],
        $data['title'],
        $data['config'],
        $data['chart_data'],
        $data['dataset_id']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 