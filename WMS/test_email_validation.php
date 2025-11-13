<?php
// Test email validation for forgot password
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "=== Testing Email Validation ===\n\n";

// Simulate POST request with invalid email
$_POST['email_step'] = true;
$_POST['email'] = 'invalid-email';

$error = '';

if (isset($_POST['email_step'])) {
    $email = trim($_POST['email']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid Email address';
    } else {
        echo "✅ Email validation passed\n";
    }
}

if ($error) {
    echo "❌ Error: $error\n";
}

echo "\n=== Test Complete ===\n";
?>
