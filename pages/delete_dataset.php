<?php
// Start the session before any output
session_start();

// Prevent any output before our JSON response
error_reporting(0);

// Set JSON content type header
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Debug session info (remove in production)
error_log('Session data: ' . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized - No session']);
    exit();
}

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate dataset ID
if (!isset($data['dataset_id']) || !is_numeric($data['dataset_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid dataset ID']);
    exit();
}

$dataset_id = (int)$data['dataset_id'];
$user_id = (int)$_SESSION['user_id'];

try {
    // Start transaction
    $mysqli->begin_transaction();

    // First, verify the dataset belongs to the user
    $check_stmt = $mysqli->prepare("SELECT id FROM datasets WHERE id = ? AND user_id = ?");
    if (!$check_stmt) {
        throw new Exception("Error preparing verification statement: " . $mysqli->error);
    }
    $check_stmt->bind_param("ii", $dataset_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Dataset not found or you don't have permission to delete it");
    }
    $check_stmt->close();

    // First, delete associated charts
    $stmt = $mysqli->prepare("DELETE FROM charts WHERE dataset_id = ?");
    if (!$stmt) {
        throw new Exception("Error preparing charts deletion statement: " . $mysqli->error);
    }
    $stmt->bind_param("i", $dataset_id);
    $stmt->execute();

    // Then delete the dataset
    $stmt = $mysqli->prepare("DELETE FROM datasets WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        throw new Exception("Error preparing dataset deletion statement: " . $mysqli->error);
    }
    $stmt->bind_param("ii", $dataset_id, $user_id);
    $stmt->execute();

    // Commit transaction
    $mysqli->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $mysqli->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    // Close the database connection
    if (isset($stmt)) {
        $stmt->close();
    }
} 