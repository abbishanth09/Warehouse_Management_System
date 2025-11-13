<?php
// Test authentication script
require_once 'config/database.php';

echo "Testing database connection...\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    echo "Database connection: SUCCESS\n";
    echo "Users count: " . $stmt->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "\n";
    exit;
}

echo "\nTesting user authentication...\n";

// NOTE: Do NOT hard-code real credentials in this file.
// For local testing, create an ignored file `config/test_credentials.php` and return an array with 'username' and 'password'.
// Example (config/test_credentials.php) - add this file locally and do NOT commit it:
// <?php return ['username' => 'your_test_user@example.com', 'password' => 'your_test_password'];

$test_creds_file = __DIR__ . '/../config/test_credentials.php';
if (file_exists($test_creds_file)) {
    $creds = include $test_creds_file;
    $username = $creds['username'] ?? 'example@example.com';
    $password = $creds['password'] ?? 'changeme';
} else {
    // Safe defaults for CI / demo purposes
    $username = 'example@example.com';
    $password = 'changeme';
}

try {
    $stmt = $pdo->prepare("SELECT id, username, password, role, status FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "User found: " . $user['username'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Status: " . $user['status'] . "\n";
        
        if (password_verify($password, $user['password'])) {
            echo "Password verification: SUCCESS\n";
            echo "Authentication should work!\n";
        } else {
            echo "Password verification: FAILED\n";
            echo "Stored hash: " . $user['password'] . "\n";
            echo "New hash for comparison: " . password_hash($password, PASSWORD_DEFAULT) . "\n";
        }
    } else {
        echo "User not found or inactive\n";
    }
} catch (Exception $e) {
    echo "Authentication test failed: " . $e->getMessage() . "\n";
}
?>
