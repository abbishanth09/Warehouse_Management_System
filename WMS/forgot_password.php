<?php
/**
 * Forgot Password Page
 * Handles OTP generation and email sending
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/auth_db.php';
require_once 'includes/db_connection.php';
require_once 'includes/email_service_working.php';

$message = '';
$error = '';
$step = 'email'; // email, otp, reset

// Check if we're in OTP verification step
if (isset($_SESSION['reset_email']) && isset($_SESSION['reset_otp'])) {
    $step = 'otp';
}

// Check if OTP is verified
if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) {
    $step = 'reset';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email_step'])) {
        // Step 1: Email submission
        $email = trim($_POST['email']);
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid Email address';
        } else {
            // Check if email exists in database
            $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
            // Generate 6-digit OTP
            $otp = rand(100000, 999999);
            
            // Store in session (in real app, store in database with expiry)
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['otp_generated_time'] = time();
            
            // Send OTP via email
            $emailSent = sendOTPEmail($email, $otp, $user['username']);
            
            if ($emailSent) {
                $message = "OTP has been sent to your email: $email<br><br>
                          📧 <strong>Check your Gmail inbox and spam folder</strong><br>
                          📂 <strong>Backup option:</strong> <a href='inbox.php' target='_blank' style='color: #667eea; text-decoration: underline;'>View Local Inbox</a><br>
                          ⚙️ <strong>Want real Gmail delivery?</strong> <a href='gmail_setup.php' target='_blank' style='color: #667eea; text-decoration: underline;'>Setup Gmail SMTP</a><br><br>
                          Please enter the 6-digit code below:";
                $step = 'otp';
            } else {
                $error = 'Failed to send OTP. Please try again.';
            }
        } else {
            $error = 'Email address not found in our system.';
        }
        }
    } elseif (isset($_POST['otp_step'])) {
        // Step 2: OTP verification
        $entered_otp = trim($_POST['otp']);
        
        // Check if OTP is still valid (10 minutes)
        if (isset($_SESSION['otp_generated_time']) && (time() - $_SESSION['otp_generated_time']) > 600) {
            $error = 'OTP has expired. Please request a new one.';
            unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_user_id'], $_SESSION['otp_generated_time']);
            $step = 'email';
        } elseif ($entered_otp == $_SESSION['reset_otp']) {
            $_SESSION['otp_verified'] = true;
            $message = 'OTP verified successfully! You can now reset your password.';
            $step = 'reset';
        } else {
            $error = 'Invalid OTP. Please try again.';
        }
    } elseif (isset($_POST['reset_step'])) {
        // Step 3: Password reset
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Enhanced password validation
        $password_errors = [];
        
        if (strlen($new_password) < 6) {
            $password_errors[] = 'Password must be at least 6 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $new_password)) {
            $password_errors[] = 'Password must contain at least one capital letter';
        }
        
        if (!preg_match('/[a-z]/', $new_password)) {
            $password_errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $new_password)) {
            $password_errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $new_password)) {
            $password_errors[] = 'Password must contain at least one symbol (!@#$%^&*()_+-=[]{}|;:,.<>?)';
        }
        
        if (!empty($password_errors)) {
            $error = implode('. ', $password_errors) . '.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Update password in database
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashed_password, $_SESSION['reset_user_id']])) {
                $message = 'Password reset successfully! You can now login with your new password.';
                
                // Clear all reset session variables
                unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_user_id'], 
                      $_SESSION['otp_generated_time'], $_SESSION['otp_verified']);
                
                // Redirect to login after 3 seconds
                header("refresh:3;url=login.php");
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Warehouse Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.6)), 
                        url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .forgot-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2),
                inset 0 -1px 0 rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.18);
            position: relative;
            overflow: hidden;
        }
        
        .forgot-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.1) 0%, 
                rgba(255, 255, 255, 0.05) 50%, 
                rgba(255, 255, 255, 0.02) 100%);
            border-radius: 20px;
            pointer-events: none;
        }
        
        .forgot-header {
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .forgot-header h1 {
            color: #ffffff;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
            font-weight: 300;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .forgot-header p {
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        }
        
        .forgot-form {
            text-align: left;
            position: relative;
            z-index: 1;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 1rem;
            color: #ffffff;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: rgba(52, 152, 219, 0.6);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 
                0 0 20px rgba(52, 152, 219, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .forgot-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, 
                rgba(52, 152, 219, 0.8) 0%, 
                rgba(41, 128, 185, 0.9) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            box-shadow: 
                0 4px 15px rgba(52, 152, 219, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }
        
        .forgot-btn:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 8px 25px rgba(52, 152, 219, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }
        
        .message {
            background: rgba(46, 204, 113, 0.15);
            backdrop-filter: blur(10px);
            color: #ffffff;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(46, 204, 113, 0.3);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .error {
            background: rgba(231, 76, 60, 0.15);
            backdrop-filter: blur(10px);
            color: #ffffff;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(231, 76, 60, 0.3);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .back-link {
            margin-top: 2rem;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .back-link a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.9rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        .back-link a:hover {
            color: #ffffff;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .step.active {
            background: rgba(52, 152, 219, 0.8);
            color: #ffffff;
        }
        
        .step.completed {
            background: rgba(46, 204, 113, 0.8);
            color: #ffffff;
        }
        
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-container input {
            padding-right: 3rem;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.7);
            transition: color 0.3s ease;
            z-index: 2;
            font-size: 1.1rem;
        }
        
        .password-toggle:hover {
            color: rgba(255, 255, 255, 1);
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <h1>🔐 Reset Password</h1>
            <p>Warehouse Management System</p>
        </div>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?php echo ($step === 'email') ? 'active' : (in_array($step, ['otp', 'reset']) ? 'completed' : ''); ?>">1</div>
            <div class="step <?php echo ($step === 'otp') ? 'active' : ($step === 'reset' ? 'completed' : ''); ?>">2</div>
            <div class="step <?php echo ($step === 'reset') ? 'active' : ''; ?>">3</div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($step === 'email'): ?>
            <!-- Step 1: Email Input -->
            <form method="POST" action="" class="forgot-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required 
                           placeholder="Enter your email address"
                           autocomplete="email">
                </div>
                
                <button type="submit" name="email_step" class="forgot-btn">
                    📧 Send OTP
                </button>
            </form>
            
        <?php elseif ($step === 'otp'): ?>
            <!-- Step 2: OTP Verification -->
            <form method="POST" action="" class="forgot-form">
                <div class="form-group">
                    <label for="otp">Enter OTP</label>
                    <input type="text" 
                           id="otp" 
                           name="otp" 
                           required 
                           placeholder="Enter 6-digit OTP"
                           maxlength="6"
                           pattern="[0-9]{6}">
                    <small style="color: rgba(255,255,255,0.7); font-size: 0.8rem;">
                        OTP sent to: <?php echo htmlspecialchars($_SESSION['reset_email']); ?>
                    </small>
                </div>
                
                <button type="submit" name="otp_step" class="forgot-btn">
                    🔍 Verify OTP
                </button>
            </form>
            
        <?php elseif ($step === 'reset'): ?>
            <!-- Step 3: Password Reset -->
            <form method="POST" action="" class="forgot-form">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-container">
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               required 
                               placeholder="Enter new password"
                               minlength="6">
                        <span class="password-toggle" onclick="togglePasswordField('new_password', this)">
                            👁️
                        </span>
                    </div>
                    <div class="password-requirements" style="margin-top: 0.5rem; font-size: 0.8rem; color: rgba(255,255,255,0.7);">
                        <div style="margin-bottom: 0.3rem;">Password must contain:</div>
                        <ul style="margin: 0; padding-left: 1.2rem; list-style: none;">
                            <li id="req-length" style="margin-bottom: 0.2rem;">✗ At least 6 characters</li>
                            <li id="req-upper" style="margin-bottom: 0.2rem;">✗ One capital letter (A-Z)</li>
                            <li id="req-lower" style="margin-bottom: 0.2rem;">✗ One lowercase letter (a-z)</li>
                            <li id="req-number" style="margin-bottom: 0.2rem;">✗ One number (0-9)</li>
                            <li id="req-symbol" style="margin-bottom: 0.2rem;">✗ One symbol (!@#$%^&*)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-container">
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               required 
                               placeholder="Confirm new password"
                               minlength="6">
                        <span class="password-toggle" onclick="togglePasswordField('confirm_password', this)">
                            👁️
                        </span>
                    </div>
                </div>
                
                <button type="submit" name="reset_step" class="forgot-btn">
                    🔄 Reset Password
                </button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
    
    <script>
        // Auto-focus on the first input field
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('input');
            if (firstInput) {
                firstInput.focus();
            }
            
            // Add email validation to forgot password form
            const emailForm = document.querySelector('form[name="email_step"], form:has([name="email_step"])');
            if (emailForm) {
                emailForm.addEventListener('submit', function(e) {
                    const emailField = document.getElementById('email');
                    if (emailField) {
                        const email = emailField.value.trim();
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        
                        if (!emailRegex.test(email)) {
                            e.preventDefault();
                            alert('Invalid Email address');
                            return false;
                        }
                    }
                });
            }
        });
        
        // Password confirmation validation
        const confirmPassword = document.getElementById('confirm_password');
        const newPassword = document.getElementById('new_password');
        
        if (confirmPassword && newPassword) {
            // Real-time password validation
            newPassword.addEventListener('input', function() {
                validatePassword(this.value);
            });
            
            confirmPassword.addEventListener('input', function() {
                if (this.value !== newPassword.value) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
        
        function validatePassword(password) {
            const requirements = {
                length: password.length >= 6,
                upper: /[A-Z]/.test(password),
                lower: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                symbol: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
            };
            
            // Update requirement indicators
            updateRequirement('req-length', requirements.length);
            updateRequirement('req-upper', requirements.upper);
            updateRequirement('req-lower', requirements.lower);
            updateRequirement('req-number', requirements.number);
            updateRequirement('req-symbol', requirements.symbol);
        }
        
        function updateRequirement(elementId, isValid) {
            const element = document.getElementById(elementId);
            if (element) {
                if (isValid) {
                    element.innerHTML = element.innerHTML.replace('✗', '✓');
                    element.style.color = '#2ecc71';
                } else {
                    element.innerHTML = element.innerHTML.replace('✓', '✗');
                    element.style.color = 'rgba(255,255,255,0.7)';
                }
            }
        }
        
        // Toggle password visibility
        function togglePasswordField(fieldId, toggleIcon) {
            const passwordField = document.getElementById(fieldId);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.innerHTML = '🙈'; // Hide password icon
            } else {
                passwordField.type = 'password';
                toggleIcon.innerHTML = '👁️'; // Show password icon
            }
        }
    </script>
</body>
</html>
