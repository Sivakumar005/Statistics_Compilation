<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Find user with this token
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Verify the user
        $user = $result->fetch_assoc();
        $update_stmt = $mysqli->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        $update_stmt->bind_param("i", $user['id']);
        $update_stmt->execute();
        
        // Redirect to login with success message
        header('Location: ' . SITE_URL . 'pages/auth/login.php?verified=1');
        exit();
    } else {
        // Invalid or expired token
        header('Location: ' . SITE_URL . 'pages/auth/login.php?error=invalid_token');
        exit();
    }
} else {    
    // No token provided
    header('Location: ' . SITE_URL . 'pages/auth/login.php?error=no_token');
    exit();
} 