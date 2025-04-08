<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        header('Location: ' . SITE_URL . 'pages/auth/register.php?error=empty_fields');
        exit();
    }
    
    // Check if username or email already exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header('Location: ' . SITE_URL . 'pages/auth/register.php?error=exists');
        exit();
    }
    
    // Generate verification token
    $verification_token = bin2hex(random_bytes(32));
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, verification_token) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $verification_token);
    
    if ($stmt->execute()) {
        // Send verification email
        $verification_link = SITE_URL . 'pages/auth/verify_email.php?token=' . $verification_token;
        $to = $email;
        $subject = "Verify your email address";
        $message = "Hello $username,\n\nPlease click the following link to verify your email address:\n\n$verification_link\n\nIf you did not create an account, please ignore this email.";
        $headers = "From: noreply@" . parse_url(SITE_URL, PHP_URL_HOST) . "\r\n";
        
        if (mail($to, $subject, $message, $headers)) {
            header('Location: ' . SITE_URL . 'pages/auth/register.php?success=1');
        } else {
            header('Location: ' . SITE_URL . 'pages/auth/register.php?error=mail_failed');
        }
    } else {
        header('Location: ' . SITE_URL . 'pages/auth/register.php?error=registration_failed');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Statistics Project</title>
    <!-- Load Tailwind CSS from CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Test if custom CSS is working */
        .test-border {
            border: 2px solid red;
        }
        
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        
        .popup-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .popup-box {
            background-color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.3s;
        }
        
        .popup-overlay.active .popup-box {
            transform: scale(1);
        }
        
        .progress-bar {
            height: 4px;
            background-color: #e2e8f0;
            margin-top: 1rem;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            height: 100%;
            background-color: #10B981;
            width: 100%;
            transition: width 5s linear;
        }

        /* Form specific styles */
        .form-container {
            background-color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }

        .form-input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
        }

        .submit-button {
            width: 100%;
            padding: 0.75rem;
            background-color: #4f46e5;
            color: white;
            border-radius: 0.25rem;
            font-weight: 500;
        }

        .submit-button:hover {
            background-color: #4338ca;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="form-container max-w-md w-full">
        <h2 class="text-3xl font-bold text-center mb-8">Create Account</h2>
        
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form class="space-y-6" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input id="username" name="username" type="text" required 
                    class="form-input" 
                    placeholder="Enter your username">
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                <input id="email" name="email" type="email" required 
                    class="form-input" 
                    placeholder="Enter your email">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input id="password" name="password" type="password" required 
                    class="form-input" 
                    placeholder="Enter your password">
            </div>

            <button type="submit" class="submit-button">
                Register
            </button>
        </form>
        
        <div class="text-center mt-4">
            <p class="text-sm text-gray-600">
                Already have an account? 
                <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Sign in
                </a>
            </p>
        </div>
    </div>

    <?php if ($success_message): ?>
    <div class="popup-overlay" id="successPopup">
        <div class="popup-box">
            <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Success!</h3>
            <p class="mt-2 text-sm text-gray-500"><?php echo htmlspecialchars($success_message); ?></p>
            <div class="progress-bar">
                <div class="progress-bar-fill"></div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const popup = document.getElementById('successPopup');
            popup.classList.add('active');
            
            setTimeout(function() {
                popup.classList.remove('active');
            }, 5000);
        });
    </script>
    <?php endif; ?>
</body>
</html>