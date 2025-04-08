<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/config.php';

// Set timezone to match your local time
date_default_timezone_set('Asia/Kolkata'); // For Indian Standard Time (IST)

// Start output buffering to prevent any unwanted output
ob_start();

echo "<h1>Session Management Test</h1>";

// Display current server time for reference
echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Check if session is started
echo "<h2>Test 1: Session Status</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Session Name: " . session_name() . "<br>";

// Test 2: Test login functionality
echo "<h2>Test 2: Login Test</h2>";
try {
    // Try to get current user (should fail if not logged in)
    $user_id = getCurrentUserId();
    echo "Current User ID: " . ($user_id ? $user_id : "Not logged in") . "<br>";
    
    // Try to get username (should fail if not logged in)
    $username = getCurrentUsername();
    echo "Current Username: " . ($username ? $username : "Not logged in") . "<br>";
    
    // Check if user is logged in
    echo "Is Logged In: " . (isLoggedIn() ? "Yes" : "No") . "<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 3: Test session timeout
echo "<h2>Test 3: Session Timeout Test</h2>";
echo "Last Activity: " . (isset($_SESSION['last_activity']) ? date('Y-m-d H:i:s', $_SESSION['last_activity']) : "Not set") . "<br>";
echo "Session Timeout: " . (isSessionTimeout() ? "Yes" : "No") . "<br>";

// Test 4: Test session data
echo "<h2>Test 4: Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test 5: Test session security
echo "<h2>Test 5: Session Security</h2>";
echo "Session Cookie Parameters:<br>";
echo "<pre>";
print_r(session_get_cookie_params());
echo "</pre>";

// Test 6: Test session cleanup
echo "<h2>Test 6: Session Cleanup</h2>";
echo "Before Cleanup - Session Data:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Try to clear the session
clearUserSession();

echo "After Cleanup - Session Data:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test 7: Test session activity tracking
echo "<h2>Test 7: Activity Tracking</h2>";
echo "Before Update - Last Activity: " . (isset($_SESSION['last_activity']) ? date('Y-m-d H:i:s', $_SESSION['last_activity']) : "Not set") . "<br>";
updateLastActivity();
echo "After Update - Last Activity: " . (isset($_SESSION['last_activity']) ? date('Y-m-d H:i:s', $_SESSION['last_activity']) : "Not set") . "<br>";

// Test 8: Test session persistence
echo "<h2>Test 8: Session Persistence</h2>";
$_SESSION['test_data'] = 'Test Value';
echo "Test Data Set: " . $_SESSION['test_data'] . "<br>";
echo "Session Data After Setting Test Value:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Clear output buffer and display results
ob_end_flush();
?> 