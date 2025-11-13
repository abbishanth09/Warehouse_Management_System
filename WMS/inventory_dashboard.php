<?php
/**
 * Inventory Dashboard - View stock levels and transaction history
 */

require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/inventory_functions.php';

$title = 'Inventory Dashboard - Warehouse Management System';
$currentPage = 'inventory';

// Get all products with current stock
try {
    $productsStmt = $pdo->prepare("
        SELECT id, name, sku, category, quantity, min_stock_level, price, location,
               CASE 
                   WHEN quantity <= min_stock_level THEN 'low'
                   WHEN quantity <= min_stock_level * 2 THEN 'warning'
                   ELSE 'normal'
               END as stock_status
        FROM products 
        ORDER BY quantity ASC, name ASC
    ");
    $productsStmt->execute();
    $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
    setFlashMessage("Error loading products: " . $e->getMessage(), "error");
}

// Get recent inventory transactions
try {
    $transactionsStmt = $pdo->prepare("
        SELECT it.*, p.name as product_name, p.sku, o.order_number
        FROM inventory_transactions it
        LEFT JOIN products p ON it.product_id = p.id
        LEFT JOIN orders o ON it.order_id = o.id
        ORDER BY it.created_at DESC
        LIMIT 20
    ");
    $transactionsStmt->execute();
    $recentTransactions = $transactionsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentTransactions = [];
}

// Get low stock alerts
$lowStockAlerts = getLowStockAlerts($pdo);

includeHeader($title, $currentPage);
?>

<div class="container">
    <h1 class="page-title">📦 Inventory Dashboard</h1>
    
    <!-- Stock Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="card" style="text-align: center; background: linear-gradient(135deg, #27ae60, #2ecc71);">
            <h3 style="color: white; margin: 0;">Total Products</h3>
            <p style="color: white; font-size: 2rem; margin: 0.5rem 0;"><?php echo count($products); ?></p>
        </div>
        <div class="card" style="text-align: center; background: linear-gradient(135deg, #e74c3c, #c0392b);">
            <h3 style="color: white; margin: 0;">Low Stock Items</h3>
            <p style="color: white; font-size: 2rem; margin: 0.5rem 0;"><?php echo count($lowStockAlerts); ?></p>
        </div>
        <div class="card" style="text-align: center; background: linear-gradient(135deg, #3498db, #2980b9);">
            <h3 style="color: white; margin: 0;">Recent Transactions</h3>
            <p style="color: white; font-size: 2rem; margin: 0.5rem 0;"><?php echo count($recentTransactions); ?></p>
        </div>
    </div>

    <?php if (!empty($lowStockAlerts)): ?>
    <!-- Low Stock Alerts -->
    <div class="card">
        <h2 style="color: #e74c3c;">⚠️ Low Stock Alerts</h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Current Stock</th>
                        <th>Min. Level</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStockAlerts as $alert): ?>
                    <tr style="background-color: #ffebee;">
                        <td><?php echo htmlspecialchars($alert['name']); ?></td>
                        <td><?php echo htmlspecialchars($alert['sku']); ?></td>
                        <td style="color: #e74c3c; font-weight: bold;"><?php echo $alert['quantity']; ?></td>
                        <td><?php echo $alert['min_stock_level']; ?></td>
                        <td><?php echo htmlspecialchars($alert['category']); ?></td>
                        <td><?php echo htmlspecialchars($alert['location']); ?></td>
                        <td>
                            <a href="product_form.php?id=<?php echo $alert['id']; ?>" class="btn btn-warning btn-sm">🔄 Reorder</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Current Inventory -->
    <div class="card">
        <h2>📋 Current Inventory</h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Stock Level</th>
                        <th>Status</th>
                        <th>Min. Level</th>
                        <th>Unit Price</th>
                        <th>Total Value</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['sku']); ?></td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td style="font-weight: bold; <?php 
                            echo $product['stock_status'] === 'low' ? 'color: #e74c3c;' : 
                                ($product['stock_status'] === 'warning' ? 'color: #f39c12;' : 'color: #27ae60;'); 
                        ?>">
                            <?php echo $product['quantity']; ?>
                        </td>
                        <td>
                            <?php if ($product['stock_status'] === 'low'): ?>
                                <span style="background: #e74c3c; color: white; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.8rem;">🔴 LOW</span>
                            <?php elseif ($product['stock_status'] === 'warning'): ?>
                                <span style="background: #f39c12; color: white; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.8rem;">🟡 WARNING</span>
                            <?php else: ?>
                                <span style="background: #27ae60; color: white; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.8rem;">🟢 GOOD</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $product['min_stock_level']; ?></td>
                        <td>Rs. <?php echo number_format($product['price'], 2); ?></td>
                        <td>Rs. <?php echo number_format($product['quantity'] * $product['price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($product['location']); ?></td>
                        <td>
                            <a href="inventory_history.php?product_id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">📊 History</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Transactions -->
    <?php if (!empty($recentTransactions)): ?>
    <div class="card">
        <h2>🔄 Recent Inventory Transactions</h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Quantity Change</th>
                        <th>Previous Qty</th>
                        <th>New Qty</th>
                        <th>Reference</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $trans): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($trans['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($trans['product_name']); ?></td>
                        <td>
                            <?php if ($trans['transaction_type'] === 'inbound'): ?>
                                <span style="color: #27ae60;">📥 Inbound</span>
                            <?php elseif ($trans['transaction_type'] === 'outbound'): ?>
                                <span style="color: #e74c3c;">📤 Outbound</span>
                            <?php else: ?>
                                <span style="color: #3498db;">🔧 <?php echo ucfirst($trans['transaction_type']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: bold; <?php echo $trans['quantity_change'] > 0 ? 'color: #27ae60;' : 'color: #e74c3c;'; ?>">
                            <?php echo $trans['quantity_change'] > 0 ? '+' : ''; ?><?php echo $trans['quantity_change']; ?>
                        </td>
                        <td><?php echo $trans['previous_quantity']; ?></td>
                        <td><?php echo $trans['new_quantity']; ?></td>
                        <td><?php echo htmlspecialchars($trans['order_number'] ?: $trans['reference_number']); ?></td>
                        <td><?php echo htmlspecialchars($trans['notes']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: center; margin-top: 1rem;">
            <a href="inventory_transactions.php" class="btn btn-primary">📊 View All Transactions</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php includeFooter(); ?>
