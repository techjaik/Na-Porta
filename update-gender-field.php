<?php
// Database update script to add gender field
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h2>🔄 Database Update: Adding Gender Field</h2>";

try {
    // Check if gender column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'gender'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "ℹ️ Gender column already exists in users table.";
        echo "</div>";
    } else {
        // Add gender column
        $pdo->exec("ALTER TABLE users ADD COLUMN gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL AFTER phone");
        echo "<div style='color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ Successfully added gender column to users table.";
        echo "</div>";
    }
    
    // Show current table structure
    echo "<h3>📋 Current Users Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5f5f5;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        $highlight = $col['Field'] === 'gender' ? 'background: #ffffcc;' : '';
        echo "<tr style='$highlight'>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>🎉 Database update completed successfully!</strong><br>";
    echo "The gender field is now available with the following options:<br>";
    echo "• Male (Masculino)<br>";
    echo "• Female (Feminino)<br>";
    echo "• Other (Outro)<br>";
    echo "• Prefer not to say (Prefiro não dizer)";
    echo "</div>";
    
    echo "<p><a href='pages/auth/register-working.php' style='color: #007cba;'>🔗 Test Registration Form</a></p>";
    echo "<p><a href='pages/account/profile-working.php' style='color: #007cba;'>🔗 Test Profile Page</a></p>";
    echo "<p><a href='admin/users.php' style='color: #007cba;'>🔗 Test Admin Users Page</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
