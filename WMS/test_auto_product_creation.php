<?php
require_once 'config/database.php';
require_once 'includes/inventory_functions.php';

echo "=== Testing Auto-Product Creation for Inbound Orders ===\n\n";

try {
    $pdo->beginTransaction();
    
    // Create a test inbound order with a product that doesn't exist
    $testProductName = "Auto-Test-Product-" . date('His');
    $testItems = [
        ['name' => $testProductName, 'quantity' => 15, 'unit_price' => 75.00, 'total' => 1125.00]
    ];
    
    $orderNumber = 'TEST-AUTO-' . date('Y-m-d-His');
    $itemsJson = json_encode($testItems);
    
    echo "Creating test inbound order...\n";
    echo "Product: $testProductName (this product doesn't exist yet)\n";
    echo "Quantity: 15\n";
    echo "Order: $orderNumber\n\n";
    
    // Insert the order
    $stmt = $pdo->prepare("
        INSERT INTO orders (order_number, order_type, status, customer_supplier, contact_person, 
                           order_date, items, total_value, inventory_processed, created_at)
        VALUES (?, 'inbound', 'pending', 'Test Auto Supplier', 'Test Contact', CURDATE(), ?, ?, FALSE, NOW())
    ");
    
    $stmt->execute([$orderNumber, $itemsJson, 1125.00]);
    $orderId = $pdo->lastInsertId();
    
    echo "✅ Order created with ID: $orderId\n";
    
    // Check if product exists before processing
    $stmt = $pdo->prepare("SELECT id, quantity FROM products WHERE name = ?");
    $stmt->execute([$testProductName]);
    $productBefore = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($productBefore) {
        echo "❌ ERROR: Product already exists! (ID: {$productBefore['id']}, Qty: {$productBefore['quantity']})\n";
    } else {
        echo "✅ Confirmed: Product doesn't exist yet\n";
    }
    
    echo "\n=== Processing Order Completion ===\n";
    
    // Now test the inventory update when marking as completed
    $result = updateInventoryFromOrder($pdo, $orderId, 'completed', 'pending');
    
    if ($result) {
        echo "✅ Inventory update returned SUCCESS\n\n";
        
        // Check if product was created
        $stmt = $pdo->prepare("SELECT id, name, sku, quantity, price FROM products WHERE name = ?");
        $stmt->execute([$testProductName]);
        $productAfter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($productAfter) {
            echo "✅ Product auto-created successfully!\n";
            echo "- ID: {$productAfter['id']}\n";
            echo "- Name: {$productAfter['name']}\n";
            echo "- SKU: {$productAfter['sku']}\n";
            echo "- Quantity: {$productAfter['quantity']}\n";
            echo "- Price: {$productAfter['price']}\n\n";
            
            // Check inventory transaction
            $stmt = $pdo->prepare("
                SELECT * FROM inventory_transactions 
                WHERE product_id = ? AND order_id = ?
            ");
            $stmt->execute([$productAfter['id'], $orderId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($transaction) {
                echo "✅ Inventory transaction created:\n";
                echo "- Type: {$transaction['transaction_type']}\n";
                echo "- Quantity Change: {$transaction['quantity_change']}\n";
                echo "- Previous Qty: {$transaction['previous_quantity']}\n";
                echo "- New Qty: {$transaction['new_quantity']}\n";
                echo "- Reference: {$transaction['reference_number']}\n\n";
            } else {
                echo "❌ No inventory transaction found!\n";
            }
            
        } else {
            echo "❌ Product was NOT created!\n";
        }
        
        // Check if order was marked as processed
        $stmt = $pdo->prepare("SELECT inventory_processed FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order && $order['inventory_processed']) {
            echo "✅ Order marked as inventory processed\n";
        } else {
            echo "❌ Order NOT marked as inventory processed\n";
        }
        
    } else {
        echo "❌ Inventory update returned FAILURE\n";
        
        // Check for error details
        if (isset($_SESSION['inventory_debug_error'])) {
            echo "Error: {$_SESSION['inventory_debug_error']}\n";
        }
    }
    
    // Rollback to clean up test data
    $pdo->rollback();
    echo "\n✅ Test completed, changes rolled back\n";
    
} catch (Exception $e) {
    $pdo->rollback();
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
