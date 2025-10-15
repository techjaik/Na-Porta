<?php
/**
 * Products Database Debug Script
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

echo "<h2>Products Database Test</h2>";

try {
    // Check if products table exists
    $tables = $db->fetchAll("SHOW TABLES LIKE 'products'");
    if (empty($tables)) {
        echo "<p>❌ Products table does not exist!</p>";
        
        // Try to create the table
        echo "<p>Creating products table...</p>";
        $create_sql = "
        CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            name VARCHAR(200) NOT NULL,
            slug VARCHAR(200) UNIQUE NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            image_url VARCHAR(255),
            is_featured BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            stock_quantity INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )";
        
        $db->query($create_sql);
        echo "<p>✅ Products table created successfully!</p>";
    } else {
        echo "<p>✅ Products table exists!</p>";
    }
    
    // Show table structure
    echo "<h3>Products Table Structure:</h3>";
    $columns = $db->fetchAll("DESCRIBE products");
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
    
    // Check existing products
    echo "<h3>Existing Products:</h3>";
    $products = $db->fetchAll("SELECT * FROM products ORDER BY id DESC LIMIT 5");
    if (empty($products)) {
        echo "<p>No products found</p>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Image URL</th><th>Active</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['id']) . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>R$ " . number_format($product['price'], 2, ',', '.') . "</td>";
            echo "<td>" . htmlspecialchars($product['image_url'] ?? 'NULL') . "</td>";
            echo "<td>" . ($product['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<p><a href='admin/products.php'>← Back to Products Admin</a></p>";
?>
