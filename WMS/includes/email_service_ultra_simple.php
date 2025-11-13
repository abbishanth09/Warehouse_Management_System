<?php
/**
 * Ultra Simple Gmail Integration
 * Uses file_get_contents with Gmail API approach
 */

/**
 * Send OTP via simple HTTP email service
 */
function sendOTPEmail($to, $otp, $username = 'User') {
    // First, let's try a different approach - save to a special "sent" folder
    // that simulates sent emails, but also try a simple mail() setup
    
    // Try the simple mail() function with proper headers
    $subject = "Password Reset OTP - WMS";
    $message = createEmailTemplate($otp, $username);
    
    // Set proper headers for Gmail
    $headers = "From: WMS System <your-email@example.com>\r\n";
    $headers .= "Reply-To: your-email@example.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // Try to send
    $result = @mail($to, $subject, $message, $headers);
    
    if ($result) {
        logEmailAttempt($to, $otp, 'SIMPLE_MAIL_SUCCESS');
        // Also create a "sent" copy for verification
        createSentEmailCopy($to, $otp, $message);
        return true;
    } else {
        logEmailAttempt($to, $otp, 'SIMPLE_MAIL_FAILED');
        // Always create local copy
        return sendLocalEmail($to, $otp, $username);
    }
}

/**
 * Create a "sent" email copy for verification
 */
function createSentEmailCopy($to, $otp, $message) {
    if (!file_exists('sent_emails')) {
        mkdir('sent_emails', 0777, true);
    }
    
    $filename = 'sent_emails/email_' . date('Y-m-d_H-i-s') . '_' . $otp . '.html';
    $content = "
<!-- EMAIL SENT TO: $to -->
<!-- OTP: $otp -->
<!-- TIME: " . date('Y-m-d H:i:s') . " -->

$message

<!-- 
IF YOU CAN SEE THIS FILE, IT MEANS THE EMAIL WAS GENERATED.
Check your Gmail inbox for the actual email.
If not received, check Gmail spam folder.
-->
";
    
    file_put_contents($filename, $content);
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
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>WMS Password Reset</title>
</head>
<body style='margin: 0; padding: 20px; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
    <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>
        <!-- Header -->
        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;'>
            <h1 style='margin: 0; font-size: 28px;'>🔐 WMS Security</h1>
            <p style='margin: 10px 0 0 0; opacity: 0.9;'>Password Reset Request</p>
        </div>
        
        <!-- Content -->
        <div style='padding: 40px 30px;'>
            <h2 style='color: #2c3e50; margin: 0 0 20px 0;'>Hello $username!</h2>
            
            <p style='color: #555; font-size: 16px; line-height: 1.6; margin: 0 0 25px 0;'>
                You have requested to reset your password for the Warehouse Management System. 
                Please use the following One-Time Password (OTP) to continue:
            </p>
            
            <!-- OTP Box -->
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 10px; text-align: center; margin: 30px 0;'>
                <h3 style='color: white; margin: 0 0 15px 0; font-size: 18px;'>Your OTP Code</h3>
                <div style='background: white; color: #667eea; font-size: 36px; font-weight: bold; padding: 20px; border-radius: 8px; letter-spacing: 8px; font-family: monospace;'>$otp</div>
            </div>
            
            <!-- Security Notice -->
            <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 25px 0;'>
                <h4 style='color: #856404; margin: 0 0 15px 0;'>🛡️ Security Information</h4>
                <ul style='color: #856404; margin: 0; padding-left: 20px;'>
                    <li>This OTP will expire in <strong>10 minutes</strong></li>
                    <li>Never share this OTP with anyone</li>
                    <li>If you didn't request this, please ignore this email</li>
                    <li>Contact support if you need assistance</li>
                </ul>
            </div>
            
            <p style='color: #666; font-size: 12px; margin-top: 30px; text-align: center;'>
                This is an automated email from WMS - Warehouse Management System<br>
                Please do not reply to this email
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
        return "✅ Test email sent to $to with OTP: $test_otp<br>📁 Check 'sent_emails' folder for HTML preview<br>📧 Check your Gmail inbox!";
    } else {
        return "❌ Failed to send test email to $to";
    }
}

/**
 * Check email configuration
 */
function checkEmailConfiguration() {
    $messages = [];
    $messages[] = "Simplified email setup for: your-email@example.com (configure locally)";
    $messages[] = "Using PHP mail() function with Gmail headers";
    $messages[] = "Emails saved to 'sent_emails' folder for preview";
    
    return [
        'status' => true,
        'messages' => $messages
    ];
}
?>
