<?php
// Prevent any output before JSON response
error_reporting(0);
ini_set('display_errors', 0);

// Ensure clean output buffer
if (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

try {
    // Include necessary files
    require_once '../includes/session.php';
    require_once '../includes/db.php';
    require_once '../includes/config.php';

    // Check if user is logged in
    requireLogin();

    // Update last activity
    updateLastActivity();

    // Check for session timeout
    if (isSessionTimeout()) {
        clearUserSession();
        throw new Exception("Session expired. Please login again.", 401);
    }

    // Get POST data
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No data received', 400);
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg(), 400);
    }

    // Validate required fields
    if (!isset($data['title']) || !isset($data['description']) || !isset($data['dataset_id'])) {
        throw new Exception('Missing required fields: title, description, and dataset_id are required', 400);
    }

    $user_id = getCurrentUserId();
    $title = trim($data['title']);
    $description = trim($data['description']);
    $dataset_id = (int)$data['dataset_id'];

    // Validate input
    if (empty($title) || empty($description)) {
        throw new Exception('Title and description cannot be empty', 400);
    }

    if ($dataset_id <= 0) {
        throw new Exception('Invalid dataset ID', 400);
    }

    // Verify dataset belongs to user
    $check_query = "SELECT * FROM datasets WHERE id = ? AND user_id = ?";
    $check_stmt = $mysqli->prepare($check_query);
    if (!$check_stmt) {
        throw new Exception('Database prepare error: ' . $mysqli->error, 500);
    }
    $check_stmt->bind_param("ii", $dataset_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $check_stmt->close();

    if ($result->num_rows === 0) {
        throw new Exception('Dataset not found or unauthorized', 404);
    }

    // Insert the note
    $query = "INSERT INTO report_notes (user_id, dataset_id, title, description, created_at) 
              VALUES (?, ?, ?, ?, NOW())";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $mysqli->error, 500);
    }
    $stmt->bind_param("iiss", $user_id, $dataset_id, $title, $description);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to save note: ' . $stmt->error, 500);
    }

    $note_id = $mysqli->insert_id;
    $stmt->close();

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Note saved successfully',
        'note_id' => $note_id
    ]);

} catch (Exception $e) {
    // Set appropriate HTTP status code
    $status_code = $e->getCode() >= 400 && $e->getCode() <= 599 ? $e->getCode() : 500;
    http_response_code($status_code);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    // Ensure the database connection is closed
    if (isset($mysqli) && $mysqli) {
        $mysqli->close();
    }
    exit();
}