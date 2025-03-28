<?php
include '../../includes/db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'] ;

    $sql = "SELECT * FROM `users` WHERE `username` = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Database error: " . $mysqli->error);
    }
    $stmt->bind_param("s", $username); 
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header("Location: ../register.html?error=Username already taken. Please choose another.");
        exit;
    }
    $sql = "INSERT INTO `users` (`username`, `email`, `password`) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Database error: " . $mysqli->error);
    }
    $stmt->bind_param("sss", $username, $email, $password); 
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Registration Successfull
        header("Location: login.html?success=Registration successful! Please log in.");
        exit;
    } else {
        // Registration failed
        header("Location: ../register.html?error=Registration failed. Please try again.");
        exit;
    }

    $stmt->close();
} else {
    header("Location: ../register.html?error=Invalid request method.");
    exit;
}
?>