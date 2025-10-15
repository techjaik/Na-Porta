<?php
/**
 * Debug Registration Issue - Check Database and Registration Process
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $auth = new Auth();
    
    echo "<h2>🔍 Registration Issue Diagnostic</h2>";
    echo "<p>Checking database structure and registration process...</p>";
    
    // 1. Check if users table exists
    echo "<h3>1. Database Structure Check</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $existingColumns = array_column($columns, 'Field');
        
        echo "✅ <strong>Users table exists</strong><br>";
        echo "<h4>Current Users Table Structure:</h4>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // Check for required columns
        $requiredColumns = ['id', 'name', 'email', 'password', 'created_at'];
        $missingColumns = [];
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $existingColumns)) {
                $missingColumns[] = $col;
            }
        }
        
        if (empty($missingColumns)) {
            echo "✅ <strong>All required columns exist</strong><br>";
        } else {
            echo "❌ <strong>Missing required columns:</strong> " . implode(', ', $missingColumns) . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Users table does not exist or error:</strong> " . $e->getMessage() . "<br>";
    }
    
    // 2. Check existing users
    echo "<br><h3>2. Existing Users Check</h3>";
    try {
        $users = $pdo->query("SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($users)) {
            echo "ℹ️ <strong>No users found in database</strong><br>";
        } else {
            echo "📊 <strong>Found " . count($users) . " users:</strong><br>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Created At</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>{$user['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table><br>";
        }
    } catch (Exception $e) {
        echo "❌ <strong>Error checking users:</strong> " . $e->getMessage() . "<br>";
    }
    
    // 3. Test email check function
    echo "<br><h3>3. Email Check Function Test</h3>";
    $testEmails = [
        'test@example.com',
        'techjaik@gmail.com',
        'newuser@test.com'
    ];
    
    foreach ($testEmails as $testEmail) {
        try {
            $existing = $db->fetch("SELECT id, name, email FROM users WHERE email = ?", [$testEmail]);
            if ($existing) {
                echo "📧 <strong>$testEmail:</strong> ❌ Already exists (ID: {$existing['id']}, Name: {$existing['name']})<br>";
            } else {
                echo "📧 <strong>$testEmail:</strong> ✅ Available for registration<br>";
            }
        } catch (Exception $e) {
            echo "📧 <strong>$testEmail:</strong> ❌ Error checking: " . $e->getMessage() . "<br>";
        }
    }
    
    // 4. Test registration process with a dummy email
    echo "<br><h3>4. Registration Process Test</h3>";
    $testName = "Test User " . date('His');
    $testEmail = "test_" . date('His') . "@example.com";
    $testPassword = "test123";
    
    echo "🧪 <strong>Testing registration with:</strong><br>";
    echo "- Name: $testName<br>";
    echo "- Email: $testEmail<br>";
    echo "- Password: $testPassword<br><br>";
    
    try {
        // First check if email exists (should not)
        $existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$testEmail]);
        if ($existing) {
            echo "⚠️ <strong>Test email already exists (this shouldn't happen)</strong><br>";
        } else {
            echo "✅ <strong>Test email is available</strong><br>";
        }
        
        // Try registration
        $result = $auth->registerUser($testName, $testEmail, $testPassword);
        
        if ($result) {
            echo "✅ <strong>Registration successful!</strong> User ID: $result<br>";
            
            // Verify user was created
            $newUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$result]);
            if ($newUser) {
                echo "✅ <strong>User verified in database:</strong><br>";
                echo "- ID: {$newUser['id']}<br>";
                echo "- Name: {$newUser['name']}<br>";
                echo "- Email: {$newUser['email']}<br>";
                echo "- Created: {$newUser['created_at']}<br>";
            }
            
            // Clean up test user
            $db->query("DELETE FROM users WHERE id = ?", [$result]);
            echo "🧹 <strong>Test user cleaned up</strong><br>";
            
        } else {
            echo "❌ <strong>Registration failed</strong><br>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Registration test error:</strong> " . $e->getMessage() . "<br>";
    }
    
    // 5. Check for database connection issues
    echo "<br><h3>5. Database Connection Test</h3>";
    try {
        $result = $pdo->query("SELECT 1 as test")->fetch();
        if ($result && $result['test'] == 1) {
            echo "✅ <strong>Database connection is working</strong><br>";
        } else {
            echo "❌ <strong>Database connection issue</strong><br>";
        }
    } catch (Exception $e) {
        echo "❌ <strong>Database connection error:</strong> " . $e->getMessage() . "<br>";
    }
    
    // 6. Check for case sensitivity issues
    echo "<br><h3>6. Case Sensitivity Test</h3>";
    $testCaseEmails = [
        'TEST@EXAMPLE.COM',
        'test@example.com',
        'Test@Example.Com'
    ];
    
    foreach ($testCaseEmails as $email) {
        $existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
        echo "📧 <strong>$email:</strong> " . ($existing ? "❌ Exists" : "✅ Available") . "<br>";
    }
    
    echo "<br><h3>🎯 Recommendations:</h3>";
    echo "<ul>";
    echo "<li>If registration still fails, check the exact email being used</li>";
    echo "<li>Clear browser cache and cookies</li>";
    echo "<li>Try with a completely different email address</li>";
    echo "<li>Check if there are any hidden characters in the email field</li>";
    echo "</ul>";
    
    echo "<br><p><a href='auth/register.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Registration Again</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Critical Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
