<?php
/    // Check if inventory_processed column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'inventory_processed'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        echo "<h2>Adding missing inventory_processed column...</h2>";
        
        // Add the column
        $pdo->exec("ALTER TABLE orders ADD COLUMN inventory_processed BOOLEAN DEFAULT FALSE");
        echo "<p style='color: green;'>✅ Successfully added inventory_processed column to orders table</p>";
        
        // Set existing completed orders as processed to avoid double-processing
        $pdo->exec("UPDATE orders SET inventory_processed = TRUE WHERE status = 'completed'");
        echo "<p style='color: blue;'>ℹ️ Marked existing completed orders as inventory_processed = TRUE</p>";
        
    } else {
        echo "<p style='color: blue;'>ℹ️ inventory_processed column already exists</p>";
    }
    
    // Check if inventory_transactions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'inventory_transactions'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<h2>Creating inventory_transactions table...</h2>";
        
        $createTableSQL = "
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
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
        )";
        
        $pdo->exec($createTableSQL);
        echo "<p style='color: green;'>✅ Successfully created inventory_transactions table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ inventory_transactions table already exists</p>";
    } inventory_processed column in orders table
 */

require_once 'config/database.php';

try {
    // Check if inventory_processed column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'inventory_processed'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        echo "<h2>Adding missing inventory_processed column...</h2>";
        
        // Add the column
        $pdo->exec("ALTER TABLE orders ADD COLUMN inventory_processed BOOLEAN DEFAULT FALSE");
        echo "<p style='color: green;'>✅ Successfully added inventory_processed column to orders table</p>";
        
        // Set existing completed orders as processed to avoid double-processing
        $pdo->exec("UPDATE orders SET inventory_processed = TRUE WHERE status = 'completed'");
        echo "<p style='color: blue;'>ℹ️ Marked existing completed orders as inventory_processed = TRUE</p>";
        
    } else {
        echo "<p style='color: blue;'>ℹ️ inventory_processed column already exists</p>";
    }
    
    // Check if products table has all necessary columns
    echo "<h3>Checking products table structure...</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['id', 'name', 'sku', 'quantity', 'price'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "<p style='color: green;'>✅ Products table has all required columns</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing columns in products table: " . implode(', ', $missingColumns) . "</p>";
        
        // Try to add missing columns
        foreach ($missingColumns as $column) {
            switch ($column) {
                case 'quantity':
                    $pdo->exec("ALTER TABLE products ADD COLUMN quantity INT DEFAULT 0");
                    echo "<p style='color: green;'>✅ Added quantity column</p>";
                    break;
                case 'price':
                    $pdo->exec("ALTER TABLE products ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00");
                    echo "<p style='color: green;'>✅ Added price column</p>";
                    break;
                case 'sku':
                    $pdo->exec("ALTER TABLE products ADD COLUMN sku VARCHAR(100) DEFAULT ''");
                    echo "<p style='color: green;'>✅ Added sku column</p>";
                    break;
            }
        }
    }
    
    // Show current products
    echo "<h3>Current Products in Database:</h3>";
    $stmt = $pdo->query("SELECT * FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "<p style='color: orange;'>⚠️ No products found. Adding sample products...</p>";
        
        // Add sample products
        $sampleProducts = [
            ['Laptop', 'LAP001', 5, 50000.00],
            ['Mouse', 'MOU001', 25, 1500.00],
            ['Keyboard', 'KEY001', 15, 3000.00],
            ['Monitor', 'MON001', 8, 25000.00],
            ['Printer Paper', 'PAP001', 100, 500.00]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO products (name, sku, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($sampleProducts as $product) {
            $stmt->execute($product);
            echo "<p>➕ Added: {$product[0]} (SKU: {$product[1]}, Qty: {$product[2]}, Price: Rs. {$product[3]})</p>";
        }
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Name</th><th>SKU</th><th>Quantity</th><th>Price</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . ($product['id'] ?? 'N/A') . "</td>";
            echo "<td>" . ($product['name'] ?? 'N/A') . "</td>";
            echo "<td>" . ($product['sku'] ?? 'N/A') . "</td>";
            echo "<td>" . ($product['quantity'] ?? 'N/A') . "</td>";
            echo "<td>Rs. " . number_format($product['price'] ?? 0, 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2 style='color: green;'>✅ Database structure check completed!</h2>";
    echo "<p><a href='order_form.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Order Form</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
