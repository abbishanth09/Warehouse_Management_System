<?php
/**
 * Inventory Management Functions
 * Handles automatic inventory updates when orders are completed
 */

// Include required functions
require_once __DIR__ . '/functions.php';

/**
 * Update product inventory when order status changes
 * @param PDO $pdo Database connection
 * @param int $orderId Order ID
 * @param string $newStatus New order status
 * @param string $oldStatus Previous order status
 * @return bool Success status
 */
function updateInventoryFromOrder($pdo, $orderId, $newStatus, $oldStatus = null) {
    try {
        // Get order details
        $orderStmt = $pdo->prepare("
            SELECT id, order_type, order_number, items, inventory_processed
            FROM orders 
            WHERE id = ?
        ");
        $orderStmt->execute([$orderId]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return false;
        }
        
        // Parse items from JSON
        $items = json_decode($order['items'], true);
        if (empty($items)) {
            return true; // No items to process
        }
        
        // Check if order is being completed and hasn't been processed yet
        if ($newStatus === 'completed' && !$order['inventory_processed']) {
            
            foreach ($items as $item) {
                // Find product by name
                $productStmt = $pdo->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
                $productStmt->execute([$item['name']]);
                $product = $productStmt->fetch();
                
                if ($product) {
                    $success = updateProductStock(
                        $pdo, 
                        $product['id'], 
                        $item['quantity'], 
                        $order['order_type'], 
                        $orderId,
                        $order['order_number'],
                        $item['unit_price']
                    );
                    
                    if (!$success) {
                        return false;
                    }
                } else {
                    // Product doesn't exist
                    if ($order['order_type'] === 'inbound') {
                        // For inbound orders, create the product automatically
                        $sku = generateSKU($pdo, $item['name'], 'Auto-Generated');
                        $createStmt = $pdo->prepare("
                            INSERT INTO products (name, sku, category, quantity, price, min_stock_level, description)
                            VALUES (?, ?, 'Auto-Generated', 0, ?, 5, 'Auto-created from inbound order')
                        ");
                        $createStmt->execute([$item['name'], $sku, $item['unit_price']]);
                        $newProductId = $pdo->lastInsertId();
                        
                        // Now update the stock
                        $success = updateProductStock(
                            $pdo, 
                            $newProductId, 
                            $item['quantity'], 
                            $order['order_type'], 
                            $orderId,
                            $order['order_number'],
                            $item['unit_price']
                        );
                        
                        if (!$success) {
                            return false;
                        }
                    } else {
                        // For outbound orders, product must exist
                        throw new Exception("Product '{$item['name']}' not found in inventory. Cannot process outbound order.");
                    }
                }
            }
            
            // Mark order as inventory processed
            $updateStmt = $pdo->prepare("UPDATE orders SET inventory_processed = TRUE WHERE id = ?");
            $updateStmt->execute([$orderId]);
            
            return true;
        }
        
        // If order is being changed from completed to another status, reverse the inventory
        if ($oldStatus === 'completed' && $newStatus !== 'completed' && $order['inventory_processed']) {
            
            foreach ($items as $item) {
                // Find product by name
                $productStmt = $pdo->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
                $productStmt->execute([$item['name']]);
                $product = $productStmt->fetch();
                
                if ($product) {
                    // Reverse the inventory change
                    $reverseType = $order['order_type'] === 'inbound' ? 'outbound' : 'inbound';
                    $success = updateProductStock(
                        $pdo, 
                        $product['id'], 
                        $item['quantity'], 
                        $reverseType, 
                        $orderId,
                        $order['order_number'] . ' (REVERSED)',
                        $item['unit_price']
                    );
                    
                    if (!$success) {
                        return false;
                    }
                }
            }
            
            // Mark order as not processed
            $updateStmt = $pdo->prepare("UPDATE orders SET inventory_processed = FALSE WHERE id = ?");
            $updateStmt->execute([$orderId]);
            
            return true;
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Inventory update error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // For debugging - you can remove this in production
        if (isset($_SESSION)) {
            $_SESSION['inventory_debug_error'] = $e->getMessage();
        }
        
        return false;
    }
}

/**
 * Update product stock quantity
 * @param PDO $pdo Database connection
 * @param int $productId Product ID
 * @param int $quantity Quantity to add/subtract
 * @param string $orderType 'inbound' or 'outbound'
 * @param int $orderId Order ID for reference
 * @param string $orderNumber Order number for reference
 * @param float $unitPrice Unit price for transaction record
 * @return bool Success status
 */
function updateProductStock($pdo, $productId, $quantity, $orderType, $orderId = null, $orderNumber = '', $unitPrice = 0) {
    try {
        // Get current product quantity
        $productStmt = $pdo->prepare("SELECT quantity, name FROM products WHERE id = ?");
        $productStmt->execute([$productId]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return false;
        }
        
        $previousQuantity = $product['quantity'];
        $quantityChange = $orderType === 'inbound' ? $quantity : -$quantity;
        $newQuantity = $previousQuantity + $quantityChange;
        
        // Check for negative inventory on outbound
        if ($orderType === 'outbound' && $newQuantity < 0) {
            throw new Exception("Insufficient stock for product: " . $product['name'] . ". Available: $previousQuantity, Required: $quantity");
        }
        
        // Update product quantity
        $updateStmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        $updateResult = $updateStmt->execute([$newQuantity, $productId]);
        
        if (!$updateResult) {
            return false;
        }
        
        // Log the transaction
        $logStmt = $pdo->prepare("
            INSERT INTO inventory_transactions 
            (product_id, order_id, transaction_type, quantity_change, previous_quantity, new_quantity, unit_price, reference_number, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $notes = "Automatic update from order completion: $orderNumber";
        $logResult = $logStmt->execute([
            $productId,
            $orderId,
            $orderType,
            $quantityChange,
            $previousQuantity,
            $newQuantity,
            $unitPrice,
            $orderNumber,
            $notes
        ]);
        
        return $logResult;
        
    } catch (Exception $e) {
        error_log("Product stock update error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Get inventory transaction history for a product
 * @param PDO $pdo Database connection
 * @param int $productId Product ID
 * @param int $limit Number of records to return
 * @return array Transaction history
 */
function getInventoryHistory($pdo, $productId, $limit = 50) {
    try {
        $stmt = $pdo->prepare("
            SELECT it.*, o.order_number, p.name as product_name
            FROM inventory_transactions it
            LEFT JOIN orders o ON it.order_id = o.id
            LEFT JOIN products p ON it.product_id = p.id
            WHERE it.product_id = ?
            ORDER BY it.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$productId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get inventory history error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get low stock alerts
 * @param PDO $pdo Database connection
 * @return array Products below minimum stock level
 */
function getLowStockAlerts($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, sku, quantity, min_stock_level, category, location
            FROM products 
            WHERE quantity <= min_stock_level
            ORDER BY (quantity - min_stock_level) ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get low stock alerts error: " . $e->getMessage());
        return [];
    }
}

/**
 * Validate stock availability for outbound order
 * @param PDO $pdo Database connection
 * @param array $orderItems Array of items with product_id and quantity
 * @return array Validation result with success status and any errors
 */
function validateStockAvailability($pdo, $orderItems) {
    $result = ['success' => true, 'errors' => []];
    
    try {
        foreach ($orderItems as $item) {
            $stmt = $pdo->prepare("SELECT name, quantity FROM products WHERE id = ?");
            $stmt->execute([$item['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                $result['success'] = false;
                $result['errors'][] = "Product ID {$item['product_id']} not found";
                continue;
            }
            
            if ($product['quantity'] < $item['quantity']) {
                $result['success'] = false;
                $result['errors'][] = "Insufficient stock for {$product['name']}. Available: {$product['quantity']}, Required: {$item['quantity']}";
            }
        }
    } catch (Exception $e) {
        $result['success'] = false;
        $result['errors'][] = "Error validating stock: " . $e->getMessage();
    }
    
    return $result;
}
