-- Inventory Management Enhancement
-- Add inventory transaction logging for automatic stock updates

USE warehouse_management;

-- Inventory transactions table for tracking all stock movements
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    order_id INT NULL,
    transaction_type ENUM('inbound', 'outbound', 'adjustment', 'initial') NOT NULL,
    quantity_change INT NOT NULL, -- Positive for additions, negative for subtractions
    previous_quantity INT NOT NULL,
    new_quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NULL,
    reference_number VARCHAR(100) NULL, -- Order number or adjustment reference
    notes TEXT NULL,
    created_by VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- Add indexes for better performance
CREATE INDEX idx_inventory_transactions_product_id ON inventory_transactions(product_id);
CREATE INDEX idx_inventory_transactions_order_id ON inventory_transactions(order_id);
CREATE INDEX idx_inventory_transactions_type ON inventory_transactions(transaction_type);
CREATE INDEX idx_inventory_transactions_date ON inventory_transactions(created_at);

-- Add a status tracking field to orders to prevent duplicate processing
ALTER TABLE orders 
ADD COLUMN inventory_processed BOOLEAN DEFAULT FALSE AFTER status;
