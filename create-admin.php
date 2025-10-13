<?php
// Create Admin User Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Create Admin User</h1>";

try {
    // Check if admin table exists
    $result = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    if ($result->rowCount() == 0) {
        echo "<p style='color: red;'>❌ Admin users table doesn't exist. Run setup first.</p>";
        echo "<p><a href='setup-fixed.php'>Run Setup</a></p>";
        exit;
    }
    
    // Delete existing admin user
    $pdo->exec("DELETE FROM admin_users WHERE username = 'admin'");
    
    // Create new admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password, role, name, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@naporta.com', $admin_password, 'super_admin', 'Administrator', 1]);
    
    echo "<p>✅ Admin user created successfully!</p>";
    echo "<p><strong>Login Details:</strong></p>";
    echo "<p>Username: <code>admin</code></p>";
    echo "<p>Password: <code>admin123</code></p>";
    echo "<p><a href='admin/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Login</a></p>";
    
    // Test the password hash
    echo "<h3>Password Verification Test:</h3>";
    if (password_verify('admin123', $admin_password)) {
        echo "<p>✅ Password hash verification works correctly</p>";
    } else {
        echo "<p style='color: red;'>❌ Password hash verification failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
}

h1 {
    color: #333;
    text-align: center;
}

p {
    background: white;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
}

code {
    background: #f1f3f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
</style>
