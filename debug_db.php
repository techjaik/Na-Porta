<?php
/**
 * Database Debug Script
 * Test database connection and table structure
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test basic connection
    echo "<p>✅ Database connection successful!</p>";
    
    // Check if categories table exists
    $tables = $db->fetchAll("SHOW TABLES LIKE 'categories'");
    if (empty($tables)) {
        echo "<p>❌ Categories table does not exist!</p>";
        
        // Try to create the table
        echo "<p>Creating categories table...</p>";
        $create_sql = "
        CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            image VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $db->query($create_sql);
        echo "<p>✅ Categories table created successfully!</p>";
    } else {
        echo "<p>✅ Categories table exists!</p>";
    }
    
    // Show table structure
    echo "<h3>Categories Table Structure:</h3>";
    $columns = $db->fetchAll("DESCRIBE categories");
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
    
    // Test insert query
    echo "<h3>Testing Insert Query:</h3>";
    $test_name = "Test Category " . time();
    $test_slug = "test-category-" . time();
    $test_description = "Test description";
    $test_image = "";
    $test_active = 1;
    
    echo "<p>Attempting to insert: $test_name</p>";
    
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
    
    // Check existing categories
    echo "<h3>Existing Categories:</h3>";
    $categories = $db->fetchAll("SELECT * FROM categories ORDER BY id DESC LIMIT 5");
    if (empty($categories)) {
        echo "<p>No categories found</p>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Slug</th><th>Image</th><th>Active</th></tr>";
        foreach ($categories as $cat) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($cat['id']) . "</td>";
            echo "<td>" . htmlspecialchars($cat['name']) . "</td>";
            echo "<td>" . htmlspecialchars($cat['slug']) . "</td>";
            echo "<td>" . htmlspecialchars($cat['image'] ?? 'NULL') . "</td>";
            echo "<td>" . ($cat['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<p><a href='admin/categories.php'>← Back to Categories Admin</a></p>";
?>
