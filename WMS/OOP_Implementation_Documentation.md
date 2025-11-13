# OOP Concepts Implementation in WMS Project
## Object-Oriented Programming Documentation

### Table of Contents
1. [Overview](#overview)
2. [OOP Concepts Applied](#oop-concepts-applied)
3. [User Management System - OOP Implementation](#user-management-system)
4. [Product Management System - OOP Implementation](#product-management-system)
5. [Code Examples](#code-examples)
6. [Benefits and Comparison](#benefits-and-comparison)

---

## Overview

This document outlines the implementation of Object-Oriented Programming (OOP) concepts in the Warehouse Management System (WMS) project. Two main classes have been created to demonstrate core OOP principles:

- **User Class** (`classes/User.php`) - User authentication and management
- **Product Class** (`classes/Product.php`) - Product and inventory management

---

## OOP Concepts Applied

### 1. Encapsulation 🔐
**Definition:** Bundling data and methods together while hiding internal implementation details.

**Implementation Locations:**
- **User Class:** Lines 12-18 (private properties), Lines 56-62 (private methods)
- **Product Class:** Lines 12-22 (private properties), Lines 58-65 (private methods)

**Examples:**
```php
// Private properties (data hiding)
private $id;
private $username;
private $email;

// Private methods (implementation hiding)
private function authenticateByUsername($username, $password) { ... }
private function validateUserData($data) { ... }
```

### 2. Abstraction 🎭
**Definition:** Providing simple interfaces while hiding complex implementation details.

**Implementation Locations:**
- **User Class:** Lines 25-35 (authenticate method), Lines 75-90 (register method)
- **Product Class:** Lines 35-55 (create method), Lines 145-155 (addStock/removeStock)

**Examples:**
```php
// Simple interface hides complex authentication logic
public function authenticate($identifier, $password) {
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        return $this->authenticateByEmail($identifier, $password);
    } else {
        return $this->authenticateByUsername($identifier, $password);
    }
}

// Simple stock management interface
public function addStock($quantity) {
    if (is_numeric($quantity) && $quantity > 0) {
        $newQuantity = $this->quantity + $quantity;
        return $this->updateQuantity($newQuantity);
    }
    return false;
}
```

### 3. Constructor Method 🏗️
**Definition:** Special method called when an object is instantiated.

**Implementation Locations:**
- **User Class:** Lines 20-23
- **Product Class:** Lines 24-27

**Examples:**
```php
// Dependency injection through constructor
public function __construct($pdo) {
    $this->pdo = $pdo;
}
```

### 4. Data Hiding 🔒
**Definition:** Restricting direct access to object data through private properties.

**Implementation Locations:**
- **User Class:** Lines 12-18 (all user data is private)
- **Product Class:** Lines 12-22 (all product data is private)

**Examples:**
```php
// Private properties cannot be accessed directly
private $id;
private $username;
private $password;

// Access only through controlled getter methods
public function getId() { return $this->id; }
public function getUsername() { return $this->username; }
```

### 5. Getter Methods (Controlled Access) 📖
**Definition:** Public methods that provide controlled access to private data.

**Implementation Locations:**
- **User Class:** Lines 145-151
- **Product Class:** Lines 205-215

**Examples:**
```php
// Controlled access to private data
public function getId() { return $this->id; }
public function getUsername() { return $this->username; }
public function getEmail() { return $this->email; }
public function getQuantity() { return $this->quantity; }
```

### 6. Static Methods ⚡
**Definition:** Methods that belong to the class rather than instances.

**Implementation Locations:**
- **Product Class:** Lines 165-190 (getAllProducts, getLowStockProducts, searchProducts)

**Examples:**
```php
// Can be called without creating object instance
public static function getAllProducts($pdo, $limit = null) {
    $sql = "SELECT * FROM products ORDER BY created_at DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Usage: Product::getAllProducts($pdo, 10);
```

### 7. State Management 🔄
**Definition:** Managing object's internal state through controlled methods.

**Implementation Locations:**
- **User Class:** Lines 65-73 (loadUserData method)
- **Product Class:** Lines 66-76 (loadProductData method), Lines 120-140 (updateQuantity method)

**Examples:**
```php
// Internal state management
private function loadUserData($userData) {
    $this->id = $userData['id'];
    $this->username = $userData['username'];
    $this->email = $userData['email'];
    // ... other properties
}

// State modification through controlled methods
public function updateQuantity($newQuantity) {
    if ($this->id && is_numeric($newQuantity) && $newQuantity >= 0) {
        $stmt = $this->pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        $result = $stmt->execute([$newQuantity, $this->id]);
        
        if ($result) {
            $this->quantity = $newQuantity; // Update internal state
        }
        return $result;
    }
    return false;
}
```

---

## User Management System - OOP Implementation

### File Location: `classes/User.php`

### Class Structure:
```
User Class
├── Private Properties (Lines 12-18)
│   ├── $pdo (database connection)
│   ├── $id (user ID)
│   ├── $username (username)
│   ├── $email (email address)
│   ├── $fullName (full name)
│   ├── $role (user role)
│   └── $status (account status)
├── Constructor (Lines 20-23)
├── Public Methods (Lines 25-143)
│   ├── authenticate() - Main authentication
│   ├── register() - User registration
│   ├── findByEmail() - Find user by email
│   ├── updatePassword() - Password updates
│   └── getSessionData() - Session management
├── Private Methods (Lines 37-73)
│   ├── authenticateByUsername() - Username auth
│   ├── authenticateByEmail() - Email auth
│   ├── loadUserData() - Internal data loading
│   ├── validateUserData() - Data validation
│   └── updateLastLogin() - Login tracking
└── Getter Methods (Lines 145-151)
    ├── getId(), getUsername(), getEmail()
    ├── getFullName(), getRole(), getStatus()
    └── getSessionData()
```

### OOP Concepts in User Class:

1. **Encapsulation Examples:**
   - **Line 12-18:** Private properties hide user data
   - **Line 37-55:** Private authentication methods hide implementation
   - **Line 56-62:** Private validation logic encapsulated

2. **Abstraction Examples:**
   - **Line 25-35:** `authenticate()` provides simple interface for complex login logic
   - **Line 75-90:** `register()` hides database complexity
   - **Line 105-115:** `findByEmail()` abstracts database queries

3. **Data Hiding Examples:**
   - **Line 12:** `private $id` - Cannot be accessed directly
   - **Line 14:** `private $username` - Protected from external modification
   - **Line 15:** `private $email` - Secure email storage

---

## Product Management System - OOP Implementation

### File Location: `classes/Product.php`

### Class Structure:
```
Product Class
├── Private Properties (Lines 12-22)
│   ├── $pdo (database connection)
│   ├── $id, $name, $sku (product identifiers)
│   ├── $description, $category, $supplier (product details)
│   ├── $quantity, $price (inventory data)
│   └── $lowStockThreshold, $location (warehouse data)
├── Constructor (Lines 24-27)
├── Public Methods (Lines 29-203)
│   ├── create() - Product creation
│   ├── findById() - Product retrieval
│   ├── update() - Product updates
│   ├── updateQuantity() - Stock management
│   ├── addStock() / removeStock() - Inventory operations
│   └── isLowStock() - Stock analysis
├── Static Methods (Lines 165-195)
│   ├── getAllProducts() - Retrieve all products
│   ├── getLowStockProducts() - Low stock analysis
│   └── searchProducts() - Product search
├── Private Methods (Lines 58-76)
│   ├── validateProductData() - Data validation
│   └── loadProductData() - Internal data management
└── Getter Methods (Lines 205-215)
    └── getId(), getName(), getSku(), getQuantity(), etc.
```

### OOP Concepts in Product Class:

1. **Encapsulation Examples:**
   - **Line 12-22:** Private properties protect product data
   - **Line 58-65:** Private validation method hides business rules
   - **Line 66-76:** Private data loading encapsulated

2. **Static Method Examples:**
   - **Line 165-175:** `getAllProducts()` - Class-level operation
   - **Line 177-187:** `getLowStockProducts()` - Utility function
   - **Line 189-200:** `searchProducts()` - Search functionality

3. **State Management Examples:**
   - **Line 120-135:** `updateQuantity()` manages internal stock state
   - **Line 145-150:** `addStock()` modifies quantity state
   - **Line 155-160:** `removeStock()` validates and updates state

---

## Code Examples

### Example 1: Using User Class
```php
// Create user manager
$userManager = new User($pdo);

// Authenticate user (works with username or email)
$success = $userManager->authenticate('admin@wms.com', 'password123');

if ($success) {
    echo "Login successful!";
    echo "User ID: " . $userManager->getId();
    echo "Username: " . $userManager->getUsername();
    echo "Role: " . $userManager->getRole();
    
    // Get data for session
    $_SESSION['user_data'] = $userManager->getSessionData();
}
```

### Example 2: Using Product Class
```php
// Create product manager
$productManager = new Product($pdo);

// Create new product
$productData = [
    'name' => 'Laptop Computer',
    'sku' => 'LAP-001',
    'description' => 'Business laptop',
    'category' => 'Electronics',
    'supplier' => 'Tech Corp',
    'quantity' => 25,
    'price' => 899.99,
    'location' => 'A1-B2'
];

$success = $productManager->create($productData);

if ($success) {
    echo "Product created with ID: " . $productManager->getId();
    
    // Manage stock
    $productManager->addStock(10);  // Add 10 units
    $productManager->removeStock(5); // Remove 5 units
    
    // Check stock status
    if ($productManager->isLowStock()) {
        echo "Warning: Low stock alert!";
    }
}

// Static methods (no object needed)
$allProducts = Product::getAllProducts($pdo, 10);
$lowStockItems = Product::getLowStockProducts($pdo);
$searchResults = Product::searchProducts($pdo, 'laptop');
```

---

## Benefits and Comparison

### OOP vs Procedural Comparison

| Aspect | Procedural (Current) | OOP (New Implementation) |
|--------|---------------------|-------------------------|
| **Code Organization** | Functions in separate files | Grouped into logical classes |
| **Data Security** | Global variables accessible | Private properties protected |
| **Code Reuse** | Copy/paste functions | Inherit and extend classes |
| **Maintenance** | Change multiple files | Change one class |
| **Testing** | Test individual functions | Test complete objects |
| **Scalability** | Hard to extend | Easy to add features |

### Benefits of OOP Implementation:

1. **Security** 🔒
   - Private properties prevent unauthorized access
   - Controlled data modification through methods
   - Input validation encapsulated within classes

2. **Maintainability** 🔧
   - All user logic in one User class
   - All product logic in one Product class
   - Easy to find and fix issues

3. **Reusability** ♻️
   - Classes can be used across multiple files
   - Inheritance allows extending functionality
   - No code duplication

4. **Scalability** 📈
   - Easy to add new methods
   - Simple to extend with new properties
   - Clean structure for team development

5. **Testing** ✅
   - Each class can be tested independently
   - Mock objects for unit testing
   - Clear separation of concerns

### When to Use Each Approach:

**Use OOP When:**
- Building complex features
- Need data security
- Working in teams
- Planning to scale the application
- Want to add new functionality frequently

**Keep Procedural When:**
- Simple operations
- Quick scripts
- Learning/prototyping
- Small applications
- Current system works well

---

## Conclusion

The OOP implementation in the WMS project demonstrates key object-oriented programming concepts while maintaining compatibility with the existing procedural code. This hybrid approach allows for gradual migration and learning while ensuring system stability.

### Files Created:
1. `classes/User.php` - User management with OOP
2. `classes/Product.php` - Product management with OOP
3. `example_oop_usage.php` - Usage examples

### Next Steps:
1. Test the OOP classes with existing data
2. Gradually migrate features to use OOP approach
3. Extend classes with additional functionality as needed

---

*Document generated on August 12, 2025*
*WMS Project - OOP Implementation Documentation*
