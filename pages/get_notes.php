<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Prevent any output before JSON response
error_reporting(0);
ini_set('display_errors', 0);

// Ensure clean output buffer
if (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Get dataset_id from query parameters
$dataset_id = isset($_GET['dataset_id']) ? (int)$_GET['dataset_id'] : null;
$user_id = $_SESSION['user_id'];

// If dataset_id is provided, verify it belongs to the user
if ($dataset_id) {
    $check_query = "SELECT id FROM datasets WHERE id = ? AND user_id = ?";
    $check_stmt = $mysqli->prepare($check_query);
    if (!$check_stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $mysqli->error]);
        exit();
    }
    
    $check_stmt->bind_param("ii", $dataset_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid dataset']);
        exit();
    }
}

// Fetch notes for the dataset using correct column names
$query = "SELECT id, title, description, created_at 
          FROM report_notes 
          WHERE user_id = ? AND dataset_id = ? 
          ORDER BY created_at DESC";

$stmt = $mysqli->prepare($query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $mysqli->error]);
    exit();
}

$stmt->bind_param("ii", $user_id, $dataset_id);
$stmt->execute();
$result = $stmt->get_result();

$notes = [];
while ($row = $result->fetch_assoc()) {
    $notes[] = [
        'id' => $row['id'],
        'title' => htmlspecialchars($row['title']),
        'description' => htmlspecialchars($row['description']),
        'created_at' => $row['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'notes' => $notes
]); 