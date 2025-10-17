<?php
/**
 * Simple Checkout Test - No Authentication Required
 * This will help us test the core checkout functionality
 */

// Start session
session_start();

// Include required files
require_once __DIR__ . '/config/database.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$success = '';
$error = '';

// Handle test order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_checkout'])) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        // Test data
        $testUserId = 1; // Assuming user ID 1 exists
        $testAddress = "Rua Teste, 123, Centro, São Paulo - SP, CEP: 01310-100";
        $testPaymentMethod = "dinheiro";
        $testTotal = 25.50;
        
        echo "<h3>🧪 Testing Order Creation...</h3>";
        
        // Create tables if needed
        try {
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
            echo "<p style='color: green;'>✅ Orders table created/verified</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Orders table error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        try {
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
            echo "<p style='color: green;'>✅ Order items table created/verified</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Order items table error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        // Test order creation with transaction
        try {
            $pdo->beginTransaction();
            echo "<p style='color: blue;'>🔄 Starting transaction...</p>";
            
            // Insert order
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, total_amount, delivery_address, payment_method, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $result = $stmt->execute([$testUserId, $testTotal, $testAddress, $testPaymentMethod]);
            
            if ($result) {
                $orderId = $pdo->lastInsertId();
                echo "<p style='color: green;'>✅ Order inserted successfully! Order ID: " . $orderId . "</p>";
                
                // Insert test order items
                $testItems = [
                    ['product_id' => 1, 'quantity' => 2, 'price' => 10.50],
                    ['product_id' => 2, 'quantity' => 1, 'price' => 4.50]
                ];
                
                foreach ($testItems as $item) {
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price)
                        VALUES (?, ?, ?, ?)
                    ");
                    $result = $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                    
                    if ($result) {
                        echo "<p style='color: green;'>✅ Order item inserted: Product " . $item['product_id'] . "</p>";
                    } else {
                        throw new Exception("Failed to insert order item for product " . $item['product_id']);
                    }
                }
                
                $pdo->commit();
                echo "<p style='color: green;'>✅ Transaction committed successfully!</p>";
                
                $success = "🎉 Test order created successfully! Order ID: " . $orderId;
                
            } else {
                throw new Exception("Failed to insert order");
            }
            
        } catch (Exception $orderError) {
            $pdo->rollback();
            echo "<p style='color: red;'>❌ Transaction rolled back</p>";
            echo "<p style='color: red;'>Error: " . htmlspecialchars($orderError->getMessage()) . "</p>";
            echo "<p style='color: red;'>File: " . $orderError->getFile() . "</p>";
            echo "<p style='color: red;'>Line: " . $orderError->getLine() . "</p>";
            $error = 'Order creation failed: ' . $orderError->getMessage();
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ General error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p style='color: red;'>File: " . $e->getFile() . "</p>";
        echo "<p style='color: red;'>Line: " . $e->getLine() . "</p>";
        $error = 'Test failed: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Checkout Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; padding: 10px; background: #f0f8f0; border-radius: 5px; }
        .error { color: red; font-weight: bold; padding: 10px; background: #f8f0f0; border-radius: 5px; }
        .button { background: #007cba; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🧪 Simple Checkout Test</h1>
    <p><strong>Purpose:</strong> Test core checkout functionality without authentication</p>
    <p><strong>Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
    
    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="section">
        <h2>Database Connection Test</h2>
        <?php
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            echo "<p style='color: green;'>✅ Database connection successful</p>";
            echo "<p>Connection status: " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Test Order Creation</h2>
        <p>This will create a test order with sample data:</p>
        <ul>
            <li>User ID: 1</li>
            <li>Address: Rua Teste, 123, Centro, São Paulo - SP</li>
            <li>Payment: Dinheiro</li>
            <li>Total: R$ 25,50</li>
            <li>Items: 2 test products</li>
        </ul>
        
        <form method="POST">
            <button type="submit" name="test_checkout" class="button">🧪 Run Test Order Creation</button>
        </form>
    </div>
    
    <div class="section">
        <h2>Recent Orders</h2>
        <?php
        try {
            $db = Database::getInstance();
            $orders = $db->fetchAll("
                SELECT id, user_id, total_amount, delivery_address, payment_method, status, created_at
                FROM orders
                ORDER BY created_at DESC
                LIMIT 5
            ");
            
            if (!empty($orders)) {
                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th>ID</th><th>User ID</th><th>Total</th><th>Address</th><th>Payment</th><th>Status</th><th>Created</th></tr>";
                foreach ($orders as $order) {
                    echo "<tr>";
                    echo "<td>" . $order['id'] . "</td>";
                    echo "<td>" . $order['user_id'] . "</td>";
                    echo "<td>R$ " . number_format($order['total_amount'], 2, ',', '.') . "</td>";
                    echo "<td>" . htmlspecialchars(substr($order['delivery_address'], 0, 50)) . "...</td>";
                    echo "<td>" . htmlspecialchars($order['payment_method']) . "</td>";
                    echo "<td>" . htmlspecialchars($order['status']) . "</td>";
                    echo "<td>" . $order['created_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No orders found.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error loading orders: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>PHP Info</h2>
        <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
        <p><strong>Memory Limit:</strong> <?= ini_get('memory_limit') ?></p>
        <p><strong>Max Execution Time:</strong> <?= ini_get('max_execution_time') ?></p>
    </div>
    
    <div class="section">
        <h2>Next Steps</h2>
        <p>1. Click "Run Test Order Creation" to test the core functionality</p>
        <p>2. If test passes, the issue is likely in the checkout form or authentication</p>
        <p>3. If test fails, the issue is in the database or order creation logic</p>
        <p>4. Check the "Recent Orders" section to see if orders are being created</p>
    </div>
</body>
</html>
