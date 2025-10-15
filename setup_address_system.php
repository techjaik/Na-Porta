<?php
/**
 * Setup Address Management System
 * Creates user_addresses table and initializes the address system
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🏠 Setting Up Address Management System</h2>";
    
    // 1. Check if user_addresses table exists
    echo "<h3>1. Checking Address Table</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE user_addresses");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ <strong>user_addresses table exists</strong><br>";
        echo "<h4>Current Structure:</h4>";
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
        
    } catch (Exception $e) {
        echo "⚠️ <strong>user_addresses table doesn't exist. Creating...</strong><br>";
        
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
            echo "✅ <strong>user_addresses table created successfully!</strong><br>";
        } catch (Exception $createError) {
            echo "❌ <strong>Error creating table:</strong> " . $createError->getMessage() . "<br>";
        }
    }
    
    // 2. Check existing addresses
    echo "<br><h3>2. Current Addresses</h3>";
    try {
        $addresses = $pdo->query("
            SELECT ua.*, u.name as user_name, u.email 
            FROM user_addresses ua 
            JOIN users u ON ua.user_id = u.id 
            ORDER BY ua.created_at DESC 
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($addresses)) {
            echo "ℹ️ <strong>No addresses found</strong><br>";
        } else {
            echo "📍 <strong>Found " . count($addresses) . " addresses:</strong><br>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>ID</th><th>User</th><th>Name</th><th>Address</th><th>Default</th><th>Created</th></tr>";
            foreach ($addresses as $addr) {
                $fullAddress = "{$addr['street']}, {$addr['number']} - {$addr['neighborhood']}, {$addr['city']}/{$addr['state']} - {$addr['cep']}";
                $isDefault = $addr['is_default'] ? '✅ Yes' : '❌ No';
                echo "<tr>";
                echo "<td>{$addr['id']}</td>";
                echo "<td>{$addr['user_name']} ({$addr['email']})</td>";
                echo "<td>{$addr['name']}</td>";
                echo "<td>" . htmlspecialchars($fullAddress) . "</td>";
                echo "<td>$isDefault</td>";
                echo "<td>{$addr['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "❌ <strong>Error checking addresses:</strong> " . $e->getMessage() . "<br>";
    }
    
    // 3. Create sample addresses for testing
    if (isset($_GET['create_sample']) && $_GET['create_sample'] === '1') {
        echo "<br><h3>3. Creating Sample Addresses</h3>";
        
        // Get first user for testing
        $user = $pdo->query("SELECT id, name FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $sampleAddresses = [
                [
                    'name' => 'Casa',
                    'cep' => '01234-567',
                    'street' => 'Rua das Flores',
                    'number' => '123',
                    'complement' => 'Apto 45',
                    'neighborhood' => 'Centro',
                    'city' => 'São Paulo',
                    'state' => 'SP',
                    'is_default' => 1
                ],
                [
                    'name' => 'Trabalho',
                    'cep' => '04567-890',
                    'street' => 'Av. Paulista',
                    'number' => '1000',
                    'complement' => 'Sala 1001',
                    'neighborhood' => 'Bela Vista',
                    'city' => 'São Paulo',
                    'state' => 'SP',
                    'is_default' => 0
                ]
            ];
            
            foreach ($sampleAddresses as $addr) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO user_addresses (user_id, name, cep, street, number, complement, neighborhood, city, state, is_default) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $user['id'], $addr['name'], $addr['cep'], $addr['street'], 
                        $addr['number'], $addr['complement'], $addr['neighborhood'], 
                        $addr['city'], $addr['state'], $addr['is_default']
                    ]);
                    echo "✅ Created sample address: {$addr['name']}<br>";
                } catch (Exception $e) {
                    echo "❌ Error creating {$addr['name']}: " . $e->getMessage() . "<br>";
                }
            }
        } else {
            echo "⚠️ No users found to create sample addresses<br>";
        }
    }
    
    // 4. Check orders table for address integration
    echo "<br><h3>4. Orders Table Integration</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE orders");
        $orderColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $orderColumnNames = array_column($orderColumns, 'Field');
        
        $addressColumns = ['delivery_name', 'delivery_cep', 'delivery_street', 'delivery_number', 'delivery_complement', 'delivery_neighborhood', 'delivery_city', 'delivery_state'];
        $missingColumns = [];
        
        foreach ($addressColumns as $col) {
            if (!in_array($col, $orderColumnNames)) {
                $missingColumns[] = $col;
            }
        }
        
        if (empty($missingColumns)) {
            echo "✅ <strong>Orders table has all address columns</strong><br>";
        } else {
            echo "⚠️ <strong>Missing address columns in orders table:</strong> " . implode(', ', $missingColumns) . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Error checking orders table:</strong> " . $e->getMessage() . "<br>";
    }
    
    echo "<br><h3>🎯 System Status</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
    echo "<strong>✅ Address Management System Ready!</strong><br>";
    echo "Users can now manage multiple addresses with default selection.";
    echo "</div>";
    
    echo "<br><h3>🛠️ Tools</h3>";
    echo "<p>";
    echo "<a href='?create_sample=1' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Create Sample Addresses</a>";
    echo "<a href='account.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Test Address Page</a>";
    echo "<a href='admin/users.php' style='background: #6c757d; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Admin Users</a>";
    echo "</p>";
    
    echo "<br><h3>📋 Next Steps</h3>";
    echo "<ol>";
    echo "<li>✅ Database table created and ready</li>";
    echo "<li>🔄 Implement address management UI</li>";
    echo "<li>🔄 Integrate with checkout system</li>";
    echo "<li>🔄 Add admin interface for address management</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h3>❌ Setup Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; font-weight: bold; }
tr:nth-child(even) { background-color: #f9f9f9; }
h2, h3 { color: #333; }
</style>
