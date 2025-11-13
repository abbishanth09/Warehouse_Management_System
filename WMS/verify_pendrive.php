<?php
require_once 'config/database.php';

echo "=== Verifying Pendrive Inventory Update ===\n";

$stmt = $pdo->prepare('SELECT * FROM products WHERE name = ?');
$stmt->execute(['Pendrive']);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product) {
    echo "Pendrive Product:\n";
    echo "- ID: {$product['id']}\n";
    echo "- Current Quantity: {$product['quantity']}\n";
    echo "- Price: {$product['price']}\n\n";
    
    $stmt = $pdo->prepare('SELECT * FROM inventory_transactions WHERE product_id = ? ORDER BY created_at DESC LIMIT 5');
    $stmt->execute([$product['id']]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Recent Transactions:\n";
    foreach ($transactions as $trans) {
        echo "- {$trans['transaction_type']} {$trans['quantity_change']} ({$trans['previous_quantity']} → {$trans['new_quantity']}) - {$trans['reference_number']} [{$trans['created_at']}]\n";
    }
} else {
    echo "Pendrive product not found\n";
}
?>
