<?php
/**
 * User Class - Object-Oriented User Management
 * Demonstrates OOP concepts: Encapsulation, Abstraction, Data Hiding
 */

class User {
    
    // ENCAPSULATION: Private properties hide internal data
    private $pdo;           // Database connection
    private $id;            // User ID
    private $username;      // Username
    private $email;         // Email address
    private $fullName;      // Full name
    private $role;          // User role (admin, manager, employee)
    private $status;        // Account status (active, inactive)
    
    // CONSTRUCTOR: Dependency injection
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // ABSTRACTION: Simple interface for complex authentication
    public function authenticate($identifier, $password) {
        // Auto-detect if identifier is email or username
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->authenticateByEmail($identifier, $password);
        } else {
            return $this->authenticateByUsername($identifier, $password);
        }
    }
    
    // ENCAPSULATION: Private method hides username authentication logic
    private function authenticateByUsername($username, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, password, email, full_name, role, status FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $this->loadUserData($user);
                $this->updateLastLogin();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // ENCAPSULATION: Private method hides email authentication logic
    private function authenticateByEmail($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, password, email, full_name, role, status FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $this->loadUserData($user);
                $this->updateLastLogin();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // STATE MANAGEMENT: Load user data into object state
    private function loadUserData($userData) {
        $this->id = $userData['id'];
        $this->username = $userData['username'];
        $this->email = $userData['email'];
        $this->fullName = $userData['full_name'];
        $this->role = $userData['role'];
        $this->status = $userData['status'];
    }
    
    // ABSTRACTION: Simple user registration interface
    public function register($userData) {
        if (!$this->validateUserData($userData)) {
            return false;
        }
        
        try {
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO users (username, password, email, full_name, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
            
            $result = $stmt->execute([
                $userData['username'],
                $hashedPassword,
                $userData['email'],
                $userData['full_name'],
                $userData['role'] ?? 'employee'
            ]);
            
            if ($result) {
                $this->id = $this->pdo->lastInsertId();
                $this->username = $userData['username'];
                $this->email = $userData['email'];
                $this->fullName = $userData['full_name'];
                $this->role = $userData['role'] ?? 'employee';
                $this->status = 'active';
            }
            
            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // ABSTRACTION: Find user by email
    public function findByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, full_name, role, status FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $this->loadUserData($user);
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // PASSWORD MANAGEMENT: Update user password
    public function updatePassword($newPassword) {
        if (!$this->id || strlen($newPassword) < 6) {
            return false;
        }
        
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            return $stmt->execute([$hashedPassword, $this->id]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // SESSION MANAGEMENT: Get data for session storage
    public function getSessionData() {
        return [
            'user_id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'full_name' => $this->fullName,
            'role' => $this->role,
            'status' => $this->status
        ];
    }
    
    // ENCAPSULATION: Private validation method
    private function validateUserData($data) {
        return isset($data['username']) && 
               isset($data['password']) && 
               isset($data['email']) && 
               filter_var($data['email'], FILTER_VALIDATE_EMAIL) &&
               strlen($data['username']) >= 3 &&
               strlen($data['password']) >= 6;
    }
    
    // ENCAPSULATION: Private method to update last login
    private function updateLastLogin() {
        if ($this->id) {
            try {
                $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$this->id]);
            } catch (PDOException $e) {
                // Silent fail for login tracking
            }
        }
    }
    
    // GETTER METHODS: Controlled access to private data
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getFullName() { return $this->fullName; }
    public function getRole() { return $this->role; }
    public function getStatus() { return $this->status; }
}
?>
