<?php
/**
 * Test script for banner upload functionality
 * This script tests the banner upload directory and permissions
 */

echo "<h2>Banner Upload Test</h2>";

// Test upload directory
$upload_dir = __DIR__ . '/uploads/banners/';
echo "<h3>Directory Tests:</h3>";
echo "Upload directory: " . $upload_dir . "<br>";
echo "Directory exists: " . (is_dir($upload_dir) ? "✅ Yes" : "❌ No") . "<br>";
echo "Directory writable: " . (is_writable($upload_dir) ? "✅ Yes" : "❌ No") . "<br>";

// Test file permissions
if (is_dir($upload_dir)) {
    $perms = fileperms($upload_dir);
    echo "Directory permissions: " . substr(sprintf('%o', $perms), -4) . "<br>";
}

// Test database connection
echo "<h3>Database Tests:</h3>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance();
    echo "Database connection: ✅ Success<br>";
    
    // Test promotional_banners table
    $result = $db->fetchAll("SHOW TABLES LIKE 'promotional_banners'");
    echo "promotional_banners table exists: " . (count($result) > 0 ? "✅ Yes" : "❌ No") . "<br>";
    
    if (count($result) > 0) {
        $columns = $db->fetchAll("DESCRIBE promotional_banners");
        echo "Table columns: ";
        foreach ($columns as $column) {
            echo $column['Field'] . " (" . $column['Type'] . "), ";
        }
        echo "<br>";
    }
    
} catch (Exception $e) {
    echo "Database connection: ❌ Error - " . $e->getMessage() . "<br>";
}

// Test PHP upload settings
echo "<h3>PHP Upload Settings:</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "file_uploads: " . (ini_get('file_uploads') ? "✅ Enabled" : "❌ Disabled") . "<br>";

// Test image processing functions
echo "<h3>Image Processing:</h3>";
echo "GD extension: " . (extension_loaded('gd') ? "✅ Available" : "❌ Not available") . "<br>";
echo "getimagesize function: " . (function_exists('getimagesize') ? "✅ Available" : "❌ Not available") . "<br>";

echo "<h3>Test Complete</h3>";
echo "<p><strong>Note:</strong> Delete this file after testing for security.</p>";
?>
