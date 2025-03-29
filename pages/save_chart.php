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

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit();
}

// Validate required fields
if (!isset($data['dataset_id'], $data['chart_type'], $data['title'], $data['config'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

// Insert the chart into the database
$query = "INSERT INTO charts (dataset_id, user_id, chart_type, title, config, created_at) 
          VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = $mysqli->prepare($query);
$user_id = $_SESSION['user_id'];
$stmt->bind_param("iisss", 
    $data['dataset_id'],
    $user_id,
    $data['chart_type'],
    $data['title'],
    $data['config']
);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'chart_id' => $mysqli->insert_id]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $mysqli->error]);
} 