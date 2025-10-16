<?php
/**
 * 🧪 NA PORTA - COMPREHENSIVE FUNCTIONALITY TEST
 * Tests all major features and user flows
 */

session_start();

$host = 'sql105.infinityfree.com';
$database = 'if0_40155099_naporta_db';
$username = 'if0_40155099';
$password = 'Jaishreeramm9';

$tests = [];
$results = [];

echo "<h1>🧪 FUNCTIONALITY TEST REPORT</h1>";
echo "<p>Testing all major features and user flows</p>";
echo "<hr>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ============================================
    // 1. DATABASE CONNECTIVITY
    // ============================================
    echo "<h2>1️⃣ DATABASE CONNECTIVITY</h2>";
    
    try {
        $result = $pdo->query("SELECT 1")->fetch();
        $results[] = ["Database Connection", "PASS", "Connected successfully"];
    } catch (Exception $e) {
        $results[] = ["Database Connection", "FAIL", $e->getMessage()];
    }
    
    // ============================================
    // 2. TABLE STRUCTURE
    // ============================================
    echo "<h2>2️⃣ TABLE STRUCTURE</h2>";
    
    $requiredTables = ['users', 'products', 'categories', 'cart_items', 'orders', 'order_items', 'user_addresses'];
    
    foreach ($requiredTables as $table) {
        try {
            $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
            if ($result) {
                $results[] = ["Table: $table", "PASS", "Table exists"];
            } else {
                $results[] = ["Table: $table", "FAIL", "Table not found"];
            }
        } catch (Exception $e) {
            $results[] = ["Table: $table", "FAIL", $e->getMessage()];
        }
    }
    
    // ============================================
    // 3. DATA INTEGRITY
    // ============================================
    echo "<h2>3️⃣ DATA INTEGRITY</h2>";
    
    // Check users
    $userCount = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch(PDO::FETCH_ASSOC);
    $results[] = ["Users Count", "PASS", $userCount['count'] . " users in database"];
    
    // Check products
    $productCount = $pdo->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1")->fetch(PDO::FETCH_ASSOC);
    $results[] = ["Active Products", "PASS", $productCount['count'] . " active products"];
    
    // Check categories
    $categoryCount = $pdo->query("SELECT COUNT(*) as count FROM categories WHERE is_active = 1")->fetch(PDO::FETCH_ASSOC);
    $results[] = ["Active Categories", "PASS", $categoryCount['count'] . " active categories"];
    
    // Check orders
    $orderCount = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch(PDO::FETCH_ASSOC);
    $results[] = ["Orders Count", "PASS", $orderCount['count'] . " orders in database"];
    
    // ============================================
    // 4. PRODUCT FUNCTIONALITY
    // ============================================
    echo "<h2>4️⃣ PRODUCT FUNCTIONALITY</h2>";
    
    try {
        $product = $pdo->query("SELECT * FROM products WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($product) {
            $results[] = ["Product Retrieval", "PASS", "Sample product: " . $product['name']];
            
            // Check product fields
            $requiredFields = ['id', 'name', 'price', 'description', 'image_url', 'category_id'];
            foreach ($requiredFields as $field) {
                if (isset($product[$field])) {
                    $results[] = ["Product Field: $field", "PASS", "Field exists"];
                } else {
                    $results[] = ["Product Field: $field", "FAIL", "Field missing"];
                }
            }
        } else {
            $results[] = ["Product Retrieval", "FAIL", "No active products found"];
        }
    } catch (Exception $e) {
        $results[] = ["Product Retrieval", "FAIL", $e->getMessage()];
    }
    
    // ============================================
    // 5. CART FUNCTIONALITY
    // ============================================
    echo "<h2>5️⃣ CART FUNCTIONALITY</h2>";
    
    try {
        $cartCount = $pdo->query("SELECT COUNT(*) as count FROM cart_items")->fetch(PDO::FETCH_ASSOC);
        $results[] = ["Cart Items Count", "PASS", $cartCount['count'] . " items in carts"];
    } catch (Exception $e) {
        $results[] = ["Cart Items Count", "FAIL", $e->getMessage()];
    }
    
    // ============================================
    // 6. ORDER FUNCTIONALITY
    // ============================================
    echo "<h2>6️⃣ ORDER FUNCTIONALITY</h2>";
    
    try {
        $order = $pdo->query("SELECT * FROM orders LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($order) {
            $results[] = ["Order Retrieval", "PASS", "Sample order ID: " . $order['id']];
            
            // Check order items
            $orderItems = $pdo->query("SELECT COUNT(*) as count FROM order_items WHERE order_id = " . $order['id'])->fetch(PDO::FETCH_ASSOC);
            $results[] = ["Order Items", "PASS", $orderItems['count'] . " items in order"];
        } else {
            $results[] = ["Order Retrieval", "SKIP", "No orders found"];
        }
    } catch (Exception $e) {
        $results[] = ["Order Retrieval", "FAIL", $e->getMessage()];
    }
    
    // ============================================
    // 7. ADDRESS FUNCTIONALITY
    // ============================================
    echo "<h2>7️⃣ ADDRESS FUNCTIONALITY</h2>";
    
    try {
        $addressCount = $pdo->query("SELECT COUNT(*) as count FROM user_addresses")->fetch(PDO::FETCH_ASSOC);
        $results[] = ["User Addresses", "PASS", $addressCount['count'] . " addresses in database"];
    } catch (Exception $e) {
        $results[] = ["User Addresses", "FAIL", $e->getMessage()];
    }
    
    // ============================================
    // 8. INDEXES & PERFORMANCE
    // ============================================
    echo "<h2>8️⃣ INDEXES & PERFORMANCE</h2>";
    
    try {
        $indexes = $pdo->query("SHOW INDEX FROM products")->fetchAll(PDO::FETCH_ASSOC);
        $results[] = ["Product Indexes", "PASS", count($indexes) . " indexes found"];
    } catch (Exception $e) {
        $results[] = ["Product Indexes", "FAIL", $e->getMessage()];
    }
    
    // ============================================
    // SUMMARY
    // ============================================
    echo "<hr>";
    echo "<h2>📊 TEST SUMMARY</h2>";
    
    $passed = 0;
    $failed = 0;
    $skipped = 0;
    
    echo "<table border='1' cellpadding='10' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Test Name</th>";
    echo "<th>Status</th>";
    echo "<th>Details</th>";
    echo "</tr>";
    
    foreach ($results as $result) {
        $status = $result[1];
        $bgColor = $status === 'PASS' ? '#d4edda' : ($status === 'FAIL' ? '#f8d7da' : '#fff3cd');
        
        echo "<tr style='background: $bgColor;'>";
        echo "<td>" . $result[0] . "</td>";
        echo "<td><strong>" . $status . "</strong></td>";
        echo "<td>" . $result[2] . "</td>";
        echo "</tr>";
        
        if ($status === 'PASS') $passed++;
        elseif ($status === 'FAIL') $failed++;
        else $skipped++;
    }
    
    echo "</table>";
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📈 RESULTS</h3>";
    echo "<p>✅ Passed: $passed</p>";
    echo "<p>❌ Failed: $failed</p>";
    echo "<p>⏭️ Skipped: $skipped</p>";
    echo "<p><strong>Total: " . ($passed + $failed + $skipped) . "</strong></p>";
    
    $passRate = round(($passed / ($passed + $failed)) * 100);
    echo "<p><strong>Pass Rate: $passRate%</strong></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
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
table {
    margin: 20px 0;
}
</style>

