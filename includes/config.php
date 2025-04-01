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

// Create report_notes table if it doesn't exist
$create_notes_table = "CREATE TABLE IF NOT EXISTS `report_notes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `dataset_id` INT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`dataset_id`) REFERENCES `datasets`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$mysqli->query($create_notes_table)) {
    error_log("Error creating report_notes table: " . $mysqli->error);
}

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