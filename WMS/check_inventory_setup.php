<?php
try {
    require_once 'config/database.php';
    
    echo "=== Checking Orders Table Structure ===\n";
    $stmt = $pdo->query('DESCRIBE orders');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasInventoryProcessed = false;
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
        if ($column['Field'] === 'inventory_processed') {
            $hasInventoryProcessed = true;
        }
    }
    
    if (!$hasInventoryProcessed) {
        echo "\n❌ inventory_processed column is MISSING!\n";
        echo "This explains why inventory is not being updated.\n\n";
        
        echo "Adding inventory_processed column...\n";
        $pdo->exec("ALTER TABLE orders ADD COLUMN inventory_processed BOOLEAN DEFAULT FALSE");
        echo "✅ inventory_processed column added successfully!\n";
    } else {
        echo "\n✅ inventory_processed column exists\n";
    }
    
    echo "\n=== Checking Products Table ===\n";
    $stmt = $pdo->query('DESCRIBE products');
    $productColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($productColumns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n=== Checking Inventory Transactions Table ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'inventory_transactions'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "❌ inventory_transactions table is MISSING!\n";
        echo "Creating inventory_transactions table...\n";
        
        $createTable = "
        CREATE TABLE inventory_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            order_id INT,
            transaction_type ENUM('inbound', 'outbound') NOT NULL,
            quantity_change INT NOT NULL,
            previous_quantity INT NOT NULL,
            new_quantity INT NOT NULL,
            unit_price DECIMAL(10,2) DEFAULT 0.00,
            reference_number VARCHAR(255),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
        )";
        
        $pdo->exec($createTable);
        echo "✅ inventory_transactions table created successfully!\n";
    } else {
        echo "✅ inventory_transactions table exists\n";
        $stmt = $pdo->query('DESCRIBE inventory_transactions');
        $transactionColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($transactionColumns as $column) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
