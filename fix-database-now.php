<?php
// Direct Database Fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Fix Database</title></head><body>";
echo "<h1>🔧 Fixing Database Now</h1>";

try {
    // Direct PDO connection
    $pdo = new PDO(
        "mysql:host=localhost;dbname=na_porta_db;charset=utf8",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database</p>";
    
    // Check if orders table exists
    $result = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($result->rowCount() == 0) {
        echo "<p>❌ Orders table doesn't exist - creating it...</p>";
        
        // Create orders table
        $pdo->exec("
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
            )
        ");
        echo "<p>✅ Orders table created</p>";
    } else {
        echo "<p>ℹ️ Orders table exists - checking columns...</p>";
        
        // Check if delivery_address column exists
        $result = $pdo->query("SHOW COLUMNS FROM orders LIKE 'delivery_address'");
        if ($result->rowCount() == 0) {
            echo "<p>❌ delivery_address column missing - adding it...</p>";
            $pdo->exec("ALTER TABLE orders ADD COLUMN delivery_address TEXT NOT NULL AFTER total_amount");
            echo "<p>✅ delivery_address column added</p>";
        } else {
            echo "<p>✅ delivery_address column exists</p>";
        }
    }
    
    // Create order_items table if not exists
    $result = $pdo->query("SHOW TABLES LIKE 'order_items'");
    if ($result->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                price DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "<p>✅ Order items table created</p>";
    }
    
    // Create user_addresses table if not exists
    $result = $pdo->query("SHOW TABLES LIKE 'user_addresses'");
    if ($result->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE user_addresses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(100) NOT NULL,
                address TEXT NOT NULL,
                is_default BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "<p>✅ User addresses table created</p>";
    }
    
    // Final verification
    $result = $pdo->query("DESCRIBE orders");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('delivery_address', $columns)) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h2>🎉 SUCCESS!</h2>";
        echo "<p>✅ delivery_address column confirmed in orders table</p>";
        echo "<p>✅ Database is now ready for checkout</p>";
        echo "</div>";
        
        echo "<p><a href='pages/cart-working.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛒 Test Checkout Now</a></p>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Still Missing Column</h3>";
        echo "<p>The delivery_address column is still missing. Try running this SQL manually in phpMyAdmin:</p>";
        echo "<code>ALTER TABLE orders ADD COLUMN delivery_address TEXT NOT NULL AFTER total_amount;</code>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Database Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<p><strong>Database connection issue.</strong> Check if:</p>";
        echo "<ul>";
        echo "<li>XAMPP MySQL is running</li>";
        echo "<li>Database 'na_porta_db' exists</li>";
        echo "<li>Username/password are correct</li>";
        echo "</ul>";
    }
}

echo "</body></html>";
?>
