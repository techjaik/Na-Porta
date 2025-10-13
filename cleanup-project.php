<?php
// Project Cleanup Script - Remove duplicate and test files
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧹 Na Porta Project Cleanup</h1>";
echo "<p>This will clean up duplicate files and use the working versions.</p>";

$cleaned = 0;
$errors = 0;

// Files to delete (test and debug files)
$filesToDelete = [
    // Test files
    'test-cart.php',
    'test-checkout.php', 
    'test-gender-and-password.php',
    'test-price-fix.php',
    'test-promotional-banners.php',
    'test.php',
    
    // Fix/Debug files
    'auto-fix-database.php',
    'complete-fix.php',
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
    
    // Duplicate setup files
    'setup.php',
    'simple-setup.php',
    'simple-home.php',
    
    // Other duplicates
    'home-working.php',
    'add_image_column.php',
    'create-admin.php',
    'create-promotional-banners-table.php',
    'update-gender-field.php',
    'debug-prices.php',
    'setup-orders.php'
];

echo "<h2>🗑️ Deleting unnecessary files:</h2>";
foreach ($filesToDelete as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "<p>✅ Deleted: $file</p>";
            $cleaned++;
        } else {
            echo "<p>❌ Failed to delete: $file</p>";
            $errors++;
        }
    }
}

// Files to rename (use fixed versions)
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

echo "<h2>🔄 Using fixed/working versions:</h2>";
foreach ($filesToRename as $oldFile => $newFile) {
    if (file_exists($oldFile)) {
        // Delete old version if exists
        if (file_exists($newFile)) {
            unlink($newFile);
        }
        
        if (rename($oldFile, $newFile)) {
            echo "<p>✅ Renamed: $oldFile → $newFile</p>";
            $cleaned++;
        } else {
            echo "<p>❌ Failed to rename: $oldFile</p>";
            $errors++;
        }
    }
}

echo "<h2>📊 Cleanup Summary:</h2>";
echo "<p><strong>Files cleaned: $cleaned</strong></p>";
echo "<p><strong>Errors: $errors</strong></p>";

if ($errors == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Cleanup Successful!</h3>";
    echo "<p>Your project is now clean and organized.</p>";
    echo "<p><strong>Main URLs:</strong></p>";
    echo "<p>• Website: <a href='/'>https://naporta.free.nf/</a></p>";
    echo "<p>• Admin: <a href='/admin/'>https://naporta.free.nf/admin/</a></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ Some errors occurred</h3>";
    echo "<p>Check file permissions and try again.</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
}

h1, h2 {
    color: #333;
}

p {
    background: white;
    padding: 8px 12px;
    border-radius: 4px;
    margin: 5px 0;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
