<?php
/**
 * Advanced Email Service with SMTP Support
 * This version includes Gmail SMTP configuration
 */

/**
 * Send OTP via Gmail SMTP
 * Configure your Gmail credentials below
 */
function sendOTPEmailGmail($to, $otp, $username = 'User') {
    // Gmail SMTP Configuration
    // TODO: Replace with your actual Gmail credentials
    $gmail_user = 'your-email@gmail.com';        // Your Gmail address
    $gmail_password = 'your-app-password';       // Your Gmail App Password (not regular password)
    $smtp_host = 'smtp.gmail.com';
    $smtp_port = 587;
    
    // Email content
    $subject = "Password Reset OTP - Warehouse Management System";
    $message = createEmailTemplate($otp, $username);
    
    // Headers for Gmail SMTP
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: WMS System <$gmail_user>\r\n";
    $headers .= "Reply-To: $gmail_user\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "X-Priority: 1\r\n";
    
    // Attempt to send email
    $result = mail($to, $subject, $message, $headers, "-f$gmail_user");
    
    // Log the attempt
    logEmailAttempt($to, $otp, $result ? 'SUCCESS' : 'FAILED');
    
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
 * Log email attempts
 */
function logEmailAttempt($to, $otp, $status) {
    $log_message = "=== EMAIL ATTEMPT ===\n";
    $log_message .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $log_message .= "To: $to\n";
    $log_message .= "OTP: $otp\n";
    $log_message .= "Status: $status\n";
    $log_message .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "\n";
    $log_message .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
    $log_message .= "===================\n\n";
    
    if (!file_exists('logs')) {
        mkdir('logs', 0777, true);
    }
    file_put_contents('logs/email_log.txt', $log_message, FILE_APPEND | LOCK_EX);
}

/**
 * Main function to send OTP (try Gmail first, fallback to basic mail)
 */
function sendOTPEmail($to, $otp, $username = 'User') {
    // Try Gmail SMTP first
    $gmail_result = sendOTPEmailGmail($to, $otp, $username);
    
    if ($gmail_result) {
        return true;
    }
    
    // Fallback to basic mail() function
    $subject = "Password Reset OTP - WMS";
    $message = createEmailTemplate($otp, $username);
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: WMS System <noreply@wms-system.local>\r\n";
    
    $basic_result = mail($to, $subject, $message, $headers);
    
    // Log the fallback attempt
    logEmailAttempt($to, $otp, $basic_result ? 'SUCCESS (BASIC)' : 'FAILED (ALL)');
    
    return $basic_result;
}

/**
 * Configuration status checker
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
