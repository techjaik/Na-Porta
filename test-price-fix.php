<?php
// Test script to verify the price doubling fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h2>Price Fix Verification</h2>";
echo "<p>This script compares the old query (that caused doubling) with the new fixed query.</p>";

try {
    // Test the OLD query (that causes doubling)
    echo "<h3>❌ OLD Query Results (with doubling issue):</h3>";
    $stmt = $pdo->prepare("
        SELECT u.name, u.email,
               COUNT(DISTINCT o.id) as total_orders,
               COUNT(DISTINCT ua.id) as total_addresses,
               SUM(o.total_amount) as total_spent_OLD
        FROM users u 
        LEFT JOIN orders o ON u.id = o.user_id 
        LEFT JOIN user_addresses ua ON u.id = ua.user_id
        WHERE o.id IS NOT NULL
        GROUP BY u.id
        ORDER BY total_spent_OLD DESC
        LIMIT 5
    ");
    $stmt->execute();
    $old_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Name</th><th>Email</th><th>Orders</th><th>Addresses</th><th>Total Spent (OLD)</th></tr>";
    foreach ($old_results as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . $user['total_orders'] . "</td>";
        echo "<td>" . $user['total_addresses'] . "</td>";
        echo "<td>R$ " . number_format($user['total_spent_OLD'], 2, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test the NEW query (fixed)
    echo "<h3>✅ NEW Query Results (fixed):</h3>";
    $stmt = $pdo->prepare("
        SELECT u.name, u.email,
               COALESCE(order_stats.total_orders, 0) as total_orders,
               COALESCE(address_stats.total_addresses, 0) as total_addresses,
               COALESCE(order_stats.total_spent, 0) as total_spent_NEW
        FROM users u 
        LEFT JOIN (
            SELECT user_id,
                   COUNT(*) as total_orders,
                   SUM(total_amount) as total_spent
            FROM orders 
            GROUP BY user_id
        ) order_stats ON u.id = order_stats.user_id
        LEFT JOIN (
            SELECT user_id,
                   COUNT(*) as total_addresses
            FROM user_addresses 
            GROUP BY user_id
        ) address_stats ON u.id = address_stats.user_id
        WHERE order_stats.user_id IS NOT NULL
        ORDER BY total_spent_NEW DESC
        LIMIT 5
    ");
    $stmt->execute();
    $new_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Name</th><th>Email</th><th>Orders</th><th>Addresses</th><th>Total Spent (NEW)</th></tr>";
    foreach ($new_results as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . $user['total_orders'] . "</td>";
        echo "<td>" . $user['total_addresses'] . "</td>";
        echo "<td>R$ " . number_format($user['total_spent_NEW'], 2, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Show comparison
    echo "<h3>📊 Comparison:</h3>";
    if (!empty($old_results) && !empty($new_results)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>User</th><th>OLD Total</th><th>NEW Total</th><th>Difference</th><th>Status</th></tr>";
        
        for ($i = 0; $i < min(count($old_results), count($new_results)); $i++) {
            $old_total = $old_results[$i]['total_spent_OLD'];
            $new_total = $new_results[$i]['total_spent_NEW'];
            $difference = $old_total - $new_total;
            $status = $difference > 0 ? "✅ Fixed (was doubled)" : ($difference < 0 ? "⚠️ Check" : "✓ Same");
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($old_results[$i]['name']) . "</td>";
            echo "<td>R$ " . number_format($old_total, 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($new_total, 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($difference, 2, ',', '.') . "</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Show individual order details for verification
    echo "<h3>🔍 Individual Order Verification:</h3>";
    $stmt = $pdo->prepare("
        SELECT o.id, o.user_id, o.total_amount, u.name,
               (SELECT COUNT(*) FROM user_addresses WHERE user_id = o.user_id) as user_addresses_count
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Order ID</th><th>User</th><th>Order Total</th><th>User's Address Count</th><th>Note</th></tr>";
    foreach ($orders as $order) {
        $note = $order['user_addresses_count'] > 1 ? "⚠️ Multiple addresses (caused doubling)" : "✓ Single address";
        echo "<tr>";
        echo "<td>#" . $order['id'] . "</td>";
        echo "<td>" . htmlspecialchars($order['name']) . "</td>";
        echo "<td>R$ " . number_format($order['total_amount'], 2, ',', '.') . "</td>";
        echo "<td>" . $order['user_addresses_count'] . "</td>";
        echo "<td>$note</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Summary:</strong> The price doubling was caused by the JOIN with user_addresses table. When users had multiple addresses, each order was counted multiple times in the SUM. The fix uses subqueries to calculate totals separately.</p>";
echo "<p><a href='admin/users.php'>→ Go to Admin Users Page to see the fix in action</a></p>";
?>
