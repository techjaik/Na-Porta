<?php
/**
 * Live Database Update Script
 * Fix database structure issues on the live server
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

echo "<h2>Live Database Update</h2>";

try {
    echo "<p>🔧 Checking and updating database structure...</p>";
    
    // Check current categories table structure
    echo "<h3>Current Categories Table Structure:</h3>";
    $columns = $db->fetchAll("DESCRIBE categories");
    $column_names = array_column($columns, 'Field');
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Add missing columns if needed
    $updates_made = false;
    
    if (!in_array('updated_at', $column_names)) {
        echo "<p>➕ Adding missing 'updated_at' column...</p>";
        $db->query("ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
        echo "<p>✅ Added 'updated_at' column</p>";
        $updates_made = true;
    }
    
    // Check products table structure
    echo "<h3>Current Products Table Structure:</h3>";
    $product_columns = $db->fetchAll("DESCRIBE products");
    $product_column_names = array_column($product_columns, 'Field');
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($product_columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Add missing columns to products if needed
    if (!in_array('updated_at', $product_column_names)) {
        echo "<p>➕ Adding missing 'updated_at' column to products...</p>";
        $db->query("ALTER TABLE products ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
        echo "<p>✅ Added 'updated_at' column to products</p>";
        $updates_made = true;
    }
    
    // Test insert query for categories
    echo "<h3>Testing Category Insert:</h3>";
    $test_name = "Test Category " . time();
    $test_slug = "test-category-" . time();
    $test_description = "Test description";
    $test_image = "";
    $test_active = 1;
    
    echo "<p>Attempting to insert: $test_name</p>";
    
    try {
        $stmt = $db->query("
            INSERT INTO categories (name, slug, description, image, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ", [$test_name, $test_slug, $test_description, $test_image, $test_active]);
        
        if ($stmt) {
            $insert_id = $db->lastInsertId();
            echo "<p>✅ Insert successful! New category ID: $insert_id</p>";
            
            // Clean up test data
            $db->query("DELETE FROM categories WHERE id = ?", [$insert_id]);
            echo "<p>✅ Test data cleaned up</p>";
        } else {
            echo "<p>❌ Insert failed!</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Insert error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Test insert query for products
    echo "<h3>Testing Product Insert:</h3>";
    
    // First get a category ID
    $category = $db->fetch("SELECT id FROM categories WHERE is_active = 1 LIMIT 1");
    if ($category) {
        $test_product_name = "Test Product " . time();
        $test_product_slug = "test-product-" . time();
        $test_product_description = "Test product description";
        $test_product_price = 10.99;
        $test_product_image = "";
        
        echo "<p>Attempting to insert: $test_product_name</p>";
        
        try {
            $stmt = $db->query("
                INSERT INTO products (name, slug, description, price, category_id, image_url, is_featured, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [$test_product_name, $test_product_slug, $test_product_description, $test_product_price, $category['id'], $test_product_image, 0, 1]);
            
            if ($stmt) {
                $insert_id = $db->lastInsertId();
                echo "<p>✅ Product insert successful! New product ID: $insert_id</p>";
                
                // Clean up test data
                $db->query("DELETE FROM products WHERE id = ?", [$insert_id]);
                echo "<p>✅ Test product data cleaned up</p>";
            } else {
                echo "<p>❌ Product insert failed!</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Product insert error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p>⚠️ No active categories found for product test</p>";
    }
    
    if ($updates_made) {
        echo "<p><strong>✅ Database structure updated successfully!</strong></p>";
    } else {
        echo "<p><strong>✅ Database structure is already correct!</strong></p>";
    }
    
    echo "<p><a href='admin/categories.php'>Test Categories Admin</a> | <a href='admin/products.php'>Test Products Admin</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
