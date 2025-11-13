<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Working Gmail Test - WMS</title>
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
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 10px;
        }
        .status {
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border: 2px solid;
        }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .test-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
            border: 1px solid #dee2e6;
        }
        input[type="email"] {
            width: 100%;
            max-width: 350px;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            margin: 15px 0;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
            margin: 10px;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .preview-box {
            background: #f1f3f4;
            border: 2px solid #dadce0;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .log-box {
            background: #1e1e1e;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        iframe {
            width: 100%;
            height: 500px;
            border: 2px solid #ddd;
            border-radius: 10px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Working Gmail SMTP Test</h1>
        <p style="text-align: center; color: #666; font-size: 18px;">
            Real Gmail delivery with proper TLS authentication
        </p>

        <?php
        require_once 'includes/email_service_working.php';
        
        // Display configuration status
        echo "<div class='status success'>";
    echo "<h3>✅ Gmail SMTP (configure locally)</h3>";
    echo "<p><strong>Email:</strong> your-email@example.com (set in `includes/email_service_working.php` or a local ignored config)</p>";
    echo "<p><strong>Authentication:</strong> App Password — store in an ignored config, do NOT commit</p>";
    echo "<p><strong>Encryption:</strong> TLS on port 587</p>";
    echo "<p><strong>Features:</strong> HTML emails + Local previews</p>";
        echo "</div>";

        // Handle test email
        if (isset($_POST['test_email'])) {
            $test_email = trim($_POST['email']);
            echo "<div class='status info'>";
            echo "<h3>🚀 Sending Real Email to Gmail...</h3>";
            
            $result = sendTestEmail($test_email);
            echo "<div style='font-size: 16px; font-weight: bold;'>$result</div>";
            
            // Show log
            if (file_exists('logs/email_log.txt')) {
                echo "<h4>📋 Live Log:</h4>";
                $log_content = file_get_contents('logs/email_log.txt');
                $log_lines = explode("\n", $log_content);
                $recent_lines = array_slice($log_lines, -8, 8);
                echo "<div class='log-box'>" . htmlspecialchars(implode("\n", $recent_lines)) . "</div>";
            }
            
            // Show email preview
            if (is_dir('email_previews')) {
                $files = glob('email_previews/*.html');
                if ($files) {
                    $latest = array_pop($files);
                    echo "<h4>📄 Email Preview (What was sent to Gmail):</h4>";
                    echo "<iframe src='$latest'></iframe>";
                    echo "<p><strong>File:</strong> <a href='$latest' target='_blank'>$latest</a></p>";
                }
            }
            
            echo "</div>";
        }
        ?>

        <div class="test-form">
            <h3>🧪 Send Real Email to Gmail</h3>
            <p>This will send an actual email to your Gmail inbox using proper SMTP authentication:</p>
            
            <form method="POST">
                <input type="email" name="email" value="your-email@example.com" placeholder="your-email@gmail.com" required>
                <br>
                <button type="submit" name="test_email" class="btn">📧 Send Real Email to Gmail</button>
            </form>
        </div>

        <div class="preview-box">
            <h3>🎯 What This Test Does:</h3>
            <ul style="font-size: 16px; line-height: 1.8;">
                <li>✅ <strong>Connects to Gmail SMTP</strong> using TLS encryption</li>
                <li>✅ <strong>Authenticates</strong> with your app password</li>
                <li>✅ <strong>Sends beautiful HTML email</strong> with OTP</li>
                <li>✅ <strong>Creates local preview</strong> so you can see what was sent</li>
                <li>✅ <strong>Logs everything</strong> for troubleshooting</li>
                <li>✅ <strong>Real Gmail delivery</strong> - check your inbox!</li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="forgot_password.php" class="btn btn-secondary">🔑 Test Forgot Password</a>
            <a href="login.php" class="btn btn-secondary">🏠 Back to Login</a>
        </div>
    </div>
</body>
</html>
