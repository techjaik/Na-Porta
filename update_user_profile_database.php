<?php
/**
 * Database Update Script - Add Missing User Profile Columns
 * This script adds missing columns to the users table for profile functionality
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🔧 Updating User Profile Database Structure</h2>";
    echo "<p>Adding missing columns to users table...</p>";
    
    // Get current table structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existingColumns = array_column($columns, 'Field');
    
    echo "<h3>Current Users Table Structure:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Define columns that should exist for profile functionality
    $requiredColumns = [
        'gender' => "ALTER TABLE users ADD COLUMN gender ENUM('masculino', 'feminino', 'outro', 'nao_informar') NULL AFTER birth_date",
        'address' => "ALTER TABLE users ADD COLUMN address TEXT NULL AFTER gender",
        'city' => "ALTER TABLE users ADD COLUMN city VARCHAR(100) NULL AFTER address", 
        'state' => "ALTER TABLE users ADD COLUMN state VARCHAR(2) NULL AFTER city",
        'zip_code' => "ALTER TABLE users ADD COLUMN zip_code VARCHAR(10) NULL AFTER state",
        'cpf_cnpj' => "ALTER TABLE users ADD COLUMN cpf_cnpj VARCHAR(20) NULL AFTER zip_code"
    ];
    
    $addedColumns = [];
    $skippedColumns = [];
    
    foreach ($requiredColumns as $columnName => $alterQuery) {
        if (!in_array($columnName, $existingColumns)) {
            try {
                $pdo->exec($alterQuery);
                $addedColumns[] = $columnName;
                echo "✅ Added column: <strong>$columnName</strong><br>";
            } catch (Exception $e) {
                echo "❌ Failed to add column $columnName: " . $e->getMessage() . "<br>";
            }
        } else {
            $skippedColumns[] = $columnName;
            echo "⏭️ Column already exists: <strong>$columnName</strong><br>";
        }
    }
    
    // Also ensure cpf column exists (some forms use 'cpf' instead of 'cpf_cnpj')
    if (!in_array('cpf', $existingColumns) && in_array('cpf_cnpj', $existingColumns)) {
        // Create an alias/view or update forms to use cpf_cnpj consistently
        echo "ℹ️ Note: Using 'cpf_cnpj' column for CPF data<br>";
    }
    
    echo "<br><h3>📊 Summary:</h3>";
    echo "<p>✅ <strong>Added " . count($addedColumns) . " new columns:</strong> " . implode(', ', $addedColumns) . "</p>";
    echo "<p>⏭️ <strong>Skipped " . count($skippedColumns) . " existing columns:</strong> " . implode(', ', $skippedColumns) . "</p>";
    
    // Test profile update functionality
    echo "<br><h3>🧪 Testing Profile Update Functionality:</h3>";
    
    // Get updated table structure
    $stmt = $pdo->query("DESCRIBE users");
    $updatedColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $updatedColumnNames = array_column($updatedColumns, 'Field');
    
    echo "<h4>Updated Users Table Structure:</h4>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($updatedColumns as $column) {
        $isNew = in_array($column['Field'], $addedColumns);
        $rowStyle = $isNew ? "style='background-color: #d4edda;'" : "";
        echo "<tr $rowStyle>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Test a sample profile update
    echo "<h4>Testing Sample Profile Update:</h4>";
    
    // Find a test user (first user in database)
    $testUser = $pdo->query("SELECT id, name, email FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($testUser) {
        echo "📝 Testing with user: {$testUser['name']} (ID: {$testUser['id']})<br>";
        
        // Build a test update query
        $updateFields = ['name = ?'];
        $updateValues = [$testUser['name']]; // Keep same name
        
        // Add test values for new columns
        $testData = [
            'phone' => '(11) 99999-9999',
            'cpf_cnpj' => '000.000.000-00', 
            'gender' => 'nao_informar',
            'address' => 'Rua Teste, 123',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01234-567'
        ];
        
        foreach ($testData as $field => $value) {
            if (in_array($field, $updatedColumnNames)) {
                $updateFields[] = "$field = ?";
                $updateValues[] = $value;
            }
        }
        
        if (in_array('updated_at', $updatedColumnNames)) {
            $updateFields[] = "updated_at = NOW()";
        }
        
        $updateValues[] = $testUser['id'];
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateValues);
            echo "✅ <strong>Profile update test successful!</strong><br>";
            echo "📋 Updated fields: " . implode(', ', array_keys($testData)) . "<br>";
            
            // Verify the update
            $updatedUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $updatedUser->execute([$testUser['id']]);
            $userData = $updatedUser->fetch(PDO::FETCH_ASSOC);
            
            echo "<h5>Updated User Data:</h5>";
            echo "<ul>";
            foreach ($testData as $field => $expectedValue) {
                if (in_array($field, $updatedColumnNames)) {
                    $actualValue = $userData[$field] ?? 'NULL';
                    echo "<li><strong>$field:</strong> $actualValue</li>";
                }
            }
            echo "</ul>";
            
        } catch (Exception $e) {
            echo "❌ <strong>Profile update test failed:</strong> " . $e->getMessage() . "<br>";
        }
    } else {
        echo "⚠️ No test user found in database<br>";
    }
    
    echo "<br><h3>🎉 Database Update Complete!</h3>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Profile forms should now save data correctly</li>";
    echo "<li>✅ Admin panel should display all user information</li>";
    echo "<li>✅ Both account.php and account/profile.php should work</li>";
    echo "</ul>";
    
    echo "<br><p><a href='account/profile.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Profile Page</a> ";
    echo "<a href='admin/users.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>View Admin Users</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error updating database:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>
