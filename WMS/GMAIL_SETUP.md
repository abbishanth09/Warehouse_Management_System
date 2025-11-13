# 📧 Simple Gmail Setup for WMS

## 🚀 Quick Setup (2 Minutes!)

### Step 1: Get Gmail App Password

1. **Go to your Gmail account settings**
   - Visit: https://myaccount.google.com/security
   - Or Google → Account → Security

2. **Enable 2-Factor Authentication** (if not already enabled)
   - Click "2-Step Verification"
   - Follow the setup process

3. **Generate App Password**
   - Go back to Security settings
   - Click "App passwords"
   - Select "Mail" and "Windows Computer"
   - Click "Generate"
   - **Copy the 16-character password** (like: abcd efgh ijkl mnop)

### Step 2: Update WMS Configuration

1. **Open file:** `gmail_config.php`
2. **Replace these lines:**
   ```php
   $GMAIL_USERNAME = 'your-email@gmail.com';     // ← Put your Gmail here
   $GMAIL_PASSWORD = 'your-app-password';        // ← Put the 16-char password here
   ```

3. **Example:**
   ```php
   $GMAIL_USERNAME = 'john.doe@gmail.com';
   $GMAIL_PASSWORD = 'abcd efgh ijkl mnop';
   ```

### Step 3: Test!

1. **Go to:** `http://localhost/WMS/email_test.php`
2. **Enter your email address**
3. **Click "Send Test Email"**
4. **Check your Gmail inbox!** 📬

---

## 🎯 That's it! 

- **If configured:** OTPs go to your Gmail
- **If not configured:** OTPs go to local_inbox folder
- **Always works:** No matter what!

## 🔧 Troubleshooting

**Problem:** "Gmail not configured"
**Solution:** Edit `gmail_config.php` with your credentials

**Problem:** "Password incorrect"
**Solution:** Use App Password, not regular Gmail password

**Problem:** "Still not working"
**Solution:** Check `logs/email_log.txt` for details

---

## 📁 Files to Know

- **Configuration:** `gmail_config.php`
- **Test Page:** `email_test.php`
- **Local Inbox:** `local_inbox/` folder
- **Logs:** `logs/email_log.txt`
