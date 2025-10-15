<?php
/**
 * Direct Address API Test - Bypass form and test API directly
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

echo "<h2>🧪 Direct Address API Test</h2>";

try {
    $db = Database::getInstance();
    $auth = new Auth();
    
    // Check if user is logged in
    $user = $auth->getCurrentUser();
    if (!$user) {
        echo "<p style='color: red;'>❌ <strong>No user logged in</strong></p>";
        echo "<p><a href='auth/login.php'>Login first</a> to test address functionality.</p>";
        exit;
    }
    
    echo "<p>✅ <strong>User logged in:</strong> {$user['name']} (ID: {$user['id']})</p>";
    
    // Test 1: Check table exists
    echo "<h3>1. Database Table Check</h3>";
    $pdo = $db->getConnection();
    try {
        $pdo->query("SELECT 1 FROM user_addresses LIMIT 1");
        echo "<p>✅ user_addresses table exists</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Creating user_addresses table...</p>";
        
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
        echo "<p>✅ user_addresses table created</p>";
    }
    
    // Test 2: Direct database insert
    echo "<h3>2. Direct Database Insert Test</h3>";
    
    $testData = [
        'user_id' => $user['id'],
        'name' => 'Direct Test ' . date('His'),
        'cep' => '01234-567',
        'street' => 'Rua Teste Direto',
        'number' => '999',
        'complement' => 'Teste',
        'neighborhood' => 'Centro',
        'city' => 'São Paulo',
        'state' => 'SP',
        'is_default' => 0
    ];
    
    try {
        $stmt = $db->query("
            INSERT INTO user_addresses (user_id, name, cep, street, number, complement, neighborhood, city, state, is_default) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $testData['user_id'],
            $testData['name'],
            $testData['cep'],
            $testData['street'],
            $testData['number'],
            $testData['complement'],
            $testData['neighborhood'],
            $testData['city'],
            $testData['state'],
            $testData['is_default']
        ]);
        
        $insertId = $db->lastInsertId();
        echo "<p>✅ <strong>Direct database insert successful!</strong> New ID: $insertId</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ <strong>Direct database insert failed:</strong> " . $e->getMessage() . "</p>";
    }
    
    // Test 3: API call simulation
    echo "<h3>3. API Call Simulation</h3>";
    
    $apiData = [
        'action' => 'add',
        'name' => 'API Test ' . date('His'),
        'cep' => '12345-678',
        'street' => 'Rua API Test',
        'number' => '888',
        'complement' => 'API',
        'neighborhood' => 'Teste',
        'city' => 'Rio de Janeiro',
        'state' => 'RJ',
        'is_default' => 0
    ];
    
    // Simulate the API logic
    try {
        $name = trim($apiData['name']);
        $cep = trim($apiData['cep']);
        $street = trim($apiData['street']);
        $number = trim($apiData['number']);
        $complement = trim($apiData['complement']);
        $neighborhood = trim($apiData['neighborhood']);
        $city = trim($apiData['city']);
        $state = trim($apiData['state']);
        $isDefault = (bool)$apiData['is_default'];
        
        // Validation
        if (empty($name) || empty($cep) || empty($street) || empty($number) || 
            empty($neighborhood) || empty($city) || empty($state)) {
            throw new Exception('Validation failed: missing required fields');
        }
        
        // If this is set as default, remove default from other addresses
        if ($isDefault) {
            $db->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?", [$user['id']]);
        }
        
        // Insert new address
        $stmt = $db->query("
            INSERT INTO user_addresses (user_id, name, cep, street, number, complement, neighborhood, city, state, is_default) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [$user['id'], $name, $cep, $street, $number, $complement, $neighborhood, $city, $state, $isDefault ? 1 : 0]);
        
        $addressId = $db->lastInsertId();
        
        echo "<p>✅ <strong>API simulation successful!</strong> New address ID: $addressId</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ <strong>API simulation failed:</strong> " . $e->getMessage() . "</p>";
    }
    
    // Test 4: Show current addresses
    echo "<h3>4. Current Addresses</h3>";
    
    $addresses = $db->fetchAll("
        SELECT * FROM user_addresses 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ", [$user['id']]);
    
    if (empty($addresses)) {
        echo "<p>No addresses found</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Address</th><th>Default</th><th>Created</th></tr>";
        foreach ($addresses as $addr) {
            echo "<tr>";
            echo "<td>{$addr['id']}</td>";
            echo "<td>{$addr['name']}</td>";
            echo "<td>{$addr['street']}, {$addr['number']} - {$addr['city']}/{$addr['state']}</td>";
            echo "<td>" . ($addr['is_default'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($addr['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 5: Real API call via cURL
    echo "<h3>5. Real API Call Test</h3>";
    echo "<button onclick='testRealAPI()' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Test Real API Call</button>";
    echo "<div id='apiResult' style='margin-top: 10px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'></div>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Critical Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}
?>

<script>
function testRealAPI() {
    const apiData = {
        action: 'add',
        name: 'Real API Test ' + Date.now(),
        cep: '98765-432',
        street: 'Rua Real API',
        number: '777',
        complement: 'Real Test',
        neighborhood: 'API Bairro',
        city: 'Brasília',
        state: 'DF',
        is_default: 0
    };
    
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = '<p>🔄 Testing API call...</p>';
    
    fetch('api/addresses.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(apiData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        try {
            const data = JSON.parse(text);
            console.log('Parsed response:', data);
            
            if (data.success) {
                resultDiv.innerHTML = '<p style="color: green;">✅ <strong>Success!</strong> Address added with ID: ' + data.address_id + '</p>';
            } else {
                resultDiv.innerHTML = '<p style="color: red;">❌ <strong>Error:</strong> ' + data.message + '</p>';
                if (data.debug) {
                    resultDiv.innerHTML += '<p style="color: red; font-size: 12px;">Debug: ' + JSON.stringify(data.debug) + '</p>';
                }
            }
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            resultDiv.innerHTML = '<p style="color: red;">❌ <strong>Parse Error:</strong> ' + parseError.message + '</p>';
            resultDiv.innerHTML += '<p style="color: red; font-size: 12px;">Raw response: ' + text + '</p>';
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        resultDiv.innerHTML = '<p style="color: red;">❌ <strong>Network Error:</strong> ' + error.message + '</p>';
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
</style>
