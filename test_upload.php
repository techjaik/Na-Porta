<?php
/**
 * Upload Test Script
 * Test file upload functionality and permissions
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Upload System Test</h2>";

// Test upload directories
$upload_dirs = [
    'uploads/categories/',
    'uploads/products/',
    'uploads/banners/'
];

echo "<h3>Directory Permissions Test:</h3>";
foreach ($upload_dirs as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    echo "<p><strong>$dir</strong></p>";
    
    if (!is_dir($full_path)) {
        echo "  ❌ Directory does not exist<br>";
        if (mkdir($full_path, 0755, true)) {
            echo "  ✅ Directory created successfully<br>";
        } else {
            echo "  ❌ Failed to create directory<br>";
        }
    } else {
        echo "  ✅ Directory exists<br>";
    }
    
    if (is_writable($full_path)) {
        echo "  ✅ Directory is writable<br>";
    } else {
        echo "  ❌ Directory is not writable<br>";
    }
    
    echo "<br>";
}

// Test PHP upload settings
echo "<h3>PHP Upload Settings:</h3>";
echo "<table border='1'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>file_uploads</td><td>" . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "</td></tr>";
echo "<tr><td>upload_max_filesize</td><td>" . ini_get('upload_max_filesize') . "</td></tr>";
echo "<tr><td>post_max_size</td><td>" . ini_get('post_max_size') . "</td></tr>";
echo "<tr><td>max_file_uploads</td><td>" . ini_get('max_file_uploads') . "</td></tr>";
echo "<tr><td>upload_tmp_dir</td><td>" . (ini_get('upload_tmp_dir') ?: 'Default') . "</td></tr>";
echo "</table>";

// Test form
echo "<h3>Test File Upload:</h3>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h4>Upload Test Results:</h4>";
    
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
        $filename = 'test_' . time() . '_' . $file['name'];
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            echo "<p>✅ File uploaded successfully to: $filename</p>";
            
            // Clean up test file
            if (unlink($target_path)) {
                echo "<p>✅ Test file cleaned up</p>";
            }
        } else {
            echo "<p>❌ Failed to move uploaded file</p>";
            echo "<p>Target path: " . htmlspecialchars($target_path) . "</p>";
            echo "<p>Upload dir writable: " . (is_writable($upload_dir) ? 'Yes' : 'No') . "</p>";
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
        <button type="submit">Test Upload</button>
    </p>
</form>

<p><a href="admin/categories.php">← Back to Categories Admin</a></p>
