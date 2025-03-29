<?php
/**
 * Authentication helper functions
 */

/**
 * Check if a user is currently logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the current logged in user's ID
 * 
 * @return int|null The user ID if logged in, null otherwise
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Require user to be logged in
 * Redirects to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
} 