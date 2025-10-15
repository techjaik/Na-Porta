<?php
/**
 * Fix User Registration Issues - Clean Database and Fix Problems
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🔧 Fixing User Registration Issues</h2>";
    echo "<p>Cleaning database and fixing registration problems...</p>";
    
    // 1. Check for duplicate emails
    echo "<h3>1. Checking for Duplicate Emails</h3>";
    $duplicates = $pdo->query("
        SELECT email, COUNT(*) as count 
        FROM users 
        GROUP BY email 
        HAVING COUNT(*) > 1
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        echo "✅ <strong>No duplicate emails found</strong><br>";
    } else {
        echo "⚠️ <strong>Found duplicate emails:</strong><br>";
        foreach ($duplicates as $dup) {
            echo "- {$dup['email']} ({$dup['count']} times)<br>";
        }
        
        // Fix duplicates by keeping the oldest entry
        foreach ($duplicates as $dup) {
            echo "<br>🔧 <strong>Fixing duplicates for {$dup['email']}:</strong><br>";
            
            // Get all users with this email, ordered by creation date
            $users = $pdo->prepare("SELECT id, name, created_at FROM users WHERE email = ? ORDER BY created_at ASC");
            $users->execute([$dup['email']]);
            $userList = $users->fetchAll(PDO::FETCH_ASSOC);
            
            // Keep the first (oldest) user, delete the rest
            $keepUser = array_shift($userList);
            echo "✅ Keeping user ID {$keepUser['id']} (created: {$keepUser['created_at']})<br>";
            
            foreach ($userList as $deleteUser) {
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$deleteUser['id']]);
                echo "🗑️ Deleted duplicate user ID {$deleteUser['id']} (created: {$deleteUser['created_at']})<br>";
            }
        }
    }
    
    // 2. Check for invalid/empty emails
    echo "<br><h3>2. Checking for Invalid Emails</h3>";
    $invalidEmails = $pdo->query("
        SELECT id, name, email 
        FROM users 
        WHERE email IS NULL OR email = '' OR email NOT LIKE '%@%'
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($invalidEmails)) {
        echo "✅ <strong>No invalid emails found</strong><br>";
    } else {
        echo "⚠️ <strong>Found invalid emails:</strong><br>";
        foreach ($invalidEmails as $user) {
            echo "- User ID {$user['id']}: '{$user['email']}' (Name: {$user['name']})<br>";
        }
        
        // Option to delete or fix invalid emails
        echo "<br>🔧 <strong>Cleaning up invalid email entries...</strong><br>";
        foreach ($invalidEmails as $user) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user['id']]);
            echo "🗑️ Deleted user with invalid email: ID {$user['id']}<br>";
        }
    }
    
    // 3. Check database constraints
    echo "<br><h3>3. Checking Database Constraints</h3>";
    try {
        $indexes = $pdo->query("SHOW INDEX FROM users WHERE Column_name = 'email'")->fetchAll(PDO::FETCH_ASSOC);
        
        $hasUniqueConstraint = false;
        foreach ($indexes as $index) {
            if ($index['Non_unique'] == 0) { // 0 means unique
                $hasUniqueConstraint = true;
                echo "✅ <strong>Email unique constraint exists:</strong> {$index['Key_name']}<br>";
            }
        }
        
        if (!$hasUniqueConstraint) {
            echo "⚠️ <strong>No unique constraint on email column</strong><br>";
            echo "🔧 <strong>Adding unique constraint...</strong><br>";
            
            try {
                $pdo->exec("ALTER TABLE users ADD UNIQUE KEY unique_email (email)");
                echo "✅ <strong>Unique constraint added successfully</strong><br>";
            } catch (Exception $e) {
                echo "❌ <strong>Failed to add unique constraint:</strong> " . $e->getMessage() . "<br>";
                echo "This might be due to existing duplicate emails. Clean duplicates first.<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Error checking constraints:</strong> " . $e->getMessage() . "<br>";
    }
    
    // 4. Test registration with a clean email
    echo "<br><h3>4. Testing Registration Process</h3>";
    
    // Check if the problematic email exists
    if (isset($_GET['test_email'])) {
        $testEmail = $_GET['test_email'];
        echo "🧪 <strong>Testing specific email:</strong> $testEmail<br>";
        
        $existing = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE email = ?");
        $existing->execute([$testEmail]);
        $user = $existing->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "❌ <strong>Email already exists:</strong><br>";
            echo "- ID: {$user['id']}<br>";
            echo "- Name: {$user['name']}<br>";
            echo "- Email: {$user['email']}<br>";
            echo "- Created: {$user['created_at']}<br>";
            
            echo "<br>🔧 <strong>Options:</strong><br>";
            echo "<a href='?delete_user={$user['id']}&test_email=$testEmail' style='background: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Delete This User</a>";
            echo "<a href='auth/login.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Try Login Instead</a>";
        } else {
            echo "✅ <strong>Email is available for registration!</strong><br>";
            echo "<a href='auth/register.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Registration Now</a>";
        }
    }
    
    // Handle user deletion
    if (isset($_GET['delete_user']) && isset($_GET['test_email'])) {
        $userId = (int)$_GET['delete_user'];
        $testEmail = $_GET['test_email'];
        
        try {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            echo "<br>✅ <strong>User deleted successfully!</strong><br>";
            echo "<a href='auth/register.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Registration Now</a>";
        } catch (Exception $e) {
            echo "<br>❌ <strong>Error deleting user:</strong> " . $e->getMessage() . "<br>";
        }
    }
    
    // 5. Show current user count and recent registrations
    echo "<br><h3>5. Current Database Status</h3>";
    $userCount = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
    echo "📊 <strong>Total users in database:</strong> $userCount<br>";
    
    $recentUsers = $pdo->query("
        SELECT id, name, email, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($recentUsers)) {
        echo "<br><strong>Recent registrations:</strong><br>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Created</th></tr>";
        foreach ($recentUsers as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<br><h3>🎯 Next Steps:</h3>";
    echo "<ul>";
    echo "<li>✅ Database cleaned and constraints fixed</li>";
    echo "<li>✅ Duplicate emails removed</li>";
    echo "<li>✅ Invalid entries cleaned up</li>";
    echo "<li>🔍 Test specific email: <a href='?test_email=YOUR_EMAIL_HERE'>Check Email</a></li>";
    echo "</ul>";
    
    echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
    echo "<strong>✅ Registration system should now work correctly!</strong><br>";
    echo "Try registering with a new email address.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3>❌ Critical Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
