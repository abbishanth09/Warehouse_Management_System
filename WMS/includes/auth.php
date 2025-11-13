<?php
/**
 * Authentication middleware
 * Checks if user is logged in, redirects to login if not
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Save the requested page to redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    header('Location: login.php');
    exit();
}

// Optional: Check session timeout (24 hours)
$session_timeout = 24 * 60 * 60; // 24 hours in seconds
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $session_timeout) {
    // Session expired
    session_destroy();
    header('Location: login.php?expired=1');
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>
