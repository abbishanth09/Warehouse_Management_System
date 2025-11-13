<?php
/**
 * Home Page - Dashboard
 * Displays overview statistics and recent activity
 */

// Check authentication
require_once 'includes/auth.php';

require_once 'config/database.php';
require_once 'includes/functions.php';

// Get dashboard statistics
$stats = getDashboardStats($pdo);
$lowStockProducts = getLowStockProducts($pdo, 10); // Get more products to ensure we catch all low stock
$recentProducts = getRecentProducts($pdo);

$title = 'Dashboard - Warehouse Management System';
$currentPage = 'home';
includeHeader($title, $currentPage);
?>

<div class="container">
    <h1 class="page-title">📊 Dashboard</h1>
    
    <!-- Statistics Cards -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <h3>Total Products</h3>
            <div class="number"><?php echo formatNumber($stats['total_products']); ?></div>
            <div class="label">Items in inventory</div>
        </div>
        
        <div class="stat-card">
            <h3>Total Value</h3>
            <div class="number"><?php echo formatCurrencyCompact($stats['total_value']); ?></div>
            <div class="label">Inventory worth</div>
        </div>
        
        <div class="stat-card">
            <h3>Low Stock Items</h3>
            <div class="number" style="color: <?php echo $stats['low_stock_items'] > 0 ? '#e74c3c' : '#27ae60'; ?>">
                <?php echo formatNumber($stats['low_stock_items']); ?>
            </div>
            <div class="label">Need restocking</div>
        </div>
        
        <div class="stat-card" style="border-left-color: #9b59b6;">
            <h3>Total Orders</h3>
            <div class="number" style="color: #9b59b6;"><?php echo formatNumber($stats['total_orders']); ?></div>
            <div class="label">All orders</div>
        </div>
        
        <div class="stat-card" style="border-left-color: #f39c12;">
            <h3>Pending Orders</h3>
            <div class="number" style="color: <?php echo $stats['pending_orders'] > 0 ? '#f39c12' : '#27ae60'; ?>">
                <?php echo formatNumber($stats['pending_orders']); ?>
            </div>
            <div class="label">Need attention</div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
        <!-- Low Stock Alert -->
        <div class="card">
            <h2 style="color: #e74c3c; margin-bottom: 1.5rem;">⚠️ Low Stock Alert</h2>
            
            <?php if ($stats['low_stock_items'] == 0 || empty($lowStockProducts)): ?>
                <p style="color: #27ae60; font-weight: 500;">✅ All products are adequately stocked!</p>
            <?php else: ?>
                <p style="color: #e74c3c; font-weight: 500; margin-bottom: 1rem;">
                    ⚠️ Found <?php echo $stats['low_stock_items']; ?> product(s) below minimum stock level:
                </p>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Current</th>
                                <th>Min Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockProducts as $product): ?>
                                <tr class="low-stock">
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                    <td style="color: #e74c3c; font-weight: bold;"><?php echo formatNumber($product['quantity']); ?></td>
                                    <td><?php echo formatNumber($product['min_stock_level']); ?></td>
                                    <td>
                                        <?php if ($product['quantity'] == 0): ?>
                                            <span style="color: #c0392b; font-weight: bold;">OUT OF STOCK</span>
                                        <?php else: ?>
                                            <span style="color: #e74c3c; font-weight: bold;">LOW STOCK</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 1rem;">
                    <a href="inventory.php?filter=low_stock" class="btn btn-danger">⚠️ View Low Stock Items</a>
                    <a href="inventory.php" class="btn btn-warning">📦 View All Inventory</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Products -->
        <div class="card">
            <h2 style="color: #3498db; margin-bottom: 1.5rem;">📦 Recent Products</h2>
            
            <?php if (empty($recentProducts)): ?>
                <p>No products found. <a href="product_form.php">Add your first product</a>.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td><?php echo formatNumber($product['quantity']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 1rem;">
                    <a href="inventory.php" class="btn btn-primary">View All Products</a>
                    <a href="product_form.php" class="btn btn-success">Add New Product</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card" style="margin-top: 2rem;">
        <h2 style="color: #2c3e50; margin-bottom: 1.5rem;">🚀 Quick Actions</h2>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="product_form.php" class="btn btn-success">➕ Add New Product</a>
            <a href="inventory.php" class="btn btn-primary">📦 View Inventory</a>
            <a href="reports.php" class="btn btn-warning">📊 Generate Reports</a>
            <a href="inventory.php?filter=low_stock" class="btn btn-danger">⚠️ Check Low Stock</a>
        </div>
    </div>
</div>

<?php includeFooter(); ?>
