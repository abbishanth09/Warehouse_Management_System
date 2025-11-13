-- Warehouse Management System Database Schema
-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS warehouse_management;
USE warehouse_management;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) UNIQUE NOT NULL,
    category VARCHAR(100) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL,
    min_stock_level INT NOT NULL DEFAULT 10,
    location VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data with Sri Lankan Rupee (LKR) prices
INSERT INTO products (name, sku, category, quantity, price, min_stock_level, location, description) VALUES
('Laptop Computer', 'LT001', 'Electronics', 50, 299997.00, 5, 'A-1-01', 'High-performance business laptop'),
('Office Chair', 'OC001', 'Furniture', 25, 89997.00, 3, 'B-2-05', 'Ergonomic office chair with lumbar support'),
('Wireless Mouse', 'WM001', 'Electronics', 150, 8997.00, 20, 'A-1-02', 'Bluetooth wireless optical mouse'),
('Steel Desk', 'SD001', 'Furniture', 12, 179997.00, 2, 'B-1-01', 'Heavy-duty steel office desk'),
('Printer Paper', 'PP001', 'Office Supplies', 500, 2997.00, 50, 'C-1-01', 'A4 size white printer paper - 500 sheets'),
('Network Cable', 'NC001', 'Electronics', 75, 4797.00, 10, 'A-2-01', 'Cat6 Ethernet cable - 5ft'),
('Whiteboard', 'WB001', 'Office Supplies', 8, 44997.00, 2, 'C-2-01', 'Magnetic dry erase whiteboard 4x6ft'),
('Security Camera', 'SC001', 'Electronics', 30, 59997.00, 5, 'A-3-01', 'HD IP security camera with night vision');
