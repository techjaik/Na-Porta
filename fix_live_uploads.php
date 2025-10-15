<?php
/**
 * Fix Live Upload Directories
 * Create missing upload directories on live server
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Live Upload Directory Fix</h2>";

// Upload directories that need to exist
$upload_dirs = [
    'uploads/',
    'uploads/categories/',
    'uploads/products/',
    'uploads/banners/'
];

echo "<h3>Checking and Creating Upload Directories:</h3>";

foreach ($upload_dirs as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    echo "<p><strong>$dir</strong></p>";
    
    if (!is_dir($full_path)) {
        echo "  ❌ Directory does not exist<br>";
        if (mkdir($full_path, 0755, true)) {
            echo "  ✅ Directory created successfully<br>";
        } else {
            echo "  ❌ Failed to create directory<br>";
            echo "  📝 Error: " . error_get_last()['message'] . "<br>";
        }
    } else {
        echo "  ✅ Directory exists<br>";
    }
    
    if (is_writable($full_path)) {
        echo "  ✅ Directory is writable<br>";
    } else {
        echo "  ❌ Directory is not writable<br>";
        // Try to fix permissions
        if (chmod($full_path, 0755)) {
            echo "  ✅ Fixed directory permissions<br>";
        } else {
            echo "  ❌ Could not fix permissions<br>";
        }
    }
    
    echo "<br>";
}

// Test file creation
echo "<h3>Testing File Creation:</h3>";

$test_dirs = ['uploads/categories/', 'uploads/products/'];
foreach ($test_dirs as $dir) {
    $test_file = __DIR__ . '/' . $dir . 'test_' . time() . '.txt';
    echo "<p>Testing: $dir</p>";
    
    if (file_put_contents($test_file, 'test content')) {
        echo "  ✅ Can create files<br>";
        unlink($test_file); // Clean up
        echo "  ✅ Test file cleaned up<br>";
    } else {
        echo "  ❌ Cannot create files<br>";
    }
    echo "<br>";
}

// Check PHP upload settings
echo "<h3>PHP Upload Settings:</h3>";
echo "<table border='1'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>file_uploads</td><td>" . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "</td></tr>";
echo "<tr><td>upload_max_filesize</td><td>" . ini_get('upload_max_filesize') . "</td></tr>";
echo "<tr><td>post_max_size</td><td>" . ini_get('post_max_size') . "</td></tr>";
echo "<tr><td>max_file_uploads</td><td>" . ini_get('max_file_uploads') . "</td></tr>";
echo "<tr><td>upload_tmp_dir</td><td>" . (ini_get('upload_tmp_dir') ?: 'Default') . "</td></tr>";
echo "</table>";

// Test form for actual upload
echo "<h3>Test File Upload on Live Server:</h3>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h4>Live Upload Test Results:</h4>";
    
    $file = $_FILES['test_file'];
    echo "<p><strong>File Info:</strong></p>";
    echo "<ul>";
    echo "<li>Name: " . htmlspecialchars($file['name']) . "</li>";
    echo "<li>Type: " . htmlspecialchars($file['type']) . "</li>";
    echo "<li>Size: " . $file['size'] . " bytes</li>";
    echo "<li>Error: " . $file['error'] . "</li>";
    echo "<li>Tmp Name: " . htmlspecialchars($file['tmp_name']) . "</li>";
    echo "</ul>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/categories/';
        $filename = 'test_live_' . time() . '_' . $file['name'];
        $target_path = $upload_dir . $filename;
        
        echo "<p>Target path: " . htmlspecialchars($target_path) . "</p>";
        echo "<p>Upload dir exists: " . (is_dir($upload_dir) ? 'Yes' : 'No') . "</p>";
        echo "<p>Upload dir writable: " . (is_writable($upload_dir) ? 'Yes' : 'No') . "</p>";
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            echo "<p>✅ File uploaded successfully to: $filename</p>";
            
            // Verify file exists
            if (file_exists($target_path)) {
                echo "<p>✅ File exists on server</p>";
                echo "<p>File size: " . filesize($target_path) . " bytes</p>";
                
                // Clean up test file
                if (unlink($target_path)) {
                    echo "<p>✅ Test file cleaned up</p>";
                }
            } else {
                echo "<p>❌ File does not exist after upload</p>";
            }
        } else {
            echo "<p>❌ Failed to move uploaded file</p>";
            $error_details = error_get_last();
            if ($error_details) {
                echo "<p>Error details: " . htmlspecialchars($error_details['message']) . "</p>";
            }
        }
    } else {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'Partial upload',
            UPLOAD_ERR_NO_FILE => 'No file selected',
            UPLOAD_ERR_NO_TMP_DIR => 'No temp directory',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
        ];
        
        echo "<p>❌ Upload error: " . ($error_messages[$file['error']] ?? 'Unknown error') . "</p>";
    }
}

?>

<form method="POST" enctype="multipart/form-data">
    <p>
        <label>Select a test image file:</label><br>
        <input type="file" name="test_file" accept="image/*" required>
    </p>
    <p>
        <button type="submit">Test Live Upload</button>
    </p>
</form>

<p><strong>✅ Upload directories setup complete!</strong></p>
<p><a href="admin/categories.php">Try Categories Admin Now</a> | <a href="admin/products.php">Try Products Admin Now</a></p>
