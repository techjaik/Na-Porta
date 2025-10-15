<?php
/**
 * GitHub Webhook Handler for Na Porta
 * This script handles GitHub webhooks to automatically pull updates
 */

// Configuration
$webhook_secret = 'your_webhook_secret_here'; // Change this to a secure secret
$repo_path = __DIR__;
$log_file = __DIR__ . '/webhook.log';

// Function to log messages
function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

// Function to execute shell commands safely
function executeCommand($command) {
    $output = [];
    $return_code = 0;
    exec($command . ' 2>&1', $output, $return_code);
    return ['output' => $output, 'return_code' => $return_code];
}

// Verify webhook signature (if secret is set)
function verifySignature($payload, $signature, $secret) {
    if (empty($secret)) return true;
    
    $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected_signature, $signature);
}

// Main webhook handler
function handleWebhook() {
    global $webhook_secret, $repo_path;
    
    // Get the payload
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    // Verify signature
    if (!verifySignature($payload, $signature, $webhook_secret)) {
        logMessage('Invalid webhook signature');
        http_response_code(403);
        echo 'Invalid signature';
        return;
    }
    
    // Parse payload
    $data = json_decode($payload, true);
    
    if (!$data) {
        logMessage('Invalid JSON payload');
        http_response_code(400);
        echo 'Invalid payload';
        return;
    }
    
    // Check if this is a push to main branch
    if ($data['ref'] !== 'refs/heads/main') {
        logMessage('Ignoring push to ' . $data['ref']);
        echo 'Not main branch, ignoring';
        return;
    }
    
    logMessage('Received push to main branch, starting deployment...');
    
    // Change to repository directory
    chdir($repo_path);
    
    // Pull latest changes
    $result = executeCommand('git pull origin main');
    
    if ($result['return_code'] === 0) {
        logMessage('Git pull successful: ' . implode('\n', $result['output']));
        
        // Run any post-deployment commands if needed
        // For example, clear cache, update database, etc.
        
        echo 'Deployment successful';
        logMessage('Deployment completed successfully');
    } else {
        logMessage('Git pull failed: ' . implode('\n', $result['output']));
        http_response_code(500);
        echo 'Deployment failed';
    }
}

// Handle the webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleWebhook();
} else {
    // Show status page for GET requests
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Na Porta Webhook Status</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .status { padding: 20px; border-radius: 5px; margin: 20px 0; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
    </style>
</head>
<body>
    <h1>Na Porta Deployment Webhook</h1>
    <div class="info">
        <h3>Status: Active</h3>
        <p>This webhook is ready to receive GitHub push notifications.</p>
        <p>Last 10 log entries:</p>
        <pre>' . htmlspecialchars(shell_exec('tail -10 ' . $log_file)) . '</pre>
    </div>
</body>
</html>';
}
?>
