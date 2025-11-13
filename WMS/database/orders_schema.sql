-- Orders Management Extension for Warehouse Management System
-- Add orders table for inbound and outbound tracking

USE warehouse_management;

-- Orders table for inbound/outbound management
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    order_type ENUM('inbound', 'outbound') NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    customer_supplier VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    order_date DATE NOT NULL,
    expected_date DATE,
    completed_date DATE NULL,
    total_items INT DEFAULT 0,
    total_value DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Order items table for detailed line items
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert sample orders with Sri Lankan Rupee (LKR) values
INSERT INTO orders (order_number, order_type, status, customer_supplier, contact_person, email, phone, address, order_date, expected_date, total_items, total_value, notes) VALUES
('INB-2025-001', 'inbound', 'completed', 'TechSupply Corp', 'John Smith', 'john@techsupply.com', '555-0101', '123 Supply Street, Tech City, TC 12345', '2025-07-25', '2025-07-28', 75, 15749175.00, 'Bulk electronics shipment - Q3 stock replenishment'),
('OUT-2025-001', 'outbound', 'processing', 'Metro Office Solutions', 'Sarah Johnson', 'sarah@metrooffice.com', '555-0102', '456 Business Ave, Metro City, MC 67890', '2025-07-30', '2025-08-02', 15, 2699955.00, 'Large office furniture order for new branch'),
('INB-2025-002', 'inbound', 'pending', 'Global Electronics Ltd', 'Mike Chen', 'mike@globalelec.com', '555-0103', '789 Import Blvd, Port City, PC 11111', '2025-08-01', '2025-08-05', 0, 0.00, 'Monthly electronics restock - August delivery'),
('OUT-2025-002', 'outbound', 'completed', 'StartUp Workspace', 'Emily Davis', 'emily@startupws.com', '555-0104', '321 Innovation Dr, Hub City, HC 22222', '2025-07-28', '2025-07-30', 8, 959976.00, 'Complete office setup for new startup'),
('INB-2025-003', 'inbound', 'processing', 'Office Plus Distributors', 'Robert Wilson', 'robert@officeplus.com', '555-0105', '654 Distribution Way, Supply Town, ST 33333', '2025-07-31', '2025-08-03', 200, 599700.00, 'Office supplies bulk order'),
('OUT-2025-003', 'outbound', 'pending', 'City Hospital Network', 'Dr. Lisa Brown', 'lisa@cityhospital.com', '555-0106', '987 Medical Center Dr, Health City, HC 44444', '2025-08-01', '2025-08-04', 0, 0.00, 'Medical office equipment order');

-- Insert sample order items with Sri Lankan Rupee (LKR) prices
INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES
-- Inbound order INB-2025-001 (completed)
(1, 1, 25, 299997.00, 7499925.00),  -- Laptops
(1, 3, 50, 8997.00, 449850.00),     -- Wireless Mouse
(1, 6, 25, 4797.00, 119925.00),     -- Network Cables
(1, 8, 25, 59997.00, 1499925.00),   -- Security Cameras

-- Outbound order OUT-2025-001 (processing)
(2, 2, 10, 89997.00, 899970.00),    -- Office Chairs
(2, 4, 5, 179997.00, 899985.00),    -- Steel Desks
(2, 7, 3, 44997.00, 134991.00),     -- Whiteboards

-- Outbound order OUT-2025-002 (completed)
(4, 1, 3, 299997.00, 899991.00),    -- Laptops
(4, 3, 5, 8997.00, 44985.00),       -- Wireless Mouse
(4, 5, 2, 2997.00, 5994.00),        -- Printer Paper
(4, 6, 3, 4797.00, 14391.00);       -- Network Cables
