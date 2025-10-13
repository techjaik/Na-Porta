<?php
// Get User Orders Details
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
    echo json_encode(['orders' => [], 'count' => 0]);
    exit();
}

try {
    // Get orders with item count
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.id) as item_count
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ? 
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $count_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'orders' => $orders, 
        'count' => (int)$count_result['count']
    ]);
} catch (Exception $e) {
    echo json_encode(['orders' => [], 'count' => 0, 'error' => $e->getMessage()]);
}
?>
