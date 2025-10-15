<?php
/**
 * AJAX endpoint to get order edit form
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
        SELECT o.*, u.name as user_name, u.email as user_email
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ", [$orderId]);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Pedido não encontrado']);
        exit;
    }
    
    // Build HTML form
    ob_start();
    ?>
    <form id="editOrderForm" onsubmit="return submitOrderEdit(event)">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Status do Pedido *</label>
                <select name="status" class="form-control" required>
                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pendente</option>
                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processando</option>
                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Enviado</option>
                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Entregue</option>
                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Status do Pagamento *</label>
                <select name="payment_status" class="form-control" required>
                    <option value="pending" <?= ($order['payment_status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Pendente</option>
                    <option value="paid" <?= ($order['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Pago</option>
                    <option value="failed" <?= ($order['payment_status'] ?? '') === 'failed' ? 'selected' : '' ?>>Falhou</option>
                    <option value="refunded" <?= ($order['payment_status'] ?? '') === 'refunded' ? 'selected' : '' ?>>Reembolsado</option>
                </select>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Método de Pagamento</label>
                <select name="payment_method" class="form-control">
                    <option value="">Selecione...</option>
                    <option value="money" <?= ($order['payment_method'] ?? '') === 'money' ? 'selected' : '' ?>>Dinheiro</option>
                    <option value="card" <?= ($order['payment_method'] ?? '') === 'card' ? 'selected' : '' ?>>Cartão</option>
                    <option value="pix" <?= ($order['payment_method'] ?? '') === 'pix' ? 'selected' : '' ?>>PIX</option>
                    <option value="bank_transfer" <?= ($order['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>Transferência</option>
                </select>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Total do Pedido *</label>
                <input type="number" name="total_amount" class="form-control" step="0.01" min="0" required
                       value="<?= $order['total_amount'] ?>">
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Taxa de Entrega</label>
                <input type="number" name="delivery_fee" class="form-control" step="0.01" min="0"
                       value="<?= $order['delivery_fee'] ?? 0 ?>">
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Desconto</label>
                <input type="number" name="discount_amount" class="form-control" step="0.01" min="0"
                       value="<?= $order['discount_amount'] ?? 0 ?>">
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Endereço de Entrega</label>
            <textarea name="delivery_address" class="form-control" rows="3" 
                      placeholder="Endereço completo de entrega"><?= htmlspecialchars($order['delivery_address'] ?? '') ?></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Nome do Destinatário</label>
                <input type="text" name="delivery_name" class="form-control" 
                       value="<?= htmlspecialchars($order['delivery_name'] ?? '') ?>"
                       placeholder="Nome completo">
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">CEP</label>
                <input type="text" name="delivery_cep" class="form-control" 
                       value="<?= htmlspecialchars($order['delivery_cep'] ?? '') ?>"
                       placeholder="00000-000">
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Rua</label>
                <input type="text" name="delivery_street" class="form-control" 
                       value="<?= htmlspecialchars($order['delivery_street'] ?? '') ?>"
                       placeholder="Nome da rua">
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Número</label>
                <input type="text" name="delivery_number" class="form-control" 
                       value="<?= htmlspecialchars($order['delivery_number'] ?? '') ?>"
                       placeholder="123">
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Complemento</label>
                <input type="text" name="delivery_complement" class="form-control" 
                       value="<?= htmlspecialchars($order['delivery_complement'] ?? '') ?>"
                       placeholder="Apto, bloco, etc.">
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Bairro</label>
                <input type="text" name="delivery_neighborhood" class="form-control" 
                       value="<?= htmlspecialchars($order['delivery_neighborhood'] ?? '') ?>"
                       placeholder="Nome do bairro">
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Cidade</label>
                <input type="text" name="delivery_city" class="form-control" 
                       value="<?= htmlspecialchars($order['delivery_city'] ?? '') ?>"
                       placeholder="Nome da cidade">
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Estado</label>
                <input type="text" name="delivery_state" class="form-control" maxlength="2"
                       value="<?= htmlspecialchars($order['delivery_state'] ?? '') ?>"
                       placeholder="SP">
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Data Prevista de Entrega</label>
                <input type="date" name="estimated_delivery" class="form-control" 
                       value="<?= $order['estimated_delivery'] ?? '' ?>">
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Observações</label>
            <textarea name="notes" class="form-control" rows="3" 
                      placeholder="Observações sobre o pedido"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label text-muted">Cliente</label>
                <div class="fw-medium"><?= htmlspecialchars($order['user_name'] ?? 'N/A') ?></div>
                <small class="text-muted"><?= htmlspecialchars($order['user_email'] ?? '') ?></small>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label text-muted">Criado em</label>
                <div class="fw-medium"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-save me-2"></i>Salvar Alterações
            </button>
        </div>
    </form>
    
    <script>
    function submitOrderEdit(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';
        
        fetch('ajax/update_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and refresh page
                bootstrap.Modal.getInstance(document.getElementById('orderEditModal')).hide();
                showToast('Pedido atualizado com sucesso!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast('Erro: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Erro de conexão. Tente novamente.', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Salvar Alterações';
        });
        
        return false;
    }
    
    // Apply CEP mask
    document.querySelector('input[name="delivery_cep"]').addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length >= 5) {
            value = value.replace(/(\d{5})(\d{0,3})/, '$1-$2');
        }
        this.value = value;
    });
    </script>
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
