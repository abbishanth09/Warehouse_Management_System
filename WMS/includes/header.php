<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Warehouse Management System'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1>🏭 Warehouse Management System</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php" class="nav-btn glass <?php echo ($currentPage === 'home') ? 'active' : ''; ?>">
                        <span>🏠 Home</span>
                    </a></li>
                    <li><a href="inventory.php" class="nav-btn glass <?php echo ($currentPage === 'inventory') ? 'active' : ''; ?>">
                        <span>📦 Inventory</span>
                    </a></li>
                    <li><a href="orders.php" class="nav-btn glass <?php echo ($currentPage === 'orders') ? 'active' : ''; ?>">
                        <span>📋 Orders</span>
                    </a></li>
                    <li><a href="reports.php" class="nav-btn glass <?php echo ($currentPage === 'reports') ? 'active' : ''; ?>">
                        <span>📊 Reports</span>
                    </a></li>
                    <li><a href="about.php" class="nav-btn glass <?php echo ($currentPage === 'about') ? 'active' : ''; ?>">
                        <span>🏢 About Us</span>
                    </a></li>
                    <li>
                        <span class="user-info">👤 <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                        <a href="logout.php" class="nav-btn glass logout" onclick="return confirm('Do you need to log out?')">
                            <span>🚪 Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
    
    <main>
        <?php
        // Display flash messages
        $flash = getFlashMessage();
        if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>
