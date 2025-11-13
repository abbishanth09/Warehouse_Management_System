<?php
/**
 * Updated Authentication Functions
 * Uses database for user authentication
 */

/**
 * Verify user credentials against database
 */
function authenticateUser($pdo, $username, $password) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, password, email, full_name, role, status FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            return $user;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Verify user credentials against database using email
 */
function authenticateUserByEmail($pdo, $email, $password) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, password, email, full_name, role, status FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            return $user;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create a new user (for registration)
 */
function createUser($pdo, $username, $password, $email, $fullName, $role = 'employee') {
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$username, $hashedPassword, $email, $fullName, $role]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check if username already exists
 */
function usernameExists($pdo, $username) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}
?>
