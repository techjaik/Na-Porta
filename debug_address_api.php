<?php
/**
 * Debug Address API - Test address functionality
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

try {
    $db = Database::getInstance();
    $auth = new Auth();
    
    echo "<h2>🔍 Address API Debug</h2>";
    
    // 1. Check if user is logged in
    echo "<h3>1. User Authentication</h3>";
    $user = $auth->getCurrentUser();
    if ($user) {
        echo "✅ <strong>User logged in:</strong> {$user['name']} (ID: {$user['id']})<br>";
    } else {
        echo "❌ <strong>No user logged in</strong><br>";
        echo "<p><a href='auth/login.php'>Login first</a> to test address functionality.</p>";
        exit;
    }
    
    // 2. Check if user_addresses table exists
    echo "<br><h3>2. Database Table Check</h3>";
    $pdo = $db->getConnection();
    try {
        $stmt = $pdo->query("DESCRIBE user_addresses");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ <strong>user_addresses table exists</strong><br>";
        echo "<h4>Table Structure:</h4>";
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
        echo "❌ <strong>user_addresses table doesn't exist</strong><br>";
        echo "Error: " . $e->getMessage() . "<br>";
        
        // Try to create the table
        echo "<br>🔧 <strong>Creating user_addresses table...</strong><br>";
        try {
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
            
            $pdo->exec($createTableSQL);
            echo "✅ <strong>user_addresses table created successfully!</strong><br>";
        } catch (Exception $createError) {
            echo "❌ <strong>Failed to create table:</strong> " . $createError->getMessage() . "<br>";
        }
    }
    
    // 3. Test API endpoints
    echo "<br><h3>3. API Endpoint Tests</h3>";
    
    // Test GET list
    echo "<h4>Testing GET /api/addresses.php?action=list</h4>";
    try {
        $addresses = $db->fetchAll("
            SELECT * FROM user_addresses 
            WHERE user_id = ? 
            ORDER BY is_default DESC, created_at DESC
        ", [$user['id']]);
        
        echo "✅ <strong>GET list successful</strong><br>";
        echo "Found " . count($addresses) . " addresses<br>";
        
        if (!empty($addresses)) {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>ID</th><th>Name</th><th>Address</th><th>Default</th></tr>";
            foreach ($addresses as $addr) {
                echo "<tr>";
                echo "<td>{$addr['id']}</td>";
                echo "<td>{$addr['name']}</td>";
                echo "<td>{$addr['street']}, {$addr['number']} - {$addr['city']}/{$addr['state']}</td>";
                echo "<td>" . ($addr['is_default'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>GET list failed:</strong> " . $e->getMessage() . "<br>";
    }
    
    // 4. Test adding an address
    if (isset($_GET['test_add']) && $_GET['test_add'] === '1') {
        echo "<br><h4>Testing POST add address</h4>";
        
        $testAddress = [
            'name' => 'Test Address ' . date('His'),
            'cep' => '01234-567',
            'street' => 'Rua de Teste',
            'number' => '123',
            'complement' => 'Apto 1',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'is_default' => false
        ];
        
        try {
            // If this is set as default, remove default from other addresses
            if ($testAddress['is_default']) {
                $db->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?", [$user['id']]);
            }
            
            // Insert new address
            $db->query("
                INSERT INTO user_addresses (user_id, name, cep, street, number, complement, neighborhood, city, state, is_default) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $user['id'], 
                $testAddress['name'], 
                $testAddress['cep'], 
                $testAddress['street'], 
                $testAddress['number'], 
                $testAddress['complement'], 
                $testAddress['neighborhood'], 
                $testAddress['city'], 
                $testAddress['state'], 
                $testAddress['is_default'] ? 1 : 0
            ]);
            
            $newId = $db->lastInsertId();
            echo "✅ <strong>POST add successful!</strong> New address ID: $newId<br>";
            
        } catch (Exception $e) {
            echo "❌ <strong>POST add failed:</strong> " . $e->getMessage() . "<br>";
        }
    }
    
    // 5. Direct API test
    echo "<br><h3>4. Direct API Test</h3>";
    echo "<p>Test the API directly:</p>";
    echo "<p>";
    echo "<a href='?test_add=1' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Test Add Address</a>";
    echo "<a href='api/addresses.php?action=list' target='_blank' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Test API List</a>";
    echo "</p>";
    
    // 6. JavaScript test
    echo "<br><h3>5. JavaScript API Test</h3>";
    echo "<button onclick='testAddressAPI()' class='btn btn-primary'>Test Add Address via JavaScript</button>";
    echo "<div id='jsTestResult' style='margin-top: 10px;'></div>";
    
    echo "<br><h3>🎯 Recommendations</h3>";
    echo "<ul>";
    echo "<li>If table creation failed, check database permissions</li>";
    echo "<li>If API tests fail, check error logs</li>";
    echo "<li>Try the JavaScript test to simulate the actual form submission</li>";
    echo "<li>Check browser console for JavaScript errors</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Critical Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>

<script>
function testAddressAPI() {
    const testData = {
        action: 'add',
        name: 'JS Test Address ' + Date.now(),
        cep: '01234-567',
        street: 'Rua JavaScript',
        number: '456',
        complement: 'Sala JS',
        neighborhood: 'Bairro Teste',
        city: 'São Paulo',
        state: 'SP',
        is_default: 0
    };
    
    fetch('api/addresses.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(testData)
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('jsTestResult');
        if (data.success) {
            resultDiv.innerHTML = '<div style="color: green;">✅ <strong>Success!</strong> Address added with ID: ' + data.address_id + '</div>';
        } else {
            resultDiv.innerHTML = '<div style="color: red;">❌ <strong>Error:</strong> ' + data.message + '</div>';
            if (data.debug) {
                resultDiv.innerHTML += '<div style="color: red; font-size: 12px;">Debug: ' + JSON.stringify(data.debug) + '</div>';
            }
        }
    })
    .catch(error => {
        document.getElementById('jsTestResult').innerHTML = '<div style="color: red;">❌ <strong>Network Error:</strong> ' + error.message + '</div>';
    });
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; font-weight: bold; }
tr:nth-child(even) { background-color: #f9f9f9; }
h2, h3 { color: #333; }
.btn { padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
</style>
