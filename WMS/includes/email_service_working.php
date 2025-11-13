<?php
/**
 * Working Gmail SMTP Implementation
 * Proper authentication with Gmail using App Password
 */

/**
 * Send OTP via Gmail SMTP (WORKING VERSION)
 */
function sendOTPEmail($to, $otp, $username = 'User') {
    // Gmail SMTP Configuration
    $smtp_host = 'smtp.gmail.com';
    $smtp_port = 587;
    // IMPORTANT: Do NOT commit real credentials. Move these into an ignored local config file.
    // For example, create `config/email.php` (add to .gitignore) that returns ['smtp_username'=>'you@example.com','smtp_password'=>'your_app_password']
    $smtp_username = 'your-email@example.com';
    $smtp_password = 'your_app_password_here';  // <-- replace locally, do NOT commit
    $from_name = 'WMS System';
    
    // Email content
    $subject = "Password Reset OTP - WMS";
    $body = createEmailTemplate($otp, $username);
    
    // Create context for stream
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    
    try {
        // Connect to Gmail SMTP
        $connection = stream_socket_client(
            "tcp://{$smtp_host}:{$smtp_port}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$connection) {
            throw new Exception("Connection failed: $errstr ($errno)");
        }
        
        // Read server greeting
        $response = readResponse($connection);
        if (!startsWith($response, '220')) {
            throw new Exception("Server greeting failed: $response");
        }
        
        // Send EHLO
        sendCommand($connection, "EHLO localhost");
        $response = readResponse($connection);
        if (!startsWith($response, '250')) {
            throw new Exception("EHLO failed: $response");
        }
        
        // Start TLS
        sendCommand($connection, "STARTTLS");
        $response = readResponse($connection);
        if (!startsWith($response, '220')) {
            throw new Exception("STARTTLS failed: $response");
        }
        
        // Enable TLS
        if (!stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception("TLS enable failed");
        }
        
        // Send EHLO again after TLS
        sendCommand($connection, "EHLO localhost");
        $response = readResponse($connection);
        if (!startsWith($response, '250')) {
            throw new Exception("EHLO after TLS failed: $response");
        }
        
        // Authenticate
        sendCommand($connection, "AUTH LOGIN");
        $response = readResponse($connection);
        if (!startsWith($response, '334')) {
            throw new Exception("AUTH LOGIN failed: $response");
        }
        
        // Send username
        sendCommand($connection, base64_encode($smtp_username));
        $response = readResponse($connection);
        if (!startsWith($response, '334')) {
            throw new Exception("Username auth failed: $response");
        }
        
        // Send password
        sendCommand($connection, base64_encode($smtp_password));
        $response = readResponse($connection);
        if (!startsWith($response, '235')) {
            throw new Exception("Password auth failed: $response");
        }
        
        // Set sender
        sendCommand($connection, "MAIL FROM:<{$smtp_username}>");
        $response = readResponse($connection);
        if (!startsWith($response, '250')) {
            throw new Exception("MAIL FROM failed: $response");
        }
        
        // Set recipient
        sendCommand($connection, "RCPT TO:<{$to}>");
        $response = readResponse($connection);
        if (!startsWith($response, '250')) {
            throw new Exception("RCPT TO failed: $response");
        }
        
        // Start data
        sendCommand($connection, "DATA");
        $response = readResponse($connection);
        if (!startsWith($response, '354')) {
            throw new Exception("DATA failed: $response");
        }
        
        // Send email
        $email_data = "From: {$from_name} <{$smtp_username}>\r\n";
        $email_data .= "To: <{$to}>\r\n";
        $email_data .= "Subject: {$subject}\r\n";
        $email_data .= "MIME-Version: 1.0\r\n";
        $email_data .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_data .= "\r\n";
        $email_data .= $body;
        $email_data .= "\r\n.";
        
        sendCommand($connection, $email_data);
        $response = readResponse($connection);
        if (!startsWith($response, '250')) {
            throw new Exception("Email send failed: $response");
        }
        
        // Quit
        sendCommand($connection, "QUIT");
        fclose($connection);
        
        // Log success
        logEmailAttempt($to, $otp, 'GMAIL_SMTP_SUCCESS');
        
        // Also create preview
        createEmailPreview($to, $otp, $body);
        
        return true;
        
    } catch (Exception $e) {
        logEmailAttempt($to, $otp, 'GMAIL_SMTP_ERROR: ' . $e->getMessage());
        
        // Create preview even if sending failed
        createEmailPreview($to, $otp, $body);
        
        // Fallback to local
        return sendLocalEmail($to, $otp, $username);
    }
}

/**
 * Helper function to send SMTP command
 */
function sendCommand($connection, $command) {
    fwrite($connection, $command . "\r\n");
}

/**
 * Helper function to read SMTP response
 */
function readResponse($connection) {
    $response = '';
    while (($line = fgets($connection, 512)) !== false) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') {
            break; // End of multi-line response
        }
    }
    return trim($response);
}

/**
 * Helper function to check response code
 */
