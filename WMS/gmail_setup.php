<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail SMTP Setup - WMS</title>
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
            font-size: 2.5em;
        }
        h2 {
            color: #34495e;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-top: 40px;
        }
        .step {
            background: #f8f9fa;
            border-left: 5px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 10px 10px 0;
        }
        .step h3 {
            color: #28a745;
            margin-top: 0;
        }
        .code-block {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 15px 0;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
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
            margin: 10px 5px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .screenshot {
            border: 2px solid #ddd;
            border-radius: 8px;
            max-width: 100%;
            margin: 10px 0;
        }
        ol {
            counter-reset: step-counter;
        }
        ol li {
            counter-increment: step-counter;
            margin: 10px 0;
            padding-left: 10px;
        }
        .highlight {
            background: #ffeb3b;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Gmail SMTP Setup Guide</h1>
        
        <div class="info">
            <h3>🎯 What This Setup Does</h3>
            <p>This guide will help you configure Gmail SMTP so your WMS system can send real OTP emails to Gmail addresses instead of just saving them to the local inbox.</p>
        </div>

        <h2>📋 Prerequisites</h2>
        <ul>
            <li>A Gmail account</li>
            <li>Access to your Google Account settings</li>
            <li>Ability to enable 2-Factor Authentication</li>
        </ul>

        <h2>🚀 Step-by-Step Setup</h2>

        <div class="step">
            <h3>Step 1: Enable 2-Factor Authentication</h3>
            <ol>
                <li>Go to <a href="https://myaccount.google.com" target="_blank">https://myaccount.google.com</a></li>
                <li>Click on <span class="highlight">"Security"</span> in the left sidebar</li>
                <li>Find <span class="highlight">"2-Step Verification"</span> and click <span class="highlight">"Get started"</span></li>
                <li>Follow the prompts to set up 2-factor authentication using your phone</li>
                <li>Complete the setup process</li>
            </ol>
            <div class="warning">
                <strong>⚠️ Important:</strong> You MUST enable 2-Factor Authentication before you can create App Passwords.
            </div>
        </div>

        <div class="step">
            <h3>Step 2: Create an App Password</h3>
            <ol>
                <li>After enabling 2FA, go back to <span class="highlight">"Security"</span></li>
                <li>Under <span class="highlight">"2-Step Verification"</span>, click on it to enter</li>
                <li>Scroll down and find <span class="highlight">"App passwords"</span></li>
                <li>Click on <span class="highlight">"App passwords"</span></li>
                <li>You might need to sign in again</li>
                <li>Select <span class="highlight">"Mail"</span> for the app</li>
                <li>Select <span class="highlight">"Windows Computer"</span> for the device</li>
                <li>Click <span class="highlight">"Generate"</span></li>
                <li>Copy the 16-character password (it looks like: <code>abcd efgh ijkl mnop</code>)</li>
            </ol>
            <div class="success">
                <strong>✅ Important:</strong> Save this App Password securely! You won't be able to see it again.
            </div>
        </div>

        <div class="step">
            <h3>Step 3: Update WMS Configuration</h3>
            <ol>
                <li>Open the file: <code>includes/email_service.php</code></li>
                <li>Find this section (around line 20):</li>
            </ol>
            <div class="code-block">
// Gmail SMTP Configuration
// UPDATE THESE WITH YOUR ACTUAL GMAIL CREDENTIALS:
$gmail_user = 'your-email@gmail.com';        // Replace with your Gmail address
$gmail_password = 'your-app-password';       // Replace with your Gmail App Password
            </div>
            <ol start="3">
                <li>Replace <span class="highlight">'your-email@gmail.com'</span> with your actual Gmail address</li>
                <li>Replace <span class="highlight">'your-app-password'</span> with the 16-character App Password from Step 2</li>
                <li>Save the file</li>
            </ol>
            
            <h4>Example:</h4>
            <div class="code-block">
// Gmail SMTP Configuration  
$gmail_user = 'john.doe@gmail.com';          // Your actual Gmail
$gmail_password = 'abcd efgh ijkl mnop';     // Your App Password (remove spaces)
            </div>
        </div>

        <div class="step">
            <h3>Step 4: Test the Configuration</h3>
            <ol>
                <li>Go to the <a href="email_test.php" target="_blank">Email Test Page</a></li>
                <li>Enter your Gmail address</li>
                <li>Click <span class="highlight">"Send Test Email"</span></li>
                <li>Check both your Gmail inbox AND spam folder</li>
                <li>Also check the local inbox as backup</li>
            </ol>
        </div>

        <h2>🔧 Troubleshooting</h2>

        <div class="warning">
            <h4>Common Issues:</h4>
            <ul>
                <li><strong>Still going to local inbox:</strong> Check that Gmail credentials are properly updated (not the default values)</li>
                <li><strong>Authentication failed:</strong> Make sure you're using the App Password, not your regular Gmail password</li>
                <li><strong>Connection timeout:</strong> Check your internet connection and firewall settings</li>
                <li><strong>No email received:</strong> Check spam folder, and verify the email address is correct</li>
            </ul>
        </div>

        <div class="info">
            <h4>How It Works:</h4>
            <ol>
                <li>System tries Gmail SMTP first</li>
                <li>If Gmail fails, tries regular PHP mail()</li>
                <li>If both fail, saves to local inbox as backup</li>
                <li>You can always check the local inbox at <a href="inbox.php">inbox.php</a></li>
            </ol>
        </div>

        <h2>🛡️ Security Notes</h2>
        
        <div class="success">
            <ul>
                <li>App Passwords are more secure than your regular password</li>
                <li>You can revoke App Passwords anytime from your Google Account</li>
                <li>Each App Password is unique to one application</li>
                <li>If compromised, revoke the App Password without changing your main password</li>
            </ul>
        </div>

        <div class="step">
            <h3>Quick Links:</h3>
            <a href="email_test.php" class="btn">📧 Test Email System</a>
            <a href="inbox.php" class="btn">📥 View Local Inbox</a>
            <a href="forgot_password.php" class="btn">🔐 Test Forgot Password</a>
            <a href="login.php" class="btn">🏠 Back to Login</a>
        </div>

        <div class="info">
            <h4>💡 Pro Tip:</h4>
            <p>After successful Gmail setup, you can remove the local inbox system if you want. But it's recommended to keep it as a backup for development and testing.</p>
        </div>
    </div>
</body>
</html>
