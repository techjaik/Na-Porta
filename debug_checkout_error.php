<?php
/**
 * 🔍 DEBUG CHECKOUT ERROR
 * Diagnose and fix checkout issues
 */

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$auth = new Auth();
$db = Database::getInstance();
$user = $auth->getCurrentUser();

echo "<!DOCTYPE html>";
echo "<html lang='pt-BR'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Debug Checkout Error</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
echo ".pass { background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 3px; }";
echo ".fail { background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 3px; }";
echo ".warn { background: #fff3cd; color: #856404; padding: 10px; margin: 5px 0; border-radius: 3px; }";
echo "code { background: #f0f0f0; padding: 10px; display: block; margin: 10px 0; border-radius: 3px; overflow-x: auto; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🔍 CHECKOUT ERROR DIAGNOSIS</h1>";

// ============================================
// 1. Check User
// ============================================
echo "<div class='section'>";
echo "<h2>1️⃣ User Authentication</h2>";

if ($user) {
    echo "<div class='pass'>✅ User logged in: " . htmlspecialchars($user['name']) . " (ID: " . $user['id'] . ")</div>";
} else {
    echo "<div class='fail'>❌ User not logged in</div>";
}

echo "</div>";

// ============================================
// 2. Check Cart
// ============================================
echo "<div class='section'>";
echo "<h2>2️⃣ Cart Items</h2>";

try {
    if ($user) {
        $cartItems = $db->fetchAll("
            SELECT ci.*, p.name, p.price
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.user_id = ?
        ", [$user['id']]);
    } else {
        $sessionId = session_id();
        $cartItems = $db->fetchAll("
            SELECT ci.*, p.name, p.price
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.session_id = ?
        ", [$sessionId]);
    }
    
    if (!empty($cartItems)) {
        echo "<div class='pass'>✅ Cart has " . count($cartItems) . " items</div>";
        foreach ($cartItems as $item) {
            echo "<div style='background: #f9f9f9; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
            echo "Product: " . htmlspecialchars($item['name']) . "<br>";
            echo "Quantity: " . $item['quantity'] . "<br>";
            echo "Price: R$ " . number_format($item['price'], 2, ',', '.') . "<br>";
            echo "</div>";
        }
    } else {
        echo "<div class='fail'>❌ Cart is empty</div>";
    }
} catch (Exception $e) {
    echo "<div class='fail'>❌ Error fetching cart: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// ============================================
// 3. Check Orders Table
// ============================================
echo "<div class='section'>";
echo "<h2>3️⃣ Orders Table</h2>";

try {
    $pdo = $db->getConnection();
    
    // Check if table exists
    $result = $pdo->query("SHOW TABLES LIKE 'orders'")->fetch();
    
    if ($result) {
        echo "<div class='pass'>✅ Orders table exists</div>";
        
        // Check table structure
        $columns = $pdo->query("DESCRIBE orders")->fetchAll();
        echo "<div style='background: #f9f9f9; padding: 10px; margin: 10px 0; border-radius: 3px;'>";
        echo "<strong>Table Columns:</strong><br>";
        foreach ($columns as $col) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
        }
        echo "</div>";
        
        // Count orders
        $orderCount = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch();
        echo "<div class='pass'>✅ Total orders: " . $orderCount['count'] . "</div>";
    } else {
        echo "<div class='warn'>⚠️ Orders table does not exist - will be created on first order</div>";
    }
} catch (Exception $e) {
    echo "<div class='fail'>❌ Error checking orders table: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// ============================================
// 4. Test Order Creation
// ============================================
echo "<div class='section'>";
echo "<h2>4️⃣ Test Order Creation</h2>";

if ($user && !empty($cartItems)) {
    echo "<form method='POST'>";
    echo "<input type='hidden' name='test_order' value='1'>";
    echo "<button type='submit' class='btn' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer;'>";
    echo "Create Test Order";
    echo "</button>";
    echo "</form>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_order'])) {
        try {
            $pdo = $db->getConnection();
            
            // Create tables if needed
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
            
            echo "<div class='pass'>✅ Tables created/verified</div>";
            
            // Calculate total
            $total = 0;
            foreach ($cartItems as $item) {
                $total += floatval($item['price']) * intval($item['quantity']);
            }
            
            // Create order
            $address = "Rua Teste, 123, Centro, São Paulo - SP, CEP: 01310-100";
            $db->query("
                INSERT INTO orders (user_id, total_amount, delivery_address, payment_method, status)
                VALUES (?, ?, ?, ?, 'pending')
            ", [$user['id'], $total, $address, 'dinheiro']);
            
            $orderId = $db->lastInsertId();
            echo "<div class='pass'>✅ Order created successfully! Order ID: " . $orderId . "</div>";
            
            // Create order items
            foreach ($cartItems as $item) {
                $db->query("
                    INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ", [$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            }
            
            echo "<div class='pass'>✅ Order items created successfully!</div>";
            echo "<div class='pass'>✅ Total: R$ " . number_format($total, 2, ',', '.') . "</div>";
            
        } catch (Exception $e) {
            echo "<div class='fail'>❌ Error creating order: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<code>" . htmlspecialchars($e->getTraceAsString()) . "</code>";
        }
    }
} else {
    echo "<div class='warn'>⚠️ Cannot test - user not logged in or cart is empty</div>";
}

echo "</div>";

// ============================================
// 5. Database Connection Info
// ============================================
echo "<div class='section'>";
echo "<h2>5️⃣ Database Connection</h2>";

try {
    $pdo = $db->getConnection();
    $result = $pdo->query("SELECT 1")->fetch();
    echo "<div class='pass'>✅ Database connection successful</div>";
    
    // Get database info
    $dbInfo = $pdo->query("SELECT DATABASE() as db, VERSION() as version")->fetch();
    echo "<div style='background: #f9f9f9; padding: 10px; margin: 10px 0; border-radius: 3px;'>";
    echo "Database: " . htmlspecialchars($dbInfo['db']) . "<br>";
    echo "MySQL Version: " . htmlspecialchars($dbInfo['version']) . "<br>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='fail'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "</body>";
echo "</html>";
?>

