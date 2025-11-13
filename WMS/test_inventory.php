<?php
/**
 * Simple inventory function test
 */

require_once 'config/database.php';
require_once 'includes/inventory_functions.php';

echo "<h2>🧪 Testing Inventory Functions</h2>";

try {
    // Create a test order first
    echo "<h3>Creating test order...</h3>";
    
    $testItems = [
        ['name' => 'Test Product', 'quantity' => 5, 'unit_price' => 100.00, 'total' => 500.00]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO orders (order_number, order_type, status, customer_supplier, 
                          contact_person, order_date, items, total_value)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        'TEST-' . time(),
        'inbound',
        'pending',
        'Test Supplier',
        'Test Person',
        date('Y-m-d'),
        json_encode($testItems),
        500.00
    ]);
    
    if ($result) {
        $testOrderId = $pdo->lastInsertId();
        echo "<p style='color: green;'>✅ Test order created with ID: $testOrderId</p>";
        
        // Add test product if it doesn't exist
        $stmt = $pdo->prepare("SELECT id FROM products WHERE name = 'Test Product'");
        $stmt->execute();
        $product = $stmt->fetch();
        
        if (!$product) {
            $stmt = $pdo->prepare("INSERT INTO products (name, sku, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute(['Test Product', 'TEST001', 10, 100.00]);
            echo "<p style='color: green;'>✅ Test product created</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Test product already exists</p>";
        }
        
        // Now test the inventory function
        echo "<h3>Testing inventory update...</h3>";
        
        $result = updateInventoryFromOrder($pdo, $testOrderId, 'completed');
        
        if ($result) {
            echo "<p style='color: green;'>✅ Inventory update successful!</p>";
            
            // Check the updated quantity
            $stmt = $pdo->prepare("SELECT quantity FROM products WHERE name = 'Test Product'");
            $stmt->execute();
            $updatedProduct = $stmt->fetch();
            
            if ($updatedProduct) {
                echo "<p>New product quantity: <strong>{$updatedProduct['quantity']}</strong></p>";
            }
            
            // Check transaction log
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM inventory_transactions WHERE order_id = ?");
            $stmt->execute([$testOrderId]);
            $transactionCount = $stmt->fetch()['count'];
            
            echo "<p>Transaction records created: <strong>$transactionCount</strong></p>";
            
        } else {
            echo "<p style='color: red;'>❌ Inventory update failed!</p>";
            
            if (isset($_SESSION['inventory_debug_error'])) {
                echo "<p style='color: red;'>Error details: " . $_SESSION['inventory_debug_error'] . "</p>";
            }
        }
        
        // Cleanup
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$testOrderId]);
        echo "<p style='color: blue;'>🧹 Cleaned up test order</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Failed to create test order</p>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Test Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; margin: 10px 0;'>" . $e->getTraceAsString() . "</pre>";
}

echo "<p><a href='order_form.php' style='background: #3498db; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Back to Order Form</a></p>";
?>
