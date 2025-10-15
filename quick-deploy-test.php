<?php
/**
 * Quick FTP Test to verify connection and find correct path
 * Run this once to test FTP connection
 */

$ftp_host = 'ftpupload.net';
$ftp_user = 'if0_40155099';
$ftp_pass = 'Jaishreeramm9';

echo "Testing FTP connection...\n";

// Connect to FTP
$ftp = ftp_connect($ftp_host);
if (!$ftp) {
    die("❌ Could not connect to FTP server\n");
}

echo "✅ Connected to FTP server\n";

// Login
if (!ftp_login($ftp, $ftp_user, $ftp_pass)) {
    die("❌ Could not login to FTP\n");
}

echo "✅ Logged in successfully\n";

// Enable passive mode
ftp_pasv($ftp, true);

// Get current directory
$current_dir = ftp_pwd($ftp);
echo "📁 Current directory: $current_dir\n";

// List directories to find the correct web root
echo "\n📋 Available directories:\n";
$files = ftp_nlist($ftp, '.');
if ($files) {
    foreach ($files as $file) {
        echo "  - $file\n";
    }
} else {
    echo "  No files/directories found\n";
}

// Test different common web root paths
$test_paths = ['/htdocs/', '/public_html/', '/www/', '/web/', '/'];

foreach ($test_paths as $path) {
    echo "\n🔍 Testing path: $path\n";
    if (@ftp_chdir($ftp, $path)) {
        echo "✅ Path exists: $path\n";
        
        // List contents
        $contents = ftp_nlist($ftp, '.');
        if ($contents) {
            echo "📋 Contents:\n";
            foreach (array_slice($contents, 0, 10) as $item) { // Show first 10 items
                echo "  - $item\n";
            }
        }
        
        // Go back to root
        ftp_chdir($ftp, '/');
    } else {
        echo "❌ Path not accessible: $path\n";
    }
}

// Test uploading a simple file to htdocs
echo "\n🧪 Testing file upload to /htdocs/...\n";
if (@ftp_chdir($ftp, '/htdocs/')) {
    $test_content = "<?php echo 'FTP Test Successful - " . date('Y-m-d H:i:s') . "'; ?>";
    $temp_file = tempnam(sys_get_temp_dir(), 'ftp_test');
    file_put_contents($temp_file, $test_content);
    
    if (ftp_put($ftp, 'ftp-test.php', $temp_file, FTP_BINARY)) {
        echo "✅ Test file uploaded successfully!\n";
        echo "🌐 Check: https://naporta.free.nf/ftp-test.php\n";
    } else {
        echo "❌ Failed to upload test file\n";
    }
    
    unlink($temp_file);
}

ftp_close($ftp);
echo "\n✅ FTP test completed!\n";
?>
