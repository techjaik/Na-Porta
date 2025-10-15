<?php
/**
 * AJAX endpoint to get order details
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$orderId = intval($_GET['id'] ?? 0);
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido inválido']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get order details
    $order = $db->fetch("
        SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ", [$orderId]);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Pedido não encontrado']);
        exit;
    }
    
    // Get order items
    $orderItems = $db->fetchAll("
        SELECT oi.*, p.name as product_name, p.image_url
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.created_at ASC
    ", [$orderId]);
    
    // Build HTML content
    ob_start();
    ?>
    <div class="row">
        <!-- Order Information -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informações do Pedido</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label text-muted">ID do Pedido</label>
                            <div class="fw-bold">#<?= $order['id'] ?></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted">Status</label>
                            <div>
                                <span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : ($order['status'] === 'processing' ? 'info' : 'secondary')) ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted">Data do Pedido</label>
                            <div class="fw-medium"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted">Última Atualização</label>
                            <div class="fw-medium"><?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted">Método de Pagamento</label>
                            <div class="fw-medium"><?= ucfirst(str_replace('_', ' ', $order['payment_method'] ?? 'N/A')) ?></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted">Status do Pagamento</label>
                            <div>
                                <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : ($order['payment_status'] === 'pending' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($order['payment_status'] ?? 'pending') ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($order['notes']): ?>
                        <div class="col-12">
                            <label class="form-label text-muted">Observações</label>
                            <div class="fw-medium"><?= nl2br(htmlspecialchars($order['notes'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Informações do Cliente</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-muted">Nome</label>
                            <div class="fw-medium"><?= htmlspecialchars($order['user_name'] ?? 'N/A') ?></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted">Email</label>
                            <div class="fw-medium"><?= htmlspecialchars($order['user_email'] ?? 'N/A') ?></div>
                        </div>
                        <?php if ($order['user_phone']): ?>
                        <div class="col-12">
                            <label class="form-label text-muted">Telefone</label>
                            <div class="fw-medium"><?= htmlspecialchars($order['user_phone']) ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <label class="form-label text-muted">Endereço de Entrega</label>
                            <div class="fw-medium"><?= nl2br(htmlspecialchars($order['delivery_address'] ?? 'N/A')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Items -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Itens do Pedido</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($orderItems)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                            <p class="mb-0">Nenhum item encontrado</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Quantidade</th>
                                        <th>Preço Unit.</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($item['image_url']): ?>
                                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                                         alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                         class="me-2 rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php endif; ?>
                                                <span><?= htmlspecialchars($item['product_name'] ?? 'Produto removido') ?></span>
                                            </div>
                                        </td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>R$ <?= number_format($item['price'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format($item['price'] * $item['quantity'], 2, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-active">
                                        <th colspan="3">Total do Pedido</th>
                                        <th>R$ <?= number_format($order['total_amount'], 2, ',', '.') ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-warning" onclick="editOrder(<?= $order['id'] ?>)">
            <i class="fas fa-edit me-2"></i>Editar Pedido
        </button>
        <button type="button" class="btn btn-info" onclick="printOrder(<?= $order['id'] ?>)">
            <i class="fas fa-print me-2"></i>Imprimir
        </button>
        <button type="button" class="btn btn-danger" onclick="deleteOrder(<?= $order['id'] ?>)">
            <i class="fas fa-trash me-2"></i>Excluir
        </button>
    </div>
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}
?>
