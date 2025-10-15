<?php
/**
 * Na Porta - Admin Index (Redirect to Dashboard)
 */

// Use absolute path for better compatibility
$authPath = __DIR__ . '/../includes/auth.php';

if (!file_exists($authPath)) {
    die('Error: Authentication system not found. Please check file paths.');
}

try {
    require_once $authPath;
    
    $auth = new Auth();
    
    // Check if admin is logged in
    if (!$auth->isAdminLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    // Redirect to dashboard
    header('Location: dashboard.php');
    exit();
    
} catch (Exception $e) {
    error_log('Admin index error: ' . $e->getMessage());
    die('System error. Please try again later or contact support.');
}
?>
