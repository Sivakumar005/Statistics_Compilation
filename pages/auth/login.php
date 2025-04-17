<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';

// Start session at the beginning
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'pages/user_dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields';
        header('Location: ' . SITE_URL . 'pages/auth/login.php');
        exit();
    }
    
    // Check if user exists and is verified
    $stmt = $mysqli->prepare("SELECT id, username, password, is_verified FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Invalid username or password';
        header('Location: ' . SITE_URL . 'pages/auth/login.php');
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        $_SESSION['error'] = 'Invalid username or password';
        header('Location: ' . SITE_URL . 'pages/auth/login.php');
        exit();
    }
    

    
    // Set user data in session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    // Clear any error messages
    unset($_SESSION['error']);
    
    // Redirect to dashboard
    header('Location: ' . SITE_URL . 'pages/user_dashboard.php');
    exit();
}

// If not a POST request, show login form
?>
