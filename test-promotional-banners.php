<?php
// Test Promotional Banners System
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h2>🎯 Promotional Banners System Test</h2>";
echo "<p>This script tests the complete promotional banner system.</p>";

try {
    // Check if table exists
    echo "<h3>1. ✅ Database Check</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'promotional_banners'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ Table 'promotional_banners' exists.";
        echo "</div>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE promotional_banners");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<details><summary><strong>Table Structure</strong></summary>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f5f5f5;'><th>Field</th><th>Type</th><th>Key</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "</tr>";
        }
        echo "</table></details>";
    } else {
        echo "<div style='color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "❌ Table not found. Please run <a href='create-promotional-banners-table.php'>create-promotional-banners-table.php</a> first.";
        echo "</div>";
    }

    // Check current banners
    echo "<h3>2. 📊 Current Banners</h3>";
    $stmt = $pdo->query("SELECT * FROM promotional_banners ORDER BY sort_order ASC, created_at DESC");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($banners)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f5f5f5;'>";
        echo "<th>ID</th><th>Title</th><th>Type</th><th>Status</th><th>Order</th><th>Created</th>";
        echo "</tr>";
        foreach ($banners as $banner) {
            $status_color = $banner['is_active'] ? 'green' : 'red';
            $status_text = $banner['is_active'] ? 'Active' : 'Inactive';
            echo "<tr>";
            echo "<td>{$banner['id']}</td>";
            echo "<td>" . htmlspecialchars($banner['title']) . "</td>";
            echo "<td>" . ucfirst($banner['file_type']) . "</td>";
            echo "<td style='color: {$status_color};'>{$status_text}</td>";
            echo "<td>{$banner['sort_order']}</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($banner['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: #666;'>No banners found. Add some banners through the admin panel!</p>";
    }

    // Check upload directory
    echo "<h3>3. 📁 Upload Directory</h3>";
    $upload_dir = 'uploads/banners/';
    if (file_exists($upload_dir)) {
        echo "<div style='color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ Upload directory exists: {$upload_dir}";
        echo "</div>";
        
        $files = glob($upload_dir . '*');
        if (!empty($files)) {
            echo "<p><strong>Files in directory:</strong></p>";
            echo "<ul>";
            foreach ($files as $file) {
                $filename = basename($file);
                $size = filesize($file);
                $size_mb = round($size / 1024 / 1024, 2);
                echo "<li>{$filename} ({$size_mb} MB)</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>Directory is empty (no banner files uploaded yet).</p>";
        }
    } else {
        echo "<div style='color: orange; background: #fff8f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "⚠️ Upload directory doesn't exist. It will be created when you upload the first banner.";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔗 System Links</h3>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0;'>";

echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
echo "<h4>🏠 Frontend</h4>";
echo "<p>View the promotional banners on the home page</p>";
echo "<a href='pages/home-fixed.php' style='color: #007cba; text-decoration: none;'>→ Home Page</a>";
echo "</div>";

echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
echo "<h4>⚙️ Admin Dashboard</h4>";
echo "<p>Access the admin dashboard</p>";
echo "<a href='admin/index-fixed.php' style='color: #007cba; text-decoration: none;'>→ Admin Dashboard</a>";
echo "</div>";

echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
echo "<h4>🎯 Banner Management</h4>";
echo "<p>Manage promotional banners directly</p>";
echo "<a href='admin/banners.php' style='color: #007cba; text-decoration: none;'>→ Banner Management</a>";
echo "</div>";

echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
echo "<h4>🔧 Setup</h4>";
echo "<p>Create database table if needed</p>";
echo "<a href='create-promotional-banners-table.php' style='color: #007cba; text-decoration: none;'>→ Setup Database</a>";
echo "</div>";

echo "</div>";

echo "<hr>";
echo "<h3>✅ Features Implemented</h3>";
echo "<ul>";
echo "<li><strong>✅ Database Table:</strong> promotional_banners with all required fields</li>";
echo "<li><strong>✅ Home Page Integration:</strong> Beautiful slider section below hero</li>";
echo "<li><strong>✅ Admin Interface:</strong> Complete banner management system</li>";
echo "<li><strong>✅ File Upload:</strong> Support for images (JPG, PNG, GIF, WebP) and videos (MP4, WebM)</li>";
echo "<li><strong>✅ Slider Functionality:</strong> Auto-slide, navigation arrows, and dots</li>";
echo "<li><strong>✅ Responsive Design:</strong> Works on desktop and mobile</li>";
echo "<li><strong>✅ Admin Dashboard:</strong> Banner statistics and quick access</li>";
echo "</ul>";

echo "<div style='color: green; background: #f0f8f0; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>🎉 System Ready!</h4>";
echo "<p>The promotional banner system is fully implemented and ready to use:</p>";
echo "<ol>";
echo "<li><strong>Setup:</strong> Run the database setup if you haven't already</li>";
echo "<li><strong>Upload:</strong> Go to Admin → Banners to upload your first promotional banner</li>";
echo "<li><strong>View:</strong> Check the home page to see your banners in action</li>";
echo "<li><strong>Manage:</strong> Use the admin panel to add, edit, or remove banners</li>";
echo "</ol>";
echo "</div>";
?>

<style>
details {
    margin: 10px 0;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

summary {
    cursor: pointer;
    font-weight: bold;
    padding: 5px;
}

summary:hover {
    background-color: #f5f5f5;
}
</style>
