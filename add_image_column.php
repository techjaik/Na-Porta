<?php
// Add image_url column to products table
require_once 'config/database.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_url'");
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        // Add the column
        $pdo->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(500) NULL AFTER description");
        echo "✅ image_url column added successfully to products table\n";
    } else {
        echo "✅ image_url column already exists in products table\n";
    }
    
    // Update some sample products with image URLs
    $sample_images = [
        'agua-mineral' => 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=400',
        'gas-de-cozinha' => 'https://images.unsplash.com/photo-1574263867128-a3d5c1b1deaa?w=400',
        'detergente' => 'https://images.unsplash.com/photo-1563453392212-326f5e854473?w=400',
        'arroz' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=400'
    ];
    
    foreach ($sample_images as $slug => $image_url) {
        $stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE slug LIKE ? AND image_url IS NULL");
        $stmt->execute([$image_url, "%$slug%"]);
    }
    
    echo "✅ Sample product images updated\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
