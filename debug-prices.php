<?php
// Debug script to check price doubling issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h2>Debug: Price Doubling Issue</h2>";

try {
    // Check orders table structure
    echo "<h3>Orders Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";

    // Check sample orders
    echo "<h3>Sample Orders:</h3>";
    $stmt = $pdo->query("SELECT id, user_id, total_amount, created_at FROM orders ORDER BY created_at DESC LIMIT 10");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>ID</th><th>User ID</th><th>Total Amount</th><th>Created At</th></tr>";
    foreach ($orders as $order) {
        echo "<tr><td>{$order['id']}</td><td>{$order['user_id']}</td><td>R$ " . number_format($order['total_amount'], 2, ',', '.') . "</td><td>{$order['created_at']}</td></tr>";
    }
    echo "</table>";

    // Check order items for a specific order
    if (!empty($orders)) {
        $first_order_id = $orders[0]['id'];
        echo "<h3>Order Items for Order #{$first_order_id}:</h3>";
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$first_order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($items)) {
            echo "<table border='1'><tr><th>Product ID</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr>";
            $items_total = 0;
            foreach ($items as $item) {
                echo "<tr><td>{$item['product_id']}</td><td>{$item['quantity']}</td><td>R$ " . number_format($item['price'], 2, ',', '.') . "</td><td>R$ " . number_format($item['subtotal'], 2, ',', '.') . "</td></tr>";
                $items_total += $item['subtotal'];
            }
            echo "</table>";
            echo "<p><strong>Items Total: R$ " . number_format($items_total, 2, ',', '.') . "</strong></p>";
            echo "<p><strong>Order Total: R$ " . number_format($orders[0]['total_amount'], 2, ',', '.') . "</strong></p>";
            
            $difference = $orders[0]['total_amount'] - $items_total;
            echo "<p><strong>Difference (likely delivery fee): R$ " . number_format($difference, 2, ',', '.') . "</strong></p>";
        } else {
            echo "<p>No order items found for this order.</p>";
        }
    }

    // Check user totals
    echo "<h3>User Total Spent Calculation:</h3>";
    $stmt = $pdo->query("
        SELECT u.id, u.name, 
               COUNT(DISTINCT o.id) as total_orders,
               SUM(o.total_amount) as total_spent
        FROM users u 
        LEFT JOIN orders o ON u.id = o.user_id 
        GROUP BY u.id
        HAVING total_orders > 0
        ORDER BY total_spent DESC
        LIMIT 5
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>User ID</th><th>Name</th><th>Total Orders</th><th>Total Spent</th></tr>";
    foreach ($users as $user) {
        echo "<tr><td>{$user['id']}</td><td>{$user['name']}</td><td>{$user['total_orders']}</td><td>R$ " . number_format($user['total_spent'], 2, ',', '.') . "</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
