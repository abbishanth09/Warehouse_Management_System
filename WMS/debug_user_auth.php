<?php
require_once 'config/database.php';
require_once 'classes/User.php';

echo "=== Debug User Authentication ===\n";

$userManager = new User($pdo);

// Test direct database query first
echo "1. Testing direct database query:\n";
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    // Use an ignored local test credentials file instead of hard-coded values
    $test_creds_file = __DIR__ . '/config/test_credentials.php';
    $test_email = 'example@example.com';
    if (file_exists($test_creds_file)) {
        $creds = include $test_creds_file;
        $test_email = $creds['username'] ?? $test_email;
    }
    $stmt->execute([$test_email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User found in database\n";
        echo "   ID: " . $user['id'] . "\n";
        echo "   Username: " . $user['username'] . "\n";
        echo "   Email: " . $user['email'] . "\n";
        echo "   Status: " . $user['status'] . "\n";
        echo "   Password hash: " . substr($user['password'], 0, 20) . "...\n";
        
        // Test password verification
    $password = 'changeme'; // replace locally via config/test_credentials.php for real tests
        if (password_verify($password, $user['password'])) {
            echo "✅ Password verification: SUCCESS\n";
        } else {
            echo "❌ Password verification: FAILED\n";
        }
    } else {
        echo "❌ User not found in database\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing User class authentication:\n";

// Test the User class
try {
    $result = $userManager->authenticate($test_email, $password);
    if ($result) {
        echo "✅ User class authentication: SUCCESS\n";
        echo "   User ID: " . $userManager->getId() . "\n";
        echo "   Username: " . $userManager->getUsername() . "\n";
    } else {
        echo "❌ User class authentication: FAILED\n";
    }
} catch (Exception $e) {
    echo "❌ User class error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
