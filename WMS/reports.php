<?php
/**
 * Reports Page
 * Displays various warehouse reports and analytics
 */

// Check authentication
require_once 'includes/auth.php';

require_once 'config/database.php';
require_once 'includes/functions.php';

// Get report data
try {
    // Basic statistics
    $stats = getDashboardStats($pdo);
    
    // Category-wise breakdown
    $stmt = $pdo->query("
        SELECT 
            category,
            COUNT(*) as product_count,
            SUM(quantity) as total_quantity,
            SUM(quantity * price) as total_value,
            AVG(price) as avg_price
        FROM products 
        GROUP BY category 
        ORDER BY total_value DESC
    ");
    $categoryStats = $stmt->fetchAll();
    
    // Low stock report
    $lowStockProducts = getLowStockProducts($pdo, 10);
    
    // High value products
    $stmt = $pdo->prepare("
        SELECT name, sku, category, quantity, price, (quantity * price) as total_value
        FROM products 
        ORDER BY total_value DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $highValueProducts = $stmt->fetchAll();
    
    // Recent activity (products added in last 30 days)
    $stmt = $pdo->query("
        SELECT name, sku, category, quantity, price, created_at
        FROM products 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY created_at DESC
    ");
    $recentActivity = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage("Error generating reports: " . $e->getMessage(), "error");
    $categoryStats = [];
    $lowStockProducts = [];
    $highValueProducts = [];
    $recentActivity = [];
}

$title = 'Reports - Warehouse Management System';
$currentPage = 'reports';
includeHeader($title, $currentPage);
?>

<div class="container">
    <h1 class="page-title">📊 Warehouse Reports & Analytics</h1>
    
    <!-- Summary Statistics -->
    <div class="card">
        <h2 style="color: #2c3e50; margin-bottom: 1.5rem;">📈 Summary Statistics</h2>
        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="number"><?php echo formatNumber($stats['total_products']); ?></div>
                <div class="label">Unique items</div>
            </div>
            
            <div class="stat-card">
                <h3>Total Inventory Value</h3>
                <div class="number"><?php echo formatCurrency($stats['total_value']); ?></div>
                <div class="label">Current worth</div>
            </div>
            
            <div class="stat-card">
                <h3>Items in Stock</h3>
                <div class="number"><?php echo formatNumber($stats['total_quantity']); ?></div>
                <div class="label">Total quantity</div>
            </div>
            
            <div class="stat-card">
                <h3>Product Categories</h3>
                <div class="number"><?php echo formatNumber($stats['total_categories']); ?></div>
                <div class="label">Different categories</div>
            </div>
            
            <div class="stat-card">
                <h3>Low Stock Alerts</h3>
                <div class="number" style="color: <?php echo $stats['low_stock_items'] > 0 ? '#e74c3c' : '#27ae60'; ?>">
                    <?php echo formatNumber($stats['low_stock_items']); ?>
                </div>
                <div class="label">Need attention</div>
            </div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
        <!-- Category Breakdown -->
        <div class="card">
            <h2 style="color: #3498db; margin-bottom: 1.5rem;">📊 Category Breakdown</h2>
            
            <?php if (empty($categoryStats)): ?>
                <p>No category data available.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Products</th>
                                <th>Total Qty</th>
                                <th>Total Value</th>
                                <th>Avg Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryStats as $category): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($category['category']); ?></strong></td>
                                    <td><?php echo formatNumber($category['product_count']); ?></td>
                                    <td><?php echo formatNumber($category['total_quantity']); ?></td>
                                    <td><?php echo formatCurrency($category['total_value']); ?></td>
                                    <td><?php echo formatCurrency($category['avg_price']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Low Stock Alert -->
        <div class="card">
            <h2 style="color: #e74c3c; margin-bottom: 1.5rem;">⚠️ Low Stock Report</h2>
            
            <?php if (empty($lowStockProducts)): ?>
                <div style="text-align: center; padding: 2rem; color: #27ae60;">
                    <h3>✅ All Good!</h3>
                    <p>No products are running low on stock.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Current</th>
                                <th>Min Level</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockProducts as $product): ?>
                                <tr class="low-stock">
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                    <td><?php echo formatNumber($product['quantity']); ?></td>
                                    <td><?php echo formatNumber($product['min_stock_level']); ?></td>
                                    <td>
                                        <?php if ($product['quantity'] == 0): ?>
                                            <span style="color: #e74c3c; font-weight: bold;">OUT OF STOCK</span>
                                        <?php else: ?>
                                            <span style="color: #f39c12; font-weight: bold;">LOW STOCK</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 1rem;">
                    <a href="inventory.php?filter=low_stock" class="btn btn-warning">View in Inventory</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- High Value Products -->
    <div class="card" style="margin-top: 2rem;">
        <h2 style="color: #27ae60; margin-bottom: 1.5rem;">💰 Top 10 High Value Products</h2>
        
        <?php if (empty($highValueProducts)): ?>
            <p>No product data available.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Product Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($highValueProducts as $index => $product): ?>
                            <tr>
                                <td><strong><?php echo $index + 1; ?></strong></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo formatNumber($product['quantity']); ?></td>
                                <td><?php echo formatCurrency($product['price']); ?></td>
                                <td><strong><?php echo formatCurrency($product['total_value']); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Activity -->
    <div class="card" style="margin-top: 2rem;">
        <h2 style="color: #9b59b6; margin-bottom: 1.5rem;">🕒 Recent Activity (Last 30 Days)</h2>
        
        <?php if (empty($recentActivity)): ?>
            <p>No recent activity found.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentActivity as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo formatNumber($product['quantity']); ?></td>
                                <td><?php echo formatCurrency($product['price']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($product['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Report Actions -->
    <div class="card" style="margin-top: 2rem;">
        <h2 style="color: #2c3e50; margin-bottom: 1.5rem;">📋 Report Actions</h2>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print Report</button>
            <a href="inventory.php" class="btn btn-success">📦 View Full Inventory</a>
            <a href="inventory.php?filter=low_stock" class="btn btn-warning">⚠️ Low Stock Items</a>
            <a href="product_form.php" class="btn btn-success">➕ Add New Product</a>
        </div>
        
        <div style="margin-top: 1rem; padding: 1rem; background-color: #f8f9fa; border-radius: 5px;">
            <h4>📊 Key Insights:</h4>
            <ul style="margin: 0.5rem 0 0 1rem; color: #666;">
                <li><strong>Inventory Health:</strong> 
                    <?php if ($stats['low_stock_items'] == 0): ?>
                        <span style="color: #27ae60;">Excellent - No low stock issues</span>
                    <?php elseif ($stats['low_stock_items'] <= 5): ?>
                        <span style="color: #f39c12;">Good - Few items need restocking</span>
                    <?php else: ?>
                        <span style="color: #e74c3c;">Attention needed - Multiple items low on stock</span>
                    <?php endif; ?>
                </li>
                <li><strong>Portfolio Diversity:</strong> <?php echo $stats['total_categories']; ?> different product categories</li>
                <li><strong>Average Investment per Product:</strong> 
                    <?php echo $stats['total_products'] > 0 ? formatCurrency($stats['total_value'] / $stats['total_products']) : '$0.00'; ?>
                </li>
                <li><strong>Report Generated:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></li>
            </ul>
        </div>
    </div>
</div>

<style>
/* Print styles */
@media print {
    header, nav, .btn, footer {
        display: none !important;
    }
    
    .container {
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        page-break-inside: avoid;
    }
    
    .page-title {
        text-align: center;
        border-bottom: 2px solid #333;
        padding-bottom: 1rem;
    }
    
    table {
        font-size: 0.8rem;
    }
    
    .dashboard-grid {
        grid-template-columns: repeat(5, 1fr) !important;
    }
    
    .stat-card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
}
</style>

<?php includeFooter(); ?>
