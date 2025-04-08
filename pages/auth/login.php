<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        header('Location: ' . SITE_URL . 'pages/auth/login.html?error=empty_fields');
        exit();
    }
    
    // Check if user exists and is verified
    $stmt = $mysqli->prepare("SELECT id, username, password, is_verified FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: ' . SITE_URL . 'pages/auth/login.html?error=invalid_credentials');
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        header('Location: ' . SITE_URL . 'pages/auth/login.html?error=invalid_credentials');
        exit();
    }
    
    // Check if email is verified
    if (!$user['is_verified']) {
        header('Location: ' . SITE_URL . 'pages/auth/login.html?error=not_verified');
        exit();
    }
    
    // Start session and set user data
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    // Redirect to dashboard
    header('Location: ' . SITE_URL . 'pages/user_dashboard.php');
    exit();
}

// If not a POST request, redirect to login page
header('Location: ' . SITE_URL . 'pages/auth/login.html');
exit();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Statistics Project</title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-md">
            <h2 class="text-3xl font-bold text-center">Sign In</h2>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <?php
                    switch ($_GET['error']) {
                        case 'empty_fields':
                            echo 'Please fill in all fields';
                            break;
                        case 'invalid_credentials':
                            echo 'Invalid username or password';
                            break;
                        case 'not_verified':
                            echo 'Please verify your email address before logging in';
                            break;
                        case 'invalid_token':
                            echo 'Invalid verification token';
                            break;
                        case 'no_token':
                            echo 'No verification token provided';
                            break;
                        default:
                            echo 'An error occurred';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['verified'])): ?>
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    Email verified successfully! You can now log in.
                </div>
            <?php endif; ?>
            
            
        </div>
    </div>
</body>
</html>