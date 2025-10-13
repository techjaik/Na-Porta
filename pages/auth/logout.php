<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Clear all session data
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Start new session for flash message
session_start();
flash_message('info', 'Você foi desconectado com sucesso. Volte sempre!');

// Redirect to home page
redirect(SITE_URL . '/pages/home.php');
?>
