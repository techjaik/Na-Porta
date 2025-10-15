<?php
/**
 * SUPER SIMPLE ADDRESS FIX - No complex logic, just fix it!
 */

// Simple database connection
$host = 'sql105.infinityfree.com';
$database = 'if0_40155099_naporta_db';
$username = 'if0_40155099';
$password = 'Jaishreeramm9';

echo "<h1>🔧 SIMPLE FIX</h1>";

try {
    // Connect directly
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database</p>";
    
    // Drop table if exists and recreate (nuclear option)
    echo "<p>🔄 Recreating table...</p>";
    
    $pdo->exec("DROP TABLE IF EXISTS user_addresses");
    
    $createSQL = "
    CREATE TABLE user_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        cep VARCHAR(9) NOT NULL,
        street VARCHAR(200) NOT NULL,
        number VARCHAR(10) NOT NULL,
        complement VARCHAR(100),
        neighborhood VARCHAR(100) NOT NULL,
        city VARCHAR(100) NOT NULL,
        state VARCHAR(2) NOT NULL,
        is_default TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($createSQL);
    
    echo "<p>✅ Table created successfully</p>";
    
    // Test insert
    $testSQL = "INSERT INTO user_addresses (user_id, name, cep, street, number, neighborhood, city, state) VALUES (1, 'Test', '12345-678', 'Test St', '123', 'Test', 'Test City', 'SP')";
    $pdo->exec($testSQL);
    $testId = $pdo->lastInsertId();
    
    echo "<p>✅ Test insert successful (ID: $testId)</p>";
    
    // Clean up test
    $pdo->exec("DELETE FROM user_addresses WHERE id = $testId");
    
    echo "<p>✅ Test cleaned up</p>";
    
    echo "<h2>🎉 FIXED!</h2>";
    echo "<p><a href='account.php' style='background: green; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>TEST NOW</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ ERROR</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    
    // Show what we can access
    echo "<h3>Database Info:</h3>";
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Tables: " . implode(', ', $tables) . "</p>";
    } catch (Exception $e2) {
        echo "<p style='color: red;'>Cannot connect: " . $e2->getMessage() . "</p>";
    }
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
p { background: #f9f9f9; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>
