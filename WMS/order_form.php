<?php
/**
 * Order Form Page - Add/Edit Orders
 * Handles both inbound and outbound order creation/editing
 */

// Check authentication
require_once 'includes/auth.php';

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/inventory_functions.php';

$errors = [];
$order = [
    'order_number' => '',
    'order_type' => 'inbound',
    'status' => 'pending',
    'customer_supplier' => '',
    'contact_person' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'order_date' => date('Y-m-d'),
    'expected_date' => '',
    'notes' => ''
];

$isEdit = false;
$orderId = null;

// Check if editing existing order
if (isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];
    $isEdit = true;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $existingOrder = $stmt->fetch();
        
        if ($existingOrder) {
            $order = $existingOrder;
            // Decode items if they exist
            $order['items_data'] = !empty($order['items']) ? json_decode($order['items'], true) : [];
        } else {
            setFlashMessage("Order not found!", "error");
            redirect("orders.php");
        }
    } catch (PDOException $e) {
        setFlashMessage("Error loading order: " . $e->getMessage(), "error");
        redirect("orders.php");
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $orderNumber = sanitizeInput($_POST['order_number'] ?? '');
    $orderType = sanitizeInput($_POST['order_type'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'pending');
    $customerSupplier = sanitizeInput($_POST['customer_supplier'] ?? '');
    $contactPerson = sanitizeInput($_POST['contact_person'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $countryCode = sanitizeInput($_POST['country_code'] ?? '+94');
    $address = sanitizeInput($_POST['address'] ?? '');
    $orderDate = sanitizeInput($_POST['order_date'] ?? '');
    $expectedDate = sanitizeInput($_POST['expected_date'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Combine country code with phone number for storage
    if (!empty($phone)) {
        $phone = $countryCode . ' ' . $phone;
    }
    
    // Process items
    $itemNames = $_POST['item_name'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unitPrices = $_POST['unit_price'] ?? [];
    
    $items = [];
    $totalOrderValue = 0;
    
    for ($i = 0; $i < count($itemNames); $i++) {
        if (!empty($itemNames[$i]) && !empty($quantities[$i]) && !empty($unitPrices[$i])) {
            $itemTotal = floatval($quantities[$i]) * floatval($unitPrices[$i]);
            $items[] = [
                'name' => sanitizeInput($itemNames[$i]),
                'quantity' => intval($quantities[$i]),
                'unit_price' => floatval($unitPrices[$i]),
                'total' => $itemTotal
            ];
            $totalOrderValue += $itemTotal;
        }
    }
    
    // Validate required fields
    $requiredFields = [
        'order_number' => $orderNumber,
        'order_type' => $orderType,
        'customer_supplier' => $customerSupplier,
        'order_date' => $orderDate
    ];
    
    $errors = array_merge($errors, validateRequired($requiredFields));
    
    // Validate items
    if (empty($items)) {
        $errors[] = "At least one item is required";
    }
    
    // Additional validations
    if (!in_array($orderType, ['inbound', 'outbound'])) {
        $errors[] = "Invalid order type";
    }
    
    if (!in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
        $errors[] = "Invalid status";
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalid";
    }
    
    // Phone validation
    if (!empty($_POST['phone'])) {
        $countryCode = $_POST['country_code'] ?? '+94';
        $phoneOnly = sanitizeInput($_POST['phone']);
        $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $phoneOnly);
        
        $phoneValid = false;
        switch ($countryCode) {
            case '+94': // Sri Lanka
                $phoneValid = preg_match('/^[0-9]{9}$/', $cleanPhone);
                break;
            case '+1': // US/Canada
                $phoneValid = preg_match('/^[0-9]{10}$/', $cleanPhone);
                break;
            case '+44': // UK
                $phoneValid = preg_match('/^[0-9]{10,11}$/', $cleanPhone);
                break;
            case '+91': // India
                $phoneValid = preg_match('/^[0-9]{10}$/', $cleanPhone);
                break;
            default:
                $phoneValid = preg_match('/^[0-9]{7,15}$/', $cleanPhone);
        }
        
        if (!$phoneValid) {
            $errors[] = "Invalid phone number for selected country";
        }
    }
    
    // Date validations
    $today = date('Y-m-d');
    
    // Validate order date - cannot be in the past
    if (!empty($orderDate)) {
        if ($orderDate < $today) {
            $errors[] = "Order date cannot be in the past. Please select today's date or a future date.";
        }
    }
    
    // Validate expected date - must be greater than or equal to order date
    if (!empty($expectedDate) && !empty($orderDate)) {
        if ($expectedDate < $orderDate) {
            $errors[] = "Expected date must be greater than or equal to the order date.";
        }
    }
    
    // Check order number uniqueness
    try {
        $sql = "SELECT COUNT(*) FROM orders WHERE order_number = ?";
        $params = [$orderNumber];
        
        if ($isEdit) {
            $sql .= " AND id != ?";
            $params[] = $orderId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Order number already exists";
        }
    } catch (PDOException $e) {
        $errors[] = "Database error during validation";
    }
    
    // If no errors, save the order
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Get old status for inventory management
            $oldStatus = null;
            if ($isEdit) {
                $oldOrderStmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
                $oldOrderStmt->execute([$orderId]);
                $oldOrder = $oldOrderStmt->fetch();
                $oldStatus = $oldOrder ? $oldOrder['status'] : null;
            }
            
            // Validate stock for outbound orders being completed
            if ($orderType === 'outbound' && $status === 'completed') {
                $orderItems = [];
                foreach ($items as $item) {
                    // Find product ID by name (you might want to improve this by using product selection)
                    $productStmt = $pdo->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
                    $productStmt->execute([$item['name']]);
                    $product = $productStmt->fetch();
                    
                    if ($product) {
                        $orderItems[] = [
                            'product_id' => $product['id'],
                            'quantity' => $item['quantity']
                        ];
                    }
                }
                
                if (!empty($orderItems)) {
                    $stockValidation = validateStockAvailability($pdo, $orderItems);
                    if (!$stockValidation['success']) {
                        $errors = array_merge($errors, $stockValidation['errors']);
                        throw new Exception("Stock validation failed");
                    }
                }
            }
            
            if ($isEdit) {
                // Update existing order
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET order_number = ?, order_type = ?, status = ?, customer_supplier = ?, 
                        contact_person = ?, email = ?, phone = ?, address = ?, 
                        order_date = ?, expected_date = ?, notes = ?, items = ?, total_value = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $orderNumber, $orderType, $status, $customerSupplier,
                    $contactPerson, $email, $phone, $address,
                    $orderDate, $expectedDate ?: null, $notes, 
                    json_encode($items), $totalOrderValue, $orderId
                ]);
                
                // Update inventory if status changed
                if ($oldStatus !== $status) {
                    $inventoryResult = updateInventoryFromOrder($pdo, $orderId, $status, $oldStatus);
                    if (!$inventoryResult) {
                        $debugError = $_SESSION['inventory_debug_error'] ?? 'Unknown inventory error';
                        throw new Exception("Failed to update inventory: " . $debugError);
                    }
                }
                
                setFlashMessage("Order updated successfully!" . ($status === 'completed' ? " Inventory has been updated." : ""), "success");
            } else {
                // Insert new order
                $stmt = $pdo->prepare("
                    INSERT INTO orders (order_number, order_type, status, customer_supplier, 
                                      contact_person, email, phone, address, order_date, expected_date, notes, items, total_value)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $orderNumber, $orderType, $status, $customerSupplier,
                    $contactPerson, $email, $phone, $address,
                    $orderDate, $expectedDate ?: null, $notes,
                    json_encode($items), $totalOrderValue
                ]);
                
                $newOrderId = $pdo->lastInsertId();
                
                // Update inventory if order is created as completed
                if ($status === 'completed') {
                    $inventoryResult = updateInventoryFromOrder($pdo, $newOrderId, $status);
                    if (!$inventoryResult) {
                        $debugError = $_SESSION['inventory_debug_error'] ?? 'Unknown inventory error';
                        throw new Exception("Failed to update inventory: " . $debugError);
                    }
                }
                
                setFlashMessage("Order created successfully!" . ($status === 'completed' ? " Inventory has been updated." : ""), "success");
            }
            
            $pdo->commit();
            redirect("orders.php");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Generate order number if creating new order
if (!$isEdit && empty($order['order_number'])) {
    $prefix = 'ORD';
    $date = date('Y-m');
    $counter = 1;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_number LIKE ?");
        $stmt->execute(["$prefix-$date-%"]);
        $counter = $stmt->fetchColumn() + 1;
    } catch (PDOException $e) {
        // Use default counter
    }
    
    $order['order_number'] = sprintf("%s-%s-%03d", $prefix, $date, $counter);
}

$title = ($isEdit ? 'Edit Order' : 'Create New Order') . ' - Warehouse Management System';
$currentPage = 'orders';

// Parse existing phone number to separate country code and number
$existingCountryCode = '+94'; // Default
$existingPhoneNumber = '';

if (!empty($order['phone'])) {
    // Try to extract country code from existing phone number
    $phoneParts = explode(' ', $order['phone'], 2);
    if (count($phoneParts) === 2 && strpos($phoneParts[0], '+') === 0) {
        $existingCountryCode = $phoneParts[0];
        $existingPhoneNumber = $phoneParts[1];
    } else {
        // If no country code found, treat entire string as phone number
        $existingPhoneNumber = $order['phone'];
    }
}

includeHeader($title, $currentPage);
?>

<div class="container">
    <h1 class="page-title">
        <?php echo $isEdit ? '✏️ Edit Order' : '➕ Create New Order'; ?>
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
                        <label for="order_number">Order Number *</label>
                        <input type="text" 
                               id="order_number" 
                               name="order_number" 
                               value="<?php echo htmlspecialchars($order['order_number']); ?>" 
                               required
                               placeholder="e.g., ORD-2025-08-001">
                    </div>
                    
                    <div class="form-group">
                        <label for="order_type">Order Type *</label>
                        <select id="order_type" name="order_type" required>
                            <option value="inbound" <?php echo $order['order_type'] === 'inbound' ? 'selected' : ''; ?>>📥 Inbound (Receiving)</option>
                            <option value="outbound" <?php echo $order['order_type'] === 'outbound' ? 'selected' : ''; ?>>📤 Outbound (Shipping)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>🔄 Processing</option>
                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>✅ Completed</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_supplier">Customer/Supplier *</label>
                        <input type="text" 
                               id="customer_supplier" 
                               name="customer_supplier" 
                               value="<?php echo htmlspecialchars($order['customer_supplier']); ?>" 
                               required
                               placeholder="Company name">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_person">Contact Person</label>
                        <input type="text" 
                               id="contact_person" 
                               name="contact_person" 
                               value="<?php echo htmlspecialchars($order['contact_person']); ?>" 
                               placeholder="Contact person name">
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($order['email']); ?>" 
                               placeholder="contact@company.com">
                        <div class="error-message" id="email-error" style="display: none; color: #e74c3c; font-size: 0.9rem; margin-top: 0.5rem;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <select id="country_code" name="country_code" style="width: 120px;" class="form-control">
                                <option value="+94" <?php echo $existingCountryCode === '+94' ? 'selected' : ''; ?>>🇱🇰 +94</option>
                                <option value="+1" <?php echo $existingCountryCode === '+1' ? 'selected' : ''; ?>>🇺🇸 +1</option>
                                <option value="+44" <?php echo $existingCountryCode === '+44' ? 'selected' : ''; ?>>🇬🇧 +44</option>
                                <option value="+91" <?php echo $existingCountryCode === '+91' ? 'selected' : ''; ?>>🇮🇳 +91</option>
                                <option value="+61" <?php echo $existingCountryCode === '+61' ? 'selected' : ''; ?>>🇦🇺 +61</option>
                                <option value="+86" <?php echo $existingCountryCode === '+86' ? 'selected' : ''; ?>>🇨🇳 +86</option>
                                <option value="+81" <?php echo $existingCountryCode === '+81' ? 'selected' : ''; ?>>🇯🇵 +81</option>
                                <option value="+49" <?php echo $existingCountryCode === '+49' ? 'selected' : ''; ?>>🇩🇪 +49</option>
                                <option value="+33" <?php echo $existingCountryCode === '+33' ? 'selected' : ''; ?>>🇫🇷 +33</option>
                                <option value="+39" <?php echo $existingCountryCode === '+39' ? 'selected' : ''; ?>>🇮🇹 +39</option>
                            </select>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?php echo htmlspecialchars($existingPhoneNumber); ?>" 
                                   placeholder="77 123 4567"
                                   style="flex: 1;"
                                   class="form-control">
                        </div>
                        <div class="error-message" id="phone-error" style="display: none; color: #e74c3c; font-size: 0.9rem; margin-top: 0.5rem;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="order_date">Order Date *</label>
                        <input type="date" 
                               id="order_date" 
                               name="order_date" 
                               value="<?php echo htmlspecialchars($order['order_date']); ?>" 
                               min="<?php echo date('Y-m-d'); ?>"
                               required>
                        <div class="error-message" id="order_date-error" style="display: none; color: #e74c3c; font-size: 0.9rem; margin-top: 0.5rem;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="expected_date">Expected Date</label>
                        <input type="date" 
                               id="expected_date" 
                               name="expected_date" 
                               value="<?php echo htmlspecialchars($order['expected_date']); ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                        <div class="error-message" id="expected_date-error" style="display: none; color: #e74c3c; font-size: 0.9rem; margin-top: 0.5rem;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Address - Full Width -->
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" 
                          name="address" 
                          rows="3"
                          placeholder="Full address for shipping/receiving..."><?php echo htmlspecialchars($order['address']); ?></textarea>
            </div>
            
            <!-- Items Section -->
            <div class="form-group">
                <h3 style="margin-bottom: 1rem; color: #2c3e50;">📦 Order Items</h3>
                <div id="items-container">
                    <?php 
                    $itemsData = $order['items_data'] ?? [];
                    if (empty($itemsData)) {
                        $itemsData = [['name' => '', 'quantity' => '', 'unit_price' => '', 'total' => '']];
                    }
                    
                    foreach ($itemsData as $index => $item): 
                        $itemNum = $index + 1;
                    ?>
                    <div class="item-row" style="border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                            <div class="form-group" style="margin: 0;">
                                <label for="item_name_<?php echo $itemNum; ?>">Product *</label>
                                <input type="text" class="form-control" name="item_name[]" id="item_name_<?php echo $itemNum; ?>" 
                                       value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" placeholder="Enter item name" required>
                                <!-- This will be dynamically converted to dropdown for outbound orders -->
                                <small class="text-info">Available Stock: <span class="stock-info">0</span> | SKU: <span class="sku-info">-</span></small>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label for="quantity_<?php echo $itemNum; ?>">Quantity *</label>
                                <input type="number" class="form-control" name="quantity[]" id="quantity_<?php echo $itemNum; ?>" 
                                       value="<?php echo htmlspecialchars($item['quantity'] ?? ''); ?>" placeholder="Qty" min="1" required>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label for="unit_price_<?php echo $itemNum; ?>">Unit Price (Rs.) *</label>
                                <input type="number" class="form-control" name="unit_price[]" id="unit_price_<?php echo $itemNum; ?>" 
                                       value="<?php echo htmlspecialchars($item['unit_price'] ?? ''); ?>" placeholder="0.00" step="0.01" min="0" required>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label for="total_<?php echo $itemNum; ?>">Total</label>
                                <input type="number" class="form-control" name="total[]" id="total_<?php echo $itemNum; ?>" 
                                       value="<?php echo htmlspecialchars($item['total'] ?? ''); ?>" placeholder="0.00" step="0.01" readonly style="background-color: #f8f9fa;">
                            </div>
                            <div style="margin-top: 1.5rem;">
                                <button type="button" class="btn btn-danger" onclick="removeItem(this)" style="padding: 0.5rem; font-size: 1.2rem;">×</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                    <button type="button" class="btn btn-warning" onclick="addItem()">➕ Add Another Item</button>
                    <div style="font-size: 1.2rem; font-weight: bold; color: #27ae60;">
                        Total Order Value: Rs. <span id="grand-total"><?php echo number_format($order['total_value'] ?? 0, 2); ?></span>
                    </div>
                </div>
                
                <!-- Order Type Instructions -->
                <div style="margin-top: 1rem; padding: 1rem; background-color: #f8f9fa; border-radius: 5px; border-left: 4px solid #3498db;">
                    <div id="order-type-info">
                        <p style="margin: 0; color: #2c3e50;">
                            <strong>📝 Instructions:</strong>
                            <span id="type-instruction">Select order type to see appropriate item fields.</span>
                        </p>
                    </div>
                </div>
            </div>
            
            <style>
            .is-invalid {
                border-color: #e74c3c !important;
                background-color: #fdf2f2 !important;
            }
            
            .product-select:focus {
                border-color: #3498db;
                box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            }
            
            .stock-info {
                font-weight: bold;
                color: #27ae60;
            }
            
            .sku-info {
                font-weight: bold;
                color: #3498db;
            }
            
            input[readonly] {
                background-color: #f8f9fa !important;
                cursor: not-allowed;
            }
            
            .quantity-error {
                border-color: #e74c3c !important;
                animation: shake 0.5s;
            }
            
            /* Ensure grid layout always shows all columns */
            .item-row > div[style*="grid-template-columns"] {
                display: grid !important;
                grid-template-columns: 2fr 1fr 1fr 1fr auto !important;
                gap: 1rem !important;
                align-items: end !important;
            }
            
            /* Prevent any visual separation of product field */
            .item-row .form-group {
                width: 100% !important;
                display: block !important;
            }
            
            /* Ensure no item rows appear outside the grid */
            .item-row:not([style*="border"]) {
                display: none !important;
            }
            
            /* Prevent any responsive collapse */
            @media (max-width: 768px) {
                .item-row > div[style*="grid-template-columns"] {
                    grid-template-columns: 2fr 1fr 1fr 1fr auto !important;
                    gap: 0.5rem !important;
                }
            }
            
            @keyframes shake {
                0%, 20%, 40%, 60%, 80% { transform: translateX(-2px); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(2px); }
                100% { transform: translateX(0); }
            }
            </style>
            
            <!-- Notes - Full Width -->
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" 
                          name="notes" 
                          rows="4"
                          placeholder="Additional notes, special instructions, etc..."><?php echo htmlspecialchars($order['notes']); ?></textarea>
            </div>
            
            <!-- Form Buttons -->
            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                <button type="submit" class="btn btn-success">
                    <?php echo $isEdit ? '💾 Update Order' : '➕ Create Order'; ?>
                </button>
                <a href="orders.php" class="btn btn-warning">↩️ Back to Orders</a>
                
                <?php if ($isEdit): ?>
                    <a href="order_details.php?id=<?php echo $orderId; ?>" class="btn btn-primary">👁️ View Details</a>
                    <a href="orders.php?action=delete&id=<?php echo $orderId; ?>" 
                       class="btn btn-danger"
                       onclick="return confirmDelete('<?php echo htmlspecialchars($order['order_number']); ?>')"
                       style="float: right;">
                        🗑️ Delete Order
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Help Section -->
    <div class="card">
        <h3>📋 Order Guidelines</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h4 style="color: #27ae60;">📥 Inbound Orders</h4>
                <ul style="color: #666; line-height: 1.8;">
                    <li>Receiving goods from suppliers</li>
                    <li>Stock replenishment orders</li>
                    <li>Purchase orders from vendors</li>
                    <li>Returns from customers</li>
                </ul>
            </div>
            <div>
                <h4 style="color: #e74c3c;">📤 Outbound Orders</h4>
                <ul style="color: #666; line-height: 1.8;">
                    <li>Shipping goods to customers</li>
                    <li>Sales orders fulfillment</li>
                    <li>Transfer to other locations</li>
                    <li>Returns to suppliers</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
let itemCounter = <?php echo count($order['items_data'] ?? [1]); ?>;
let availableProducts = [];

// Fetch available products when page loads
async function fetchAvailableProducts() {
    try {
        const response = await fetch('get_available_products.php');
        const data = await response.json();
        availableProducts = data;
    } catch (error) {
        console.error('Error fetching products:', error);
    }
}

function addItem() {
    itemCounter++;
    const container = document.getElementById('items-container');
    const orderType = document.querySelector('select[name="order_type"]').value;
    
    let itemNameField = '';
    if (orderType === 'outbound') {
        // Create dropdown for outbound orders
        let productOptions = '<option value="">Select Product</option>';
        availableProducts.forEach(product => {
            productOptions += `<option value="${product.name}" data-id="${product.id}" data-price="${product.price}" data-stock="${product.stock}" data-sku="${product.sku}">${product.name} (Stock: ${product.stock})</option>`;
        });
        
        itemNameField = `
            <label for="item_name_${itemCounter}">Product *</label>
            <select class="form-control product-select" name="item_name[]" id="item_name_${itemCounter}" required>
                ${productOptions}
            </select>
            <small class="text-info">Available Stock: <span class="stock-info">0</span> | SKU: <span class="sku-info">-</span></small>
        `;
    } else {
        // Free text input for inbound orders
        itemNameField = `
            <label for="item_name_${itemCounter}">Item Name *</label>
            <input type="text" class="form-control" name="item_name[]" id="item_name_${itemCounter}" placeholder="Enter item name" required>
        `;
    }
    
    const newItem = `
        <div class="item-row" style="border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    ${itemNameField}
                </div>
                <div class="form-group" style="margin: 0;">
                    <label for="quantity_${itemCounter}">Quantity *</label>
                    <input type="number" class="form-control" name="quantity[]" id="quantity_${itemCounter}" placeholder="Qty" min="1" required>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label for="unit_price_${itemCounter}">Unit Price (Rs.) *</label>
                    <input type="number" class="form-control" name="unit_price[]" id="unit_price_${itemCounter}" placeholder="0.00" step="0.01" min="0" required ${orderType === 'outbound' ? 'readonly style="background-color: #f8f9fa;"' : ''}>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label for="total_${itemCounter}">Total</label>
                    <input type="number" class="form-control" name="total[]" id="total_${itemCounter}" placeholder="0.00" step="0.01" readonly style="background-color: #f8f9fa;">
                </div>
                <div style="margin-top: 1.5rem;">
                    <button type="button" class="btn btn-danger" onclick="removeItem(this)" style="padding: 0.5rem; font-size: 1.2rem;">×</button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', newItem);
    
    // Apply appropriate conversion based on order type
    const newRow = container.lastElementChild;
    if (orderType === 'outbound') {
        setupOutboundRow(newRow);
    }
    
    attachCalculationEvents();
}

function setupOutboundRow(row) {
    const productSelect = row.querySelector('.product-select');
    const quantityInput = row.querySelector('input[name="quantity[]"]');
    const unitPriceInput = row.querySelector('input[name="unit_price[]"]');
    const stockInfo = row.querySelector('.stock-info');
    const skuInfo = row.querySelector('.sku-info');
    
    if (!productSelect) return;
    
    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const stock = parseInt(selectedOption.dataset.stock);
            const price = parseFloat(selectedOption.dataset.price);
            const sku = selectedOption.dataset.sku;
            
            stockInfo.textContent = stock;
            skuInfo.textContent = sku;
            unitPriceInput.value = price.toFixed(2);
            quantityInput.max = stock;
            quantityInput.placeholder = `Max: ${stock}`;
            
            // Clear any previous validation
            quantityInput.setCustomValidity('');
            quantityInput.classList.remove('is-invalid');
            
            // Trigger calculation
            quantityInput.dispatchEvent(new Event('input'));
        } else {
            stockInfo.textContent = '0';
            skuInfo.textContent = '-';
            unitPriceInput.value = '';
            quantityInput.max = '';
            quantityInput.placeholder = 'Qty';
        }
    });
    
    // Add stock validation for quantity
    quantityInput.addEventListener('input', function() {
        const selectedProduct = productSelect.options[productSelect.selectedIndex];
        if (selectedProduct.value) {
            const maxStock = parseInt(selectedProduct.dataset.stock);
            const enteredQty = parseInt(this.value);
            
            if (enteredQty > maxStock) {
                this.setCustomValidity(`Only ${maxStock} units available in stock`);
                this.classList.add('is-invalid');
                this.style.borderColor = '#e74c3c';
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                this.style.borderColor = '';
            }
        }
    });
}

function handleOrderTypeChange(orderType) {
    const itemsContainer = document.getElementById('items-container');
    const itemRows = itemsContainer.querySelectorAll('.item-row');
    const instructionSpan = document.getElementById('type-instruction');
    
    // Update instructions
    if (orderType === 'outbound') {
        instructionSpan.innerHTML = '📤 <strong>Outbound Order:</strong> Select products from available inventory. Unit prices are auto-filled. Stock levels are validated.';
        instructionSpan.parentElement.style.borderLeftColor = '#e74c3c';
    } else {
        instructionSpan.innerHTML = '📥 <strong>Inbound Order:</strong> Enter new item names manually. Set your own unit prices for incoming products.';
        instructionSpan.parentElement.style.borderLeftColor = '#27ae60';
    }
    
    itemRows.forEach(row => {
        if (orderType === 'outbound') {
            convertToOutboundRow(row);
        } else {
            convertToInboundRow(row);
        }
    });
}

function convertToOutboundRow(row) {
    const itemNameField = row.querySelector('div:first-child .form-group, div:first-child');
    const quantityInput = row.querySelector('input[name="quantity[]"]');
    const unitPriceInput = row.querySelector('input[name="unit_price[]"]');
    
    // Create product dropdown
    let productOptions = '<option value="">Select Product</option>';
    availableProducts.forEach(product => {
        productOptions += `<option value="${product.name}" data-id="${product.id}" data-price="${product.price}" data-stock="${product.stock}" data-sku="${product.sku}">${product.name} (Stock: ${product.stock})</option>`;
    });
    
    // Only replace the content within the first form-group div, maintaining the grid structure
    if (itemNameField.classList && itemNameField.classList.contains('form-group')) {
        itemNameField.innerHTML = `
            <label>Product *</label>
            <select class="form-control product-select" name="item_name[]" required>
                ${productOptions}
            </select>
            <small class="text-info">Available Stock: <span class="stock-info">0</span> | SKU: <span class="sku-info">-</span></small>
        `;
    } else {
        // Fallback - find the form-group within the first div
        const formGroup = itemNameField.querySelector('.form-group');
        if (formGroup) {
            formGroup.innerHTML = `
                <label>Product *</label>
                <select class="form-control product-select" name="item_name[]" required>
                    ${productOptions}
                </select>
                <small class="text-info">Available Stock: <span class="stock-info">0</span> | SKU: <span class="sku-info">-</span></small>
            `;
        }
    }
    
    // Make unit price readonly for outbound
    unitPriceInput.readOnly = true;
    unitPriceInput.style.backgroundColor = '#f8f9fa';
    
    setupOutboundRow(row);
}

function convertToInboundRow(row) {
    const itemNameField = row.querySelector('div:first-child .form-group, div:first-child');
    const quantityInput = row.querySelector('input[name="quantity[]"]');
    const unitPriceInput = row.querySelector('input[name="unit_price[]"]');
    
    // Only replace the content within the first form-group div, maintaining the grid structure
    if (itemNameField.classList && itemNameField.classList.contains('form-group')) {
        itemNameField.innerHTML = `
            <label>Product *</label>
            <input type="text" class="form-control" name="item_name[]" placeholder="Enter item name" required>
            <small class="text-info" style="display: none;">Available Stock: <span class="stock-info">0</span> | SKU: <span class="sku-info">-</span></small>
        `;
    } else {
        // Fallback - find the form-group within the first div
        const formGroup = itemNameField.querySelector('.form-group');
        if (formGroup) {
            formGroup.innerHTML = `
                <label>Product *</label>
                <input type="text" class="form-control" name="item_name[]" placeholder="Enter item name" required>
                <small class="text-info" style="display: none;">Available Stock: <span class="stock-info">0</span> | SKU: <span class="sku-info">-</span></small>
            `;
        }
    }
    
    // Make unit price editable for inbound
    unitPriceInput.readOnly = false;
    unitPriceInput.style.backgroundColor = '';
    
    // Remove stock restrictions
    quantityInput.removeAttribute('max');
    quantityInput.placeholder = 'Qty';
    quantityInput.setCustomValidity('');
    quantityInput.classList.remove('is-invalid');
    quantityInput.style.borderColor = '';
}

function removeItem(button) {
    if (document.querySelectorAll('.item-row').length > 1) {
        button.closest('.item-row').remove();
        calculateGrandTotal();
    } else {
        alert('At least one item is required');
    }
}

function attachCalculationEvents() {
    document.querySelectorAll('input[name="quantity[]"], input[name="unit_price[]"]').forEach(input => {
        input.addEventListener('input', function() {
            const row = this.closest('.item-row');
            const quantity = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
            const unitPrice = parseFloat(row.querySelector('input[name="unit_price[]"]').value) || 0;
            const total = quantity * unitPrice;
            row.querySelector('input[name="total[]"]').value = total.toFixed(2);
            calculateGrandTotal();
        });
    });
}

function calculateGrandTotal() {
    let grandTotal = 0;
    document.querySelectorAll('input[name="total[]"]').forEach(input => {
        grandTotal += parseFloat(input.value) || 0;
    });
    document.getElementById('grand-total').textContent = grandTotal.toFixed(2);
}

// Auto-generate order number based on type
document.addEventListener('DOMContentLoaded', async function() {
    const typeField = document.querySelector('select[name="order_type"]');
    const numberField = document.querySelector('input[name="order_number"]');
    
    // Fetch available products first
    await fetchAvailableProducts();
    
    // Initialize calculation events on page load
    attachCalculationEvents();
    
    // Clean up any duplicate or standalone item fields
    function cleanupItemRows() {
        const itemsContainer = document.getElementById('items-container');
        const allRows = itemsContainer.querySelectorAll('.item-row');
        
        allRows.forEach(row => {
            // Check if this row has the proper grid structure
            const gridDiv = row.querySelector('div[style*="grid-template-columns"]');
            if (!gridDiv) {
                // Remove rows that don't have the proper grid structure
                row.remove();
            }
        });
    }
    
    cleanupItemRows();
    
    // Listen for order type changes
    if (typeField) {
        typeField.addEventListener('change', function() {
            handleOrderTypeChange(this.value);
            updateOrderNumber(); // Also update order number when type changes
        });
        
        // Initialize based on current order type
        if (typeField.value) {
            handleOrderTypeChange(typeField.value);
        }
    }
    
    // Email validation function
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Phone validation function
    function validatePhone(countryCode, phone) {
        // Remove all spaces, dashes, and special characters except +
        const cleanPhone = phone.replace(/[\s\-\(\)]/g, '');
        
        // Phone validation rules based on country code
        const phoneRules = {
            '+94': /^[0-9]{9}$/, // Sri Lanka: 9 digits
            '+1': /^[0-9]{10}$/, // US/Canada: 10 digits
            '+44': /^[0-9]{10,11}$/, // UK: 10-11 digits
            '+91': /^[0-9]{10}$/, // India: 10 digits
            '+61': /^[0-9]{9}$/, // Australia: 9 digits
            '+86': /^[0-9]{11}$/, // China: 11 digits
            '+81': /^[0-9]{10,11}$/, // Japan: 10-11 digits
            '+49': /^[0-9]{10,12}$/, // Germany: 10-12 digits
            '+33': /^[0-9]{9,10}$/, // France: 9-10 digits
            '+39': /^[0-9]{9,10}$/, // Italy: 9-10 digits
        };
        
        const rule = phoneRules[countryCode];
        return rule ? rule.test(cleanPhone) : /^[0-9]{7,15}$/.test(cleanPhone);
    }
    
    // Date validation functions
    function validateOrderDate(orderDate) {
        const today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format
        return orderDate >= today;
    }
    
    function validateExpectedDate(orderDate, expectedDate) {
        return expectedDate >= orderDate;
    }
    
    // Show error message
    function showError(fieldId, message) {
        const errorElement = document.getElementById(fieldId + '-error');
        const inputElement = document.getElementById(fieldId);
        
        if (errorElement && inputElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            inputElement.style.borderColor = '#e74c3c';
            inputElement.style.backgroundColor = '#fdf2f2';
        }
    }
    
    // Clear error message
    function clearError(fieldId) {
        const errorElement = document.getElementById(fieldId + '-error');
        const inputElement = document.getElementById(fieldId);
        
        if (errorElement && inputElement) {
            errorElement.style.display = 'none';
            inputElement.style.borderColor = '';
            inputElement.style.backgroundColor = '';
        }
    }
    
    // Add real-time validation
    document.addEventListener('DOMContentLoaded', function() {
        const emailField = document.getElementById('email');
        const phoneField = document.getElementById('phone');
        const countryCodeField = document.getElementById('country_code');
        
        // Email validation on blur
        if (emailField) {
            emailField.addEventListener('blur', function() {
                const email = this.value.trim();
                if (email && !validateEmail(email)) {
                    showError('email', 'Email invalid');
                } else {
                    clearError('email');
                }
            });
            
            emailField.addEventListener('input', function() {
                if (this.value.trim()) {
                    clearError('email');
                }
            });
        }
        
        // Phone validation on blur
        if (phoneField && countryCodeField) {
            function validatePhoneField() {
                const phone = phoneField.value.trim();
                const countryCode = countryCodeField.value;
                
                if (phone && !validatePhone(countryCode, phone)) {
                    const countryName = countryCodeField.options[countryCodeField.selectedIndex].text.split(' ')[1];
                    showError('phone', `Invalid phone number for ${countryName}`);
                } else {
                    clearError('phone');
                }
            }
            
            phoneField.addEventListener('blur', validatePhoneField);
            countryCodeField.addEventListener('change', validatePhoneField);
            
            phoneField.addEventListener('input', function() {
                if (this.value.trim()) {
                    clearError('phone');
                }
            });
        }
        
        // Date validation
        const orderDateField = document.getElementById('order_date');
        const expectedDateField = document.getElementById('expected_date');
        
        if (orderDateField) {
            orderDateField.addEventListener('change', function() {
                const orderDate = this.value;
                if (orderDate && !validateOrderDate(orderDate)) {
                    showError('order_date', 'Order date cannot be in the past. Please select today\'s date or a future date.');
                } else {
                    clearError('order_date');
                    
                    // Update expected date minimum to match order date
                    if (expectedDateField) {
                        expectedDateField.min = orderDate;
                        
                        // Also validate expected date when order date changes
                        if (expectedDateField.value) {
                            const expectedDate = expectedDateField.value;
                            if (!validateExpectedDate(orderDate, expectedDate)) {
                                showError('expected_date', 'Expected date must be greater than or equal to the order date.');
                            } else {
                                clearError('expected_date');
                            }
                        }
                    }
                }
            });
        }
        
        if (expectedDateField) {
            expectedDateField.addEventListener('change', function() {
                const expectedDate = this.value;
                const orderDate = orderDateField ? orderDateField.value : '';
                
                if (expectedDate && orderDate && !validateExpectedDate(orderDate, expectedDate)) {
                    showError('expected_date', 'Expected date must be greater than or equal to the order date.');
                } else {
                    clearError('expected_date');
                }
            });
        }
    });
    
    <?php if (!$isEdit): ?>
    function updateOrderNumber() {
        if (typeField && numberField) {
            const type = typeField.value;
            const prefix = type === 'inbound' ? 'INB' : 'OUT';
            const date = new Date().toISOString().slice(0, 7); // YYYY-MM
            const timestamp = Date.now().toString().slice(-3);
            
            numberField.value = `${prefix}-${date}-${timestamp}`;
        }
    }
    
    // Generate initial order number
    updateOrderNumber();
    <?php endif; ?>
});
</script>

<?php includeFooter(); ?>
