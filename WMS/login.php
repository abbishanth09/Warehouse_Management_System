<?php
/**
 * Login Page for Warehouse Management System
 * Simple authentication with username: admin, password: admin
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$error = '';

// Check for session expiry message
if (isset($_GET['expired']) && $_GET['expired'] == '1') {
    $error = 'Your session has expired. Please login again.';
}

// Include database connection and auth functions
require_once 'config/database.php';
require_once 'includes/auth_db.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    $user = false;
    
    // Check if the input looks like an email
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        // Database authentication with email
        $user = authenticateUserByEmail($pdo, $username, $password);
    } else {
        // Regular username authentication
        $user = authenticateUser($pdo, $username, $password);
    }
    
    if ($user) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['login_time'] = time();
        
        // Check for redirect URL
        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
        unset($_SESSION['redirect_after_login']);
        
        // Redirect to intended page or dashboard
        header('Location: ' . $redirect);
        exit();
    } else {
        // Only set error if not already set (e.g., by email validation)
        if (!isset($error) || empty($error)) {
            $error = 'Invalid username/email or password!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Warehouse Management System</title>
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
        
        .login-container {
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
            max-width: 400px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.18);
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
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
        
        .login-header {
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #ffffff;
            margin-bottom: 0.5rem;
            font-size: 2rem;
            font-weight: 300;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .login-header p {
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .login-form {
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
        
        .login-btn {
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
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 8px 25px rgba(52, 152, 219, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            background: linear-gradient(135deg, 
                rgba(52, 152, 219, 0.9) 0%, 
                rgba(41, 128, 185, 1) 100%);
        }
        
        .error-message {
            background: rgba(231, 76, 60, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #ffffff;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(231, 76, 60, 0.3);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            box-shadow: 
                0 4px 15px rgba(231, 76, 60, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 1;
        }
        
        .footer-text {
            margin-top: 2rem;
            color: #7f8c8d;
            font-size: 0.9rem;
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
    <div class="login-container">
        <div class="login-header">
            <h1>🏭 WMS Login</h1>
            <p>Warehouse Management System</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="login-form">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       required 
                       placeholder="Enter your username or email"
                       autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           placeholder="Enter your password"
                           autocomplete="current-password">
                    <span class="password-toggle" onclick="togglePassword()">
                        👁️
                    </span>
                </div>
            </div>
            
            <button type="submit" class="login-btn">
                🔐 Login to WMS
            </button>
            
            <div style="text-align: center; margin-top: 1.5rem; position: relative; z-index: 1;">
                <a href="forgot_password.php" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.9rem; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);">
                    🔑 Forgot Password?
                </a>
            </div>
        </form>
        
    </div>
    
    <script>
        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Add some visual feedback on form submission
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const usernameField = document.getElementById('username');
            const username = usernameField.value.trim();
            
            // Check if input looks like email and validate it
            if (username.includes('@')) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(username)) {
                    e.preventDefault();
                    alert('Invalid Email address');
                    return false;
                }
            }
            
            const button = document.querySelector('.login-btn');
            button.innerHTML = '🔄 Logging in...';
            button.disabled = true;
        });
        
        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');
            
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
