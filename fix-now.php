<?php
// Immediate Database Fix
header('Content-Type: text/html; charset=utf-8');

try {
    require_once 'config/database.php';
    
    echo "<h2>🔧 Fixing Database Now...</h2>";
    
    // Drop and recreate orders table with correct structure
    $pdo->exec("DROP TABLE IF EXISTS order_items");
    $pdo->exec("DROP TABLE IF EXISTS orders");
    
    // Create orders table
    $pdo->exec("
        CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            delivery_address TEXT NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            notes TEXT,
            status ENUM('pending', 'confirmed', 'preparing', 'delivering', 'delivered', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    echo "✅ Orders table created with delivery_address column<br>";
    
    // Create order_items table
    $pdo->exec("
        CREATE TABLE order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )
    ");
    
    echo "✅ Order items table created<br>";
    
    // Create user_addresses table
    $pdo->exec("DROP TABLE IF EXISTS user_addresses");
    $pdo->exec("
        CREATE TABLE user_addresses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(100) NOT NULL,
            address TEXT NOT NULL,
            is_default BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    echo "✅ User addresses table created<br>";
    
    // Test the structure
    $result = $pdo->query("DESCRIBE orders");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('delivery_address', $columns)) {
        echo "✅ Confirmed: delivery_address column exists in orders table<br>";
    } else {
        echo "❌ Error: delivery_address column missing<br>";
    }
    
    echo "<br><h3>🎉 Database Fixed Successfully!</h3>";
    echo "<p>Now you can:</p>";
    echo "<ul>";
    echo "<li>✅ Place orders from cart checkout</li>";
    echo "<li>✅ Save new addresses during checkout</li>";
    echo "<li>✅ Manage addresses in your account</li>";
    echo "</ul>";
    
    echo '<p><a href="pages/cart-working.php" style="background:#007cba;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">🛒 Test Cart Checkout Now</a></p>';
    
} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
    echo "<br><br>If you see a foreign key error, run this in phpMyAdmin:";
    echo "<pre>";
    echo "SET FOREIGN_KEY_CHECKS = 0;\n";
    echo "DROP TABLE IF EXISTS order_items;\n";
    echo "DROP TABLE IF EXISTS orders;\n";
    echo "DROP TABLE IF EXISTS user_addresses;\n";
    echo "SET FOREIGN_KEY_CHECKS = 1;\n";
    echo "</pre>";
    echo "Then refresh this page.";
}
?>
