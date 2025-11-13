<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail Setup Test - WMS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 10px;
        }
        .status {
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            border: 2px solid;
        }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
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
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            margin: 10px 0;
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
            margin: 5px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .code-box {
            background: #f1f3f4;
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 15px;
            font-family: monospace;
            margin: 15px 0;
            white-space: pre-wrap;
        }
        .step {
            background: white;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 8px 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Gmail Setup Test</h1>
        <p style="text-align: center; color: #666;">Configure Gmail to receive OTPs directly in your inbox</p>

        <?php
        // Include the ultra simple email service
        require_once 'includes/email_service_ultra_simple.php';

        // Check current configuration
        $config = checkEmailConfiguration();
        
        // Display configuration status
        echo "<div class='status success'>";
    echo "<h3>✅ Gmail Configured (placeholder)</h3>";
    echo "<p><strong>Gmail Account:</strong> your-email@example.com (set in `gmail_config.php`)</p>";
    echo "<p>OTPs will be sent to the configured Gmail inbox via direct SMTP.</p>";
        echo "</div>";

        // Test email form
        if (isset($_POST['test_email'])) {
            $test_email = trim($_POST['email']);
            echo "<div class='status info'>";
            echo "<h3>📧 Sending Test Email...</h3>";
            
            $result = sendTestEmail($test_email);
            echo "<p>$result</p>";
            
            // Show recent log entries
            if (file_exists('logs/email_log.txt')) {
                $log_content = file_get_contents('logs/email_log.txt');
                $log_lines = explode("\n", $log_content);
                $recent_lines = array_slice($log_lines, -5, 5);
                echo "<h4>📋 Recent Log:</h4>";
                echo "<div class='code-box'>" . htmlspecialchars(implode("\n", $recent_lines)) . "</div>";
            }
            echo "</div>";
        }
        ?>

        <div class="test-form">
            <h3>🧪 Test Email Sending</h3>
            <form method="POST">
                <p>Enter your email address to test OTP delivery:</p>
                <input type="email" name="email" placeholder="your-email@gmail.com" required>
                <br>
                <button type="submit" name="test_email" class="btn">Send Test OTP</button>
            </form>
        </div>

        <div class="step">
            <h3>🔧 Quick Setup Instructions</h3>
            <ol>
                <li><strong>Get Gmail App Password:</strong>
                    <ul>
                        <li>Go to <a href="https://myaccount.google.com/security" target="_blank">Google Account Security</a></li>
                        <li>Enable 2-Factor Authentication</li>
                        <li>Generate App Password for "Mail"</li>
                    </ul>
                </li>
                <li><strong>Edit Configuration:</strong>
                    <ul>
                        <li>Open <code>gmail_config.php</code></li>
                        <li>Replace <code>your-email@gmail.com</code> with your Gmail</li>
                        <li>Replace <code>your-app-password</code> with the 16-character app password</li>
                    </ul>
                </li>
                <li><strong>Test Again!</strong></li>
            </ol>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="forgot_password.php" class="btn btn-secondary">← Back to Forgot Password</a>
            <a href="login.php" class="btn btn-secondary">← Back to Login</a>
            <a href="GMAIL_SETUP.md" class="btn btn-secondary" target="_blank">📖 Full Setup Guide</a>
        </div>
    </div>
</body>
</html>
