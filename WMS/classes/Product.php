<?php
/**
 * Product Class - Object-Oriented Product Management
 * Demonstrates OOP concepts: Encapsulation, Static Methods, State Management
 */

class Product {
    
    // ENCAPSULATION: Private properties hide internal data
    private $pdo;                   // Database connection
    private $id;                    // Product ID
    private $name;                  // Product name
    private $sku;                   // Stock Keeping Unit
    private $description;           // Product description
    private $category;              // Product category
    private $supplier;              // Supplier information
    private $quantity;              // Current stock quantity
    private $price;                 // Product price
    private $lowStockThreshold;     // Minimum stock level
    private $location;              // Warehouse location
    
    // CONSTRUCTOR: Dependency injection
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // ABSTRACTION: Simple interface for product creation
    public function create($productData) {
        if (!$this->validateProductData($productData)) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO products (name, sku, description, category, quantity, price, min_stock_level, location) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $productData['name'],
                $productData['sku'],
                $productData['description'] ?? '',
                $productData['category'],
                $productData['quantity'],
                $productData['price'],
                $productData['min_stock_level'] ?? 10,
                $productData['location'] ?? ''
            ]);
            
            if ($result) {
                $this->id = $this->pdo->lastInsertId();
                $this->loadProductData($productData);
            }
            
            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // ENCAPSULATION: Private validation method
    private function validateProductData($data) {
        return isset($data['name']) && 
               isset($data['sku']) && 
               isset($data['category']) && 
               isset($data['quantity']) && 
               isset($data['price']) &&
               is_numeric($data['quantity']) &&
               is_numeric($data['price']);
    }
    
    // STATE MANAGEMENT: Load product data into object state
    private function loadProductData($productData) {
        if (isset($productData['id'])) $this->id = $productData['id'];
        $this->name = $productData['name'];
        $this->sku = $productData['sku'];
        $this->description = $productData['description'] ?? '';
        $this->category = $productData['category'];
        $this->supplier = $productData['supplier'] ?? '';
        $this->quantity = $productData['quantity'];
        $this->price = $productData['price'];
        $this->lowStockThreshold = $productData['min_stock_level'] ?? 10;
        $this->location = $productData['location'] ?? '';
    }
    
    // ABSTRACTION: Find product by ID
    public function findById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            if ($product) {
                $this->loadProductData($product);
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // ABSTRACTION: Update product information
    public function update($productData) {
        if (!$this->id || !$this->validateProductData($productData)) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE products 
                SET name = ?, sku = ?, description = ?, category = ?, 
                    quantity = ?, price = ?, min_stock_level = ?, location = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $productData['name'],
                $productData['sku'],
                $productData['description'] ?? '',
                $productData['category'],
                $productData['quantity'],
                $productData['price'],
                $productData['min_stock_level'] ?? 10,
                $productData['location'] ?? '',
                $this->id
            ]);
            
            if ($result) {
                $this->loadProductData($productData);
            }
            
            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // STATE MANAGEMENT: Update quantity with validation
    public function updateQuantity($newQuantity) {
        if (!$this->id || !is_numeric($newQuantity) || $newQuantity < 0) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $result = $stmt->execute([$newQuantity, $this->id]);
            
            if ($result) {
                $this->quantity = $newQuantity; // Update internal state
            }
            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // ABSTRACTION: Add stock with validation
    public function addStock($quantity) {
        if (!is_numeric($quantity) || $quantity <= 0) {
            return false;
        }
        
        $newQuantity = $this->quantity + $quantity;
        return $this->updateQuantity($newQuantity);
    }
    
    // ABSTRACTION: Remove stock with validation
    public function removeStock($quantity) {
        if (!is_numeric($quantity) || $quantity <= 0 || $quantity > $this->quantity) {
            return false;
        }
        
        $newQuantity = $this->quantity - $quantity;
        return $this->updateQuantity($newQuantity);
    }
    
    // ABSTRACTION: Check if stock is low
    public function isLowStock() {
        return $this->quantity <= $this->lowStockThreshold;
    }
    
    // STATIC METHODS: Class-level operations (no object instance needed)
    public static function getAllProducts($pdo, $limit = null) {
        try {
            $sql = "SELECT * FROM products ORDER BY created_at DESC";
            if ($limit) {
                $sql .= " LIMIT " . intval($limit);
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // STATIC METHODS: Get products with low stock
    public static function getLowStockProducts($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE quantity <= min_stock_level ORDER BY quantity ASC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // STATIC METHODS: Search products
    public static function searchProducts($pdo, $searchTerm) {
        try {
            $searchTerm = "%{$searchTerm}%";
            $stmt = $pdo->prepare("
                SELECT * FROM products 
                WHERE name LIKE ? OR sku LIKE ? OR category LIKE ? OR description LIKE ?
                ORDER BY name ASC
            ");
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // GETTER METHODS: Controlled access to private data
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getSku() { return $this->sku; }
    public function getDescription() { return $this->description; }
    public function getCategory() { return $this->category; }
    public function getSupplier() { return $this->supplier; }
    public function getQuantity() { return $this->quantity; }
    public function getPrice() { return $this->price; }
    public function getLowStockThreshold() { return $this->lowStockThreshold; }
    public function getLocation() { return $this->location; }
}
?>
