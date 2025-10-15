<?php
/**
 * Fix Checkout Tables - Create orders and order_items tables
 */

// Simple database connection
$host = 'sql105.infinityfree.com';
$database = 'if0_40155099_naporta_db';
$username = 'if0_40155099';
$password = 'Jaishreeramm9';

echo "<h1>🔧 CHECKOUT TABLES FIX</h1>";

try {
    // Connect directly
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database</p>";
    
    // Check existing tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Existing tables:</strong> " . implode(', ', $tables) . "</p>";
    
    // Create orders table
    echo "<h3>Creating orders table...</h3>";
    $ordersSQL = "
    CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        delivery_address TEXT NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($ordersSQL);
    echo "<p>✅ orders table ready</p>";
    
    // Create order_items table
    echo "<h3>Creating order_items table...</h3>";
    $orderItemsSQL = "
    CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($orderItemsSQL);
    echo "<p>✅ order_items table ready</p>";
    
    // Test insert
    echo "<h3>Testing order creation...</h3>";
    
    // Insert test order
    $pdo->exec("
        INSERT INTO orders (user_id, total_amount, delivery_address, payment_method, status) 
        VALUES (1, 25.50, 'Test Address, Test City - SP, CEP: 12345-678', 'cash', 'pending')
    ");
    $testOrderId = $pdo->lastInsertId();
    echo "<p>✅ Test order created (ID: $testOrderId)</p>";
    
    // Insert test order item
    $pdo->exec("
        INSERT INTO order_items (order_id, product_id, quantity, price) 
        VALUES ($testOrderId, 1, 2, 12.75)
    ");
    echo "<p>✅ Test order item created</p>";
    
    // Clean up test data
    $pdo->exec("DELETE FROM order_items WHERE order_id = $testOrderId");
    $pdo->exec("DELETE FROM orders WHERE id = $testOrderId");
    echo "<p>🧹 Test data cleaned up</p>";
    
    echo "<h2>🎉 CHECKOUT TABLES READY!</h2>";
    echo "<p><a href='checkout.php' style='background: green; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>TEST CHECKOUT NOW</a></p>";
    
    // Show table structures
    echo "<h3>Table Structures:</h3>";
    
    echo "<h4>orders table:</h4>";
    $ordersDesc = $pdo->query("DESCRIBE orders")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($ordersDesc as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h4>order_items table:</h4>";
    $orderItemsDesc = $pdo->query("DESCRIBE order_items")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($orderItemsDesc as $column) {
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
    echo "<h2>❌ ERROR</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #333; }
p { background: #f9f9f9; padding: 10px; border-radius: 5px; margin: 10px 0; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; font-weight: bold; }
tr:nth-child(even) { background-color: #f9f9f9; }
</style>
