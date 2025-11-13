<?php
/**
 * Database Connection for WMS
 * MySQL database connection using PDO
 */

// Database configuration
$host = 'localhost';
$dbname = 'warehouse_management';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    
    // Show user-friendly error page
    die("
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Connection Error - WMS</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 50px; text-align: center; }
            .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .error-icon { font-size: 48px; color: #dc3545; margin-bottom: 20px; }
            h1 { color: #dc3545; margin-bottom: 20px; }
            p { color: #666; font-size: 16px; line-height: 1.6; margin-bottom: 20px; }
            .error-code { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; color: #e83e8c; margin: 20px 0; }
            .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <div class='error-icon'>🚫</div>
            <h1>Database Connection Error</h1>
            <p>Unable to connect to the WMS database. This could be due to:</p>
            <ul style='text-align: left; color: #666;'>
                <li>MySQL server is not running</li>
                <li>Database 'wms_db' does not exist</li>
                <li>Incorrect database credentials</li>
                <li>Network connectivity issues</li>
            </ul>
            <div class='error-code'>Error: " . htmlspecialchars($e->getMessage()) . "</div>
            <p><strong>To fix this:</strong></p>
            <ol style='text-align: left; color: #666;'>
                <li>Make sure XAMPP MySQL is running</li>
                <li>Check if database 'wms_db' exists in phpMyAdmin</li>
                <li>Verify database credentials in includes/db_connection.php</li>
            </ol>
            <a href='http://localhost/phpmyadmin' class='btn'>Open phpMyAdmin</a>
        </div>
    </body>
    </html>
    ");
}

// Test the connection (optional - remove in production)
if (isset($_GET['test_db'])) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '$dbname'");
        $result = $stmt->fetch();
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px; color: #155724;'>";
        echo "<h3>✅ Database Connection Successful!</h3>";
        echo "<p><strong>Database:</strong> $dbname</p>";
        echo "<p><strong>Host:</strong> $host</p>";
        echo "<p><strong>Tables found:</strong> " . $result['table_count'] . "</p>";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px; color: #721c24;'>";
        echo "<h3>❌ Database Test Failed!</h3>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}
?>
