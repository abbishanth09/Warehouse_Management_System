<?php
/**
 * Email Configuration and Test Script
 * This script helps configure and test email functionality
 */

require_once 'includes/email_service.php';

// Email configuration test
echo "<h2>🔧 Email Configuration Test</h2>";

// Check email configuration
$config = checkEmailConfiguration();
echo "<h3>Configuration Status:</h3>";
echo "<ul>";
foreach ($config['messages'] as $message) {
    echo "<li>⚠️ $message</li>";
}
echo "</ul>";

// Show current PHP mail settings
echo "<h3>Current PHP Mail Settings:</h3>";
echo "<ul>";
echo "<li><strong>SMTP:</strong> " . (ini_get('SMTP') ?: 'Not set') . "</li>";
echo "<li><strong>SMTP Port:</strong> " . (ini_get('smtp_port') ?: 'Not set') . "</li>";
echo "<li><strong>Sendmail From:</strong> " . (ini_get('sendmail_from') ?: 'Not set') . "</li>";
echo "<li><strong>Sendmail Path:</strong> " . (ini_get('sendmail_path') ?: 'Not set') . "</li>";
echo "</ul>";

// Test email form
if (isset($_POST['test_email'])) {
    $test_email = $_POST['email'];
    echo "<h3>📧 Sending Test Email...</h3>";
    
    $result = sendTestEmail($test_email);
    echo "<p style='padding: 10px; background: #e8f5e8; border: 1px solid #4caf50; border-radius: 5px;'>$result</p>";
    
    // Show log content
    if (file_exists('logs/email_log.txt')) {
        echo "<h4>📋 Email Log (Last 5 entries):</h4>";
        $log_content = file_get_contents('logs/email_log.txt');
        $log_lines = explode("\n", $log_content);
        $recent_lines = array_slice($log_lines, -20); // Last 20 lines
        echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars(implode("\n", $recent_lines));
        echo "</pre>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Configuration Test - WMS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        h3 {
            color: #34495e;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        .test-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border: 1px solid #dee2e6;
        }
        input[type="email"] {
            width: 100%;
            max-width: 300px;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        ul {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-form">
            <h3>📧 Test Email Sending</h3>
            <form method="POST">
                <p>Enter your email address to test the OTP email system:</p>
                <input type="email" name="email" placeholder="your-email@example.com" required>
                <br><br>
                <button type="submit" name="test_email" class="btn">Send Test Email</button>
            </form>
        </div>

        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffeaa7; margin: 20px 0;">
            <h4>📝 Email Setup Options:</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                <div>
                    <h5>🎯 Current Status:</h5>
                    <ul>
                        <li>✅ Local inbox working</li>
                        <li>⏳ Gmail SMTP needs setup</li>
                        <li>📧 Emails saved locally for now</li>
                    </ul>
                </div>
                <div>
                    <h5>🚀 Next Steps:</h5>
                    <ul>
                        <li><a href="gmail_setup.php" style="color: #007bff; text-decoration: none;">📧 Setup Gmail SMTP</a></li>
                        <li><a href="inbox.php" style="color: #007bff; text-decoration: none;">📥 View Local Inbox</a></li>
                        <li>🔧 Test real email delivery</li>
                    </ul>
                </div>
            </div>
        </div>

        <div style="background: #d4edda; padding: 15px; border-radius: 8px; border: 1px solid #c3e6cb; margin: 20px 0;">
            <h4>✨ How to Get Real Gmail Delivery:</h4>
            <ol>
                <li><strong>Quick Setup:</strong> <a href="gmail_setup.php" style="color: #28a745; font-weight: bold;">Follow Gmail Setup Guide</a></li>
                <li><strong>Enable 2FA:</strong> On your Gmail account</li>
                <li><strong>Create App Password:</strong> For the WMS system</li>
                <li><strong>Update Config:</strong> Add your credentials to email service</li>
                <li><strong>Test:</strong> Use this page to verify delivery</li>
            </ol>
        </div>

        <a href="forgot_password.php" class="back-link">← Back to Forgot Password</a>
        <a href="login.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>
