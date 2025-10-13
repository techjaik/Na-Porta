<?php
// Quick fix for database issues
require_once 'config/database.php';

try {
    echo "🔧 Fixing database issues...\n\n";
    
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
    echo "✅ Orders table created\n";
    
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
    echo "✅ Order items table created\n";
    
    // Drop and recreate addresses table to ensure correct structure
    $pdo->exec("DROP TABLE IF EXISTS user_addresses");
    
    // Create addresses table with correct structure
    $addresses_sql = "
    CREATE TABLE user_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        is_default BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($addresses_sql);
    echo "✅ User addresses table created with correct structure\n";
    
    // Add image_url to products if missing
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_url'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(500) NULL AFTER description");
        echo "✅ Added image_url to products\n";
    }
    
    echo "\n🎉 Database fixed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
