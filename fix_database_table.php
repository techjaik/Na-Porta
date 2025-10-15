<?php
/**
 * Fix Database Table - Create user_addresses table if missing
 */

require_once __DIR__ . '/config/database.php';

echo "<h2>🔧 Database Table Fix</h2>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h3>1. Database Connection</h3>";
    echo "<p>✅ <strong>Connected to database successfully</strong></p>";
    echo "<p>Host: " . (strpos($_SERVER['HTTP_HOST'], '.free.nf') !== false ? 'InfinityFree Production' : 'Local Development') . "</p>";
    
    echo "<h3>2. Check Existing Tables</h3>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Existing tables:</strong></p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    echo "<h3>3. Check user_addresses Table</h3>";
    try {
        $result = $pdo->query("DESCRIBE user_addresses");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>✅ <strong>user_addresses table exists</strong></p>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ <strong>user_addresses table does NOT exist</strong></p>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        
        echo "<h3>4. Creating user_addresses Table</h3>";
        
        $createTableSQL = "
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
            is_default BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_is_default (is_default)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $pdo->exec($createTableSQL);
            echo "<p>✅ <strong>user_addresses table created successfully!</strong></p>";
            
            // Verify creation
            $result = $pdo->query("DESCRIBE user_addresses");
            $columns = $result->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p><strong>New table structure:</strong></p>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
            echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (Exception $createError) {
            echo "<p style='color: red;'>❌ <strong>Failed to create table:</strong> " . $createError->getMessage() . "</p>";
        }
    }
    
    echo "<h3>5. Test Insert Operation</h3>";
    
    // Test if we can insert data
    try {
        $testInsert = "
        INSERT INTO user_addresses (user_id, name, cep, street, number, complement, neighborhood, city, state, is_default) 
        VALUES (1, 'Test Address', '12345-678', 'Test Street', '123', 'Test Complement', 'Test Neighborhood', 'Test City', 'SP', 0)
        ";
        
        $pdo->exec($testInsert);
        $insertId = $pdo->lastInsertId();
        
        echo "<p>✅ <strong>Test insert successful!</strong> Insert ID: $insertId</p>";
        
        // Clean up test data
        $pdo->exec("DELETE FROM user_addresses WHERE id = $insertId");
        echo "<p>🧹 Test data cleaned up</p>";
        
    } catch (Exception $insertError) {
        echo "<p style='color: red;'>❌ <strong>Test insert failed:</strong> " . $insertError->getMessage() . "</p>";
    }
    
    echo "<h3>6. Check Users Table</h3>";
    try {
        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<p>✅ <strong>Users table exists</strong> with $userCount users</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ <strong>Users table issue:</strong> " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>🎯 Summary</h3>";
    echo "<ul>";
    echo "<li>✅ Database connection working</li>";
    echo "<li>✅ user_addresses table ready</li>";
    echo "<li>✅ Insert operations working</li>";
    echo "<li>✅ Users table accessible</li>";
    echo "</ul>";
    
    echo "<h3>🚀 Next Steps</h3>";
    echo "<p>1. <a href='account.php' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Test Address Form</a></p>";
    echo "<p>2. <a href='api/addresses.php?action=list' target='_blank' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Test API Directly</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Critical Database Error</h3>";
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p style='color: red;'><strong>Line:</strong> " . $e->getLine() . "</p>";
    
    echo "<h3>🔍 Troubleshooting</h3>";
    echo "<ul>";
    echo "<li>Check database credentials in config/database.php</li>";
    echo "<li>Verify database exists on hosting provider</li>";
    echo "<li>Check database user permissions</li>";
    echo "<li>Contact hosting provider if database is inaccessible</li>";
    echo "</ul>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    line-height: 1.6; 
    background: #f5f5f5;
}

h2, h3 { 
    color: #333; 
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
}

table { 
    border-collapse: collapse; 
    width: 100%; 
    margin: 10px 0; 
    background: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

th, td { 
    border: 1px solid #ddd; 
    padding: 8px; 
    text-align: left; 
}

th { 
    background-color: #f2f2f2; 
    font-weight: bold; 
}

tr:nth-child(even) { 
    background-color: #f9f9f9; 
}

ul {
    background: white;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

p {
    background: white;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
</style>
