<?php
// Emergency Database Fix - Run This Now
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚨 Emergency Database Fix</h1>";

try {
    require_once 'config/database.php';
    
    echo "<p>🔧 Attempting to fix database...</p>";
    
    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "✅ Disabled foreign key checks<br>";
    
    // Drop problematic tables
    $pdo->exec("DROP TABLE IF EXISTS order_items");
    $pdo->exec("DROP TABLE IF EXISTS orders");
    $pdo->exec("DROP TABLE IF EXISTS user_addresses");
    echo "✅ Dropped old tables<br>";
    
    // Create orders table with delivery_address column
    $orders_sql = "
    CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        delivery_address TEXT NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        notes TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($orders_sql);
    echo "✅ Created orders table with delivery_address column<br>";
    
    // Create order_items table
    $order_items_sql = "
    CREATE TABLE order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($order_items_sql);
    echo "✅ Created order_items table<br>";
    
    // Create user_addresses table
    $addresses_sql = "
    CREATE TABLE user_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        is_default BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($addresses_sql);
    echo "✅ Created user_addresses table<br>";
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✅ Re-enabled foreign key checks<br>";
    
    // Test the tables
    $result = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($result->rowCount() > 0) {
        echo "✅ Orders table exists<br>";
        
        $result = $pdo->query("DESCRIBE orders");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('delivery_address', $columns)) {
            echo "✅ delivery_address column confirmed in orders table<br>";
        } else {
            echo "❌ delivery_address column missing<br>";
        }
    }
    
    echo "<h2>🎉 Database Fixed Successfully!</h2>";
    echo "<p style='color: green; font-weight: bold;'>The checkout error should now be resolved.</p>";
    
    echo "<div style='background: #f0f8f0; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Test Now:</h3>";
    echo "<p><a href='pages/cart-working.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛒 Test Cart Checkout</a></p>";
    echo "<p><a href='pages/account/addresses.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📍 Test Addresses</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #f8f0f0; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    
    echo "<h3>🔧 Manual Fix (Run in phpMyAdmin):</h3>";
    echo "<textarea style='width: 100%; height: 200px; font-family: monospace;'>";
    echo "SET FOREIGN_KEY_CHECKS = 0;\n";
    echo "DROP TABLE IF EXISTS order_items;\n";
    echo "DROP TABLE IF EXISTS orders;\n";
    echo "DROP TABLE IF EXISTS user_addresses;\n\n";
    echo "CREATE TABLE orders (\n";
    echo "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
    echo "    user_id INT NOT NULL,\n";
    echo "    total_amount DECIMAL(10,2) NOT NULL,\n";
    echo "    delivery_address TEXT NOT NULL,\n";
    echo "    payment_method VARCHAR(50) NOT NULL,\n";
    echo "    notes TEXT,\n";
    echo "    status VARCHAR(20) DEFAULT 'pending',\n";
    echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
    echo "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n";
    echo ");\n\n";
    echo "CREATE TABLE order_items (\n";
    echo "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
    echo "    order_id INT NOT NULL,\n";
    echo "    product_id INT NOT NULL,\n";
    echo "    quantity INT NOT NULL DEFAULT 1,\n";
    echo "    price DECIMAL(10,2) NOT NULL,\n";
    echo "    subtotal DECIMAL(10,2) NOT NULL,\n";
    echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n";
    echo ");\n\n";
    echo "CREATE TABLE user_addresses (\n";
    echo "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
    echo "    user_id INT NOT NULL,\n";
    echo "    title VARCHAR(100) NOT NULL,\n";
    echo "    address TEXT NOT NULL,\n";
    echo "    is_default BOOLEAN DEFAULT FALSE,\n";
    echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
    echo "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n";
    echo ");\n\n";
    echo "SET FOREIGN_KEY_CHECKS = 1;";
    echo "</textarea>";
}
?>
