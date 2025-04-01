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

    if (!isset($data['note_id'])) {
        throw new Exception('Missing note ID');
    }

    $user_id = $_SESSION['user_id'];
    $note_id = (int)$data['note_id'];

    // Verify the note belongs to the user
    $check_query = "SELECT id FROM report_notes WHERE id = ? AND user_id = ?";
    $check_stmt = $mysqli->prepare($check_query);
    if (!$check_stmt) {
        throw new Exception('Database error: ' . $mysqli->error);
    }

    $check_stmt->bind_param("ii", $note_id, $user_id);
    if (!$check_stmt->execute()) {
        throw new Exception('Failed to verify note: ' . $check_stmt->error);
    }
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Invalid note');
    }

    // Delete the note
    $query = "DELETE FROM report_notes WHERE id = ? AND user_id = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $mysqli->error);
    }

    $stmt->bind_param("ii", $note_id, $user_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to delete note: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception('Note not found or already deleted');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Note deleted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if ($mysqli) {
        $mysqli->close();
    }
    exit();
} 