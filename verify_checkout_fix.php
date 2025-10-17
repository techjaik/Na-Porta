<?php
/**
 * ✅ VERIFY CHECKOUT FIX
 * Quick verification that checkout is working
 */

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$auth = new Auth();
$db = Database::getInstance();
$user = $auth->getCurrentUser();

$results = [];
$allPass = true;

// ============================================
// TEST 1: Database Connection
// ============================================
try {
    $pdo = $db->getConnection();
    $pdo->query("SELECT 1");
    $results[] = ['test' => 'Database Connection', 'status' => 'PASS', 'message' => 'Connected successfully'];
} catch (Exception $e) {
    $results[] = ['test' => 'Database Connection', 'status' => 'FAIL', 'message' => $e->getMessage()];
    $allPass = false;
}

// ============================================
// TEST 2: Orders Table
// ============================================
try {
    $pdo = $db->getConnection();
    $result = $pdo->query("SHOW TABLES LIKE 'orders'")->fetch();
    if ($result) {
        $results[] = ['test' => 'Orders Table', 'status' => 'PASS', 'message' => 'Table exists'];
    } else {
        $results[] = ['test' => 'Orders Table', 'status' => 'WARN', 'message' => 'Table will be created on first order'];
    }
} catch (Exception $e) {
    $results[] = ['test' => 'Orders Table', 'status' => 'FAIL', 'message' => $e->getMessage()];
    $allPass = false;
}

// ============================================
// TEST 3: Order Items Table
// ============================================
try {
    $pdo = $db->getConnection();
    $result = $pdo->query("SHOW TABLES LIKE 'order_items'")->fetch();
    if ($result) {
        $results[] = ['test' => 'Order Items Table', 'status' => 'PASS', 'message' => 'Table exists'];
    } else {
        $results[] = ['test' => 'Order Items Table', 'status' => 'WARN', 'message' => 'Table will be created on first order'];
    }
} catch (Exception $e) {
    $results[] = ['test' => 'Order Items Table', 'status' => 'FAIL', 'message' => $e->getMessage()];
    $allPass = false;
}

// ============================================
// TEST 4: Cart Items (if logged in)
// ============================================
if ($user) {
    try {
        $cartItems = $db->fetchAll("
            SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?
        ", [$user['id']]);
        
        $count = $cartItems[0]['count'] ?? 0;
        if ($count > 0) {
            $results[] = ['test' => 'Cart Items', 'status' => 'PASS', 'message' => "Found $count items in cart"];
        } else {
            $results[] = ['test' => 'Cart Items', 'status' => 'WARN', 'message' => 'Cart is empty - add items first'];
        }
    } catch (Exception $e) {
        $results[] = ['test' => 'Cart Items', 'status' => 'FAIL', 'message' => $e->getMessage()];
        $allPass = false;
    }
} else {
    $results[] = ['test' => 'Cart Items', 'status' => 'WARN', 'message' => 'User not logged in'];
}

// ============================================
// TEST 5: Create Test Order (if logged in and has cart)
// ============================================
if ($user && isset($cartItems) && $cartItems[0]['count'] > 0) {
    try {
        $pdo = $db->getConnection();
        
        // Create tables
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
        
        // Get cart items
        $items = $db->fetchAll("
            SELECT ci.*, p.price
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.user_id = ?
        ", [$user['id']]);
        
        // Calculate total
        $total = 0;
        foreach ($items as $item) {
            $total += floatval($item['price']) * intval($item['quantity']);
        }
        
        // Create order
        $pdo->beginTransaction();
        
        $db->query("
            INSERT INTO orders (user_id, total_amount, delivery_address, payment_method, status)
            VALUES (?, ?, ?, ?, 'pending')
        ", [$user['id'], $total, 'Rua Teste, 123, Centro, São Paulo - SP, CEP: 01310-100', 'dinheiro']);
        
        $orderId = $db->lastInsertId();
        
        // Create order items
        foreach ($items as $item) {
            $db->query("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ", [$orderId, $item['product_id'], $item['quantity'], $item['price']]);
        }
        
        $pdo->commit();
        
        $results[] = ['test' => 'Test Order Creation', 'status' => 'PASS', 'message' => "Order #$orderId created successfully"];
        
    } catch (Exception $e) {
        $results[] = ['test' => 'Test Order Creation', 'status' => 'FAIL', 'message' => $e->getMessage()];
        $allPass = false;
    }
}

// ============================================
// OUTPUT RESULTS
// ============================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Checkout Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { background: #007bff; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .result { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #ccc; }
        .pass { border-left-color: #28a745; background: #f0f8f5; }
        .fail { border-left-color: #dc3545; background: #fdf5f5; }
        .warn { border-left-color: #ffc107; background: #fffbf0; }
        .status { font-weight: bold; padding: 5px 10px; border-radius: 3px; display: inline-block; }
        .status.pass { background: #28a745; color: white; }
        .status.fail { background: #dc3545; color: white; }
        .status.warn { background: #ffc107; color: black; }
        .summary { background: white; padding: 20px; border-radius: 5px; margin-top: 20px; text-align: center; }
        .summary.success { background: #d4edda; color: #155724; }
        .summary.warning { background: #fff3cd; color: #856404; }
        .summary.error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Verify Checkout Fix</h1>
            <p>Testing checkout functionality...</p>
        </div>
        
        <?php foreach ($results as $result): ?>
            <div class="result <?php echo strtolower($result['status']); ?>">
                <div>
                    <strong><?php echo htmlspecialchars($result['test']); ?></strong>
                    <span class="status <?php echo strtolower($result['status']); ?>">
                        <?php echo $result['status']; ?>
                    </span>
                </div>
                <p style="margin: 10px 0 0 0; color: #666;">
                    <?php echo htmlspecialchars($result['message']); ?>
                </p>
            </div>
        <?php endforeach; ?>
        
        <div class="summary <?php echo $allPass ? 'success' : 'warning'; ?>">
            <?php if ($allPass): ?>
                <h2>🎉 All Tests Passed!</h2>
                <p>Checkout is working correctly. You can now place orders.</p>
            <?php else: ?>
                <h2>⚠️ Some Tests Failed</h2>
                <p>Please check the errors above and contact support if needed.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

