<?php
/**
 * Application configuration file
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'statistics_db');

// Application configuration
define('SITE_URL', 'http://localhost/Statistics%20project');
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . '/assets/uploads/');
define('CHART_DIR', BASE_PATH . '/assets/charts/');
define('CHART_PREVIEW_DIR', BASE_PATH . '/assets/chart_previews/');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Create database if it doesn't exist
$mysqli->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);

// Select the database
$mysqli->select_db(DB_NAME);

// Set charset to utf8mb4
$mysqli->set_charset("utf8mb4");

// Create required directories if they don't exist
$directories = [UPLOAD_DIR, CHART_DIR, CHART_PREVIEW_DIR];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0777, true)) {
            die("Failed to create directory: " . $dir);
        }
    }
    // Ensure directory is writable
    if (!is_writable($dir)) {
        die("Directory is not writable: " . $dir);
    }
}

// Helper function to get absolute URL
function getUrl($path = '') {
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

// Helper function to sanitize file names
function sanitizeFileName($fileName) {
    // Remove any path traversal attempts
    $fileName = basename($fileName);
    // Replace spaces with underscores
    $fileName = str_replace(' ', '_', $fileName);
    // Remove any characters that aren't alphanumeric, dots, or underscores
    $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
    return $fileName;
} 