<?php
// Simple Admin Logout
session_start();

// Clear all admin session data
$admin_keys = ['admin_id', 'admin_username', 'admin_name', 'admin_email', 'admin_role'];
foreach ($admin_keys as $key) {
    unset($_SESSION[$key]);
}

// Clear admin remember me cookie
if (isset($_COOKIE['admin_remember_token'])) {
    setcookie('admin_remember_token', '', time() - 3600, '/admin/');
}

// Redirect to admin login
header('Location: login.php');
exit();
?>
