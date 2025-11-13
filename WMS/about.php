<?php
/**
 * About Us Page
 * Information about the Warehouse Management System
 */

// Check authentication
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$title = 'About Us - Warehouse Management System';
$currentPage = 'about';
includeHeader($title, $currentPage);
?>

<div class="container">
    <h1 class="page-title">🏢 About Our Warehouse Management System</h1>
    
    <!-- Company Overview -->
    <div class="card">
        <h2>🎯 Our Mission</h2>
        <p style="font-size: 1.1rem; line-height: 1.8; color: #555;">
            We are dedicated to providing comprehensive warehouse management solutions that streamline 
            inventory operations, enhance efficiency, and drive business growth. Our system empowers 
            businesses to manage their warehouse operations with precision and ease.
        </p>
    </div>

    <!-- System Features -->
    <div class="card">
        <h2>⚡ What We Offer</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 1.5rem;">
            <div style="padding: 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px;">
                <h3 style="color: white; margin-bottom: 1rem;">📦 Inventory Management</h3>
                <ul style="line-height: 1.8;">
                    <li>Real-time stock tracking</li>
                    <li>Automatic stock alerts</li>
                    <li>Product categorization</li>
                    <li>SKU generation</li>
                    <li>Location tracking</li>
                </ul>
            </div>
            
            <div style="padding: 1.5rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 10px;">
                <h3 style="color: white; margin-bottom: 1rem;">📋 Order Processing</h3>
                <ul style="line-height: 1.8;">
                    <li>Inbound & outbound orders</li>
                    <li>Automatic inventory updates</li>
                    <li>Order status tracking</li>
                    <li>Customer/supplier management</li>
                    <li>Email & phone validation</li>
                </ul>
            </div>
            
            <div style="padding: 1.5rem; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border-radius: 10px;">
                <h3 style="color: white; margin-bottom: 1rem;">📊 Reporting & Analytics</h3>
                <ul style="line-height: 1.8;">
                    <li>Comprehensive reports</li>
                    <li>Transaction history</li>
                    <li>Stock level analytics</li>
                    <li>Order value tracking</li>
                    <li>Performance metrics</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Technology Stack -->
    <div class="card">
        <h2>🔧 Technology Stack</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
            <div style="text-align: center; padding: 1rem;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">🐘</div>
                <h4>PHP 8+</h4>
                <p style="color: #666;">Server-side scripting for robust backend functionality</p>
            </div>
            
            <div style="text-align: center; padding: 1rem;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">🗄️</div>
                <h4>MySQL Database</h4>
                <p style="color: #666;">Reliable data storage with ACID compliance</p>
            </div>
            
            <div style="text-align: center; padding: 1rem;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">🎨</div>
                <h4>Modern CSS</h4>
                <p style="color: #666;">Responsive design with clean, intuitive interface</p>
            </div>
            
            <div style="text-align: center; padding: 1rem;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">⚡</div>
                <h4>JavaScript</h4>
                <p style="color: #666;">Dynamic interactions and real-time validation</p>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h2 style="color: white;">📞 Get In Touch</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 1.5rem;">
            <div>
                <h4 style="color: white;">💼 Business Inquiries</h4>
                <p>Email: business@wms-system.com</p>
                <p>Phone: +94 77 123 4567</p>
            </div>
            
            <div>
                <h4 style="color: white;">🛠️ Technical Support</h4>
                <p>Email: support@wms-system.com</p>
                <p>Phone: +94 77 765 4321</p>
            </div>
            
            <div>
                <h4 style="color: white;">🌐 Address</h4>
                <p>123 Technology Street<br>
                Colombo 03, Sri Lanka<br>
                Business Hours: 9 AM - 6 PM</p>
            </div>
        </div>
    </div>

    <!-- Footer Note -->
    <div style="text-align: center; margin: 3rem 0; color: #666;">
        <p style="font-size: 1.1rem;">
            <strong>Thank you for choosing our Warehouse Management System!</strong><br>
            We're committed to helping your business succeed through efficient warehouse operations.
        </p>
    </div>
</div>

<style>
.card {
    margin-bottom: 2rem;
}

.card h2 {
    color: #2c3e50;
    border-bottom: 3px solid #3498db;
    padding-bottom: 0.5rem;
    margin-bottom: 1.5rem;
}

.card h4 {
    margin-bottom: 0.5rem;
}

.card ul {
    padding-left: 1.5rem;
}

.card li {
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .container {
        padding: 1rem;
    }
    
    .card {
        padding: 1.5rem;
    }
}
</style>

<?php includeFooter(); ?>
