<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Orders - Na Porta</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #005a87; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🛒 Setup Orders System - Na Porta</h1>
    
    <?php
    if (isset($_POST['setup_orders'])) {
        try {
            require_once 'config/database.php';
            
            echo "<h2>Setting up Orders System...</h2>";
            
            // Create orders table
            $orders_sql = "
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
            )";
            
            $pdo->exec($orders_sql);
            echo "<div class='success'>✅ Orders table created successfully</div>";
            
            // Create order_items table
            $order_items_sql = "
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
            )";
            
            $pdo->exec($order_items_sql);
            echo "<div class='success'>✅ Order items table created successfully</div>";
            
            // Add image_url column to products if it doesn't exist
            $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_url'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(500) NULL AFTER description");
                echo "<div class='success'>✅ Added image_url column to products table</div>";
            } else {
                echo "<div class='info'>ℹ️ Products image_url column already exists</div>";
            }
            
            echo "<div class='success'><strong>🎉 Orders system setup completed successfully!</strong></div>";
            echo "<div class='info'>";
            echo "<h3>✅ What's Now Working:</h3>";
            echo "<ul>";
            echo "<li><strong>Cart Checkout:</strong> Full checkout process with address and payment method</li>";
            echo "<li><strong>Order Creation:</strong> Orders are saved to database with all details</li>";
            echo "<li><strong>Order Items:</strong> Individual products in orders are tracked</li>";
            echo "<li><strong>Order Success:</strong> Beautiful confirmation page after checkout</li>";
            echo "<li><strong>Order History:</strong> Users can view their past orders</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "<p><a href='pages/cart-working.php' style='color: #007cba;'>🛒 Test the Cart Checkout</a></p>";
            echo "<p><a href='pages/home-fixed.php' style='color: #007cba;'>🏠 Go to Homepage</a></p>";
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
    ?>
    
    <div class="info">
        <h3>🔍 Orders System Setup</h3>
        <p>This script will create the necessary database tables for the checkout and orders system.</p>
        <p><strong>Tables to be created:</strong></p>
        <ul>
            <li><code>orders</code> - Store customer orders with delivery info and payment method</li>
            <li><code>order_items</code> - Store individual products in each order</li>
        </ul>
        <p><strong>Features that will work after setup:</strong></p>
        <ul>
            <li>✅ Complete checkout process with address and payment selection</li>
            <li>✅ Order confirmation and success page</li>
            <li>✅ Order history for users</li>
            <li>✅ Admin order management</li>
        </ul>
    </div>
    
    <form method="POST">
        <button type="submit" name="setup_orders">🛒 Setup Orders System</button>
    </form>
    
    <hr>
    <h3>📋 Manual SQL (Alternative)</h3>
    <p>You can also run these SQL commands in phpMyAdmin:</p>
    
    <pre>-- Orders table
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
);

-- Order items table
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
);

-- Add image URL to products (if not exists)
ALTER TABLE products ADD COLUMN image_url VARCHAR(500) NULL AFTER description;</pre>
    
    <?php } ?>
</body>
</html>
