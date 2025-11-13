<?php
/**
 * Simple Email Service for OTP
 * Easy Gmail setup with local fallback
 */

// Include Gmail configuration
require_once __DIR__ . '/../gmail_config.php';

/**
 * Main function to send OTP
 */
function sendOTPEmail($to, $otp, $username = 'User') {
    // Use the simple Gmail function from gmail_config.php
    return sendGmailOTP($to, $otp, $username);
}
    
    // If both methods fail, use local file system as fallback
    logEmailAttempt($to, $otp, 'REAL EMAIL FAILED - USING LOCAL FALLBACK');
    return sendOTPEmailLocal($to, $otp, $username);
}

/**
 * Send OTP via Gmail SMTP
 */
function sendOTPEmailGmail($to, $otp, $username = 'User') {
    // Gmail SMTP Configuration
    // UPDATE THESE WITH YOUR ACTUAL GMAIL CREDENTIALS:
    $gmail_user = 'your-email@gmail.com';        // Replace with your Gmail address
    $gmail_password = 'your-app-password';       // Replace with your Gmail App Password
    
    // Check if Gmail credentials are configured
    if ($gmail_user === 'your-email@gmail.com' || $gmail_password === 'your-app-password') {
        logEmailAttempt($to, $otp, 'SKIPPED - Gmail credentials not configured');
        return false;
    }
    
    // Include Gmail SMTP functions if file exists
    if (file_exists('gmail_smtp.php')) {
        include_once 'gmail_smtp.php';
        return sendOTPEmailGmail($to, $otp, $username, $gmail_user, $gmail_password);
    }
    
    return false;
}

/**
 * Attempt to send real email
 */
function sendRealEmail($to, $otp, $username) {
    // Email configuration
    $subject = "Password Reset OTP - Warehouse Management System";
    
    // Create beautiful HTML email template
    $htmlBody = createEmailTemplate($otp, $username);
    
    // Email headers for better delivery
    $headers = array();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/html; charset=UTF-8";
    $headers[] = "From: WMS System <noreply@localhost>";
    $headers[] = "Reply-To: noreply@localhost";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    $headers[] = "X-Priority: 1"; // High priority
    $headers[] = "Return-Path: noreply@localhost";
    
    // Join headers
    $header_string = implode("\r\n", $headers);
    
    // Attempt to send the email
    $result = @mail($to, $subject, $htmlBody, $header_string);
    
    // Log the attempt
    if ($result) {
        logEmailAttempt($to, $otp, 'SUCCESS - REAL EMAIL');
    }
    
    return $result;
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
        <title>Password Reset OTP</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f4f4f4; padding: 20px;'>
            <tr>
                <td align='center'>
                    <table width='600' cellpadding='0' cellspacing='0' style='background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>
                        <!-- Header -->
                        <tr>
                            <td style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;'>
                                <h1 style='color: white; margin: 0; font-size: 28px;'>🔐 WMS Security</h1>
                                <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;'>Password Reset Request</p>
                            </td>
                        </tr>
                        
                        <!-- Content -->
                        <tr>
                            <td style='padding: 40px 30px;'>
                                <h2 style='color: #2c3e50; margin: 0 0 20px 0;'>Hello $username!</h2>
                                
                                <p style='color: #555; font-size: 16px; line-height: 1.6; margin: 0 0 25px 0;'>
                                    You have requested to reset your password for the Warehouse Management System. 
                                    Please use the following One-Time Password (OTP) to continue:
                                </p>
                                
                                <!-- OTP Box -->
                                <table width='100%' cellpadding='0' cellspacing='0' style='margin: 30px 0;'>
                                    <tr>
                                        <td align='center' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 10px;'>
                                            <h3 style='color: white; margin: 0 0 10px 0; font-size: 18px;'>Your OTP Code</h3>
                                            <div style='background: white; color: #667eea; font-size: 32px; font-weight: bold; padding: 15px 25px; border-radius: 8px; letter-spacing: 5px; font-family: Monaco, monospace;'>$otp</div>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Security Notice -->
                                <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 25px 0;'>
                                    <h4 style='color: #856404; margin: 0 0 15px 0; font-size: 16px;'>🛡️ Security Information</h4>
                                    <ul style='color: #856404; margin: 0; padding-left: 20px;'>
                                        <li>This OTP will expire in <strong>10 minutes</strong></li>
                                        <li>Never share this OTP with anyone</li>
                                        <li>If you didn't request this, please ignore this email</li>
                                        <li>Contact support if you need assistance</li>
                                    </ul>
                                </div>
                                
                                <p style='color: #555; font-size: 14px; line-height: 1.6; margin: 25px 0 0 0;'>
                                    If you're having trouble with the password reset process, please contact your system administrator.
                                </p>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style='background: #f8f9fa; padding: 20px 30px; border-top: 1px solid #dee2e6; text-align: center;'>
                                <p style='color: #6c757d; font-size: 12px; margin: 0; line-height: 1.4;'>
                                    This is an automated message from the Warehouse Management System.<br>
                                    Please do not reply to this email.<br><br>
                                    © " . date('Y') . " WMS - Warehouse Management System
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>";
}

