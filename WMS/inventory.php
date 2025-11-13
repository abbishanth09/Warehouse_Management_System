<?php
/**
 * Inventory Display Page
 * Shows all products with search and filter functionality
 */

// Check authentication
require_once 'includes/auth.php';

require_once 'config/database.php';
require_once 'includes/functions.php';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        setFlashMessage("Product deleted successfully!", "success");
        redirect("inventory.php");
    } catch (PDOException $e) {
        setFlashMessage("Error deleting product: " . $e->getMessage(), "error");
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$lowStockFilter = isset($_GET['filter']) && $_GET['filter'] === 'low_stock';

// Build query with filters
$sql = "SELECT * FROM products WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR sku LIKE ? OR description LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

if ($lowStockFilter) {
    $sql .= " AND quantity <= min_stock_level";
}

$sql .= " ORDER BY name ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    setFlashMessage("Error loading products: " . $e->getMessage(), "error");
}

// Get categories for filter dropdown
$categories = getCategories($pdo);

$title = 'Inventory - Warehouse Management System';
$currentPage = 'inventory';
includeHeader($title, $currentPage);
?>

<div class="container">
    <h1 class="page-title">📦 Inventory Management</h1>
    
    <!-- Search and Filter Section -->
    <div class="card">
        <form method="GET" action="inventory.php" class="search-filter">
            <div class="search-box">
                <input type="text" 
                       id="searchInput" 
                       name="search" 
                       placeholder="Search by name, SKU, or description..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <select name="category" id="categoryFilter">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                            <?php echo $category === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-primary">🔍 Search</button>
            <a href="inventory.php" class="btn btn-warning">🔄 Clear</a>
            <a href="inventory.php?filter=low_stock" class="btn btn-danger">⚠️ Low Stock</a>
        </form>
        
        <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <span id="searchResults">Showing <?php echo count($products); ?> items</span>
            <a href="product_form.php" class="btn btn-success">➕ Add New Product</a>
        </div>
    </div>
    
    <!-- Products Table -->
    <div class="card">
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 3rem;">
                <h3>No products found</h3>
                <p>No products match your search criteria.</p>
                <a href="product_form.php" class="btn btn-success">Add Your First Product</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table id="productTable">
                    <thead>
                        <tr>
                            <th data-sort="name">Product Name</th>
                            <th data-sort="sku">SKU</th>
                            <th data-sort="category">Category</th>
                            <th data-sort="quantity">Quantity</th>
                            <th data-sort="price">Price</th>
                            <th data-sort="min_stock_level">Min Stock</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr <?php echo $product['quantity'] <= $product['min_stock_level'] ? 'class="low-stock"' : ''; ?>>
                                <td data-field="name">
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <?php if (!empty($product['description'])): ?>
                                        <br><small style="color: #666;"><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td data-field="sku"><?php echo htmlspecialchars($product['sku']); ?></td>
                                <td data-field="category"><?php echo htmlspecialchars($product['category']); ?></td>
                                <td data-field="quantity">
                                    <?php echo formatNumber($product['quantity']); ?>
                                    <?php if ($product['quantity'] <= $product['min_stock_level']): ?>
                                        <span style="color: #e74c3c; font-weight: bold;"> ⚠️</span>
                                    <?php endif; ?>
                                </td>
                                <td data-field="price"><?php echo formatCurrency($product['price']); ?></td>
                                <td data-field="min_stock_level"><?php echo formatNumber($product['min_stock_level']); ?></td>
                                <td><?php echo htmlspecialchars($product['location'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="product_form.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-warning btn-small">✏️ Edit</a>
                                    <a href="inventory.php?action=delete&id=<?php echo $product['id']; ?>" 
                                       class="btn btn-danger btn-small"
                                       onclick="return confirmDelete('<?php echo htmlspecialchars($product['name']); ?>')">🗑️ Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary Information -->
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e0e0e0;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <strong>Total Products:</strong> <?php echo formatNumber(count($products)); ?>
                    </div>
                    <div>
                        <strong>Total Quantity:</strong> 
                        <?php echo formatNumber(array_sum(array_column($products, 'quantity'))); ?>
                    </div>
                    <div>
                        <strong>Total Value:</strong> 
                        <?php 
                        $totalValue = 0;
                        foreach ($products as $product) {
                            $totalValue += $product['quantity'] * $product['price'];
                        }
                        echo formatCurrency($totalValue);
                        ?>
                    </div>
                    <div>
                        <strong>Low Stock Items:</strong> 
                        <span style="color: #e74c3c;">
                            <?php 
                            $lowStockCount = 0;
                            foreach ($products as $product) {
                                if ($product['quantity'] <= $product['min_stock_level']) {
                                    $lowStockCount++;
                                }
                            }
                            echo formatNumber($lowStockCount);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Initialize search functionality when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Highlight low stock items
    highlightLowStock();
    
    // Real-time search functionality
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            performSearch();
        });
    }
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            performSearch();
        });
    }
});
</script>

<?php includeFooter(); ?>
