<?php
try {
    require_once 'config/database.php';
    require_once 'includes/inventory_functions.php';
    
    echo "=== Testing Inventory Update ===\n\n";
    
    // Find an inbound order that's pending or processing
    $stmt = $pdo->query("
        SELECT id, order_number, order_type, status, items, inventory_processed 
        FROM orders 
        WHERE order_type = 'inbound' AND status != 'completed'
        ORDER BY id DESC 
        LIMIT 1
    ");
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "❌ No inbound orders found to test.\n";
        echo "Creating a test inbound order...\n\n";
        
        // Create test order
        $testItems = [
            ['name' => 'Test Product A', 'quantity' => 5, 'unit_price' => 100.00, 'total' => 500.00],
            ['name' => 'Test Product B', 'quantity' => 3, 'unit_price' => 50.00, 'total' => 150.00]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO orders (order_number, order_type, status, customer_supplier, contact_person, 
                               order_date, items, total_value, created_at)
            VALUES (?, 'inbound', 'pending', 'Test Supplier', 'Test Contact', CURDATE(), ?, ?, NOW())
        ");
        
        $orderNumber = 'TEST-INB-' . date('Y-m-d-His');
        $itemsJson = json_encode($testItems);
        $totalValue = 650.00;
        
        $stmt->execute([$orderNumber, $itemsJson, $totalValue]);
        $testOrderId = $pdo->lastInsertId();
        
        echo "✅ Test order created: $orderNumber (ID: $testOrderId)\n\n";
        
        // Get the created order
        $stmt = $pdo->prepare("SELECT id, order_number, order_type, status, items, inventory_processed FROM orders WHERE id = ?");
        $stmt->execute([$testOrderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    echo "Found order to test:\n";
    echo "- ID: {$order['id']}\n";
    echo "- Number: {$order['order_number']}\n";
    echo "- Type: {$order['order_type']}\n";
    echo "- Status: {$order['status']}\n";
    echo "- Inventory Processed: " . ($order['inventory_processed'] ? 'Yes' : 'No') . "\n";
    echo "- Items: {$order['items']}\n\n";
    
    // Parse items to check if products exist
    $items = json_decode($order['items'], true);
    echo "Items in order:\n";
    foreach ($items as $item) {
        echo "- {$item['name']} (Qty: {$item['quantity']})\n";
        
        // Check if product exists
        $stmt = $pdo->prepare("SELECT id, name, quantity FROM products WHERE name = ?");
        $stmt->execute([$item['name']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            echo "  ✅ Product exists - Current stock: {$product['quantity']}\n";
        } else {
            echo "  ❌ Product doesn't exist - Creating it...\n";
            
            // Create the product
            $stmt = $pdo->prepare("
                INSERT INTO products (name, sku, category, quantity, price, min_stock_level) 
                VALUES (?, ?, 'Test Category', 0, ?, 5)
            ");
            $sku = 'SKU-' . strtoupper(substr(str_replace(' ', '', $item['name']), 0, 6)) . '-' . rand(100, 999);
            $stmt->execute([$item['name'], $sku, $item['unit_price']]);
            echo "  ✅ Product created with SKU: $sku\n";
        }
    }
    
    echo "\n=== Testing Inventory Update Function ===\n";
    
    // Test the inventory update function
    $result = updateInventoryFromOrder($pdo, $order['id'], 'completed', $order['status']);
    
    if ($result) {
        echo "✅ Inventory update function returned SUCCESS\n\n";
        
        // Check if products were updated
        echo "Checking product quantities after update:\n";
        foreach ($items as $item) {
            $stmt = $pdo->prepare("SELECT id, name, quantity FROM products WHERE name = ?");
            $stmt->execute([$item['name']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                echo "- {$product['name']}: {$product['quantity']} (should have increased by {$item['quantity']})\n";
            }
        }
        
        // Check inventory transactions
        echo "\nInventory transactions created:\n";
        $stmt = $pdo->prepare("
            SELECT it.*, p.name as product_name 
            FROM inventory_transactions it 
            JOIN products p ON it.product_id = p.id 
            WHERE it.order_id = ?
            ORDER BY it.created_at DESC
        ");
        $stmt->execute([$order['id']]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($transactions)) {
            echo "❌ No inventory transactions found!\n";
        } else {
            foreach ($transactions as $trans) {
                echo "- {$trans['product_name']}: {$trans['transaction_type']} {$trans['quantity_change']} ({$trans['previous_quantity']} → {$trans['new_quantity']})\n";
            }
        }
        
    } else {
        echo "❌ Inventory update function returned FAILURE\n";
        
        // Check for debug error
        session_start();
        if (isset($_SESSION['inventory_debug_error'])) {
            echo "Debug error: {$_SESSION['inventory_debug_error']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
