<?php
/**
 * Local Email Inbox Viewer
 * Shows OTP emails that were saved locally
 */

require_once 'includes/email_local.php';

// Handle email deletion
if (isset($_GET['delete']) && isset($_GET['file'])) {
    $file_to_delete = $_GET['file'];
    if (file_exists($file_to_delete) && strpos($file_to_delete, 'inbox/') === 0) {
        unlink($file_to_delete);
        // Also delete corresponding HTML file
        $html_file = str_replace('OTP_QUICK_', 'OTP_', $file_to_delete);
        $html_file = str_replace('.txt', '.html', $html_file);
        if (file_exists($html_file)) {
            unlink($html_file);
        }
        header('Location: inbox.php?deleted=1');
        exit;
    }
}

// Get recent emails
$recent_emails = getRecentOTPEmails(20);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Email Inbox - WMS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
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
        .email-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .email-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        .email-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .email-time {
            color: #666;
            font-size: 14px;
        }
        .email-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            white-space: pre-line;
        }
        .otp-highlight {
            background: #fff3cd;
            color: #856404;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .btn-view {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .empty-inbox {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        .stats {
            background: #e7f3ff;
            border: 1px solid #2196f3;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .navigation {
            text-align: center;
            margin: 30px 0;
        }
        .navigation a {
            margin: 0 10px;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #b8daff;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Local Email Inbox</h1>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">✅ Email deleted successfully!</div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <strong>ℹ️ Info:</strong> This inbox shows OTP emails that were saved locally because the email server is not configured. 
            In production, these would be sent to actual email addresses.
        </div>
        
        <div class="stats">
            <h3>📊 Inbox Statistics</h3>
            <p><strong>Total OTP Emails:</strong> <?php echo count($recent_emails); ?></p>
            <p><strong>Storage Location:</strong> <code>inbox/</code> directory</p>
            <p><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        
        <?php if (empty($recent_emails)): ?>
            <div class="empty-inbox">
                <h3>📭 Inbox is Empty</h3>
                <p>No OTP emails have been generated yet.</p>
                <p>Try the forgot password feature to generate test emails.</p>
                <a href="forgot_password.php" class="btn">🔐 Test Forgot Password</a>
            </div>
        <?php else: ?>
            <h3>📬 Recent OTP Emails (<?php echo count($recent_emails); ?>)</h3>
            
            <?php foreach ($recent_emails as $email): ?>
                <div class="email-item">
                    <div class="email-header">
                        <strong>OTP Email</strong>
                        <div>
                            <span class="email-time"><?php echo date('Y-m-d H:i:s', $email['time']); ?></span>
                            <a href="<?php echo str_replace('OTP_QUICK_', 'OTP_', str_replace('.txt', '.html', $email['file'])); ?>" 
                               class="btn btn-view" target="_blank">👁️ View Full Email</a>
                            <a href="?delete=1&file=<?php echo urlencode($email['file']); ?>" 
                               class="btn btn-danger" onclick="return confirm('Delete this email?')">🗑️ Delete</a>
                        </div>
                    </div>
                    
                    <div class="email-content"><?php 
                        $content = $email['content'];
                        // Highlight OTP code
                        $content = preg_replace('/OTP CODE: (\d{6})/', 'OTP CODE: <span class="otp-highlight">$1</span>', $content);
                        echo $content; 
                    ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="navigation">
            <a href="forgot_password.php" class="btn">🔐 Test Forgot Password</a>
            <a href="email_setup.php" class="btn">⚙️ Email Setup</a>
            <a href="login.php" class="btn" style="background: #6c757d;">← Back to Login</a>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px; text-align: center;">
            <h4>🔧 Quick Actions</h4>
            <p>
                <a href="?clear_all=1" class="btn btn-danger" onclick="return confirm('Clear all emails from inbox?')">🗑️ Clear All Emails</a>
                <a href="email_test.php" class="btn">🧪 Test Email System</a>
            </p>
        </div>
    </div>
</body>
</html>

<?php
// Handle clear all emails
if (isset($_GET['clear_all'])) {
    $files = glob('inbox/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    header('Location: inbox.php?cleared=1');
    exit;
}
?>
