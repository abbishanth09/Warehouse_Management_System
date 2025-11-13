<?php
/**
 * Database Update Runner
 * Run this file once to update the database schema for inventory management
 */

require_once 'config/database.php';

try {
    echo "<h2>🔄 Updating Database Schema for Inventory Management...</h2>";
    
    // Read and execute the inventory management schema
    $sql = file_get_contents('database/inventory_management_schema.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "<p style='color: green;'>✅ Executed: " . substr($statement, 0, 50) . "...</p>";
            } catch (PDOException $e) {
                echo "<p style='color: orange;'>⚠️ Warning: " . $e->getMessage() . "</p>";
                echo "<p>Statement: " . substr($statement, 0, 100) . "...</p>";
            }
        }
    }
    
    // Run the orders table update
    echo "<br><h3>🔄 Updating Orders Table...</h3>";
    $ordersSql = file_get_contents('database/update_orders_table.sql');
    $orderStatements = array_filter(array_map('trim', explode(';', $ordersSql)));
    
    foreach ($orderStatements as $statement) {
        if (!empty($statement) && !str_starts_with(trim($statement), '--')) {
            try {
                $pdo->exec($statement);
                echo "<p style='color: green;'>✅ Executed: " . substr($statement, 0, 50) . "...</p>";
            } catch (PDOException $e) {
                echo "<p style='color: orange;'>⚠️ Warning: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<br><h2 style='color: green;'>🎉 Database Update Complete!</h2>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Inventory transactions table created</li>";
    echo "<li>✅ Orders table updated with inventory processing flag</li>";
    echo "<li>✅ Items and total_value columns added to orders</li>";
    echo "<li>🔄 Test the functionality by creating a new order</li>";
    echo "</ul>";
    
    echo "<p><a href='order_form.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>➕ Create Test Order</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error updating database:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #2c3e50; }
p { margin: 5px 0; }
ul { margin: 10px 0; }
</style>
