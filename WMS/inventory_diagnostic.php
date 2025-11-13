<?php
/**
 * Diagnostic script to check inventory system status
 */

require_once 'config/database.php';

echo "<h2>🔍 Inventory System Diagnostic</h2>";

try {
    // Check orders table structure
    echo "<h3>📋 Orders Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasInventoryProcessed = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'inventory_processed') {
            $hasInventoryProcessed = true;
            break;
        }
    }
    
    if ($hasInventoryProcessed) {
        echo "<p style='color: green;'>✅ inventory_processed column exists</p>";
    } else {
        echo "<p style='color: red;'>❌ inventory_processed column missing</p>";
        
        // Add it
        $pdo->exec("ALTER TABLE orders ADD COLUMN inventory_processed BOOLEAN DEFAULT FALSE");
        echo "<p style='color: green;'>✅ Added inventory_processed column</p>";
    }
    
    // Check products table
    echo "<h3>📦 Products Table</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $productCount = $stmt->fetch()['count'];
    echo "<p>Products in database: <strong>$productCount</strong></p>";
    
    if ($productCount > 0) {
        $stmt = $pdo->query("SELECT id, name, quantity FROM products LIMIT 5");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Quantity</th></tr>";
        foreach ($products as $product) {
            echo "<tr><td>{$product['id']}</td><td>{$product['name']}</td><td>{$product['quantity']}</td></tr>";
        }
        echo "</table>";
    }
    
    // Check inventory_transactions table
    echo "<h3>📊 Inventory Transactions Table</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'inventory_transactions'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ inventory_transactions table exists</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory_transactions");
        $transactionCount = $stmt->fetch()['count'];
        echo "<p>Transaction records: <strong>$transactionCount</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ inventory_transactions table missing</p>";
        
        // Create it
        $createSQL = "
        CREATE TABLE inventory_transactions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            product_id INT NOT NULL,
            order_id INT NULL,
            transaction_type ENUM('inbound', 'outbound', 'adjustment') NOT NULL,
            quantity_change INT NOT NULL,
            previous_quantity INT NOT NULL,
            new_quantity INT NOT NULL,
            unit_price DECIMAL(10,2) DEFAULT 0.00,
            reference_number VARCHAR(100) DEFAULT '',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product_id (product_id),
            INDEX idx_order_id (order_id)
        )";
        
        $pdo->exec($createSQL);
        echo "<p style='color: green;'>✅ Created inventory_transactions table</p>";
    }
    
    // Test a simple inventory function call
    echo "<h3>🧪 Test Inventory Function</h3>";
    
    // Get a sample order to test with
    $stmt = $pdo->query("SELECT id, order_type, items, status FROM orders WHERE items IS NOT NULL AND items != '' LIMIT 1");
    $testOrder = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testOrder) {
        echo "<p>Testing with Order ID: <strong>{$testOrder['id']}</strong></p>";
        echo "<p>Order Type: <strong>{$testOrder['order_type']}</strong></p>";
        echo "<p>Status: <strong>{$testOrder['status']}</strong></p>";
        echo "<p>Items: <strong>" . htmlspecialchars($testOrder['items']) . "</strong></p>";
        
        // Parse items
        $items = json_decode($testOrder['items'], true);
        if (is_array($items) && !empty($items)) {
            echo "<p style='color: green;'>✅ Items JSON is valid and contains " . count($items) . " item(s)</p>";
            
            foreach ($items as $index => $item) {
                echo "<p>Item " . ($index + 1) . ": {$item['name']} (Qty: {$item['quantity']})</p>";
                
                // Check if product exists
                $stmt = $pdo->prepare("SELECT id, quantity FROM products WHERE name = ?");
                $stmt->execute([$item['name']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    echo "<p style='color: green;'>  ✅ Product found in database (ID: {$product['id']}, Stock: {$product['quantity']})</p>";
                } else {
                    echo "<p style='color: orange;'>  ⚠️ Product '{$item['name']}' not found in products table</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ Items JSON is invalid or empty</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No orders with items found for testing</p>";
    }
    
    echo "<h2 style='color: green;'>✅ Diagnostic completed!</h2>";
    echo "<p><a href='order_form.php' style='background: #3498db; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Test Order Form</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Diagnostic Error:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
