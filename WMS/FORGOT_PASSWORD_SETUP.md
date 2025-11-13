# Forgot Password System Setup Guide

## 🔐 Complete Forgot Password System

Your WMS now has a complete forgot password system with OTP verification!

## ✅ Features Implemented

### 1. **Three-Step Process:**
- **Step 1**: Email address input
- **Step 2**: OTP verification (6-digit code)
- **Step 3**: New password setup

### 2. **Security Features:**
- ✅ OTP expires in 10 minutes
- ✅ Email verification required
- ✅ Secure password hashing
- ✅ Session-based state management
- ✅ Input validation and sanitization

### 3. **User Experience:**
- ✅ Beautiful glass morphism design
- ✅ Step-by-step progress indicator
- ✅ Clear error and success messages
- ✅ Mobile-responsive design
- ✅ Auto-focus on input fields

## 🚀 How to Use

### For Users:
1. **Go to Login Page**: `http://localhost/WMS/login.php`
2. **Click "Forgot Password?"** link
3. **Enter Email**: Your registered email address
4. **Enter OTP**: Check email for 6-digit code (demo shows OTP on screen)
5. **Set New Password**: Enter and confirm new password
6. **Login**: Use your new password to login

### For Testing:
- **Email**: `abbishanth1209@gmail.com`
- **Demo Mode**: OTP is displayed on screen for testing
- **Production**: OTP will be sent via email

## 📧 Email Setup (Production)

### Current Status: **DEMO MODE**
- OTPs are logged to `logs/email_log.txt`
- OTPs are displayed on screen for testing

### For Production Email:

#### Option 1: PHPMailer + Gmail
```bash
# Install PHPMailer
composer require phpmailer/phpmailer
```

#### Option 2: Built-in PHP mail()
```php
// Configure PHP.ini for mail
sendmail_from = your-email@domain.com
SMTP = your-smtp-server.com
smtp_port = 587
```

#### Option 3: Email Service APIs
- **SendGrid**: Professional email service
- **Mailgun**: Reliable email API
- **Amazon SES**: AWS email service

## 🔧 Configuration Files

### Files Created:
- **`forgot_password.php`**: Main forgot password page
- **`includes/email_service.php`**: Email sending functions
- **`logs/`**: Directory for email logs (demo mode)

### Files Modified:
- **`login.php`**: Added "Forgot Password?" link

## 🛡️ Security Considerations

### Current Security:
- ✅ Password hashing with `password_hash()`
- ✅ SQL injection protection with prepared statements
- ✅ XSS protection with `htmlspecialchars()`
- ✅ CSRF protection via session state
- ✅ OTP expiration (10 minutes)
- ✅ Input validation and sanitization

### Additional Security (Recommended):
- 🔄 Rate limiting for OTP requests
- 🔄 Account lockout after failed attempts
- 🔄 IP logging and monitoring
- 🔄 Two-factor authentication
- 🔄 Password strength requirements

## 📱 Testing Workflow

### Test Scenario 1: Successful Reset
1. Go to `http://localhost/WMS/forgot_password.php`
2. Enter: `abbishanth1209@gmail.com`
3. Click "Send OTP"
4. Copy the displayed OTP
5. Enter the OTP and click "Verify OTP"
6. Enter new password (min 6 characters)
7. Confirm password
8. Click "Reset Password"
9. Login with new password

### Test Scenario 2: Invalid Email
1. Enter non-existent email
2. Verify error message appears

### Test Scenario 3: Invalid OTP
1. Enter correct email
2. Enter wrong OTP
3. Verify error message appears

### Test Scenario 4: OTP Expiry
1. Wait 10+ minutes after OTP generation
2. Try to verify OTP
3. Verify expiry message appears

## 🎯 Production Checklist

- [ ] Configure real email service
- [ ] Remove demo OTP display
- [ ] Set up SSL certificate
- [ ] Configure rate limiting
- [ ] Add monitoring and logging
- [ ] Test email delivery
- [ ] Set up backup authentication method

## 🆘 Troubleshooting

### Common Issues:
1. **OTP not received**: Check spam folder, verify email service
2. **OTP expired**: Request new OTP
3. **Database errors**: Check MySQL connection
4. **Email service errors**: Check SMTP configuration

### Log Files:
- **Email logs**: `logs/email_log.txt`
- **PHP errors**: Check Apache error logs
- **Database**: Check MySQL error logs

Your forgot password system is now fully functional! 🎉
