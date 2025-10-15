<?php
/**
 * Simple Registration Fix - Direct Database Access
 */

// Direct database connection (bypass any framework issues)
$host = 'sql105.infinityfree.com';
$dbname = 'if0_40155099_naporta_db';
$username = 'if0_40155099';
$password = 'Jaishreeramm9';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Direct Registration Fix</h2>";
    
    // 1. Check if users table exists and its structure
    echo "<h3>1. Database Structure</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Users table exists with columns:<br>";
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']})<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
    
    // 2. Show all users
    echo "<br><h3>2. All Users in Database</h3>";
    try {
        $users = $pdo->query("SELECT id, name, email, created_at FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($users)) {
            echo "ℹ️ No users found<br>";
        } else {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Created</th><th>Action</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>{$user['created_at']}</td>";
                echo "<td><a href='?delete_user={$user['id']}' onclick='return confirm(\"Delete this user?\")' style='color: red;'>Delete</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
    
    // 3. Handle user deletion
    if (isset($_GET['delete_user'])) {
        $userId = (int)$_GET['delete_user'];
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            echo "<br>✅ <strong>User ID $userId deleted successfully!</strong><br>";
            echo "<script>setTimeout(function(){ window.location.href = 'simple_registration_fix.php'; }, 2000);</script>";
        } catch (Exception $e) {
            echo "<br>❌ Error deleting user: " . $e->getMessage() . "<br>";
        }
    }
    
    // 4. Test specific email
    if (isset($_GET['test_email'])) {
        $testEmail = trim($_GET['test_email']);
        echo "<br><h3>3. Testing Email: " . htmlspecialchars($testEmail) . "</h3>";
        
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE email = ?");
            $stmt->execute([$testEmail]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "❌ <strong>Email already exists:</strong><br>";
                echo "- ID: {$user['id']}<br>";
                echo "- Name: " . htmlspecialchars($user['name']) . "<br>";
                echo "- Email: " . htmlspecialchars($user['email']) . "<br>";
                echo "- Created: {$user['created_at']}<br>";
                echo "<br><a href='?delete_user={$user['id']}' onclick='return confirm(\"Delete this user to free up the email?\")' style='background: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Delete This User</a>";
            } else {
                echo "✅ <strong>Email is available!</strong><br>";
                echo "<a href='auth/register.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Registration</a>";
            }
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "<br>";
        }
    }
    
    // 5. Clear all users (emergency option)
    if (isset($_GET['clear_all']) && $_GET['clear_all'] === 'confirm') {
        try {
            $pdo->exec("DELETE FROM users");
            echo "<br>✅ <strong>All users cleared!</strong><br>";
            echo "<script>setTimeout(function(){ window.location.href = 'simple_registration_fix.php'; }, 2000);</script>";
        } catch (Exception $e) {
            echo "<br>❌ Error clearing users: " . $e->getMessage() . "<br>";
        }
    }
    
    // 6. Create test user
    if (isset($_GET['create_test'])) {
        $testName = "Test User " . date('His');
        $testEmail = "test_" . date('His') . "@example.com";
        $testPassword = password_hash("test123", PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$testName, $testEmail, $testPassword]);
            $newId = $pdo->lastInsertId();
            
            echo "<br>✅ <strong>Test user created successfully!</strong><br>";
            echo "- ID: $newId<br>";
            echo "- Name: $testName<br>";
            echo "- Email: $testEmail<br>";
            echo "- Password: test123<br>";
            echo "<script>setTimeout(function(){ window.location.href = 'simple_registration_fix.php'; }, 2000);</script>";
        } catch (Exception $e) {
            echo "<br>❌ Error creating test user: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br><h3>🛠️ Tools</h3>";
    echo "<form method='GET' style='margin: 10px 0;'>";
    echo "<input type='text' name='test_email' placeholder='Enter email to test' style='padding: 8px; width: 250px;'>";
    echo "<input type='submit' value='Test Email' style='padding: 8px 16px; margin-left: 5px;'>";
    echo "</form>";
    
    echo "<p>";
    echo "<a href='?create_test=1' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Create Test User</a>";
    echo "<a href='?clear_all=confirm' onclick='return confirm(\"Are you sure you want to delete ALL users? This cannot be undone!\")' style='background: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Clear All Users</a>";
    echo "</p>";
    
    echo "<br><h3>📋 Next Steps</h3>";
    echo "<ol>";
    echo "<li>Check if your email exists in the table above</li>";
    echo "<li>If it exists, delete it using the 'Delete' link</li>";
    echo "<li>Try registration again</li>";
    echo "<li>If problems persist, try creating a test user first</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h3>❌ Database Connection Error</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p>Please check database credentials and connection.</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; font-weight: bold; }
tr:nth-child(even) { background-color: #f9f9f9; }
h2, h3 { color: #333; }
.success { color: #28a745; }
.error { color: #dc3545; }
.info { color: #17a2b8; }
</style>
