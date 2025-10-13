<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Clear all admin session data
$admin_keys = ['admin_id', 'admin_username', 'admin_name', 'admin_email', 'admin_role'];
foreach ($admin_keys as $key) {
    unset($_SESSION[$key]);
}

// Clear admin remember me cookie
if (isset($_COOKIE['admin_remember_token'])) {
    setcookie('admin_remember_token', '', time() - 3600, '/admin/');
}

// Set flash message
flash_message('info', 'Você foi desconectado do painel administrativo.');

// Redirect to admin login
redirect(SITE_URL . '/admin/login.php');
?>
