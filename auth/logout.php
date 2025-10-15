<?php
/**
 * Na Porta - User Logout
 */

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->logout();

// Redirect to home page
header('Location: ../index.php?message=logged_out');
exit();
?>