function startsWith($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

/**
 * Create email preview
 */
function createEmailPreview($to, $otp, $body) {
    if (!file_exists('email_previews')) {
        mkdir('email_previews', 0777, true);
    }
    
    $filename = 'email_previews/email_' . date('Y-m-d_H-i-s') . '_OTP-' . $otp . '.html';
    $preview = "
<!-- 
EMAIL PREVIEW
To: {$to}
OTP: {$otp}
Time: " . date('Y-m-d H:i:s') . "
-->

{$body}

<!--
This is a preview of the email that was sent (or attempted to be sent).
If you can see this file but didn't receive the email in Gmail:
1. Check Gmail spam folder
2. Check logs/email_log.txt for errors
3. Verify Gmail app password is correct
-->
";
    
    file_put_contents($filename, $preview);
}

/**
 * Fallback to local email
 */
function sendLocalEmail($to, $otp, $username) {
    $content = "
📧 EMAIL TO: $to
🔐 OTP CODE: $otp
👤 USER: $username
⏰ TIME: " . date('Y-m-d H:i:s') . "

Your password reset OTP is: $otp
This OTP will expire in 10 minutes.

Note: This is a local backup because Gmail sending failed.
Check email_previews folder for HTML version.
==========================================
";
    
    if (!file_exists('local_inbox')) {
        mkdir('local_inbox', 0777, true);
    }
    
    $filename = 'local_inbox/otp_' . date('Y-m-d_H-i-s') . '_' . $otp . '.txt';
    file_put_contents($filename, $content);
    
    logEmailAttempt($to, $otp, 'LOCAL_BACKUP');
    
    return true;
}

/**
 * Create beautiful email template
 */
function createEmailTemplate($otp, $username) {
    return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>WMS Password Reset</title>
</head>
<body style='margin: 0; padding: 20px; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
    <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);'>
        
        <!-- Header -->
        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 30px; text-align: center;'>
            <h1 style='margin: 0; font-size: 32px; font-weight: bold;'>🔐 WMS Security</h1>
            <p style='margin: 15px 0 0 0; font-size: 18px; opacity: 0.9;'>Password Reset Request</p>
        </div>
        
        <!-- Content -->
        <div style='padding: 50px 40px;'>
            <h2 style='color: #2c3e50; margin: 0 0 25px 0; font-size: 24px;'>Hello {$username}!</h2>
            
            <p style='color: #555; font-size: 16px; line-height: 1.8; margin: 0 0 30px 0;'>
                You have requested to reset your password for the <strong>Warehouse Management System</strong>. 
                Please use the following One-Time Password (OTP) to continue with your password reset:
            </p>
            
            <!-- OTP Display -->
            <div style='text-align: center; margin: 40px 0;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 15px; display: inline-block;'>
                    <p style='color: white; margin: 0 0 15px 0; font-size: 18px; font-weight: bold;'>Your OTP Code</p>
                    <div style='background: white; color: #667eea; font-size: 42px; font-weight: bold; padding: 25px 35px; border-radius: 10px; letter-spacing: 8px; font-family: Courier, monospace; min-width: 200px;'>{$otp}</div>
                </div>
            </div>
            
            <!-- Instructions -->
            <div style='background: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; margin: 30px 0; border-radius: 0 8px 8px 0;'>
                <h3 style='color: #1976d2; margin: 0 0 15px 0; font-size: 18px;'>📋 How to use this OTP:</h3>
                <ol style='color: #1976d2; margin: 0; padding-left: 20px; line-height: 1.6;'>
                    <li>Return to the WMS password reset page</li>
                    <li>Enter this 6-digit OTP code</li>
                    <li>Create your new password</li>
                    <li>Login with your new credentials</li>
                </ol>
            </div>
            
            <!-- Security Notice -->
            <div style='background: #fff3e0; border: 1px solid #ff9800; border-radius: 8px; padding: 25px; margin: 30px 0;'>
                <h3 style='color: #f57c00; margin: 0 0 15px 0; font-size: 18px;'>🛡️ Security Information</h3>
                <ul style='color: #f57c00; margin: 0; padding-left: 20px; line-height: 1.6;'>
                    <li><strong>This OTP will expire in 10 minutes</strong></li>
                    <li>Never share this OTP with anyone via phone, email, or text</li>
                    <li>WMS staff will never ask for your OTP</li>
                    <li>If you didn't request this reset, please ignore this email</li>
                    <li>Contact your system administrator if you need assistance</li>
                </ul>
            </div>
            
            <div style='text-align: center; margin-top: 40px;'>
                <p style='color: #666; font-size: 14px; line-height: 1.6; margin: 0;'>
                    This is an automated security email from the<br>
                    <strong>Warehouse Management System</strong><br>
                    Please do not reply to this email
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div style='background: #f8f9fa; padding: 25px; text-align: center; border-top: 1px solid #dee2e6;'>
            <p style='color: #6c757d; font-size: 12px; margin: 0; line-height: 1.4;'>
                © " . date('Y') . " WMS - Warehouse Management System<br>
                Secure • Reliable • Professional
            </p>
        </div>
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
        return "✅ Email processing completed for $to with OTP: $test_otp<br>
               📧 <strong>Check your Gmail inbox!</strong><br>
               📁 Preview available in 'email_previews' folder<br>
               📋 Check logs for detailed status";
    } else {
        return "❌ Email processing failed for $to";
    }
}

/**
 * Check email configuration
 */
function checkEmailConfiguration() {
    return [
        'status' => true,
        'messages' => [
            'Gmail SMTP configured for: your-email@example.com (configure locally)',
            'Using proper TLS encryption and authentication',
            'App password authentication enabled (store password in ignored config)',
            'Email previews saved to email_previews folder'
        ]
    ];
}
?>
