<?php
/**
 * Gmail SMTP Email Service for Windows XAMPP
 * This version uses socket connection to Gmail SMTP
 */

/**
 * Send email via Gmail SMTP using socket connection
 */
function sendOTPEmailGmail($to, $otp, $username = 'User', $gmail_user = '', $gmail_password = '') {
    // Gmail SMTP settings
    $smtp_server = 'smtp.gmail.com';
    $smtp_port = 587;
    $timeout = 30;
    
    // Validate inputs
    if (empty($gmail_user) || empty($gmail_password)) {
        logEmailAttempt($to, $otp, 'FAILED - Gmail credentials not provided');
        return false;
    }
    
    // Create email content
    $subject = "Password Reset OTP - WMS";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: WMS System <$gmail_user>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "\r\n";
    
    $body = createSimpleEmailTemplate($otp, $username);
    $email_data = $headers . $body;
    
    try {
        // Connect to Gmail SMTP
        $socket = fsockopen($smtp_server, $smtp_port, $errno, $errstr, $timeout);
        if (!$socket) {
            logEmailAttempt($to, $otp, "FAILED - Cannot connect to Gmail SMTP: $errstr ($errno)");
            return false;
        }
        
        // Read initial response
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '220') {
            logEmailAttempt($to, $otp, "FAILED - Invalid SMTP response: $response");
            fclose($socket);
            return false;
        }
        
        // Send EHLO command
        fputs($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 515);
        
        // Start TLS
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);
        
        // Enable crypto
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        
        // Send EHLO again after TLS
        fputs($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 515);
        
        // Authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, base64_encode($gmail_user) . "\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, base64_encode($gmail_password) . "\r\n");
        $response = fgets($socket, 515);
        
        if (substr($response, 0, 3) != '235') {
            logEmailAttempt($to, $otp, "FAILED - Gmail authentication failed: $response");
            fclose($socket);
            return false;
        }
        
        // Send email
        fputs($socket, "MAIL FROM: <$gmail_user>\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, $email_data . "\r\n.\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        logEmailAttempt($to, $otp, 'SUCCESS - Gmail SMTP');
        return true;
        
    } catch (Exception $e) {
        logEmailAttempt($to, $otp, "FAILED - Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Simple email template for compatibility
 */
function createSimpleEmailTemplate($otp, $username) {
    return "
    <html>
    <body style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>
        <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;'>
                <h1 style='margin: 0;'>🔐 WMS Password Reset</h1>
            </div>
            <div style='padding: 30px;'>
                <h2 style='color: #333;'>Hello $username!</h2>
                <p style='color: #666; font-size: 16px;'>You requested a password reset for your WMS account.</p>
                
                <div style='background: #f8f9fa; border: 2px solid #667eea; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0;'>
                    <h3 style='margin: 0 0 10px 0; color: #667eea;'>Your OTP Code</h3>
                    <div style='font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 3px; font-family: monospace;'>$otp</div>
                </div>
                
                <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0;'>
                    <strong>⚠️ Important:</strong>
                    <ul style='margin: 10px 0;'>
                        <li>This OTP expires in 10 minutes</li>
                        <li>Never share this code with anyone</li>
                        <li>If you didn't request this, ignore this email</li>
                    </ul>
                </div>
                
                <p style='color: #666; font-size: 14px;'>Need help? Contact your system administrator.</p>
            </div>
            <div style='background: #f8f9fa; padding: 15px; text-align: center; color: #666; font-size: 12px;'>
                © " . date('Y') . " WMS - Warehouse Management System
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Updated main sendOTPEmail function with Gmail support
 */
function sendOTPEmailWithGmail($to, $otp, $username = 'User') {
    // Gmail configuration - YOU NEED TO SET THESE!
    $gmail_user = '';  // Enter your Gmail address here
    $gmail_password = '';  // Enter your Gmail App Password here
    
    // If Gmail credentials are provided, try Gmail SMTP
    if (!empty($gmail_user) && !empty($gmail_password)) {
        $result = sendOTPEmailGmail($to, $otp, $username, $gmail_user, $gmail_password);
        if ($result) {
            return true;
        }
    }
    
    // Fallback to regular mail() function
    return sendOTPEmail($to, $otp, $username);
}
?>
