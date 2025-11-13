<?php
/**
 * Advanced Gmail SMTP Implementation
 * Uses direct socket connection to Gmail SMTP
 */

// Gmail SMTP Configuration
$GMAIL_USERNAME = 'your-email@example.com';
$GMAIL_PASSWORD = 'your_app_password_here';
$GMAIL_FROM_NAME = 'WMS System';

/**
 * Send email using Gmail SMTP with socket connection (Advanced Version)
 */
function sendGmailOTPAdvanced($to, $otp, $username = 'User') {
    global $GMAIL_USERNAME, $GMAIL_PASSWORD, $GMAIL_FROM_NAME;
    
    // Gmail SMTP settings
    $smtp_host = 'smtp.gmail.com';
    $smtp_port = 587;
    
    // Create email content
    $subject = "Password Reset OTP - WMS";
    $message = createSimpleEmailTemplate($otp, $username);
    
    try {
        // Create socket connection
        $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 30);
        if (!$socket) {
            throw new Exception("Failed to connect to Gmail SMTP: $errstr ($errno)");
        }
        
        // Read initial response
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '220') {
            throw new Exception("SMTP Error: $response");
        }
        
        // Send EHLO command
        fputs($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 512);
        
        // Start TLS
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '220') {
            throw new Exception("STARTTLS failed: $response");
        }
        
        // Enable crypto
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception("Failed to enable TLS encryption");
        }
        
        // Send EHLO again after TLS
        fputs($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 512);
        
        // Authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '334') {
            throw new Exception("AUTH LOGIN failed: $response");
        }
        
        // Send username (base64 encoded)
        fputs($socket, base64_encode($GMAIL_USERNAME) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '334') {
            throw new Exception("Username authentication failed: $response");
        }
        
        // Send password (base64 encoded)
        fputs($socket, base64_encode($GMAIL_PASSWORD) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '235') {
            throw new Exception("Password authentication failed: $response");
        }
        
        // Set sender
        fputs($socket, "MAIL FROM: <$GMAIL_USERNAME>\r\n");
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
            throw new Exception("DATA command failed: $response");
        }
        
        // Send email headers and body
        $email_data = "From: $GMAIL_FROM_NAME <$GMAIL_USERNAME>\r\n";
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
            throw new Exception("Email sending failed: $response");
        }
        
        // Quit
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        // Log success
        logEmailAttempt($to, $otp, 'GMAIL_SUCCESS_SMTP');
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
    
    // Also log it
    logEmailAttempt($to, $otp, 'LOCAL_INBOX');
    
    return true;
}

/**
 * Simple email template
 */
function createSimpleEmailTemplate($otp, $username) {
    return "
<html>
<body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px;'>
        <h2>🔐 Password Reset OTP</h2>
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
?>
