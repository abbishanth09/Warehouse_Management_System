<?php
/**
 * Product Form Page - Add/Edit Products
 * Handles both adding new products and editing existing ones
 */

// Check authentication
require_once 'includes/auth.php';

require_once 'config/database.php';
require_once 'includes/functions.php';

$errors = [];
$product = [
    'name' => '',
    'sku' => '',
    'category' => '',
    'quantity' => '',
    'price' => '',
    'min_stock_level' => '10',
    'location' => '',
    'description' => ''
];

$isEdit = false;
$productId = null;

// Check if editing existing product
if (isset($_GET['id'])) {
    $productId = (int)$_GET['id'];
    $isEdit = true;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $existingProduct = $stmt->fetch();
        
        if ($existingProduct) {
            $product = $existingProduct;
        } else {
            setFlashMessage("Product not found!", "error");
            redirect("inventory.php");
        }
    } catch (PDOException $e) {
        setFlashMessage("Error loading product: " . $e->getMessage(), "error");
        redirect("inventory.php");
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $name = sanitizeInput($_POST['name'] ?? '');
    $sku = sanitizeInput($_POST['sku'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $minStockLevel = (int)($_POST['min_stock_level'] ?? 10);
    $location = sanitizeInput($_POST['location'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    // Validate required fields
    $requiredFields = [
        'name' => $name,
        'sku' => $sku,
        'category' => $category,
        'quantity' => $quantity,
        'price' => $price
    ];
    
    $errors = array_merge($errors, validateRequired($requiredFields));
    
    // Additional validations
    if (!validateSKU($sku)) {
        $errors[] = "SKU must be alphanumeric and 3-20 characters long";
    }
    
    if (!validatePrice($price)) {
        $errors[] = "Price must be a positive number";
    }
    
    if (!validateQuantity($quantity)) {
        $errors[] = "Quantity must be a non-negative number";
    }
    
    if ($minStockLevel < 0) {
        $errors[] = "Minimum stock level must be non-negative";
    }
    
    // Check SKU uniqueness
    if (skuExists($pdo, $sku, $productId)) {
        $errors[] = "SKU already exists. Please choose a different SKU.";
    }
    
    // If no errors, save the product
    if (empty($errors)) {
        try {
            if ($isEdit) {
                // Update existing product
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET name = ?, sku = ?, category = ?, quantity = ?, price = ?, 
                        min_stock_level = ?, location = ?, description = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $sku, $category, $quantity, $price, 
                    $minStockLevel, $location, $description, $productId
                ]);
                setFlashMessage("Product updated successfully!", "success");
            } else {
                // Insert new product
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, sku, category, quantity, price, min_stock_level, location, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name, $sku, $category, $quantity, $price, 
                    $minStockLevel, $location, $description
                ]);
                setFlashMessage("Product added successfully!", "success");
            }
            
            redirect("inventory.php");
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Get existing categories for dropdown
$categories = getCategories($pdo);

$title = ($isEdit ? 'Edit Product' : 'Add New Product') . ' - Warehouse Management System';
$currentPage = 'add_product';
includeHeader($title, $currentPage);
?>

<div class="container">
    <h1 class="page-title">
        <?php echo $isEdit ? '✏️ Edit Product' : '➕ Add New Product'; ?>
    </h1>
    
    <div class="card">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 0.5rem 0 0 1rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" data-validate="true">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Left Column -->
                <div>
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="<?php echo htmlspecialchars($product['name']); ?>" 
                               required
                               placeholder="Enter product name">
                    </div>
                    
                    <div class="form-group">
                        <label for="sku">SKU (Stock Keeping Unit) *</label>
                        <input type="text" 
                               id="sku" 
                               name="sku" 
                               value="<?php echo htmlspecialchars($product['sku']); ?>" 
                               required
                               placeholder="e.g., LT001, ABC123"
                               pattern="[A-Za-z0-9]{3,20}"
                               title="SKU must be 3-20 alphanumeric characters">
                        <small style="color: #666;">3-20 alphanumeric characters. Leave empty to auto-generate.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo $product['category'] === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="Electronics" <?php echo $product['category'] === 'Electronics' ? 'selected' : ''; ?>>Electronics</option>
                            <option value="Furniture" <?php echo $product['category'] === 'Furniture' ? 'selected' : ''; ?>>Furniture</option>
                            <option value="Office Supplies" <?php echo $product['category'] === 'Office Supplies' ? 'selected' : ''; ?>>Office Supplies</option>
                            <option value="Tools" <?php echo $product['category'] === 'Tools' ? 'selected' : ''; ?>>Tools</option>
                            <option value="Clothing" <?php echo $product['category'] === 'Clothing' ? 'selected' : ''; ?>>Clothing</option>
                            <option value="Books" <?php echo $product['category'] === 'Books' ? 'selected' : ''; ?>>Books</option>
                            <option value="Other" <?php echo $product['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Storage Location</label>
                        <input type="text" 
                               id="location" 
                               name="location" 
                               value="<?php echo htmlspecialchars($product['location']); ?>" 
                               placeholder="e.g., A-1-01, Warehouse B, Shelf 3">
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <div class="form-group">
                        <label for="quantity">Current Quantity *</label>
                        <input type="number" 
                               id="quantity" 
                               name="quantity" 
                               value="<?php echo htmlspecialchars($product['quantity']); ?>" 
                               required
                               min="0"
                               placeholder="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Unit Price (LKR) *</label>
                        <input type="number" 
                               id="price" 
                               name="price" 
                               value="<?php echo htmlspecialchars($product['price']); ?>" 
                               required
                               min="1"
                               step="0.01"
                               placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="min_stock_level">Minimum Stock Level *</label>
                        <input type="number" 
                               id="min_stock_level" 
                               name="min_stock_level" 
                               value="<?php echo htmlspecialchars($product['min_stock_level']); ?>" 
                               required
                               min="0"
                               placeholder="10">
                        <small style="color: #666;">Alert will be shown when quantity reaches this level.</small>
                    </div>
                </div>
            </div>
            
            <!-- Description - Full Width -->
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" 
                          name="description" 
                          rows="4"
                          placeholder="Enter product description, specifications, or notes..."><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            
            <!-- Form Buttons -->
            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                <button type="submit" class="btn btn-success">
                    <?php echo $isEdit ? '💾 Update Product' : '➕ Add Product'; ?>
                </button>
                <a href="inventory.php" class="btn btn-warning">↩️ Back to Inventory</a>
                
                <?php if ($isEdit): ?>
                    <a href="inventory.php?action=delete&id=<?php echo $productId; ?>" 
                       class="btn btn-danger"
                       onclick="return confirmDelete('<?php echo htmlspecialchars($product['name']); ?>')"
                       style="float: right;">
                        🗑️ Delete Product
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Help Section -->
    <div class="card">
        <h3>📋 Form Guidelines</h3>
        <ul style="color: #666; line-height: 1.8;">
            <li><strong>Product Name:</strong> Use descriptive names that clearly identify the product</li>
            <li><strong>SKU:</strong> Must be unique, 3-20 alphanumeric characters. Auto-generated if left empty</li>
            <li><strong>Category:</strong> Choose existing category or select from common options</li>
            <li><strong>Quantity:</strong> Current number of items in stock</li>
            <li><strong>Price:</strong> Unit price in LKR </li>
            <li><strong>Min Stock Level:</strong> When to show low stock warnings</li>
            <li><strong>Location:</strong> Where the product is stored (optional but recommended)</li>
        </ul>
    </div>
</div>

<script>
// Auto-generate SKU if name and category are provided
document.addEventListener('DOMContentLoaded', function() {
    const nameField = document.querySelector('input[name="name"]');
    const categoryField = document.querySelector('select[name="category"]');
    const skuField = document.querySelector('input[name="sku"]');
    
    function autoGenerateSKU() {
        // Only auto-generate for new products (not editing)
        <?php if (!$isEdit): ?>
        if (nameField.value && categoryField.value && !skuField.value) {
            generateSKU();
        }
        <?php endif; ?>
    }
    
    if (nameField && categoryField) {
        nameField.addEventListener('blur', autoGenerateSKU);
        categoryField.addEventListener('change', autoGenerateSKU);
    }
});
</script>

<?php includeFooter(); ?>
