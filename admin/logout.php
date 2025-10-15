<?php
/**
 * Na Porta - Clean Admin Logout
 */

require_once '../includes/auth.php';

$auth = new Auth();
$auth->logoutAdmin();

// Destroy session completely
session_destroy();

// Redirect to login
header('Location: login.php');
exit();
?>
