<?php
require_once 'config/database.php';
require_once 'includes/inventory_functions.php';

echo "=== Finding and Processing Unprocessed Inbound Orders ===\n\n";

// Find completed inbound orders that haven't been processed
$stmt = $pdo->query("
    SELECT id, order_number, status, inventory_processed, items 
    FROM orders 
    WHERE order_type = 'inbound' 
    AND status = 'completed' 
    AND inventory_processed = FALSE 
    AND items IS NOT NULL 
    AND items != ''
    ORDER BY id DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($orders)) {
    echo "No unprocessed inbound orders found.\n";
    
    // Check for any completed inbound orders
    $stmt = $pdo->query("
        SELECT id, order_number, status, inventory_processed 
        FROM orders 
        WHERE order_type = 'inbound' AND status = 'completed'
        ORDER BY id DESC
    ");
    $allCompleted = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($allCompleted)) {
        echo "\nAll completed inbound orders:\n";
        foreach ($allCompleted as $order) {
            echo "- {$order['order_number']} (ID: {$order['id']}) - Processed: " . ($order['inventory_processed'] ? 'YES' : 'NO') . "\n";
        }
    }
} else {
    echo "Found " . count($orders) . " unprocessed inbound order(s):\n\n";
    
    foreach ($orders as $order) {
        echo "Processing Order: {$order['order_number']} (ID: {$order['id']})\n";
        
        $items = json_decode($order['items'], true);
        if ($items) {
            echo "Items to process:\n";
            foreach ($items as $item) {
                echo "- {$item['name']} (Qty: {$item['quantity']})\n";
            }
            
            echo "\nRunning inventory update...\n";
            $result = updateInventoryFromOrder($pdo, $order['id'], 'completed', 'completed');
            
            if ($result) {
                echo "✅ Successfully processed inventory for order {$order['order_number']}\n";
            } else {
                echo "❌ Failed to process inventory for order {$order['order_number']}\n";
                if (isset($_SESSION['inventory_debug_error'])) {
                    echo "Error: {$_SESSION['inventory_debug_error']}\n";
                }
            }
        } else {
            echo "❌ No valid items found in order\n";
        }
        
        echo str_repeat('-', 50) . "\n";
    }
}
?>
