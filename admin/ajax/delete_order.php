<?php
/**
 * AJAX endpoint to delete order
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$orderId = intval($_POST['order_id'] ?? 0);

// Validation
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido inválido']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Check if order exists
    $existingOrder = $db->fetch("SELECT id, status FROM orders WHERE id = ?", [$orderId]);
    if (!$existingOrder) {
        echo json_encode(['success' => false, 'message' => 'Pedido não encontrado']);
        exit;
    }
    
    // Check if order can be deleted (only pending or cancelled orders)
    if (!in_array($existingOrder['status'], ['pending', 'cancelled'])) {
        echo json_encode(['success' => false, 'message' => 'Apenas pedidos pendentes ou cancelados podem ser excluídos']);
        exit;
    }
    
    // Start transaction
    $pdo = $db->getConnection();
    $pdo->beginTransaction();
    
    try {
        // Delete order items first (foreign key constraint)
        $db->query("DELETE FROM order_items WHERE order_id = ?", [$orderId]);
        
        // Delete the order
        $db->query("DELETE FROM orders WHERE id = ?", [$orderId]);
        
        // Commit transaction
        $pdo->commit();
        
        // Log the action
        $admin = $auth->getCurrentAdmin();
        error_log("Admin {$admin['username']} deleted order ID {$orderId}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Pedido excluído com sucesso!'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error deleting order: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir pedido. Tente novamente.'
    ]);
}
?>
