<?php
/**
 * Debug Products - Check what's in the products table
 */

require_once 'config/database.php';

echo "<h2>🔍 Products Debug Information</h2>";

try {
    // Check if products table exists and has data
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products");
    $stmt->execute();
    $total = $stmt->fetchColumn();
    
    echo "<p><strong>Total products in database:</strong> $total</p>";
    
    if ($total > 0) {
        // Get all products
        $stmt = $pdo->prepare("
            SELECT id, name, sku, quantity, price, category, location
            FROM products 
            ORDER BY name ASC
        ");
        $stmt->execute();
        
        echo "<h3>📋 All Products:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Name</th><th>SKU</th><th>Quantity</th><th>Price</th><th>Category</th><th>Location</th>";
        echo "</tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stockColor = $row['quantity'] > 0 ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['sku']}</td>";
            echo "<td style='color: $stockColor; font-weight: bold;'>{$row['quantity']}</td>";
            echo "<td>Rs. " . number_format($row['price'], 2) . "</td>";
            echo "<td>{$row['category']}</td>";
            echo "<td>{$row['location']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Get products with stock > 0
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as available_count
            FROM products 
            WHERE quantity > 0
        ");
        $stmt->execute();
        $availableCount = $stmt->fetchColumn();
        
        echo "<p><strong>Products with stock > 0:</strong> $availableCount</p>";
        
        if ($availableCount > 0) {
            echo "<h3>✅ Available Products (for outbound orders):</h3>";
            $stmt = $pdo->prepare("
                SELECT id, name, sku, quantity, price
                FROM products 
                WHERE quantity > 0
                ORDER BY name ASC
            ");
            $stmt->execute();
            
            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'sku' => $row['sku'],
                    'price' => floatval($row['price']),
                    'stock' => intval($row['quantity'])
                ];
            }
            
            echo "<p><strong>JSON Response:</strong></p>";
            echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 5px;'>";
            echo json_encode($products, JSON_PRETTY_PRINT);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'><strong>❌ No products have stock > 0!</strong></p>";
            echo "<p><strong>💡 Solution:</strong> Create some inbound orders to add stock, or manually update product quantities.</p>";
        }
    } else {
        echo "<p style='color: red;'><strong>❌ No products found in database!</strong></p>";
        echo "<p><strong>💡 Solution:</strong> Run the database schema to create sample products.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Database Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='order_form.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔙 Back to Order Form</a></p>";
echo "<p><a href='inventory.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📦 View Inventory</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
h2, h3 { color: #2c3e50; }
</style>
