<?php
/**
 * Simple Email Service for OTP
 * Advanced Gmail SMTP with local fallback
 */

// Include advanced Gmail configuration
require_once __DIR__ . '/../gmail_config_advanced.php';

/**
 * Main function to send OTP
 */
function sendOTPEmail($to, $otp, $username = 'User') {
    // Use the advanced Gmail SMTP function
    return sendGmailOTPAdvanced($to, $otp, $username);
}

/**
 * Simple test function
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
    global $GMAIL_USERNAME;
    
    $messages = [];
    
    if (!isset($GMAIL_USERNAME) || $GMAIL_USERNAME === 'your-email@gmail.com') {
        $messages[] = "Gmail not configured - using local inbox";
        $messages[] = "Edit gmail_config_advanced.php to set up Gmail";
    } else {
        $messages[] = "Gmail configured for: $GMAIL_USERNAME";
        $messages[] = "Using advanced SMTP connection";
    }
    
    return [
        'status' => true,
        'messages' => $messages
    ];
}
?>
