-- Create users table for authentication
USE warehouse_management;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    role ENUM('admin', 'manager', 'employee') DEFAULT 'employee',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert admin user
-- Password is 'Abbi+1209' hashed using PHP's password_hash()
INSERT INTO users (username, password, email, full_name, role, status) VALUES
('abbishanth1209@gmail.com', '$2y$10$CBOCGlKLfCoHt/BKUdlf9OEgHV6gvdlTMsk8U.1qDKe8U.RQH5F5m', 'abbishanth1209@gmail.com', 'System Administrator', 'admin', 'active');

-- Note: The password hash above corresponds to 'Abbi+1209'
-- You can generate new hashes using PHP: password_hash('your_password', PASSWORD_DEFAULT)

-- Display created user
SELECT id, username, email, full_name, role, status, created_at FROM users;
