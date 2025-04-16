<?php
// Enable error logging for debugging
ini_set('display_errors', 0); // Disable errors in output
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log'); // Log errors to a file
error_reporting(E_ALL);

// Ensure no output before JSON
ob_start();

// Set the correct header
header('Content-Type: application/json');

// Include required files
try {
    require_once '../../includes/config.php';
    require_once '../../includes/db.php';
} catch (Exception $e) {
    error_log("Failed to include config/db: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server configuration error']);
    ob_end_flush();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        ob_end_flush();
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email address']);
        ob_end_flush();
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
        ob_end_flush();
        exit;
    }

    // Check if username or email already exists
    try {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $mysqli->error);
        }
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Username or email already exists']);
            ob_end_flush();
            exit;
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
        ob_end_flush();
        exit;
    }

    // Generate verification token (for potential future use)
    try {
        $verification_token = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        error_log("Failed to generate token: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Server error']);
        ob_end_flush();
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    if ($hashed_password === false) {
        echo json_encode(['success' => false, 'error' => 'Password hashing failed']);
        ob_end_flush();
        exit;
    }

    // Insert new user
    try {
        $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, verification_token) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $mysqli->error);
        }
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $verification_token);
        if (!$stmt->execute()) {
            throw new Exception("Database insert failed: " . $stmt->error);
        }

        // Success: User inserted
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Database insert error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Registration failed']);
        ob_end_flush();
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

ob_end_flush();
exit;
?>