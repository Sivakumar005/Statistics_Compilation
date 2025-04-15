<?php
/**
 * Application configuration file
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'statistics_db');

// Upload directory configuration
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');

// Other configuration constants
define('SITE_URL', 'http://localhost/Statistics%20project/');
define('SITE_NAME', 'Statistics Compilation');

// Application configuration
define('BASE_PATH', dirname(__DIR__));
define('CHART_DIR', BASE_PATH . '/assets/charts/');
define('CHART_PREVIEW_DIR', BASE_PATH . '/assets/chart_previews/');

// Error reporting - only show errors in development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 in production
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');

// Create connection with error handling
try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    // Set charset to utf8mb4
    $mysqli->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log($e->getMessage());
    die("A database error occurred. Please try again later.");
}

// Create required directories if they don't exist
$directories = [UPLOAD_DIR, CHART_DIR, CHART_PREVIEW_DIR];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0777, true)) {
            error_log("Failed to create directory: " . $dir);
        }
    }
}

// Helper function to get absolute URL
function getUrl($path = '') {
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

// Helper function to sanitize file names
function sanitizeFileName($fileName) {
    $fileName = basename($fileName);
    $fileName = str_replace(' ', '_', $fileName);
    $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
    return $fileName;
} 