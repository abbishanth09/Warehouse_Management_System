<?php
// Set PHP mail configuration for Windows
ini_set('SMTP', 'smtp.gmail.com');
ini_set('smtp_port', '587');
ini_set('sendmail_from', 'your-email@example.com');

// Include the email service
require_once 'includes/email_service_ultra_simple.php';

echo "<h2>🧪 Direct Email Test</h2>";
echo "<p>Testing direct email sending...</p>";

if (isset($_GET['test'])) {
    echo "<h3>📧 Sending test email...</h3>";
    
    $result = sendTestEmail('your-email@example.com');
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; border: 1px solid #4caf50;'>";
    echo $result;
    echo "</div>";
    
    echo "<h4>📁 Check these locations:</h4>";
    echo "<ul>";
    echo "<li><strong>Gmail Inbox:</strong> Check for the actual email</li>";
    echo "<li><strong>Gmail Spam:</strong> Check spam folder</li>";
    echo "<li><strong>sent_emails folder:</strong> HTML preview of the email</li>";
    echo "<li><strong>local_inbox folder:</strong> Backup copy</li>";
    echo "</ul>";
    
    if (file_exists('logs/email_log.txt')) {
        echo "<h4>📋 Recent Log:</h4>";
        $log = file_get_contents('logs/email_log.txt');
        $lines = explode("\n", $log);
        $recent = array_slice($lines, -5);
        echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 5px;'>";
        echo htmlspecialchars(implode("\n", $recent));
        echo "</pre>";
    }
    
    if (is_dir('sent_emails')) {
        $files = glob('sent_emails/*.html');
        if ($files) {
            $latest = array_pop($files);
            echo "<h4>📄 Latest Sent Email Preview:</h4>";
            echo "<iframe src='$latest' style='width: 100%; height: 400px; border: 1px solid #ddd; border-radius: 5px;'></iframe>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Direct Email Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>📧 Direct Email Test</h1>
    
    <?php if (!isset($_GET['test'])): ?>
    <p>This will test email sending with simplified configuration.</p>
    <a href="?test=1" class="btn">🚀 Send Test Email</a>
    <?php endif; ?>
    
    <p><a href="gmail_test.php">← Back to Gmail Test</a></p>
</body>
</html>
