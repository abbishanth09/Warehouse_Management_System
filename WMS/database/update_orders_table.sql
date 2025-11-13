-- Update orders table to include items and total_value columns
-- Run this script to add the new columns for order items functionality

ALTER TABLE orders 
ADD COLUMN items TEXT AFTER notes,
ADD COLUMN total_value DECIMAL(15,2) DEFAULT 0.00 AFTER items;

-- Update the items column comment
ALTER TABLE orders 
MODIFY COLUMN items TEXT COMMENT 'JSON array of order items with name, quantity, unit_price, and total';

-- Update the total_value column comment  
ALTER TABLE orders 
MODIFY COLUMN total_value DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total value of all items in the order';

-- Example of how the items JSON will look:
-- [{"name":"Laptop","quantity":2,"unit_price":50000.00,"total":100000.00},{"name":"Mouse","quantity":5,"unit_price":1500.00,"total":7500.00}]
