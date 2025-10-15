<?php
/**
 * Test Checkout Form Submission
 */

session_start();

echo "<h1>🧪 CHECKOUT TEST</h1>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ Not logged in. <a href='auth/login.php'>Login first</a></p>";
    exit;
}

echo "<p>✅ User logged in (ID: {$_SESSION['user_id']})</p>";

// Simple database connection
$host = 'sql105.infinityfree.com';
$database = 'if0_40155099_naporta_db';
$username = 'if0_40155099';
$password = 'Jaishreeramm9';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Database connected</p>";
    
    // Check cart items
    $cartItems = $pdo->prepare("
        SELECT ci.*, p.name, p.price
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ? AND p.is_active = 1
    ");
    $cartItems->execute([$_SESSION['user_id']]);
    $items = $cartItems->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo "<p style='color: orange;'>⚠️ Cart is empty. <a href='products.php'>Add some products first</a></p>";
    } else {
        echo "<p>✅ Cart has " . count($items) . " items</p>";
        
        $total = 0;
        echo "<h3>Cart Items:</h3>";
        echo "<ul>";
        foreach ($items as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $total += $itemTotal;
            echo "<li>{$item['name']} - Qty: {$item['quantity']} - R$ " . number_format($itemTotal, 2, ',', '.') . "</li>";
        }
        echo "</ul>";
        echo "<p><strong>Total: R$ " . number_format($total, 2, ',', '.') . "</strong></p>";
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_order'])) {
        echo "<h3>🚀 Processing Test Order...</h3>";
        
        $address = trim($_POST['address'] ?? '');
        $payment_method = $_POST['payment_method'] ?? '';
        
        echo "<p><strong>Address:</strong> $address</p>";
        echo "<p><strong>Payment:</strong> $payment_method</p>";
        
        if (empty($address) || empty($payment_method)) {
            echo "<p style='color: red;'>❌ Missing required fields</p>";
        } else {
            try {
                // Calculate total
                $total = 0;
                foreach ($items as $item) {
                    $total += $item['price'] * $item['quantity'];
                }
                
                // Create order
                $orderStmt = $pdo->prepare("
                    INSERT INTO orders (user_id, total_amount, delivery_address, payment_method, status, created_at) 
                    VALUES (?, ?, ?, ?, 'pending', NOW())
                ");
                $orderStmt->execute([$_SESSION['user_id'], $total, $address, $payment_method]);
                
                $orderId = $pdo->lastInsertId();
                echo "<p>✅ Order created (ID: $orderId)</p>";
                
                // Create order items
                foreach ($items as $item) {
                    $itemStmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                }
                
                echo "<p>✅ Order items created</p>";
                
                // Clear cart
                $clearStmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
                $clearStmt->execute([$_SESSION['user_id']]);
                
                echo "<p>✅ Cart cleared</p>";
                
                echo "<h2>🎉 ORDER SUCCESSFUL!</h2>";
                echo "<p>Order ID: $orderId</p>";
                echo "<p>Total: R$ " . number_format($total, 2, ',', '.') . "</p>";
                echo "<p><a href='checkout.php'>Try Real Checkout Now</a></p>";
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Order failed: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>

<h3>🧪 Test Order Form</h3>
<form method="POST" style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
    <input type="hidden" name="test_order" value="1">
    
    <div style="margin-bottom: 15px;">
        <label><strong>Delivery Address:</strong></label><br>
        <textarea name="address" rows="3" style="width: 100%; padding: 8px;" placeholder="Enter your full address...">Rua Teste, 123 - Centro, São Paulo - SP, CEP: 01234-567</textarea>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>Payment Method:</strong></label><br>
        <label><input type="radio" name="payment_method" value="cash" checked> Cash on Delivery</label><br>
        <label><input type="radio" name="payment_method" value="card"> Card on Delivery</label>
    </div>
    
    <button type="submit" style="background: green; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        🚀 Test Order Submission
    </button>
</form>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #333; }
p { background: #f9f9f9; padding: 10px; border-radius: 5px; margin: 10px 0; }
ul { background: white; padding: 15px; border-radius: 5px; }
</style>
