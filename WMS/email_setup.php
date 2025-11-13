<?php
/**
 * Email Setup Wizard for Windows XAMPP
 * This script helps configure email settings
 */

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Setup Wizard - WMS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
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
            margin-bottom: 30px;
        }
        .option-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        .option-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .option-title {
            color: #667eea;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .option-description {
            color: #666;
            margin-bottom: 15px;
        }
        .steps {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin: 10px 0;
            overflow-x: auto;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            color: #856404;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            color: #155724;
        }
        .test-section {
            background: #e7f3ff;
            border: 2px solid #2196f3;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Setup Wizard for WMS</h1>
        
        <div class="warning">
            <strong>⚠️ Current Issue:</strong> Sendmail is not configured for Windows. Choose one of the options below to enable email functionality.
        </div>

        <!-- Option 1: Gmail SMTP -->
        <div class="option-card">
            <div class="option-title">🚀 Option 1: Gmail SMTP (Recommended)</div>
            <div class="option-description">Use your Gmail account to send emails. This is the most reliable option.</div>
            
            <div class="steps">
                <strong>Setup Steps:</strong>
                <ol>
                    <li>Go to your Google Account settings</li>
                    <li>Enable 2-Factor Authentication</li>
                    <li>Generate an App Password for "Mail"</li>
                    <li>Update the configuration below</li>
                </ol>
            </div>

            <div class="code-block">
// Edit: includes/email_service.php
// Add these lines at the top:

$GMAIL_USER = 'your-email@gmail.com';      // Your Gmail address
$GMAIL_PASSWORD = 'your-app-password';     // Your Gmail App Password

// Then replace sendOTPEmail function call with:
// sendOTPEmailWithGmail($to, $otp, $username);
            </div>
            
            <a href="https://support.google.com/accounts/answer/185833" target="_blank" class="btn">📖 Gmail App Password Guide</a>
        </div>

        <!-- Option 2: Local SMTP Server -->
        <div class="option-card">
            <div class="option-title">🏠 Option 2: Local SMTP Server</div>
            <div class="option-description">Install a local SMTP server for testing purposes.</div>
            
            <div class="steps">
                <strong>Recommended: hMailServer</strong>
                <ol>
                    <li>Download hMailServer (free)</li>
                    <li>Install with default settings</li>
                    <li>Configure a domain (e.g., localhost.local)</li>
                    <li>Create an email account</li>
                    <li>Update PHP.ini settings</li>
                </ol>
            </div>

            <div class="code-block">
// PHP.ini settings for hMailServer:
SMTP = localhost
smtp_port = 25
sendmail_from = wms@localhost.local
            </div>
            
            <a href="https://www.hmailserver.com/download" target="_blank" class="btn">📥 Download hMailServer</a>
        </div>

        <!-- Option 3: Online Email Service -->
        <div class="option-card">
            <div class="option-title">☁️ Option 3: Online Email Service</div>
            <div class="option-description">Use professional email services for production.</div>
            
            <div class="steps">
                <strong>Recommended Services:</strong>
                <ul>
                    <li><strong>Mailtrap:</strong> Perfect for testing (free tier available)</li>
                    <li><strong>SendGrid:</strong> Professional email delivery</li>
                    <li><strong>Mailgun:</strong> Developer-friendly email API</li>
                </ul>
            </div>
            
            <a href="https://mailtrap.io/" target="_blank" class="btn">🔗 Try Mailtrap (Free)</a>
        </div>

        <!-- Quick Test Section -->
        <div class="test-section">
            <h3>🧪 Quick Test - Demo Mode</h3>
            <p>For now, you can test the forgot password system in demo mode:</p>
            
            <?php if (isset($_POST['test_demo'])): ?>
                <?php
                require_once 'includes/email_service.php';
                $test_result = sendTestEmail('test@example.com');
                ?>
                <div class="success">
                    <strong>Demo Test Result:</strong><br>
                    <?php echo $test_result; ?>
                    <br><br>
                    Check the log file: <code>logs/email_log.txt</code>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <button type="submit" name="test_demo" class="btn">🧪 Test Demo Email System</button>
            </form>
            
            <p><small>This will create a log entry showing what would be sent via email.</small></p>
        </div>

        <!-- Navigation -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="forgot_password.php" class="btn">🔐 Test Forgot Password</a>
            <a href="login.php" class="btn" style="background: #6c757d;">← Back to Login</a>
        </div>

        <!-- Current Configuration Display -->
        <div style="margin-top: 30px; background: #f8f9fa; padding: 20px; border-radius: 10px;">
            <h4>🔧 Current PHP Mail Configuration:</h4>
            <?php
            echo "<strong>SMTP Host:</strong> " . (ini_get('SMTP') ?: 'Not set') . "<br>";
            echo "<strong>SMTP Port:</strong> " . (ini_get('smtp_port') ?: 'Not set') . "<br>";
            echo "<strong>Sendmail From:</strong> " . (ini_get('sendmail_from') ?: 'Not set') . "<br>";
            echo "<strong>Mail Function:</strong> " . (function_exists('mail') ? '✅ Available' : '❌ Not Available') . "<br>";
            ?>
        </div>
    </div>
</body>
</html>
