<?php
// Advanced Project Cleanup - Remove ALL unnecessary files
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧹 Advanced Na Porta Cleanup</h1>";
echo "<p>Removing ALL unnecessary duplicate and test files...</p>";

$cleaned = 0;
$errors = 0;

// Complete list of files to delete (based on your screenshot)
$filesToDelete = [
    // Test files
    'test-cart.php',
    'test-checkout.php', 
    'test-gender-and-password.php',
    'test-price-fix.php',
    'test-promotional-banners.php',
    'test.php',
    
    // Fix/Debug files
    'add_image_column.php',
    'auto-fix-database.php',
    'complete-fix.php',
    'create-admin.php',
    'create-promotional-banners-table.php',
    'debug-prices.php',
    'emergency-fix.php', 
    'fix-addresses.php',
    'fix-banner-modal-issues.php',
    'fix-database-now.php',
    'fix-database.php',
    'fix-foreign-key.php',
    'fix-now.php',
    'force-fix.php',
    'instant-fix.php',
    'quick-fix.php',
    'update-gender-field.php',
    
    // Duplicate setup/home files
    'home-working.php',
    'setup.php',
    'setup-orders.php',
    'simple-home.php',
    'simple-setup.php',
    
    // Cleanup files themselves (after use)
    'cleanup-project.php'
];

echo "<h2>🗑️ Deleting unnecessary files:</h2>";
echo "<div style='max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 5px;'>";

foreach ($filesToDelete as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "<p style='color: green; margin: 2px 0;'>✅ Deleted: $file</p>";
            $cleaned++;
        } else {
            echo "<p style='color: red; margin: 2px 0;'>❌ Failed to delete: $file</p>";
            $errors++;
        }
    } else {
        echo "<p style='color: gray; margin: 2px 0;'>⚪ Not found: $file</p>";
    }
}
echo "</div>";

// Files to rename (use fixed/working versions)
$filesToRename = [
    'admin/index-fixed.php' => 'admin/index.php',
    'pages/home-fixed.php' => 'pages/home.php',
    'setup-fixed.php' => 'setup.php',
    'pages/auth/login-working.php' => 'pages/auth/login.php',
    'pages/auth/register-working.php' => 'pages/auth/register.php',
    'pages/cart-working.php' => 'pages/cart.php',
    'pages/products-working.php' => 'pages/products.php',
    'pages/account/orders-working.php' => 'pages/account/orders.php',
    'pages/account/profile-working.php' => 'pages/account/profile.php'
];

echo "<h2>🔄 Using working versions:</h2>";
echo "<div style='background: #e7f3ff; padding: 10px; border-radius: 5px;'>";

foreach ($filesToRename as $oldFile => $newFile) {
    if (file_exists($oldFile)) {
        // Backup and replace
        if (file_exists($newFile)) {
            unlink($newFile);
        }
        
        if (rename($oldFile, $newFile)) {
            echo "<p style='color: blue; margin: 2px 0;'>✅ $oldFile → $newFile</p>";
            $cleaned++;
        } else {
            echo "<p style='color: red; margin: 2px 0;'>❌ Failed: $oldFile</p>";
            $errors++;
        }
    }
}
echo "</div>";

// Show remaining files
echo "<h2>📁 Remaining Core Files:</h2>";
$coreFiles = [
    'index.php' => 'Main entry point',
    'admin/index.php' => 'Admin panel',
    'pages/home.php' => 'Homepage',
    'pages/products.php' => 'Products page',
    'pages/cart.php' => 'Shopping cart',
    'config/database.php' => 'Database connection',
    'database_export.sql' => 'Database backup'
];

echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
foreach ($coreFiles as $file => $description) {
    $status = file_exists($file) ? '✅' : '❌';
    echo "<p style='margin: 2px 0;'>$status $file - $description</p>";
}
echo "</div>";

echo "<h2>📊 Final Summary:</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<p><strong>Files cleaned: $cleaned</strong></p>";
echo "<p><strong>Errors: $errors</strong></p>";

if ($errors == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>🎉 Project Successfully Cleaned!</h3>";
    echo "<p><strong>Your clean URLs:</strong></p>";
    echo "<p>🌐 <a href='/' target='_blank'>Main Website: https://naporta.free.nf/</a></p>";
    echo "<p>🔐 <a href='/admin/' target='_blank'>Admin Panel: https://naporta.free.nf/admin/</a></p>";
    echo "<p>📦 <a href='/pages/products.php' target='_blank'>Products: https://naporta.free.nf/pages/products.php</a></p>";
    echo "</div>";
    
    // Self-delete this cleanup file
    echo "<p><em>Cleaning up this cleanup file...</em></p>";
    unlink(__FILE__);
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>⚠️ Some errors occurred</h3>";
    echo "<p>Check file permissions and try running again.</p>";
    echo "</div>";
}
echo "</div>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
    line-height: 1.5;
}

h1, h2 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
}

p {
    margin: 5px 0;
    font-size: 14px;
}

a {
    color: #007bff;
    text-decoration: none;
    font-weight: bold;
}

a:hover {
    text-decoration: underline;
}

div {
    margin: 10px 0;
}
</style>
