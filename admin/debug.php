<?php
/**
 * Na Porta - Admin Debug Page
 * This file helps identify issues with the admin panel
 */

echo "<h1>Admin Debug Information</h1>";

// Check PHP version
echo "<h2>PHP Information</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";

// Check file paths
echo "<h2>File Path Checks</h2>";
$authPath = '../includes/auth.php';
$fullAuthPath = __DIR__ . '/../includes/auth.php';

echo "Auth file relative path: " . $authPath . "<br>";
echo "Auth file full path: " . $fullAuthPath . "<br>";
echo "Auth file exists (relative): " . (file_exists($authPath) ? 'YES' : 'NO') . "<br>";
echo "Auth file exists (full): " . (file_exists($fullAuthPath) ? 'YES' : 'NO') . "<br>";

// Check database config
$dbPath = '../config/database.php';
$fullDbPath = __DIR__ . '/../config/database.php';
echo "Database file relative path: " . $dbPath . "<br>";
echo "Database file full path: " . $fullDbPath . "<br>";
echo "Database file exists (relative): " . (file_exists($dbPath) ? 'YES' : 'NO') . "<br>";
echo "Database file exists (full): " . (file_exists($fullDbPath) ? 'YES' : 'NO') . "<br>";

// Try to include auth file
echo "<h2>Include Test</h2>";
try {
    if (file_exists($fullAuthPath)) {
        require_once $fullAuthPath;
        echo "Auth file included successfully!<br>";
        
        // Test Auth class
        if (class_exists('Auth')) {
            echo "Auth class exists!<br>";
            $auth = new Auth();
            echo "Auth object created successfully!<br>";
            echo "Admin logged in: " . ($auth->isAdminLoggedIn() ? 'YES' : 'NO') . "<br>";
        } else {
            echo "Auth class does not exist!<br>";
        }
    } else {
        echo "Auth file not found!<br>";
    }
} catch (Exception $e) {
    echo "Error including auth file: " . $e->getMessage() . "<br>";
}

// Test database connection
echo "<h2>Database Test</h2>";
try {
    if (file_exists($fullDbPath)) {
        require_once $fullDbPath;
        echo "Database file included successfully!<br>";
        
        if (class_exists('Database')) {
            echo "Database class exists!<br>";
            $db = Database::getInstance();
            echo "Database connection successful!<br>";
        } else {
            echo "Database class does not exist!<br>";
        }
    } else {
        echo "Database file not found!<br>";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>Session Information</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session ID: " . session_id() . "<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";

echo "<hr>";
echo "<a href='login.php'>Go to Admin Login</a> | ";
echo "<a href='../'>Go to Main Site</a>";
?>
