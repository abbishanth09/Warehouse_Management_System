-- Update all prices from USD to Sri Lankan Rupees (LKR)
-- Exchange rate: 1 USD = 300 LKR (approximate)
USE warehouse_management;

-- Update products table with LKR prices
UPDATE products SET 
    price = CASE 
        WHEN id = 1 THEN 299997.00  -- Laptop: $999.99 * 300
        WHEN id = 2 THEN 89997.00   -- Office Chair: $299.99 * 300
        WHEN id = 3 THEN 8997.00    -- Wireless Mouse: $29.99 * 300
        WHEN id = 4 THEN 179997.00  -- Steel Desk: $599.99 * 300
        WHEN id = 5 THEN 2997.00    -- Printer Paper: $9.99 * 300
        WHEN id = 6 THEN 4797.00    -- Network Cable: $15.99 * 300
        WHEN id = 7 THEN 44997.00   -- Whiteboard: $149.99 * 300
        WHEN id = 8 THEN 59997.00   -- Security Camera: $199.99 * 300
        ELSE price
    END;

-- Update orders table with LKR values (if orders table exists)
UPDATE orders SET 
    total_value = CASE 
        WHEN id = 1 THEN 15749175.00  -- INB-2025-001: $52,497.25 * 300
        WHEN id = 2 THEN 2699955.00   -- OUT-2025-001: $8,999.85 * 300
        WHEN id = 3 THEN 0.00         -- INB-2025-002: $0.00
        WHEN id = 4 THEN 959976.00    -- OUT-2025-002: $3,199.92 * 300
        WHEN id = 5 THEN 599700.00    -- INB-2025-003: $1,999.00 * 300
        WHEN id = 6 THEN 0.00         -- OUT-2025-003: $0.00
        ELSE total_value
    END
WHERE id <= 6;

-- Update order_items table with LKR prices (if order_items table exists)
UPDATE order_items SET 
    unit_price = CASE 
        WHEN product_id = 1 THEN 299997.00  -- Laptop
        WHEN product_id = 2 THEN 89997.00   -- Office Chair
        WHEN product_id = 3 THEN 8997.00    -- Wireless Mouse
        WHEN product_id = 4 THEN 179997.00  -- Steel Desk
        WHEN product_id = 5 THEN 2997.00    -- Printer Paper
        WHEN product_id = 6 THEN 4797.00    -- Network Cable
        WHEN product_id = 7 THEN 44997.00   -- Whiteboard
        WHEN product_id = 8 THEN 59997.00   -- Security Camera
        ELSE unit_price
    END;

-- Recalculate total_price for all order items
UPDATE order_items SET total_price = quantity * unit_price;

-- Update order totals based on order items
UPDATE orders o SET 
    total_value = (
        SELECT COALESCE(SUM(total_price), 0) 
        FROM order_items oi 
        WHERE oi.order_id = o.id
    );

-- Display updated prices for verification
SELECT 'Products with LKR prices:' as info;
SELECT id, name, CONCAT('Rs. ', FORMAT(price, 2)) as price_lkr FROM products;

SELECT 'Orders with LKR values:' as info;
SELECT order_number, order_type, CONCAT('Rs. ', FORMAT(total_value, 2)) as total_value_lkr FROM orders;
