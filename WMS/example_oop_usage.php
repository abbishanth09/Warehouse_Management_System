<?php
/**
 * Example OOP Usage - Demonstrates User and Product Classes
 * Shows how to use the implemented OOP concepts
 */

require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Product.php';

echo "=== WMS OOP Implementation Examples ===\n\n";

// ===== USER CLASS EXAMPLES =====
echo "1. USER CLASS EXAMPLES\n";
echo "----------------------\n\n";

// Create User object (Constructor - Dependency Injection)
$userManager = new User($pdo);

echo "Testing User Authentication:\n";

// Test authentication with email (Abstraction)
$loginSuccess = $userManager->authenticate('abbishanth1209@gmail.com', 'admin123');

if ($loginSuccess) {
    echo "✅ Email login successful!\n";
    echo "   User ID: " . $userManager->getId() . "\n";
    echo "   Username: " . $userManager->getUsername() . "\n";
    echo "   Email: " . $userManager->getEmail() . "\n";
    echo "   Role: " . $userManager->getRole() . "\n";
    echo "   Status: " . $userManager->getStatus() . "\n\n";
    
    // Get session data (State Management)
    $sessionData = $userManager->getSessionData();
    echo "Session Data:\n";
    print_r($sessionData);
    echo "\n";
} else {
    echo "❌ Login failed!\n\n";
}

// Test finding user by email
echo "Testing Find User by Email:\n";
$userManager2 = new User($pdo);
$found = $userManager2->findByEmail('abbishanth1209@gmail.com');

if ($found) {
    echo "✅ User found by email!\n";
    echo "   Full Name: " . $userManager2->getFullName() . "\n\n";
} else {
    echo "❌ User not found!\n\n";
}

// ===== PRODUCT CLASS EXAMPLES =====
echo "2. PRODUCT CLASS EXAMPLES\n";
echo "-------------------------\n\n";

// Create Product object (Constructor)
$productManager = new Product($pdo);

echo "Testing Product Creation:\n";

// Create new product (Abstraction)
$uniqueSku = 'OOP-LAP-' . date('His'); // Add timestamp for uniqueness
$productData = [
    'name' => 'OOP Test Laptop ' . date('H:i:s'),
    'sku' => $uniqueSku,
    'description' => 'Test laptop for OOP demonstration',
    'category' => 'Electronics',
    'quantity' => 25,
    'price' => 1299.99,
    'min_stock_level' => 5,
    'location' => 'A1-OOP-01'
];

$createSuccess = $productManager->create($productData);

if ($createSuccess) {
    echo "✅ Product created successfully!\n";
    echo "   Product ID: " . $productManager->getId() . "\n";
    echo "   Name: " . $productManager->getName() . "\n";
    echo "   SKU: " . $productManager->getSku() . "\n";
    echo "   Quantity: " . $productManager->getQuantity() . "\n";
    echo "   Price: $" . number_format($productManager->getPrice(), 2) . "\n\n";
    
    // Test stock management (State Management)
    echo "Testing Stock Management:\n";
    
    // Add stock
    echo "Current stock: " . $productManager->getQuantity() . "\n";
    $productManager->addStock(10);
    echo "After adding 10: " . $productManager->getQuantity() . "\n";
    
    // Remove stock
    $productManager->removeStock(5);
    echo "After removing 5: " . $productManager->getQuantity() . "\n";
    
    // Check low stock status
    if ($productManager->isLowStock()) {
        echo "⚠️  WARNING: Stock is low!\n";
    } else {
        echo "✅ Stock level is good\n";
    }
    echo "\n";
    
} else {
    echo "❌ Product creation failed!\n\n";
}

// ===== STATIC METHODS EXAMPLES =====
echo "3. STATIC METHODS EXAMPLES\n";
echo "--------------------------\n\n";

// Get all products (Static Method - no object needed)
echo "All Products (Static Method):\n";
$allProducts = Product::getAllProducts($pdo, 5);
echo "Found " . count($allProducts) . " products:\n";
foreach ($allProducts as $product) {
    echo "- " . $product['name'] . " (SKU: " . $product['sku'] . ")\n";
}
echo "\n";

// Get low stock products (Static Method)
echo "Low Stock Products (Static Method):\n";
$lowStockProducts = Product::getLowStockProducts($pdo);
echo "Found " . count($lowStockProducts) . " low stock items:\n";
foreach ($lowStockProducts as $product) {
    echo "- " . $product['name'] . " (Qty: " . $product['quantity'] . "/" . $product['min_stock_level'] . ")\n";
}
echo "\n";

// Search products (Static Method)
echo "Product Search (Static Method):\n";
$searchResults = Product::searchProducts($pdo, 'laptop');
echo "Search results for 'laptop': " . count($searchResults) . " found\n";
foreach ($searchResults as $product) {
    echo "- " . $product['name'] . " (Category: " . $product['category'] . ")\n";
}
echo "\n";

// ===== OOP CONCEPTS DEMONSTRATION =====
echo "4. OOP CONCEPTS DEMONSTRATED\n";
echo "----------------------------\n\n";

echo "✅ ENCAPSULATION:\n";
echo "   - Private properties protect data\n";
echo "   - Private methods hide implementation\n";
echo "   - Example: \$userManager->getId() (controlled access)\n\n";

echo "✅ ABSTRACTION:\n";
echo "   - Simple interfaces hide complexity\n";
echo "   - authenticate() method handles both username/email\n";
echo "   - addStock() simplifies quantity management\n\n";

echo "✅ DATA HIDING:\n";
echo "   - Cannot access \$userManager->id directly\n";
echo "   - Must use getter methods for read access\n";
echo "   - Data modification through controlled methods\n\n";

echo "✅ CONSTRUCTOR (Dependency Injection):\n";
echo "   - Database connection injected in constructor\n";
echo "   - Objects are properly initialized\n\n";

echo "✅ STATIC METHODS:\n";
echo "   - Utility functions at class level\n";
echo "   - No object instance required\n";
echo "   - Example: Product::getAllProducts()\n\n";

echo "✅ STATE MANAGEMENT:\n";
echo "   - Objects maintain internal state\n";
echo "   - State changes through controlled methods\n";
echo "   - Example: addStock() updates internal quantity\n\n";

echo "=== OOP Implementation Complete! ===\n";
?>
