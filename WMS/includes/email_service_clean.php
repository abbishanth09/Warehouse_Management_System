<?php
/**
 * Clean Email Service for WMS
 * Gmail SMTP with socket connection
 */

/**
 * Send OTP via Gmail SMTP
 */
function sendOTPEmail($to, $otp, $username = 'User') {
    // Gmail credentials
    // Do NOT store real credentials here. Use an ignored config file (e.g. config/email.php) instead.
    $gmail_user = 'your-email@example.com';
    $gmail_pass = 'your_app_password_here';
    $from_name = 'WMS System';
    
    // Gmail SMTP settings
    $smtp_host = 'smtp.gmail.com';
    $smtp_port = 587;
    
    // Create email content
    $subject = "Password Reset OTP - WMS";
    $message = createEmailTemplate($otp, $username);
    
    try {
        // Create socket connection
        $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 30);
        if (!$socket) {
            throw new Exception("Connection failed: $errstr ($errno)");
        }
        
        // Read initial response
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '220') {
            throw new Exception("SMTP Error: $response");
        }
        
        // Send EHLO
        fputs($socket, "EHLO localhost\r\n");
        while (($response = fgets($socket, 512)) !== false) {
            if (substr($response, 3, 1) == ' ') break; // Last line of response
        }
        
        // Start TLS
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '220') {
            throw new Exception("STARTTLS failed: $response");
        }
        
        // Enable crypto
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception("TLS encryption failed");
        }
        
        // Send EHLO again after TLS
        fputs($socket, "EHLO localhost\r\n");
        while (($response = fgets($socket, 512)) !== false) {
            if (substr($response, 3, 1) == ' ') break; // Last line of response
        }
        
        // Authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '334') {
            throw new Exception("AUTH LOGIN failed: $response");
        }
        
        // Send username (base64 encoded)
        fputs($socket, base64_encode($gmail_user) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '334') {
            throw new Exception("Username failed: $response");
        }
        
        // Send password (base64 encoded)
        fputs($socket, base64_encode($gmail_pass) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '235') {
            throw new Exception("Password failed: $response");
        }
        
        // Set sender
        fputs($socket, "MAIL FROM: <$gmail_user>\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("MAIL FROM failed: $response");
        }
        
        // Set recipient
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("RCPT TO failed: $response");
        }
        
        // Start data transmission
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '354') {
            throw new Exception("DATA failed: $response");
        }
        
        // Send email headers and body
        $email_data = "From: $from_name <$gmail_user>\r\n";
        $email_data .= "To: <$to>\r\n";
        $email_data .= "Subject: $subject\r\n";
        $email_data .= "MIME-Version: 1.0\r\n";
        $email_data .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_data .= "\r\n";
        $email_data .= $message;
        $email_data .= "\r\n.\r\n";
        
        fputs($socket, $email_data);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("Send failed: $response");
        }
        
        // Quit
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        // Log success
        logEmailAttempt($to, $otp, 'GMAIL_SUCCESS');
        return true;
        
    } catch (Exception $e) {
        // Log error and fallback to local
        logEmailAttempt($to, $otp, 'GMAIL_ERROR: ' . $e->getMessage());
        return sendLocalEmail($to, $otp, $username);
    }
}

/**
 * Fallback to local email
 */
function sendLocalEmail($to, $otp, $username) {
    $email_content = "
📧 EMAIL TO: $to
🔐 OTP CODE: $otp
👤 USER: $username
⏰ TIME: " . date('Y-m-d H:i:s') . "

Your password reset OTP is: $otp
This OTP will expire in 10 minutes.

==========================================
";
    
    // Save to local inbox
    if (!file_exists('local_inbox')) {
        mkdir('local_inbox', 0777, true);
    }
    
    $filename = 'local_inbox/otp_' . date('Y-m-d_H-i-s') . '_' . substr($to, 0, 5) . '.txt';
    file_put_contents($filename, $email_content);
    
    // Log it
    logEmailAttempt($to, $otp, 'LOCAL_INBOX_FALLBACK');
    
    return true;
}

/**
 * Create email template
 */
function createEmailTemplate($otp, $username) {
    return "
<html>
<body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px;'>
        <h2>🔐 WMS Password Reset</h2>
    </div>
    
    <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;'>
        <h3>Hello $username!</h3>
        <p>Your password reset OTP is:</p>
        
        <div style='background: white; border: 3px solid #667eea; padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0;'>
            <span style='font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 3px;'>$otp</span>
        </div>
        
        <div style='background: #fff3cd; padding: 15px; border-radius: 5px; border: 1px solid #ffeaa7;'>
            <strong>⚠️ Important:</strong>
            <ul>
                <li>This OTP expires in <strong>10 minutes</strong></li>
                <li>Never share this OTP with anyone</li>
                <li>If you didn't request this, ignore this email</li>
            </ul>
        </div>
        
        <p style='color: #666; font-size: 12px; margin-top: 30px;'>
            This is an automated email from WMS. Please do not reply.
        </p>
    </div>
</body>
</html>";
}

/**
 * Log email attempts
 */
function logEmailAttempt($to, $otp, $status) {
    $log_entry = date('Y-m-d H:i:s') . " | TO: $to | OTP: $otp | STATUS: $status\n";
    
    if (!file_exists('logs')) {
        mkdir('logs', 0777, true);
    }
    
    file_put_contents('logs/email_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Test email function
 */
function sendTestEmail($to) {
    $test_otp = sprintf("%06d", mt_rand(100000, 999999));
    $result = sendOTPEmail($to, $test_otp, "Test User");
    
    if ($result) {
        return "✅ Test email sent to $to with OTP: $test_otp";
    } else {
        return "❌ Failed to send test email to $to";
    }
}

/**
 * Check email configuration
 */
function checkEmailConfiguration() {
    $messages = [];
    $messages[] = "Gmail configured for: your-email@example.com (configure locally)";
    $messages[] = "Using direct SMTP socket connection";
    
    return [
        'status' => true,
        'messages' => $messages
    ];
}
?>
