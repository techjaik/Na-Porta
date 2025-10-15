<?php
/**
 * Na Porta - Admin Index (Redirect to Dashboard)
 */

require_once '../includes/auth.php';

$auth = new Auth();

// Check if admin is logged in
if (!$auth->isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Redirect to dashboard
header('Location: dashboard.php');
exit();
?>
