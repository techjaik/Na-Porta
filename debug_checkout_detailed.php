<?php
/**
 * Detailed Checkout Debug Tool
 * This script will help identify the exact issue with checkout
 */

// Start session
session_start();

// Include required files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Detailed Checkout Debug</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .pass { color: green; font-weight: bold; }
    .fail { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .code { background: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; }
    .button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 10px 5px; }
</style></head><body>";

echo "<h1>🔍 Detailed Checkout Debug Tool</h1>";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Database Connection
echo "<div class='section'>";
echo "<h2>1. Database Connection Test</h2>";
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "<div class='pass'>✅ Database connection successful</div>";
    echo "<div class='code'>Host: " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "</div>";
} catch (Exception $e) {
    echo "<div class='fail'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}
echo "</div>";

// Test 2: Authentication
echo "<div class='section'>";
echo "<h2>2. Authentication Test</h2>";
try {
    $auth = new Auth();
    $user = $auth->getCurrentUser();
    
    if ($user) {
        echo "<div class='pass'>✅ User authenticated</div>";
        echo "<div class='code'>User ID: " . $user['id'] . "<br>";
        echo "User Name: " . htmlspecialchars($user['name']) . "<br>";
        echo "User Email: " . htmlspecialchars($user['email']) . "</div>";
    } else {
        echo "<div class='warning'>⚠️ User not authenticated</div>";
        echo "<div class='code'>Session ID: " . session_id() . "</div>";
    }
} catch (Exception $e) {
    echo "<div class='fail'>❌ Authentication error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// Test 3: Cart Items
echo "<div class='section'>";
echo "<h2>3. Cart Items Test</h2>";
try {
    $cartItems = [];
    if ($user && !empty($user['id'])) {
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
        $total = 0;
        foreach ($cartItems as $item) {
            $itemTotal = floatval($item['price']) * intval($item['quantity']);
            $total += $itemTotal;
            echo "<div class='code'>";
            echo "Product: " . htmlspecialchars($item['name']) . "<br>";
            echo "Price: R$ " . number_format($item['price'], 2, ',', '.') . "<br>";
            echo "Quantity: " . $item['quantity'] . "<br>";
            echo "Subtotal: R$ " . number_format($itemTotal, 2, ',', '.') . "<br>";
            echo "</div>";
        }
        echo "<div class='pass'>Total: R$ " . number_format($total, 2, ',', '.') . "</div>";
    } else {
        echo "<div class='fail'>❌ Cart is empty</div>";
        echo "<div class='code'>User ID: " . ($user['id'] ?? 'N/A') . "<br>";
        echo "Session ID: " . session_id() . "</div>";
    }
} catch (Exception $e) {
    echo "<div class='fail'>❌ Cart error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// Test 4: Tables Existence
echo "<div class='section'>";
echo "<h2>4. Database Tables Test</h2>";
try {
    // Check orders table
    $result = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($result->rowCount() > 0) {
        echo "<div class='pass'>✅ Orders table exists</div>";
        
        // Check table structure
        $columns = $pdo->query("DESCRIBE orders")->fetchAll();
        echo "<div class='code'>Orders table columns:<br>";
        foreach ($columns as $col) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
        }
        echo "</div>";
    } else {
        echo "<div class='warning'>⚠️ Orders table does not exist</div>";
    }
    
    // Check order_items table
    $result = $pdo->query("SHOW TABLES LIKE 'order_items'");
    if ($result->rowCount() > 0) {
        echo "<div class='pass'>✅ Order items table exists</div>";
        
        // Check table structure
        $columns = $pdo->query("DESCRIBE order_items")->fetchAll();
        echo "<div class='code'>Order items table columns:<br>";
        foreach ($columns as $col) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
        }
        echo "</div>";
    } else {
        echo "<div class='warning'>⚠️ Order items table does not exist</div>";
    }
} catch (Exception $e) {
    echo "<div class='fail'>❌ Table check error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// Test 5: Create Tables Test
echo "<div class='section'>";
echo "<h2>5. Create Tables Test</h2>";
try {
    // Try to create orders table
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
    echo "<div class='pass'>✅ Orders table created/verified</div>";
    
    // Try to create order_items table
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
    echo "<div class='pass'>✅ Order items table created/verified</div>";
    
} catch (Exception $e) {
    echo "<div class='fail'>❌ Table creation error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// Test 6: Test Order Creation (if cart has items)
if (!empty($cartItems) && $user) {
    echo "<div class='section'>";
    echo "<h2>6. Test Order Creation</h2>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_order'])) {
        try {
            $pdo->beginTransaction();
            
            $testAddress = "Rua Teste, 123, Centro, São Paulo - SP, CEP: 01310-100";
            $userId = $user['id'];
            $total = 0;
            foreach ($cartItems as $item) {
                $total += floatval($item['price']) * intval($item['quantity']);
            }
            
            // Insert order
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, total_amount, delivery_address, payment_method, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$userId, $total, $testAddress, 'dinheiro']);
            
            $orderId = $pdo->lastInsertId();
            echo "<div class='pass'>✅ Order created successfully! Order ID: " . $orderId . "</div>";
            
            // Insert order items
            foreach ($cartItems as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            }
            
            echo "<div class='pass'>✅ Order items created successfully!</div>";
            
            $pdo->commit();
            echo "<div class='pass'>✅ Transaction committed successfully!</div>";
            echo "<div class='code'>Order ID: " . $orderId . "<br>";
            echo "Total: R$ " . number_format($total, 2, ',', '.') . "<br>";
            echo "Address: " . htmlspecialchars($testAddress) . "</div>";
            
        } catch (Exception $e) {
            $pdo->rollback();
            echo "<div class='fail'>❌ Order creation failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='code'>Error details:<br>";
            echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "File: " . $e->getFile() . "<br>";
            echo "Line: " . $e->getLine() . "<br>";
            echo "Trace:<br>" . htmlspecialchars($e->getTraceAsString()) . "</div>";
        }
    } else {
        echo "<form method='POST'>";
        echo "<button type='submit' name='test_order' class='button'>🧪 Test Order Creation</button>";
        echo "</form>";
        echo "<p>This will create a test order with your current cart items.</p>";
    }
    echo "</div>";
}

// Test 7: PHP Configuration
echo "<div class='section'>";
echo "<h2>7. PHP Configuration</h2>";
echo "<div class='code'>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";
echo "Error Reporting: " . error_reporting() . "<br>";
echo "Display Errors: " . ini_get('display_errors') . "<br>";
echo "</div>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>8. Next Steps</h2>";
echo "<p>If you see any ❌ errors above, those need to be fixed first.</p>";
echo "<p>If everything shows ✅, try the test order creation button.</p>";
echo "<p>If test order works but checkout doesn't, the issue is in the checkout form processing.</p>";
echo "</div>";

echo "</body></html>";
?>
