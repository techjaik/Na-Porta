<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Database - Na Porta</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; }
        .error { color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; }
        .info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>🔧 Database Fix - Na Porta</h1>
    
    <?php
    if (isset($_POST['fix_database'])) {
        try {
            require_once 'config/database.php';
            
            echo "<h2>Fixing Database Structure...</h2>";
            
            // Check if image_url column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_url'");
            $column_exists = $stmt->rowCount() > 0;
            
            if (!$column_exists) {
                // Add the image_url column
                $pdo->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(500) NULL AFTER description");
                echo "<div class='success'>✅ Added image_url column to products table</div>";
            } else {
                echo "<div class='info'>ℹ️ image_url column already exists</div>";
            }
            
            // Check if updated_at column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'updated_at'");
            $updated_at_exists = $stmt->rowCount() > 0;
            
            if (!$updated_at_exists) {
                $pdo->exec("ALTER TABLE products ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
                echo "<div class='success'>✅ Added updated_at column to products table</div>";
            } else {
                echo "<div class='info'>ℹ️ updated_at column already exists</div>";
            }
            
            // Check categories table
            $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'updated_at'");
            $cat_updated_exists = $stmt->rowCount() > 0;
            
            if (!$cat_updated_exists) {
                $pdo->exec("ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
                echo "<div class='success'>✅ Added updated_at column to categories table</div>";
            } else {
                echo "<div class='info'>ℹ️ Categories updated_at column already exists</div>";
            }
            
            // Add some sample images to existing products
            $sample_updates = [
                "UPDATE products SET image_url = 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=400&h=400&fit=crop' WHERE name LIKE '%água%' AND image_url IS NULL",
                "UPDATE products SET image_url = 'https://images.unsplash.com/photo-1574263867128-a3d5c1b1deaa?w=400&h=400&fit=crop' WHERE name LIKE '%gás%' AND image_url IS NULL",
                "UPDATE products SET image_url = 'https://images.unsplash.com/photo-1563453392212-326f5e854473?w=400&h=400&fit=crop' WHERE name LIKE '%detergente%' AND image_url IS NULL",
                "UPDATE products SET image_url = 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=400&h=400&fit=crop' WHERE name LIKE '%arroz%' AND image_url IS NULL",
                "UPDATE products SET image_url = 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=400&h=400&fit=crop' WHERE name LIKE '%óleo%' AND image_url IS NULL"
            ];
            
            $updated_count = 0;
            foreach ($sample_updates as $sql) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $updated_count += $stmt->rowCount();
            }
            
            if ($updated_count > 0) {
                echo "<div class='success'>✅ Updated $updated_count products with sample images</div>";
            }
            
            echo "<div class='success'><strong>🎉 Database fixed successfully!</strong></div>";
            echo "<p><a href='admin/products.php' style='color: #007cba;'>← Go back to Products Admin</a></p>";
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
    ?>
    
    <div class="info">
        <h3>🔍 Database Issue Detected</h3>
        <p>The products table is missing the <code>image_url</code> column needed for product images.</p>
        <p>This script will:</p>
        <ul>
            <li>Add <code>image_url</code> column to products table</li>
            <li>Add <code>updated_at</code> columns for tracking changes</li>
            <li>Add sample images to existing products</li>
        </ul>
    </div>
    
    <form method="POST">
        <button type="submit" name="fix_database">🔧 Fix Database Now</button>
    </form>
    
    <hr>
    <p><strong>Manual Alternative:</strong></p>
    <p>You can also run this SQL command in phpMyAdmin:</p>
    <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">ALTER TABLE products ADD COLUMN image_url VARCHAR(500) NULL AFTER description;</pre>
    
    <?php } ?>
</body>
</html>
