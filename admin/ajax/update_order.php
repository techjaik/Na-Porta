<?php
/**
 * AJAX endpoint to update order information
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
$status = trim($_POST['status'] ?? '');
$paymentStatus = trim($_POST['payment_status'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? '');
$totalAmount = floatval($_POST['total_amount'] ?? 0);
$deliveryFee = floatval($_POST['delivery_fee'] ?? 0);
$discountAmount = floatval($_POST['discount_amount'] ?? 0);
$deliveryAddress = trim($_POST['delivery_address'] ?? '');
$deliveryName = trim($_POST['delivery_name'] ?? '');
$deliveryCep = trim($_POST['delivery_cep'] ?? '');
$deliveryStreet = trim($_POST['delivery_street'] ?? '');
$deliveryNumber = trim($_POST['delivery_number'] ?? '');
$deliveryComplement = trim($_POST['delivery_complement'] ?? '');
$deliveryNeighborhood = trim($_POST['delivery_neighborhood'] ?? '');
$deliveryCity = trim($_POST['delivery_city'] ?? '');
$deliveryState = trim($_POST['delivery_state'] ?? '');
$estimatedDelivery = $_POST['estimated_delivery'] ?? null;
$notes = trim($_POST['notes'] ?? '');

// Validation
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido inválido']);
    exit;
}

if (empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Status é obrigatório']);
    exit;
}

if ($totalAmount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Total do pedido deve ser maior que zero']);
    exit;
}

// Validate status values
$validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Status inválido']);
    exit;
}

$validPaymentStatuses = ['pending', 'paid', 'failed', 'refunded'];
if (!in_array($paymentStatus, $validPaymentStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Status de pagamento inválido']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Check if order exists
    $existingOrder = $db->fetch("SELECT id FROM orders WHERE id = ?", [$orderId]);
    if (!$existingOrder) {
        echo json_encode(['success' => false, 'message' => 'Pedido não encontrado']);
        exit;
    }
    
    // Update order
    $db->query("
        UPDATE orders SET 
            status = ?,
            payment_status = ?,
            payment_method = ?,
            subtotal = ?,
            delivery_fee = ?,
            discount_amount = ?,
            total_amount = ?,
            delivery_address = ?,
            delivery_name = ?,
            delivery_cep = ?,
            delivery_street = ?,
            delivery_number = ?,
            delivery_complement = ?,
            delivery_neighborhood = ?,
            delivery_city = ?,
            delivery_state = ?,
            estimated_delivery = ?,
            notes = ?,
            updated_at = NOW()
        WHERE id = ?
    ", [
        $status,
        $paymentStatus,
        $paymentMethod ?: null,
        $totalAmount - $deliveryFee + $discountAmount, // Calculate subtotal
        $deliveryFee,
        $discountAmount,
        $totalAmount,
        $deliveryAddress ?: null,
        $deliveryName ?: null,
        $deliveryCep ?: null,
        $deliveryStreet ?: null,
        $deliveryNumber ?: null,
        $deliveryComplement ?: null,
        $deliveryNeighborhood ?: null,
        $deliveryCity ?: null,
        $deliveryState ?: null,
        $estimatedDelivery ?: null,
        $notes ?: null,
        $orderId
    ]);
    
    // Log the action
    $admin = $auth->getCurrentAdmin();
    error_log("Admin {$admin['username']} updated order ID {$orderId}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Pedido atualizado com sucesso!'
    ]);
    
} catch (Exception $e) {
    error_log("Error updating order: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar pedido. Tente novamente.'
    ]);
}
?>
