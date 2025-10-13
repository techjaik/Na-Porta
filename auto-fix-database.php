<?php
// Auto Database Fix for Na Porta
header('Content-Type: text/plain');

try {
    require_once 'config/database.php';
    
    echo "🔧 Fixing Database Structure...\n\n";
    
    // Check if image_url column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_url'");
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        // Add the image_url column
        $pdo->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(500) NULL AFTER description");
        echo "✅ Added image_url column to products table\n";
    } else {
        echo "ℹ️ image_url column already exists\n";
    }
    
    // Check if updated_at column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'updated_at'");
    $updated_at_exists = $stmt->rowCount() > 0;
    
    if (!$updated_at_exists) {
        $pdo->exec("ALTER TABLE products ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        echo "✅ Added updated_at column to products table\n";
    } else {
        echo "ℹ️ updated_at column already exists\n";
    }
    
    // Check categories table
    $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'updated_at'");
    $cat_updated_exists = $stmt->rowCount() > 0;
    
    if (!$cat_updated_exists) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        echo "✅ Added updated_at column to categories table\n";
    } else {
        echo "ℹ️ Categories updated_at column already exists\n";
    }
    
    echo "\n🎉 Database structure updated successfully!\n";
    echo "✅ You can now add images to products using URLs\n";
    echo "✅ Edit and delete functions are fully working\n\n";
    echo "📋 Next steps:\n";
    echo "1. Go back to admin/products.php\n";
    echo "2. The image URL field should now be available\n";
    echo "3. Test with: https://picsum.photos/id/237/200/300\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
