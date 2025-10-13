<?php
// Simple setup without sessions
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Na Porta Setup</title></head><body>";
echo "<h1>Na Porta - Simple Setup</h1>";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'na_porta_db';

try {
    echo "<p>Connecting to MySQL...</p>";
    
    // Connect to MySQL (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to MySQL server</p>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ Database '$database' created/verified</p>";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database</p>";
    
    // Create a simple test table
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100))");
    echo "<p>✅ Test table created</p>";
    
    echo "<div style='background: #d4edda; padding: 20px; margin: 20px 0;'>";
    echo "<h3>Database Connection Successful!</h3>";
    echo "<p>MySQL is working correctly.</p>";
    echo "<p><a href='test.php'>Test PHP Configuration</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
