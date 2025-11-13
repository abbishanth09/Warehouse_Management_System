<?php
/**
 * Temporary Email Solution for Windows XAMPP
 * This creates a local "inbox" folder to simulate email delivery
 */

/**
 * Send OTP via local file system (temporary solution)
 * Creates email files in a local "inbox" folder
 */
function sendOTPEmailLocal($to, $otp, $username = 'User') {
    // Create inbox directory if it doesn't exist
    $inbox_dir = 'inbox';
    if (!file_exists($inbox_dir)) {
        mkdir($inbox_dir, 0777, true);
    }
    
    // Create unique filename for this email
    $timestamp = date('Y-m-d_H-i-s');
    $safe_email = preg_replace('/[^a-zA-Z0-9@._-]/', '_', $to);
    $filename = "{$inbox_dir}/OTP_{$safe_email}_{$timestamp}.html";
    
    // Create email content
    $subject = "Password Reset OTP - WMS";
    $email_content = createLocalEmailTemplate($to, $otp, $username, $subject);
    
    // Save email to file
    $result = file_put_contents($filename, $email_content);
    
    // Log the attempt
    logEmailAttempt($to, $otp, $result ? 'SUCCESS (LOCAL FILE)' : 'FAILED (LOCAL FILE)');
    
    // Also create a simple text version for quick viewing
    if ($result) {
        $txt_filename = "{$inbox_dir}/OTP_QUICK_{$safe_email}_{$timestamp}.txt";
        $simple_content = "OTP EMAIL FOR: $to\n";
        $simple_content .= "OTP CODE: $otp\n";
        $simple_content .= "TIME: " . date('Y-m-d H:i:s') . "\n";
        $simple_content .= "EXPIRES: " . date('Y-m-d H:i:s', time() + 600) . "\n";
        $simple_content .= "\nView full email: $filename\n";
        
        file_put_contents($txt_filename, $simple_content);
    }
    
    return $result !== false;
}

/**
 * Create local email template
 */
function createLocalEmailTemplate($to, $otp, $username, $subject) {
    return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>$subject</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .email-container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; }
        .otp-box { background: #f8f9fa; border: 3px solid #667eea; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
        .otp-code { font-size: 36px; font-weight: bold; color: #667eea; letter-spacing: 5px; font-family: monospace; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
        .meta-info { background: #e9ecef; padding: 15px; margin: 20px 0; border-radius: 5px; font-size: 14px; }
    </style>
</head>
<body>
    <div class='email-container'>
        <div class='meta-info'>
            <strong>📧 EMAIL SIMULATION</strong><br>
            <strong>To:</strong> $to<br>
            <strong>Subject:</strong> $subject<br>
            <strong>Date:</strong> " . date('Y-m-d H:i:s') . "<br>
            <strong>Type:</strong> Password Reset OTP
        </div>
        
        <div class='header'>
            <h1 style='margin: 0;'>🔐 WMS Security Alert</h1>
            <p style='margin: 10px 0 0 0; opacity: 0.9;'>Password Reset Request</p>
        </div>
        
        <div class='content'>
            <h2 style='color: #2c3e50; margin: 0 0 20px 0;'>Hello $username!</h2>
            
            <p style='color: #555; font-size: 16px; line-height: 1.6;'>
                You have requested to reset your password for the Warehouse Management System. 
                Please use the following One-Time Password (OTP) to continue with the password reset process:
            </p>
            
            <div class='otp-box'>
                <h3 style='margin: 0 0 15px 0; color: #667eea;'>Your OTP Code</h3>
                <div class='otp-code'>$otp</div>
                <p style='margin: 15px 0 0 0; font-size: 14px; color: #666;'>Enter this code in the password reset form</p>
            </div>
            
            <div class='warning'>
                <h4 style='margin: 0 0 10px 0; color: #856404;'>🛡️ Security Information</h4>
                <ul style='margin: 0; color: #856404;'>
                    <li>This OTP will expire in <strong>10 minutes</strong> (" . date('H:i:s', time() + 600) . ")</li>
                    <li>Never share this OTP with anyone</li>
                    <li>If you didn't request this, please ignore this email</li>
                    <li>Contact support if you need assistance</li>
                </ul>
            </div>
            
            <p style='color: #555; font-size: 14px; margin-top: 25px;'>
                If you're having trouble with the password reset process, please contact your system administrator 
                or try requesting a new OTP.
            </p>
        </div>
        
        <div class='footer'>
            <p style='margin: 0;'>
                This is an automated message from the Warehouse Management System.<br>
                Please do not reply to this email.<br><br>
                © " . date('Y') . " WMS - Warehouse Management System
            </p>
        </div>
    </div>
    
    <div style='background: #17a2b8; color: white; padding: 15px; margin: 20px auto; max-width: 600px; border-radius: 5px; text-align: center;'>
        <strong>📁 LOCAL EMAIL SIMULATION</strong><br>
        This email was saved locally because email server is not configured.<br>
        In production, this would be sent to: <strong>$to</strong>
    </div>
</body>
</html>";
}

/**
 * List recent OTP emails from local inbox
 */
function getRecentOTPEmails($limit = 10) {
    $inbox_dir = 'inbox';
    if (!file_exists($inbox_dir)) {
        return [];
    }
    
    $files = glob("$inbox_dir/OTP_QUICK_*.txt");
    rsort($files); // Sort by newest first
    
    $emails = [];
    $count = 0;
    foreach ($files as $file) {
        if ($count >= $limit) break;
        
        $content = file_get_contents($file);
        $emails[] = [
            'file' => $file,
            'content' => $content,
            'time' => filemtime($file)
        ];
        $count++;
    }
    
    return $emails;
}

/**
 * Updated main function to use local email system
 */
function sendOTPEmailTemp($to, $otp, $username = 'User') {
    return sendOTPEmailLocal($to, $otp, $username);
}
?>
