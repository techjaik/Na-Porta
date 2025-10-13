<?php
// Simple PHP test file
echo "<h1>PHP Test</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";

// Test error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<p>Error reporting enabled</p>";

// Test file paths
echo "<p>Current directory: " . __DIR__ . "</p>";
echo "<p>Config file exists: " . (file_exists(__DIR__ . '/config/config.php') ? 'YES' : 'NO') . "</p>";
echo "<p>Database file exists: " . (file_exists(__DIR__ . '/config/database.php') ? 'YES' : 'NO') . "</p>";

// Test basic PHP functionality
try {
    echo "<p>Testing basic PHP...</p>";
    
    // Test include
    if (file_exists(__DIR__ . '/config/config.php')) {
        require_once __DIR__ . '/config/config.php';
        echo "<p>✅ Config file loaded successfully</p>";
        echo "<p>Site Name: " . (defined('SITE_NAME') ? SITE_NAME : 'Not defined') . "</p>";
    } else {
        echo "<p>❌ Config file not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p>Test completed!</p>";
?>
