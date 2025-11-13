<?php
/**
 * Get Available Products for Outbound Orders
 * Returns JSON data of products with current stock levels
 */

require_once 'config/database.php';

header('Content-Type: application/json');

try {
    // Get products with current stock levels
    $stmt = $pdo->prepare("
        SELECT id, name, sku, quantity, price, category, location
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
            'stock' => intval($row['quantity']),
            'category' => $row['category'],
            'location' => $row['location']
        ];
    }
    
    echo json_encode($products);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
