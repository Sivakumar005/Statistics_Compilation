<?php
// Prevent any output before headers
ob_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Debug database connection
if (!isset($mysqli)) {
    die(json_encode(['error' => 'Database connection not established']));
}

// Print database debug info
error_log("Current database: " . $mysqli->query("SELECT DATABASE()")->fetch_row()[0]);
error_log("Tables in database: ");
$result = $mysqli->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    error_log($row[0]);
}

// Function to send JSON response and exit
function sendJsonResponse($data) {
    ob_clean(); // Clear any output buffers
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Check if user is logged in
if (!isLoggedIn()) {
    sendJsonResponse(['error' => 'Unauthorized']);
}

// Check if dataset ID is provided
if (!isset($_GET['id'])) {
    sendJsonResponse(['error' => 'Dataset ID is required']);
}

$dataset_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // Verify dataset belongs to user
    $check_query = "SELECT * FROM datasets WHERE id = ? AND user_id = ?";
    if (!($stmt = $mysqli->prepare($check_query))) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("ii", $dataset_id, $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Get result failed: " . $stmt->error);
    }

    if ($result->num_rows === 0) {
        sendJsonResponse(['error' => 'Dataset not found or unauthorized']);
    }

    $dataset = $result->fetch_assoc();

    // Debug: Print dataset info
    error_log("Dataset found: " . print_r($dataset, true));
    
    // Get the data from the dataset
    $data_query = "SELECT id, dataset_id, value, label, category, timestamp 
                   FROM dataset_data WHERE dataset_id = ?";
    if (!($stmt = $mysqli->prepare($data_query))) {
        throw new Exception("Prepare failed for data query: " . $mysqli->error . 
                          "\nFull query: " . $data_query);
    }
    
    $stmt->bind_param("i", $dataset_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed for data query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Get result failed for data query: " . $stmt->error);
    }
    
    $data = $result->fetch_all(MYSQLI_ASSOC);

    // Debug: Print data count
    error_log("Data rows found: " . count($data));

    // Send the results
    sendJsonResponse([
        'success' => true,
        'dataset_name' => $dataset['dataset_name'],
        'columns' => ['value'], // We only need the value column
        'stats' => []
    ]);

} catch (Exception $e) {
    error_log("Error in get_dataset_stats.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendJsonResponse(['error' => 'An error occurred while processing the request: ' . $e->getMessage()]);
}

// Helper function to calculate median
function calculateMedian($values) {
    sort($values);
    $count = count($values);
    $middle = floor($count / 2);
    
    if ($count % 2 == 0) {
        return ($values[$middle - 1] + $values[$middle]) / 2;
    } else {
        return $values[$middle];
    }
}

// Helper function to calculate standard deviation
function calculateStdDev($values) {
    $mean = array_sum($values) / count($values);
    $squaredDiffs = array_map(function($value) use ($mean) {
        return pow($value - $mean, 2);
    }, $values);
    
    return sqrt(array_sum($squaredDiffs) / count($values));
} 