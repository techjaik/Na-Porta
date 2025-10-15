<?php
/**
 * Test file to verify deployment is working
 * If you can see this file at https://naporta.free.nf/test-deployment.php
 * then deployment is working correctly
 */

echo "<h1>🎉 Deployment Test</h1>";
echo "<p><strong>Success!</strong> This file was deployed automatically from GitHub.</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>If you can see this, the deployment system is working!</p>";

// Check if admin directory exists
if (is_dir(__DIR__ . '/admin')) {
    echo "<p>✅ Admin directory exists</p>";
    
    // List admin files
    $admin_files = scandir(__DIR__ . '/admin');
    echo "<p>Admin files found:</p><ul>";
    foreach ($admin_files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>❌ Admin directory NOT found</p>";
}

// Check current directory contents
echo "<h2>Current Directory Contents:</h2>";
$files = scandir(__DIR__);
echo "<ul>";
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        if (is_dir(__DIR__ . '/' . $file)) {
            echo "<li>📁 $file/</li>";
        } else {
            echo "<li>📄 $file</li>";
        }
    }
}
echo "</ul>";

echo "<p><em>Delete this file after testing for security.</em></p>";
?>
