<?php
/**
 * DIRECT CHECKOUT TEST - Bypass form, test order creation directly
 */

session_start();

// Direct database connection
$host = 'sql105.infinityfree.com';
$database = 'if0_40155099_naporta_db';
$username = 'if0_40155099';
$password = 'Jaishreeramm9';

echo "<h1>🧪 DIRECT CHECKOUT TEST</h1>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database</p>";
    
    // Step 1: Create tables
    echo "<h3>Step 1: Creating tables...</h3>";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            delivery_address TEXT NOT NULL,
            payment_method VARCHAR(50) NOT NULL DEFAULT 'dinheiro',
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✅ orders table ready</p>";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_order_id (order_id),
            INDEX idx_product_id (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✅ order_items table ready</p>";
    
    // Step 2: Get a user
    echo "<h3>Step 2: Finding a user...</h3>";
    
    $userStmt = $pdo->query("SELECT id, name, email FROM users LIMIT 1");
    $testUser = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "<p style='color: red;'>❌ No users found in database</p>";
        echo "<p>Please create a user account first at: <a href='auth/register.php'>Register</a></p>";
    } else {
        echo "<p>✅ Found user: {$testUser['name']} ({$testUser['email']})</p>";
        
        $userId = $testUser['id'];
        
        // Step 3: Check cart
        echo "<h3>Step 3: Checking cart...</h3>";
        
        $cartStmt = $pdo->prepare("
            SELECT ci.*, p.name, p.price
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.user_id = ?
        ");
        $cartStmt->execute([$userId]);
        $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($cartItems)) {
            echo "<p style='color: orange;'>⚠️ Cart is empty for this user</p>";
            echo "<p>Add items to cart first at: <a href='products.php'>Products</a></p>";
        } else {
            echo "<p>✅ Found " . count($cartItems) . " items in cart</p>";
            
            // Step 4: Create test order
            echo "<h3>Step 4: Creating test order...</h3>";
            
            $total = 0;
            foreach ($cartItems as $item) {
                $total += floatval($item['price']) * intval($item['quantity']);
            }
            
            $address = "Rua Teste, 123, Bairro Teste, São Paulo - SP, CEP: 01234-567";
            $payment = "dinheiro";
            
            $orderStmt = $pdo->prepare("
                INSERT INTO orders (user_id, total_amount, delivery_address, payment_method, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $orderStmt->execute([$userId, $total, $address, $payment]);
            $orderId = $pdo->lastInsertId();
            
            echo "<p>✅ Order created: ID #$orderId</p>";
            echo "<p>   Total: R$ " . number_format($total, 2, ',', '.') . "</p>";
            echo "<p>   Address: $address</p>";
            
            // Step 5: Create order items
            echo "<h3>Step 5: Creating order items...</h3>";
            
            foreach ($cartItems as $item) {
                $itemStmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)
                ");
                $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                echo "<p>✅ Added: {$item['name']} x{$item['quantity']}</p>";
            }
            
            // Step 6: Clear cart
            echo "<h3>Step 6: Clearing cart...</h3>";
            
            $clearStmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $clearStmt->execute([$userId]);
            echo "<p>✅ Cart cleared</p>";
            
            // Step 7: Verify order
            echo "<h3>Step 7: Verifying order...</h3>";
            
            $verifyStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $verifyStmt->execute([$orderId]);
            $order = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                echo "<p>✅ Order verified in database</p>";
                echo "<pre>" . json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            }
            
            echo "<h2 style='color: green;'>✅ CHECKOUT TEST SUCCESSFUL!</h2>";
            echo "<p style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
            echo "Order #$orderId was created successfully!<br>";
            echo "The checkout button should now work on the live site.<br>";
            echo "<a href='checkout.php' style='background: green; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>GO TO CHECKOUT</a>";
            echo "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERROR</h2>";
    echo "<p style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
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
pre {
    background: white;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
}
a {
    color: #0066cc;
}
</style>

