<?php
$password = 'Abbi+1209';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "New Hash: $hash\n";

// Test verification
if (password_verify($password, $hash)) {
    echo "✅ Hash verification works!\n";
} else {
    echo "❌ Hash verification failed!\n";
}
?>
