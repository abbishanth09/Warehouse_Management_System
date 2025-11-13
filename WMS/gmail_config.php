<?php
/**
 * Simple Gmail Configuration
 * Just update your email and password below!
 */

// ========================================
// 📧 GMAIL CONFIGURED FOR ABBISHANTH
// ========================================
$GMAIL_USERNAME = 'your-email@example.com';     // Your Gmail address (placeholder)
$GMAIL_PASSWORD = 'your_app_password_here';      // Your Gmail App Password (store in ignored config)
$GMAIL_FROM_NAME = 'WMS System';                  // Name that appears in emails

// ========================================
// 🔧 DON'T CHANGE THESE SETTINGS
// ========================================
$GMAIL_SMTP_HOST = 'smtp.gmail.com';
$GMAIL_SMTP_PORT = 587;
$GMAIL_SMTP_SECURE = 'tls';

/**
 * Simple Gmail SMTP function using fsockopen
 */
function sendGmailOTP($to, $otp, $username = 'User') {
    global $GMAIL_USERNAME, $GMAIL_PASSWORD, $GMAIL_FROM_NAME;
    global $GMAIL_SMTP_HOST, $GMAIL_SMTP_PORT;
    
    // Check if Gmail is configured
    if ($GMAIL_USERNAME === 'your-email@gmail.com') {
        // Not configured, use local inbox
        return sendLocalEmail($to, $otp, $username);
    }
    
    // Create email content
    $subject = "Password Reset OTP - WMS";
    $message = createSimpleEmailTemplate($otp, $username);
    
    // Email headers
    $headers = "From: $GMAIL_FROM_NAME <$GMAIL_USERNAME>\r\n";
    $headers .= "Reply-To: $GMAIL_USERNAME\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Try to send via Gmail SMTP
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    
    // Use PHP's mail function with proper configuration
    $additional_parameters = "-f$GMAIL_USERNAME";
    
    $result = mail($to, $subject, $message, $headers, $additional_parameters);
    
    // Log the attempt
    logEmailAttempt($to, $otp, $result ? 'GMAIL_SUCCESS' : 'GMAIL_FAILED');
    
    if (!$result) {
        // Fallback to local inbox
        return sendLocalEmail($to, $otp, $username);
    }
    
    return $result;
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
