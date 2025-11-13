<?php
// Simulate the login process
session_start();

// For local tests, do NOT hard-code your real credentials here.
// Create `config/test_credentials.php` (ignored) with:
// <?php return ['username'=>'your_test_user@example.com','password'=>'your_test_password'];
$test_creds_file = __DIR__ . '/config/test_credentials.php';
if (file_exists($test_creds_file)) {
    $creds = include $test_creds_file;
    $_POST['username'] = $creds['username'] ?? 'example@example.com';
    $_POST['password'] = $creds['password'] ?? 'changeme';
} else {
    $_POST['username'] = 'example@example.com';
    $_POST['password'] = 'changeme';
}

require_once 'config/database.php';
require_once 'includes/auth_db.php';

echo "Simulating login process...\n";

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

echo "Input username: '$username'\n";
echo "Input password: '$password'\n";

$user = false;
$error = '';

// Check if the input looks like an email
if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
    echo "Detected as email format\n";
    // Database authentication with email
    $user = authenticateUserByEmail($pdo, $username, $password);
    echo "authenticateUserByEmail result: " . ($user ? 'SUCCESS' : 'FAILED') . "\n";
    if ($user) {
        print_r($user);
    }
} else {
    echo "Detected as username format\n";
    // Regular username authentication
    $user = authenticateUser($pdo, $username, $password);
    echo "authenticateUser result: " . ($user ? 'SUCCESS' : 'FAILED') . "\n";
}

if ($user) {
    echo "Login would be SUCCESSFUL\n";
} else {
    // Only set error if not already set (e.g., by email validation)
    if (!isset($error) || empty($error)) {
        $error = 'Invalid username/email or password!';
    }
    echo "Login FAILED with error: $error\n";
}
?>
