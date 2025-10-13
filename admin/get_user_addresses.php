<?php
// Get User Addresses
header('Content-Type: application/json');

session_start();
require_once '../config/database.php';

// Simple admin check
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = (int)($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(['addresses' => []]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT id, title, address, is_default, created_at, updated_at
        FROM user_addresses 
        WHERE user_id = ? 
        ORDER BY is_default DESC, created_at DESC
    ");
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['addresses' => $addresses]);
} catch (Exception $e) {
    echo json_encode(['addresses' => [], 'error' => $e->getMessage()]);
}
?>
