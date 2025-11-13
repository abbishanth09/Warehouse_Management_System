<?php
/**
 * Orders Management Page
 * Displays all inbound and outbound orders with filtering
 */

// Check authentication
require_once 'includes/auth.php';

require_once 'config/database.php';
require_once 'includes/functions.php';

// Handle order actions
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        setFlashMessage("Order deleted successfully!", "success");
        redirect("orders.php");
    } catch (PDOException $e) {
        setFlashMessage("Error deleting order: " . $e->getMessage(), "error");
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';

// Build query with filters
$sql = "SELECT o.*, 
               COUNT(oi.id) as item_count,
               SUM(oi.total_price) as calculated_total
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (o.order_number LIKE ? OR o.customer_supplier LIKE ? OR o.contact_person LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($type)) {
    $sql .= " AND o.order_type = ?";
    $params[] = $type;
}

if (!empty($status)) {
    $sql .= " AND o.status = ?";
    $params[] = $status;
}

$sql .= " GROUP BY o.id ORDER BY o.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    setFlashMessage("Error loading orders: " . $e->getMessage(), "error");
}

// Get statistics
try {
    $stats = [];
    
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $stats['total_orders'] = $stmt->fetchColumn();
    
    // Inbound orders
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_type = 'inbound'");
    $stats['inbound_orders'] = $stmt->fetchColumn();
    
    // Outbound orders
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_type = 'outbound'");
    $stats['outbound_orders'] = $stmt->fetchColumn();
    
    // Pending orders
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $stats['pending_orders'] = $stmt->fetchColumn();
    
    // Total order value
    $stmt = $pdo->query("SELECT SUM(total_value) FROM orders WHERE status != 'cancelled'");
    $stats['total_value'] = $stmt->fetchColumn() ?: 0;
    
} catch (PDOException $e) {
    $stats = [
        'total_orders' => 0,
        'inbound_orders' => 0,
        'outbound_orders' => 0,
        'pending_orders' => 0,
        'total_value' => 0
    ];
}

$title = 'Orders Management - Warehouse Management System';
$currentPage = 'orders';
includeHeader($title, $currentPage);
?>

<div class="container">
    <h1 class="page-title">📋 Orders Management</h1>
    
    <!-- Statistics Cards -->
    <div class="dashboard-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <h3>Total Orders</h3>
            <div class="number"><?php echo formatNumber($stats['total_orders']); ?></div>
            <div class="label">All orders</div>
        </div>
        
        <div class="stat-card" style="border-left-color: #27ae60;">
            <h3>Inbound Orders</h3>
            <div class="number" style="color: #27ae60;"><?php echo formatNumber($stats['inbound_orders']); ?></div>
            <div class="label">Receiving goods</div>
        </div>
        
        <div class="stat-card" style="border-left-color: #e74c3c;">
            <h3>Outbound Orders</h3>
            <div class="number" style="color: #e74c3c;"><?php echo formatNumber($stats['outbound_orders']); ?></div>
            <div class="label">Shipping goods</div>
        </div>
        
        <div class="stat-card" style="border-left-color: #f39c12;">
            <h3>Pending Orders</h3>
            <div class="number" style="color: #f39c12;"><?php echo formatNumber($stats['pending_orders']); ?></div>
            <div class="label">Need attention</div>
        </div>
        
        <div class="stat-card" style="border-left-color: #9b59b6;">
            <h3>Total Value</h3>
            <div class="number" style="color: #9b59b6;"><?php echo formatCurrency($stats['total_value']); ?></div>
            <div class="label">Order value</div>
        </div>
    </div>
    
    <!-- Search and Filter Section -->
    <div class="card">
        <form method="GET" action="orders.php" class="search-filter">
            <div class="search-box">
                <input type="text" 
                       id="searchInput" 
                       name="search" 
                       placeholder="Search by order number, customer, or contact..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <select name="type">
                <option value="">All Types</option>
                <option value="inbound" <?php echo $type === 'inbound' ? 'selected' : ''; ?>>📥 Inbound</option>
                <option value="outbound" <?php echo $type === 'outbound' ? 'selected' : ''; ?>>📤 Outbound</option>
            </select>
            
            <select name="status">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>🔄 Processing</option>
                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>✅ Completed</option>
                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
            </select>
            
            <button type="submit" class="btn btn-primary">🔍 Search</button>
            <a href="orders.php" class="btn btn-warning">🔄 Clear</a>
        </form>
        
        <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <span>Showing <?php echo count($orders); ?> orders</span>
            <div>
                <a href="order_form.php" class="btn btn-success">➕ New Order</a>
                <a href="orders.php?type=inbound" class="btn btn-primary">📥 Inbound</a>
                <a href="orders.php?type=outbound" class="btn btn-danger">📤 Outbound</a>
            </div>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card">
        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 3rem;">
                <h3>No orders found</h3>
                <p>No orders match your search criteria.</p>
                <a href="order_form.php" class="btn btn-success">Create Your First Order</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Customer/Supplier</th>
                            <th>Contact</th>
                            <th>Order Date</th>
                            <th>Expected Date</th>
                            <th>Items</th>
                            <th>Value</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($order['order_type'] === 'inbound'): ?>
                                        <span style="color: #27ae60; font-weight: bold;">📥 Inbound</span>
                                    <?php else: ?>
                                        <span style="color: #e74c3c; font-weight: bold;">📤 Outbound</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'pending' => '#f39c12',
                                        'processing' => '#3498db', 
                                        'completed' => '#27ae60',
                                        'cancelled' => '#95a5a6'
                                    ];
                                    $statusIcons = [
                                        'pending' => '⏳',
                                        'processing' => '🔄',
                                        'completed' => '✅',
                                        'cancelled' => '❌'
                                    ];
                                    $color = $statusColors[$order['status']] ?? '#333';
                                    $icon = $statusIcons[$order['status']] ?? '';
                                    ?>
                                    <span style="color: <?php echo $color; ?>; font-weight: bold;">
                                        <?php echo $icon . ' ' . ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['customer_supplier']); ?></strong>
                                    <?php if (!empty($order['email'])): ?>
                                        <br><small style="color: #666;"><?php echo htmlspecialchars($order['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($order['contact_person'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <?php if ($order['expected_date']): ?>
                                        <?php echo date('M j, Y', strtotime($order['expected_date'])); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatNumber($order['item_count']); ?></td>
                                <td><?php echo formatCurrency($order['calculated_total'] ?: $order['total_value']); ?></td>
                                <td>
                                    <a href="order_form.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-warning btn-small">✏️ Edit</a>
                                    <a href="orders.php?action=delete&id=<?php echo $order['id']; ?>" 
                                       class="btn btn-danger btn-small"
                                       onclick="return confirmDelete('<?php echo htmlspecialchars($order['order_number']); ?>')">🗑️ Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Real-time search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // You can add real-time filtering here if needed
        });
    }
});
</script>

<?php includeFooter(); ?>
