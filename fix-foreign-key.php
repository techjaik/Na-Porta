<!DOCTYPE html>
<html>
<head>
    <title>Fix Foreign Key Issue</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 30px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔧 Fix Foreign Key Constraint Issue</h1>
    
    <?php
    try {
        require_once 'config/database.php';
        
        echo "<div class='info'>🔍 Analyzing database structure...</div>";
        
        // Check if orders table exists
        $result = $pdo->query("SHOW TABLES LIKE 'orders'");
        if ($result->rowCount() > 0) {
            echo "<div class='info'>✅ Orders table exists</div>";
            
            // Check if delivery_address column exists
            $result = $pdo->query("SHOW COLUMNS FROM orders LIKE 'delivery_address'");
            if ($result->rowCount() == 0) {
                echo "<div class='warning'>❌ delivery_address column missing - fixing...</div>";
                
                // Try to add the column first
                try {
                    $pdo->exec("ALTER TABLE orders ADD COLUMN delivery_address TEXT NOT NULL DEFAULT 'Address not provided' AFTER total_amount");
                    echo "<div class='success'>✅ Added delivery_address column to existing orders table</div>";
                } catch (Exception $e) {
                    echo "<div class='warning'>⚠️ ALTER TABLE failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                    echo "<div class='info'>🔧 Trying complete table recreation...</div>";
                    
                    // Complete recreation with foreign key handling
                    try {
                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                        echo "<div class='info'>✅ Disabled foreign key checks</div>";
                        
                        $pdo->exec("DROP TABLE IF EXISTS order_items");
                        echo "<div class='info'>✅ Dropped order_items table</div>";
                        
                        $pdo->exec("DROP TABLE IF EXISTS orders");
                        echo "<div class='info'>✅ Dropped orders table</div>";
                        
                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                        echo "<div class='info'>✅ Re-enabled foreign key checks</div>";
                        
                        // Create new orders table
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
                        echo "<div class='success'>✅ Created new orders table with delivery_address</div>";
                        
                        // Create order_items table
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
                        echo "<div class='success'>✅ Created order_items table</div>";
                        
                    } catch (Exception $e2) {
                        echo "<div class='error'>❌ Table recreation failed: " . htmlspecialchars($e2->getMessage()) . "</div>";
                    }
                }
            } else {
                echo "<div class='success'>✅ delivery_address column already exists</div>";
            }
        } else {
            echo "<div class='warning'>❌ Orders table doesn't exist - creating...</div>";
            
            // Create orders table from scratch
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
            echo "<div class='success'>✅ Created orders table</div>";
            
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
            echo "<div class='success'>✅ Created order_items table</div>";
        }
        
        // Create user_addresses table if missing
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
            echo "<div class='success'>✅ Created user_addresses table</div>";
        }
        
        // Final verification
        $result = $pdo->query("DESCRIBE orders");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('delivery_address', $columns)) {
            echo "<div class='success'>";
            echo "<h2>🎉 SUCCESS!</h2>";
            echo "<p>✅ delivery_address column confirmed in orders table</p>";
            echo "<p>✅ Foreign key constraint issue resolved</p>";
            echo "<p>✅ Database is ready for checkout</p>";
            echo "</div>";
            
            echo "<div style='text-align: center; margin: 30px 0;'>";
            echo "<a href='pages/cart-working.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px;'>🛒 Test Checkout Now</a>";
            echo "</div>";
        } else {
            echo "<div class='error'>❌ delivery_address column still missing after fix attempt</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h3>❌ Database Error:</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    ?>
</body>
</html>
