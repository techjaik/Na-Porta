<?php
/**
 * Manual Deployment Script for Na Porta
 * This script can be used to deploy files manually via FTP
 */

// Configuration - DO NOT commit these credentials to Git
$ftp_config = [
    'host' => 'ftpupload.net', // InfinityFree FTP server
    'username' => 'if0_40155099', // Your FTP username
    'password' => '', // Add your FTP password here (DO NOT COMMIT)
    'remote_dir' => '/htdocs/',
    'local_dir' => __DIR__
];

// Files and directories to exclude from deployment
$exclude_patterns = [
    '.git',
    '.github',
    'node_modules',
    'tests',
    '.env',
    'README.md',
    '.gitignore',
    'composer.lock',
    'package-lock.json',
    'deploy.php',
    'test_*.php',
    'debug_*.php'
];

function deployToFTP($config, $exclude_patterns) {
    echo "Starting deployment...\n";
    
    // Connect to FTP server
    $ftp = ftp_connect($config['host']);
    if (!$ftp) {
        die("Could not connect to FTP server: {$config['host']}\n");
    }
    
    // Login
    if (!ftp_login($ftp, $config['username'], $config['password'])) {
        die("Could not login to FTP server\n");
    }
    
    // Enable passive mode
    ftp_pasv($ftp, true);
    
    echo "Connected to FTP server successfully\n";
    
    // Upload files recursively
    uploadDirectory($ftp, $config['local_dir'], $config['remote_dir'], $exclude_patterns);
    
    // Close connection
    ftp_close($ftp);
    
    echo "Deployment completed successfully!\n";
}

function uploadDirectory($ftp, $local_dir, $remote_dir, $exclude_patterns) {
    $files = scandir($local_dir);
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        // Check if file should be excluded
        $should_exclude = false;
        foreach ($exclude_patterns as $pattern) {
            if (fnmatch($pattern, $file)) {
                $should_exclude = true;
                break;
            }
        }
        
        if ($should_exclude) {
            echo "Skipping: $file\n";
            continue;
        }
        
        $local_path = $local_dir . '/' . $file;
        $remote_path = $remote_dir . $file;
        
        if (is_dir($local_path)) {
            // Create remote directory if it doesn't exist
            if (!@ftp_chdir($ftp, $remote_path)) {
                ftp_mkdir($ftp, $remote_path);
                echo "Created directory: $remote_path\n";
            }
            ftp_chdir($ftp, $remote_dir);
            
            // Recursively upload directory
            uploadDirectory($ftp, $local_path, $remote_path . '/', $exclude_patterns);
        } else {
            // Upload file
            if (ftp_put($ftp, $remote_path, $local_path, FTP_BINARY)) {
                echo "Uploaded: $file\n";
            } else {
                echo "Failed to upload: $file\n";
            }
        }
    }
}

// Usage instructions
if (empty($ftp_config['password'])) {
    echo "=== DEPLOYMENT SCRIPT ===\n";
    echo "Before running this script:\n";
    echo "1. Add your FTP password to the \$ftp_config array\n";
    echo "2. Make sure you don't commit the password to Git\n";
    echo "3. Run: php deploy.php\n";
    echo "\nAlternatively, use the GitHub Actions workflow for automatic deployment.\n";
} else {
    // Run deployment
    deployToFTP($ftp_config, $exclude_patterns);
}
?>
