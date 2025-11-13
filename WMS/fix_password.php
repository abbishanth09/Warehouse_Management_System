<?php
require_once 'config/database.php';

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Updating password to: $password\n";
echo "Hash: $hash\n\n";

try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $result = $stmt->execute([$hash]);
    
    if ($result) {
        echo "✅ Password updated successfully!\n";
        
        // Test verification immediately
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
        $stmt->execute();
        $storedHash = $stmt->fetchColumn();
        
        if (password_verify($password, $storedHash)) {
            echo "✅ Password verification works!\n";
        } else {
            echo "❌ Password verification failed!\n";
            echo "Stored hash: $storedHash\n";
        }
    } else {
        echo "❌ Password update failed!\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
