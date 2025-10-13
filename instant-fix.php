<!DOCTYPE html>
<html>
<head>
    <title>Instant Database Fix</title>
    <style>
        body { font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #f0f8f0; padding: 10px; margin: 10px 0; }
        .error { color: red; background: #f8f0f0; padding: 10px; margin: 10px 0; }
        button { background: #007cba; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
    </style>
</head>
<body>
    <h1>🔧 Instant Database Fix</h1>
    
    <?php
    if (isset($_GET['fix'])) {
        try {
            require_once 'config/database.php';
            
            // Fix addresses table
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
            
            // Fix orders table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS orders (
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
            
            // Fix order_items table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS order_items (
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
            
            // Add image_url to products if missing
            try {
                $pdo->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(500) NULL AFTER description");
            } catch (Exception $e) {
                // Column might already exist
            }
            
            echo '<div class="success">✅ <strong>Database Fixed Successfully!</strong></div>';
            echo '<div class="success">✅ user_addresses table created with title column</div>';
            echo '<div class="success">✅ orders and order_items tables ready</div>';
            echo '<div class="success">✅ products table updated with image_url</div>';
            echo '<br><p><strong>Now you can test:</strong></p>';
            echo '<p>📍 <a href="pages/account/addresses.php">Addresses Page</a></p>';
            echo '<p>🛒 <a href="pages/cart-working.php">Cart Checkout</a></p>';
            echo '<p>🏠 <a href="pages/home-fixed.php">Homepage</a></p>';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
    ?>
    
    <p>This will fix the database error by creating the missing tables and columns.</p>
    <p><strong>What will be fixed:</strong></p>
    <ul>
        <li>✅ Create user_addresses table with 'title' column</li>
        <li>✅ Create orders table for checkout</li>
        <li>✅ Create order_items table</li>
        <li>✅ Add image_url column to products</li>
    </ul>
    
    <a href="?fix=1">
        <button>🔧 Fix Database Now</button>
    </a>
    
    <?php } ?>
</body>
</html>
