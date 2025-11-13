<?php
require_once 'config/database.php';

echo "=== Checking Inventory Updates for Completed Orders ===\n\n";

// Check orders that have been completed and have items
$stmt = $pdo->query("
    SELECT id, order_number, order_type, status, items, inventory_processed 
    FROM orders 
    WHERE status = 'completed' AND items IS NOT NULL AND items != ''
    ORDER BY id DESC 
    LIMIT 3
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($orders as $order) {
    echo "Order: {$order['order_number']} (ID: {$order['id']})\n";
    echo "Type: {$order['order_type']}, Status: {$order['status']}\n";
    echo "Inventory Processed: " . ($order['inventory_processed'] ? 'YES' : 'NO') . "\n";
    
    $items = json_decode($order['items'], true);
    if ($items) {
        echo "Items:\n";
        foreach ($items as $item) {
            echo "- {$item['name']} (Qty: {$item['quantity']})\n";
            
            // Check if product exists and current stock
            $stmt = $pdo->prepare("SELECT id, name, quantity FROM products WHERE name = ?");
            $stmt->execute([$item['name']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                echo "  Current stock: {$product['quantity']}\n";
                
                // Check transactions for this product and order
                $stmt = $pdo->prepare("
                    SELECT * FROM inventory_transactions 
                    WHERE product_id = ? AND order_id = ?
                ");
                $stmt->execute([$product['id'], $order['id']]);
                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($transactions)) {
                    echo "  ❌ No inventory transactions found!\n";
                } else {
                    foreach ($transactions as $trans) {
                        echo "  ✅ Transaction: {$trans['transaction_type']} {$trans['quantity_change']} ({$trans['previous_quantity']} → {$trans['new_quantity']})\n";
                    }
                }
            } else {
                echo "  ❌ Product not found in inventory!\n";
            }
        }
    }
    echo str_repeat('-', 50) . "\n";
}
?>
