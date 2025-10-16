<?php
/**
 * SETUP ORDER TABLES - Create orders and order_items tables
 */

$host = 'sql105.infinityfree.com';
$database = 'if0_40155099_naporta_db';
$username = 'if0_40155099';
$password = 'Jaishreeramm9';

echo "<h1>🔧 SETTING UP ORDER TABLES</h1>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database</p>";
    
    // Check if orders table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'orders'")->fetchAll();
    
    if (empty($tables)) {
        echo "<h3>Creating orders table...</h3>";
        $pdo->exec("
            CREATE TABLE orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                delivery_address TEXT NOT NULL,
                payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
                status VARCHAR(20) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p>✅ orders table created</p>";
    } else {
        echo "<p>✅ orders table already exists</p>";
    }
    
    // Check if order_items table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'order_items'")->fetchAll();
    
    if (empty($tables)) {
        echo "<h3>Creating order_items table...</h3>";
        $pdo->exec("
            CREATE TABLE order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order_id (order_id),
                INDEX idx_product_id (product_id),
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p>✅ order_items table created</p>";
    } else {
        echo "<p>✅ order_items table already exists</p>";
    }
    
    // Verify tables
    echo "<h3>📋 Verifying Tables:</h3>";
    
    $ordersDesc = $pdo->query("DESCRIBE orders")->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>orders table structure:</strong></p>";
    echo "<ul>";
    foreach ($ordersDesc as $col) {
        echo "<li>{$col['Field']} ({$col['Type']})</li>";
    }
    echo "</ul>";
    
    $orderItemsDesc = $pdo->query("DESCRIBE order_items")->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>order_items table structure:</strong></p>";
    echo "<ul>";
    foreach ($orderItemsDesc as $col) {
        echo "<li>{$col['Field']} ({$col['Type']})</li>";
    }
    echo "</ul>";
    
    // Test insert
    echo "<h3>🧪 Testing Order Creation:</h3>";
    
    try {
        // Get a user ID (use 1 as test)
        $testUserId = 1;
        
        // Insert test order
        $pdo->exec("
            INSERT INTO orders (user_id, total_amount, delivery_address, payment_method, status) 
            VALUES ($testUserId, 99.99, 'Test Address, Test City - SP, CEP: 12345-678', 'cash', 'pending')
        ");
        $testOrderId = $pdo->lastInsertId();
        echo "<p>✅ Test order created (ID: $testOrderId)</p>";
        
        // Insert test order item
        $pdo->exec("
            INSERT INTO order_items (order_id, product_id, quantity, price) 
            VALUES ($testOrderId, 1, 2, 49.99)
        ");
        echo "<p>✅ Test order item created</p>";
        
        // Verify
        $order = $pdo->query("SELECT * FROM orders WHERE id = $testOrderId")->fetch(PDO::FETCH_ASSOC);
        echo "<p>✅ Order verified: " . json_encode($order) . "</p>";
        
        // Clean up
        $pdo->exec("DELETE FROM order_items WHERE order_id = $testOrderId");
        $pdo->exec("DELETE FROM orders WHERE id = $testOrderId");
        echo "<p>🧹 Test data cleaned up</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Test insert warning: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>✅ ORDER TABLES READY!</h2>";
    echo "<p style='background: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
    echo "<strong>✅ SUCCESS!</strong> Your checkout system is now ready to process orders.<br>";
    echo "<a href='checkout.php' style='background: green; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>GO TO CHECKOUT</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ ERROR</h2>";
    echo "<p style='color: red; background: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine();
    echo "</p>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    line-height: 1.6;
    background: #f5f5f5;
}
h1, h2, h3 { 
    color: #333; 
}
p { 
    background: #f9f9f9; 
    padding: 10px; 
    border-radius: 5px; 
    margin: 10px 0; 
}
ul { 
    background: white; 
    padding: 15px; 
    border-radius: 5px;
    margin: 10px 0;
}
li {
    padding: 5px 0;
}
</style>
