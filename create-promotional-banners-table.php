<?php
// Create promotional banners table
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h2>🎯 Creating Promotional Banners Table</h2>";

try {
    // Check if table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'promotional_banners'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='color: orange; background: #fff8f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "⚠️ Table 'promotional_banners' already exists. Skipping creation.";
        echo "</div>";
    } else {
        // Create promotional_banners table
        $sql = "CREATE TABLE promotional_banners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            file_path VARCHAR(500) NOT NULL,
            file_type ENUM('image', 'video') NOT NULL DEFAULT 'image',
            link_url VARCHAR(500),
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($pdo->exec($sql)) {
            echo "<div style='color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ Table 'promotional_banners' created successfully!";
            echo "</div>";
        } else {
            echo "<div style='color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "❌ Error creating table.";
            echo "</div>";
        }
    }
    
    // Show table structure
    echo "<h3>📋 Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE promotional_banners");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5f5f5;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Insert sample data
    echo "<h3>📝 Adding Sample Data</h3>";
    
    // Check if sample data already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM promotional_banners");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $sample_data = [
            [
                'title' => 'Big Bang Diwali Sale',
                'description' => 'Upgrade your kitchen - Prestige, Pigeon & more from ₹99',
                'file_path' => 'uploads/banners/sample-banner-1.jpg',
                'file_type' => 'image',
                'link_url' => '/products',
                'is_active' => 1,
                'sort_order' => 1
            ],
            [
                'title' => 'Special Offers',
                'description' => 'Get 10% instant discount on SBI cards',
                'file_path' => 'uploads/banners/sample-banner-2.jpg',
                'file_type' => 'image',
                'link_url' => '/offers',
                'is_active' => 1,
                'sort_order' => 2
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO promotional_banners (title, description, file_path, file_type, link_url, is_active, sort_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sample_data as $banner) {
            $stmt->execute([
                $banner['title'],
                $banner['description'],
                $banner['file_path'],
                $banner['file_type'],
                $banner['link_url'],
                $banner['is_active'],
                $banner['sort_order']
            ]);
        }
        
        echo "<div style='color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ Sample promotional banners added successfully!";
        echo "</div>";
    } else {
        echo "<div style='color: blue; background: #f0f8ff; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "ℹ️ Sample data already exists. Found {$count} banner(s).";
        echo "</div>";
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/banners';
    if (!file_exists($upload_dir)) {
        if (mkdir($upload_dir, 0755, true)) {
            echo "<div style='color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ Upload directory '{$upload_dir}' created successfully!";
            echo "</div>";
        } else {
            echo "<div style='color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "❌ Failed to create upload directory '{$upload_dir}'.";
            echo "</div>";
        }
    }

} catch (Exception $e) {
    echo "<div style='color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔗 Next Steps</h3>";
echo "<ol>";
echo "<li><a href='pages/home-fixed.php'>View Home Page</a> - See the promotional section</li>";
echo "<li><a href='admin/index-fixed.php'>Admin Dashboard</a> - Manage banners</li>";
echo "<li><a href='admin/banners.php'>Banner Management</a> - Direct banner admin (will be created)</li>";
echo "</ol>";
?>
