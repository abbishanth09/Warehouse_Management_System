<?php
/**
 * Common functions and utilities for the Warehouse Management System
 */

/**
 * Sanitize input data to prevent XSS attacks
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate required fields
 */
function validateRequired($fields) {
    $errors = [];
    foreach ($fields as $field => $value) {
        if (empty(trim($value))) {
            $errors[] = ucfirst($field) . " is required";
        }
    }
    return $errors;
}

/**
 * Validate SKU format
 */
function validateSKU($sku) {
    return preg_match('/^[A-Za-z0-9]{3,20}$/', $sku);
}

/**
 * Validate price
 */
function validatePrice($price) {
    return is_numeric($price) && $price > 0;
}

/**
 * Validate quantity
 */
function validateQuantity($quantity) {
    return is_numeric($quantity) && $quantity >= 0;
}

/**
 * Format currency for display (Sri Lankan Rupees)
 */
function formatCurrency($amount) {
    return 'Rs. ' . number_format($amount, 2);
}

/**
 * Format currency in compact form for dashboard (Sri Lankan Rupees)
 */
function formatCurrencyCompact($amount) {
    if ($amount >= 10000000) { // 10 million or more
        return 'Rs. ' . number_format($amount / 1000000, 1) . 'M';
    } elseif ($amount >= 100000) { // 100 thousand or more
        return 'Rs. ' . number_format($amount / 1000, 0) . 'K';
    } else {
        return 'Rs. ' . number_format($amount, 0);
    }
}

/**
 * Format number with commas
 */
function formatNumber($number) {
    return number_format($number);
}

/**
 * Get all unique categories from products
 */
function getCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Check if SKU already exists (for new products)
 */
function skuExists($pdo, $sku, $excludeId = null) {
    try {
        $sql = "SELECT COUNT(*) FROM products WHERE sku = ?";
        $params = [$sku];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get dashboard statistics
 */
function getDashboardStats($pdo) {
    try {
        $stats = [];
        
        // Total products
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
        $stats['total_products'] = $stmt->fetchColumn();
        
        // Total inventory value
        $stmt = $pdo->query("SELECT SUM(quantity * price) FROM products");
        $stats['total_value'] = $stmt->fetchColumn() ?: 0;
        
        // Low stock items
        $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity <= min_stock_level");
        $stats['low_stock_items'] = $stmt->fetchColumn();
        
        // Total quantity
        $stmt = $pdo->query("SELECT SUM(quantity) FROM products");
        $stats['total_quantity'] = $stmt->fetchColumn() ?: 0;
        
        // Categories
        $stmt = $pdo->query("SELECT COUNT(DISTINCT category) FROM products");
        $stats['total_categories'] = $stmt->fetchColumn();
        
        // Orders statistics (check if orders table exists)
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
            $stats['total_orders'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
            $stats['pending_orders'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_type = 'inbound'");
            $stats['inbound_orders'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_type = 'outbound'");
            $stats['outbound_orders'] = $stmt->fetchColumn();
        } catch (PDOException $e) {
            // Orders table doesn't exist yet
            $stats['total_orders'] = 0;
            $stats['pending_orders'] = 0;
            $stats['inbound_orders'] = 0;
            $stats['outbound_orders'] = 0;
        }
        
        return $stats;
    } catch (PDOException $e) {
        return [
            'total_products' => 0,
            'total_value' => 0,
            'low_stock_items' => 0,
            'total_quantity' => 0,
            'total_categories' => 0,
            'total_orders' => 0,
            'pending_orders' => 0,
            'inbound_orders' => 0,
            'outbound_orders' => 0
        ];
    }
}

/**
 * Get low stock products
 */
function getLowStockProducts($pdo, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT name, sku, quantity, min_stock_level 
            FROM products 
            WHERE quantity <= min_stock_level 
            ORDER BY (quantity / min_stock_level) ASC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get recent products (last 5 added)
 */
function getRecentProducts($pdo, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT name, sku, category, quantity, created_at 
            FROM products 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Generate a unique SKU if not provided
 */
function generateSKU($pdo, $name, $category) {
    $nameWords = explode(' ', trim($name));
    $namePrefix = '';
    foreach ($nameWords as $word) {
        if (!empty($word)) {
            $namePrefix .= strtoupper(substr($word, 0, 1));
        }
    }
    
    $categoryPrefix = strtoupper(substr($category, 0, 2));
    $timestamp = substr(time(), -3);
    
    $baseSKU = $categoryPrefix . $namePrefix . $timestamp;
    $sku = $baseSKU;
    $counter = 1;
    
    // Ensure uniqueness
    while (skuExists($pdo, $sku)) {
        $sku = $baseSKU . $counter;
        $counter++;
    }
    
    return $sku;
}

/**
 * Redirect function
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'success') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Include header template
 */
function includeHeader($title = 'Warehouse Management System', $currentPage = '') {
    include 'includes/header.php';
}

/**
 * Include footer template
 */
function includeFooter() {
    include 'includes/footer.php';
}
?>
