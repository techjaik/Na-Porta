<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect to home page
header('Location: pages/home.php');
exit();
?>
