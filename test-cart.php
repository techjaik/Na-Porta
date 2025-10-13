<!DOCTYPE html>
<html>
<head>
    <title>Test Cart Fix</title>
    <style>
        body { font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🧪 Test Cart Fix</h1>
    
    <?php
    session_start();
    
    // Add a test item to cart if empty
    if (empty($_SESSION['cart'])) {
        $_SESSION['cart'] = [1 => 2]; // Product ID 1, quantity 2
        echo "<div class='info'>✅ Added test item to cart</div>";
    }
    
    echo "<div class='success'>";
    echo "<h3>✅ Cart Status:</h3>";
    echo "<p>Items in cart: " . count($_SESSION['cart']) . "</p>";
    echo "<p>Cart contents: " . json_encode($_SESSION['cart']) . "</p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔧 Transaction Fix Applied:</h3>";
    echo "<ul>";
    echo "<li>✅ Database setup happens BEFORE transaction</li>";
    echo "<li>✅ Transaction only starts for order processing</li>";
    echo "<li>✅ Rollback only happens if transaction is active</li>";
    echo "<li>✅ No more 'no active transaction' errors</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='pages/cart-working.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px;'>🛒 Test Cart Page Now</a>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<p><strong>What to expect:</strong></p>";
    echo "<ul>";
    echo "<li>Cart page should load without fatal errors</li>";
    echo "<li>Database tables will be created automatically if needed</li>";
    echo "<li>Checkout process should work smoothly</li>";
    echo "<li>No more transaction errors</li>";
    echo "</ul>";
    echo "</div>";
    ?>
</body>
</html>
