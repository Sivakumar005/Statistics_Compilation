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

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        throw new Exception('Not authenticated');
    }

    // Get POST data
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No data received');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    if (!isset($data['title']) || !isset($data['description'])) {
        throw new Exception('Missing required fields');
    }

    $user_id = $_SESSION['user_id'];
    $title = trim($data['title']);
    $description = trim($data['description']);
    $dataset_id = isset($data['dataset_id']) ? (int)$data['dataset_id'] : null;

    // Validate input
    if (empty($title) || empty($description)) {
        throw new Exception('Title and description are required');
    }

    // If dataset_id is provided, verify it belongs to the user
    if ($dataset_id) {
        $check_query = "SELECT id FROM datasets WHERE id = ? AND user_id = ?";
        $check_stmt = $mysqli->prepare($check_query);
        if (!$check_stmt) {
            throw new Exception('Database error: ' . $mysqli->error);
        }
        
        $check_stmt->bind_param("ii", $dataset_id, $user_id);
        if (!$check_stmt->execute()) {
            throw new Exception('Failed to verify dataset: ' . $check_stmt->error);
        }
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Invalid dataset');
        }
    }

    // Insert note into database using correct column names
    $query = "INSERT INTO report_notes (user_id, dataset_id, title, description, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $mysqli->error);
    }

    $stmt->bind_param("iiss", $user_id, $dataset_id, $title, $description);

    if (!$stmt->execute()) {
        throw new Exception('Failed to save note: ' . $stmt->error);
    }

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Note saved successfully',
        'note_id' => $mysqli->insert_id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    // Ensure the response is complete
    if ($mysqli) {
        $mysqli->close();
    }
    exit();
} 