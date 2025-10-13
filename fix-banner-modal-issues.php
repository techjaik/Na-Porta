<?php
// Fix Banner Modal Issues
echo "<h2>🔧 Fixing Banner Modal Issues</h2>";

// Check if the promotional_banners table exists
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'promotional_banners'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ Database table 'promotional_banners' exists.";
        echo "</div>";
    } else {
        echo "<div style='color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "❌ Database table 'promotional_banners' does not exist.";
        echo "<br><strong>Solution:</strong> <a href='create-promotional-banners-table.php'>Click here to create the table</a>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ Database connection error: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔧 Issues Fixed</h3>";
echo "<ul>";
echo "<li><strong>✅ Modal Attributes:</strong> Added both Bootstrap 5 and MDBootstrap compatibility</li>";
echo "<li><strong>✅ JavaScript Handlers:</strong> Added manual modal initialization</li>";
echo "<li><strong>✅ Button Events:</strong> Ensured cancel and save buttons work properly</li>";
echo "<li><strong>✅ Database Table:</strong> Instructions to create missing table</li>";
echo "</ul>";

echo "<hr>";
echo "<h3>🔗 Test Links</h3>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0;'>";

echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
echo "<h4>🎯 Banner Management</h4>";
echo "<p>Test the fixed banner management page</p>";
echo "<a href='admin/banners.php' style='color: #007cba; text-decoration: none;'>→ Banner Management</a>";
echo "</div>";

echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
echo "<h4>🔧 Create Table</h4>";
echo "<p>Create the database table if needed</p>";
echo "<a href='create-promotional-banners-table.php' style='color: #007cba; text-decoration: none;'>→ Setup Database</a>";
echo "</div>";

echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
echo "<h4>⚙️ Admin Dashboard</h4>";
echo "<p>Access the main admin dashboard</p>";
echo "<a href='admin/index-fixed.php' style='color: #007cba; text-decoration: none;'>→ Admin Dashboard</a>";
echo "</div>";

echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
echo "<h4>🏠 Home Page</h4>";
echo "<p>View the promotional banners in action</p>";
echo "<a href='pages/home-fixed.php' style='color: #007cba; text-decoration: none;'>→ Home Page</a>";
echo "</div>";

echo "</div>";

echo "<div style='color: blue; background: #f0f8ff; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>📋 Next Steps</h4>";
echo "<ol>";
echo "<li><strong>Create Database Table:</strong> If you see the red error, click the 'Setup Database' link above</li>";
echo "<li><strong>Test Modal Functionality:</strong> Go to Banner Management and try clicking 'Novo Banner'</li>";
echo "<li><strong>Upload a Banner:</strong> Test the complete upload process</li>";
echo "<li><strong>View Results:</strong> Check the home page to see your banners</li>";
echo "</ol>";
echo "</div>";

echo "<div style='color: green; background: #f0f8f0; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>🎉 Modal Issues Fixed!</h4>";
echo "<p>The banner management page now has:</p>";
echo "<ul>";
echo "<li><strong>Working Cancel Button:</strong> Properly closes the modal</li>";
echo "<li><strong>Working Save Button:</strong> Submits the form correctly</li>";
echo "<li><strong>Cross-Browser Compatibility:</strong> Works with different Bootstrap versions</li>";
echo "<li><strong>Proper Event Handling:</strong> Manual JavaScript initialization as backup</li>";
echo "</ul>";
echo "</div>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

h2, h3, h4 {
    color: #333;
}

a {
    color: #007cba;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

ul, ol {
    padding-left: 20px;
}

li {
    margin-bottom: 5px;
}
</style>
