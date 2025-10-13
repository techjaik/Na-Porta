<?php
// User Logout
session_start();

// Clear user session data
$user_keys = ['user_id', 'user_name', 'user_email'];
foreach ($user_keys as $key) {
    unset($_SESSION[$key]);
}

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to homepage
header('Location: ../home-fixed.php?logout=1');
exit();
?>
