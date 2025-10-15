<?php
/**
 * Na Porta - Authentication Manager
 * Unified authentication for both users and admins
 */

require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Admin Login
     */
    public function loginAdmin($username, $password) {
        $sql = "SELECT * FROM admin_users WHERE username = ? AND is_active = 1";
        $admin = $this->db->fetch($sql, [$username]);
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_email'] = $admin['email'];
            
            // Update last login
            $this->db->query("UPDATE admin_users SET last_login = NOW() WHERE id = ?", [$admin['id']]);
            
            return true;
        }
        return false;
    }
    
    /**
     * User Login
     */
    public function loginUser($email, $password) {
        $sql = "SELECT * FROM users WHERE email = ? AND is_active = 1";
        $user = $this->db->fetch($sql, [$email]);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_phone'] = $user['phone'];
            
            // Update last login (if column exists)
            try {
                $this->db->query("UPDATE users SET updated_at = NOW() WHERE id = ?", [$user['id']]);
            } catch (Exception $e) {
                // Ignore if column doesn't exist
            }
            
            return true;
        }
        return false;
    }
    
    /**
     * Register new user
     */
    public function registerUser($name, $email, $password, $phone = null, $cpf_cnpj = null, $gender = null) {
        try {
            // Check if email exists
            $existing = $this->db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existing) {
                return false; // Return false instead of throwing exception
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (name, email, phone, cpf_cnpj, gender, password, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
            
            $this->db->query($sql, [$name, $email, $phone, $cpf_cnpj, $gender, $hashedPassword]);
            
            $userId = $this->db->lastInsertId();
            
            // Auto login after registration
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = $phone;
            
            return $userId;
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if admin is logged in
     */
    public function isAdminLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
    
    /**
     * Check if user is logged in
     */
    public function isUserLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get current admin
     */
    public function getCurrentAdmin() {
        if (!$this->isAdminLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
            'name' => $_SESSION['admin_name'],
            'role' => $_SESSION['admin_role'],
            'email' => $_SESSION['admin_email']
        ];
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isUserLoggedIn()) {
            return null;
        }
        
        // Get fresh data from database
        try {
            $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
            if ($user) {
                return $user;
            }
        } catch (Exception $e) {
            error_log("getCurrentUser error: " . $e->getMessage());
        }
        
        // Fallback to session data
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'phone' => $_SESSION['user_phone'] ?? null,
            'created_at' => date('Y-m-d H:i:s') // Default to current date
        ];
    }
    
    /**
     * Admin logout
     */
    public function logoutAdmin() {
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_name']);
        unset($_SESSION['admin_role']);
        unset($_SESSION['admin_email']);
    }
    
    /**
     * User logout
     */
    public function logoutUser() {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_phone']);
    }
    
    /**
     * General logout (both user and admin)
     */
    public function logout() {
        $this->logoutUser();
        $this->logoutAdmin();
    }
    
    /**
     * Require admin login
     */
    public function requireAdmin() {
        if (!$this->isAdminLoggedIn()) {
            // Get the correct path to admin login
            $currentPath = $_SERVER['REQUEST_URI'];
            if (strpos($currentPath, '/admin/') !== false) {
                header('Location: login.php');
            } else {
                header('Location: admin/login.php');
            }
            exit();
        }
    }
    
    /**
     * Require user login
     */
    public function requireUser() {
        if (!$this->isUserLoggedIn()) {
            header('Location: auth/login.php');
            exit();
        }
    }
    
    /**
     * Check admin permission
     */
    public function hasAdminPermission($requiredRole = 'editor') {
        if (!$this->isAdminLoggedIn()) {
            return false;
        }
        
        $roleHierarchy = ['editor' => 1, 'manager' => 2, 'super_admin' => 3];
        $userRole = $_SESSION['admin_role'];
        
        return $roleHierarchy[$userRole] >= $roleHierarchy[$requiredRole];
    }
}
?>
