<?php
session_start();
include '../../includes/db.php'; // Updated path for db.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute the query to fetch user details
    $stmt = $mysqli->prepare("SELECT `id`, `username`, `password` FROM `users` WHERE `username` = ?");
    if (!$stmt) {
        die("Database error: " . $mysqli->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verify the password (plaintext comparison in this example; use password_hash() and password_verify() in production)
        if ($password === $user['password']) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            // Redirect to the dashboard
            header("Location: ../user_dashboard.php"); // Updated path
            exit;
        } else {
            // Invalid password
            header("Location: ../pages/auth/login.html?error=Invalid username or password."); // Updated path
            exit;
        }
    } else {
        // User not found
        header("Location: ../pages/auth/login.html?error=Invalid username or password."); // Updated path
        exit;
    }
    $stmt->close();
} else {
    // Invalid request method
    header("Location: ../pages/auth/login.html?error=Invalid request method."); // Updated path
    exit;
}
?>