/**
 * Log email attempts with detailed information
 */
function logEmailAttempt($to, $otp, $status) {
    $log_message = "=== EMAIL ATTEMPT ===\n";
    $log_message .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $log_message .= "To: $to\n";
    $log_message .= "OTP: $otp\n";
    $log_message .= "Status: $status\n";
    $log_message .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "\n";
    $log_message .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
    $log_message .= "PHP Mail Function: " . (function_exists('mail') ? 'Available' : 'Not Available') . "\n";
    $log_message .= "SMTP Host: " . (ini_get('SMTP') ?: 'Not configured') . "\n";
    $log_message .= "SMTP Port: " . (ini_get('smtp_port') ?: 'Not configured') . "\n";
    $log_message .= "===================\n\n";
    
    // Ensure logs directory exists
    if (!file_exists('logs')) {
        mkdir('logs', 0777, true);
    }
    
    // Write to log file
    file_put_contents('logs/email_log.txt', $log_message, FILE_APPEND | LOCK_EX);
}

/**
 * Email configuration checker
 */
function checkEmailConfiguration() {
    $config_ok = true;
    $messages = [];
    
    // Check if mail() function is available
    if (!function_exists('mail')) {
        $config_ok = false;
        $messages[] = "PHP mail() function is not available";
    }
    
    // Check sendmail configuration (Windows)
    $sendmail_path = ini_get('sendmail_path');
    if (empty($sendmail_path) && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $messages[] = "Sendmail not configured for Windows - emails may not be delivered";
    }
    
    // Check SMTP configuration
    $smtp_host = ini_get('SMTP');
    if (empty($smtp_host)) {
        $messages[] = "No SMTP server configured in php.ini";
    }
    
    return [
        'status' => $config_ok,
        'messages' => $messages
    ];
}

/**
 * Test email function
 */
function sendTestEmail($to) {
    $test_otp = sprintf("%06d", mt_rand(100000, 999999));
    $result = sendOTPEmail($to, $test_otp, "Test User");
    
    if ($result) {
        return "✅ Test email sent successfully to $to with OTP: $test_otp";
    } else {
        return "❌ Failed to send test email to $to. Check configuration and logs.";
    }
}

/**
 * Get email configuration status
 */
function getEmailConfigStatus() {
    $status = [];
    
    // Check if mail function exists
    $status['mail_function'] = function_exists('mail');
    
    // Check PHP mail configuration
    $status['smtp_host'] = ini_get('SMTP') ?: 'Not configured';
    $status['smtp_port'] = ini_get('smtp_port') ?: 'Not configured';
    $status['sendmail_from'] = ini_get('sendmail_from') ?: 'Not configured';
    
    // Check if logs directory is writable
    $status['logs_writable'] = is_writable('logs') || is_writable('.');
    
    return $status;
}
?>
