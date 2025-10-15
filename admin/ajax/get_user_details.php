<?php
/**
 * AJAX endpoint to get user details
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$userId = intval($_GET['id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de usuário inválido']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // First check which columns exist in the users table
    $pdo = $db->getConnection();
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existingColumns = array_column($columns, 'Field');
    
    // Build dynamic SELECT clause
    $selectFields = ['u.id', 'u.name', 'u.email', 'u.is_active', 'u.created_at'];
    $optionalFields = ['phone', 'cpf_cnpj', 'gender', 'birth_date', 'address', 'city', 'state', 'zip_code', 'updated_at'];
    
    foreach ($optionalFields as $field) {
        if (in_array($field, $existingColumns)) {
            $selectFields[] = "u.$field";
        }
    }
    
    // Get user details with dynamic query
    $user = $db->fetch("
        SELECT " . implode(', ', $selectFields) . ",
               COUNT(DISTINCT o.id) as total_orders,
               SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END) as total_spent,
               MAX(o.created_at) as last_order_date,
               MIN(o.created_at) as first_order_date
        FROM users u 
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.id = ?
        GROUP BY u.id
    ", [$userId]);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        exit;
    }
    
    // Get recent orders
    $recentOrders = $db->fetchAll("
        SELECT id, status, total_amount, created_at
        FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ", [$userId]);
    
    // Build HTML content
    ob_start();
    ?>
    <div class="row">
        <!-- User Info -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Informações Pessoais</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <span class="text-white fw-bold fs-2">
                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                            </span>
                        </div>
                        <h5 class="mt-2 mb-0"><?= htmlspecialchars($user['name']) ?></h5>
                        <small class="text-muted">ID: #<?= $user['id'] ?></small>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-muted">Email</label>
                            <div class="fw-medium"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        
                        <?php if (isset($user['phone']) && $user['phone']): ?>
                        <div class="col-6">
                            <label class="form-label text-muted">Telefone</label>
                            <div class="fw-medium"><?= htmlspecialchars($user['phone']) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($user['cpf_cnpj']) && $user['cpf_cnpj']): ?>
                        <div class="col-6">
                            <label class="form-label text-muted">CPF/CNPJ</label>
                            <div class="fw-medium"><?= htmlspecialchars($user['cpf_cnpj']) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($user['gender']) && $user['gender']): ?>
                        <div class="col-6">
                            <label class="form-label text-muted">Gênero</label>
                            <div class="fw-medium"><?= ucfirst($user['gender']) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($user['birth_date']) && $user['birth_date']): ?>
                        <div class="col-6">
                            <label class="form-label text-muted">Data de Nascimento</label>
                            <div class="fw-medium"><?= date('d/m/Y', strtotime($user['birth_date'])) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($user['address']) && $user['address']): ?>
                        <div class="col-12">
                            <label class="form-label text-muted">Endereço</label>
                            <div class="fw-medium"><?= htmlspecialchars($user['address']) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ((isset($user['city']) && $user['city']) || (isset($user['state']) && $user['state'])): ?>
                        <?php if (isset($user['city']) && $user['city']): ?>
                        <div class="col-8">
                            <label class="form-label text-muted">Cidade</label>
                            <div class="fw-medium"><?= htmlspecialchars($user['city']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($user['state']) && $user['state']): ?>
                        <div class="col-4">
                            <label class="form-label text-muted">Estado</label>
                            <div class="fw-medium"><?= htmlspecialchars($user['state']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (isset($user['zip_code']) && $user['zip_code']): ?>
                        <div class="col-6">
                            <label class="form-label text-muted">CEP</label>
                            <div class="fw-medium"><?= htmlspecialchars($user['zip_code']) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-6">
                            <label class="form-label text-muted">Status</label>
                            <div>
                                <span class="badge <?= $user['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $user['is_active'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-6">
                            <label class="form-label text-muted">Cadastro</label>
                            <div class="fw-medium"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics & Orders -->
        <div class="col-md-6">
            <!-- Statistics -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estatísticas</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="border rounded p-3">
                                <h4 class="text-primary mb-1"><?= $user['total_orders'] ?></h4>
                                <small class="text-muted">Total de Pedidos</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="border rounded p-3">
                                <h4 class="text-success mb-1">R$ <?= number_format($user['total_spent'] ?? 0, 2, ',', '.') ?></h4>
                                <small class="text-muted">Total Gasto</small>
                            </div>
                        </div>
                        <?php if ($user['first_order_date']): ?>
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <div class="fw-medium"><?= date('d/m/Y', strtotime($user['first_order_date'])) ?></div>
                                <small class="text-muted">Primeiro Pedido</small>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($user['last_order_date']): ?>
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <div class="fw-medium"><?= date('d/m/Y', strtotime($user['last_order_date'])) ?></div>
                                <small class="text-muted">Último Pedido</small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Pedidos Recentes</h6>
                    <?php if ($user['total_orders'] > 0): ?>
                    <a href="orders.php?user_id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">
                        Ver Todos
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($recentOrders)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                            <p class="mb-0">Nenhum pedido encontrado</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentOrders as $order): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Pedido #<?= $order['id'] ?></h6>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-medium">R$ <?= number_format($order['total_amount'], 2, ',', '.') ?></div>
                                        <span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-warning" onclick="editUser(<?= $user['id'] ?>)">
            <i class="fas fa-edit me-2"></i>Editar Usuário
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
