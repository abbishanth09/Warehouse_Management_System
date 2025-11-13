<?php
require_once 'config/database.php';
require_once 'includes/auth_db.php';

echo "Testing email authentication...\n";

// For local tests, don't use real credentials in the repo.
// Create `config/test_credentials.php` (ignored) returning ['username'=>'user@example.com','password'=>'pass'] and set values there.
$test_creds_file = __DIR__ . '/config/test_credentials.php';
if (file_exists($test_creds_file)) {
    $creds = include $test_creds_file;
    $email = $creds['username'] ?? 'example@example.com';
    $password = $creds['password'] ?? 'changeme';
} else {
    $email = 'example@example.com';
    $password = 'changeme';
}

echo "Email: $email\n";
echo "Password: $password\n\n";

// Test direct database query
try {
    $stmt = $pdo->prepare("SELECT id, username, password, email, role, status FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "User found in database:\n";
        echo "- ID: " . $user['id'] . "\n";
        echo "- Username: " . $user['username'] . "\n";
        echo "- Email: " . $user['email'] . "\n";
        echo "- Role: " . $user['role'] . "\n";
        echo "- Status: " . $user['status'] . "\n";
        echo "- Password hash: " . substr($user['password'], 0, 20) . "...\n\n";
        
        // Test password verification
        if (password_verify($password, $user['password'])) {
            echo "Password verification: SUCCESS\n";
        } else {
            echo "Password verification: FAILED\n";
            echo "Trying to generate new hash for comparison...\n";
            echo "New hash: " . password_hash($password, PASSWORD_DEFAULT) . "\n";
        }
    } else {
        echo "User NOT found in database with email: $email\n";
    }
    
    // Test the authenticateUserByEmail function
    echo "\nTesting authenticateUserByEmail function...\n";
    $result = authenticateUserByEmail($pdo, $email, $password);
    if ($result) {
        echo "authenticateUserByEmail: SUCCESS\n";
        print_r($result);
    } else {
        echo "authenticateUserByEmail: FAILED\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
