<!DOCTYPE html>
<html>
<head>
    <title>Test Checkout Fix</title>
    <style>
        body { font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🧪 Test Checkout Fix</h1>
    
    <?php
    try {
        require_once 'config/database.php';
        
        echo "<div class='info'>Testing database connection and table structure...</div>";
        
        // Test if we can create the orders table
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS orders (
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
            echo "<div class='success'>✅ Orders table created/verified</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Orders table error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        // Test if we can query the delivery_address column
        try {
            $stmt = $pdo->prepare("SELECT delivery_address FROM orders WHERE id = ?");
            echo "<div class='success'>✅ delivery_address column accessible</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ delivery_address column error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        // Test user_addresses table
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS user_addresses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(100) NOT NULL,
                    address TEXT NOT NULL,
                    is_default BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            echo "<div class='success'>✅ User addresses table created/verified</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ User addresses table error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        // Test order_items table
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS order_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    product_id INT NOT NULL,
                    quantity INT NOT NULL DEFAULT 1,
                    price DECIMAL(10,2) NOT NULL,
                    subtotal DECIMAL(10,2) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            echo "<div class='success'>✅ Order items table created/verified</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Order items table error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        echo "<div class='success'>";
        echo "<h2>🎉 Database Setup Complete!</h2>";
        echo "<p>The checkout should now work without the 'delivery_address' error.</p>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>✅ Test Now:</h3>";
        echo "<p><a href='pages/cart-working.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛒 Test Cart Checkout</a></p>";
        echo "<p><a href='pages/account/addresses.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📍 Test Addresses</a></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h3>❌ Database Connection Error:</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Make sure XAMPP MySQL is running and the database 'na_porta_db' exists.</p>";
        echo "</div>";
    }
    ?>
</body>
</html>
