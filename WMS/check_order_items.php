<?php
require_once 'config/database.php';

echo "=== Checking Recent Orders ===\n";
$stmt = $pdo->query('SELECT id, order_number, items, order_type, status FROM orders ORDER BY id DESC LIMIT 5');
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($orders as $order) {
    echo "\nOrder: {$order['order_number']} (ID: {$order['id']})\n";
    echo "Type: {$order['order_type']}, Status: {$order['status']}\n";
    echo "Items data: ";
    if (empty($order['items'])) {
        echo "❌ EMPTY/NULL\n";
    } else {
        echo "✅ Has data\n";
        echo "Raw: " . substr($order['items'], 0, 200) . "...\n";
        
        $items = json_decode($order['items'], true);
        if ($items) {
            echo "Parsed items:\n";
            foreach ($items as $item) {
                echo "- {$item['name']} (Qty: {$item['quantity']}, Price: {$item['unit_price']})\n";
            }
        } else {
            echo "❌ Failed to parse JSON\n";
        }
    }
    echo str_repeat('-', 50) . "\n";
}
?>
