<?php
/**
 * Database Setup Script
 * Creates the database and imports the schema
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Setup</h2>";

try {
    // Connect to MySQL without specifying database
    $host = 'localhost';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<p>✅ Connected to MySQL server</p>";
    
    // Create database if it doesn't exist
    $database = 'na_porta_db';
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ Database '$database' created/verified</p>";
    
    // Use the database
    $pdo->exec("USE `$database`");
    echo "<p>✅ Using database '$database'</p>";
    
    // Read and execute schema
    $schema_file = __DIR__ . '/database/schema.sql';
    if (!file_exists($schema_file)) {
        throw new Exception("Schema file not found: $schema_file");
    }
    
    $schema = file_get_contents($schema_file);
    if ($schema === false) {
        throw new Exception("Could not read schema file");
    }
    
    echo "<p>📄 Reading schema file...</p>";
    
    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty lines and comments
        }
        
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "<p>⚠️ Warning executing statement: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p>Statement: " . htmlspecialchars(substr($statement, 0, 100)) . "...</p>";
            }
        }
    }
    
    echo "<p>✅ Schema imported successfully!</p>";
    
    // Verify tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Created Tables:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    echo "<p><strong>✅ Database setup complete!</strong></p>";
    echo "<p><a href='debug_db.php'>Test Categories</a> | <a href='debug_products.php'>Test Products</a></p>";
    echo "<p><a href='admin/categories.php'>Go to Categories Admin</a> | <a href='admin/products.php'>Go to Products Admin</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
