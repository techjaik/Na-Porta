<?php
/**
 * Na Porta - Admin Orders Management
 */

require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$pageTitle = 'Pedidos';
$pageSubtitle = 'Gerenciar pedidos dos clientes';

$success = '';
$error = '';

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($order_id > 0 && !empty($status)) {
            try {
                $db->query("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $order_id]);
                $success = "Status do pedido atualizado com sucesso!";
            } catch (Exception $e) {
                $error = "Erro ao atualizar status: " . $e->getMessage();
            }
        }
    }
}

// Get orders with pagination and filtering
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$orders = [];
$totalOrders = 0;
try {
    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    
    if ($statusFilter) {
        $whereConditions[] = "o.status = ?";
        $params[] = $statusFilter;
    }
    
    if ($search) {
        $whereConditions[] = "(u.name LIKE ? OR u.email LIKE ? OR o.id = ? OR o.delivery_address LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = is_numeric($search) ? intval($search) : 0;
        $params[] = "%$search%";
    }
    
    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $totalOrders = $db->fetch("
        SELECT COUNT(DISTINCT o.id) as count 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        $whereClause
    ", $params)['count'] ?? 0;
    
    // Get orders
    $orders = $db->fetchAll("
        SELECT o.*, u.name as user_name, u.email as user_email,
               COUNT(oi.id) as item_count
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        $whereClause
        GROUP BY o.id
        ORDER BY o.created_at DESC 
        LIMIT ? OFFSET ?
    ", array_merge($params, [$limit, $offset]));
    
} catch (Exception $e) {
    $error = "Erro ao carregar pedidos: " . $e->getMessage();
}

$totalPages = ceil($totalOrders / $limit);

require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Success/Error Messages -->
<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Orders Statistics -->
<div class="row mb-4">
    <?php
    try {
        $stats = [
            'pending' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'] ?? 0,
            'processing' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'processing'")['count'] ?? 0,
            'completed' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")['count'] ?? 0,
            'cancelled' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")['count'] ?? 0,
        ];
    } catch (Exception $e) {
        $stats = ['pending' => 0, 'processing' => 0, 'completed' => 0, 'cancelled' => 0];
    }
    ?>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning-color), #d97706);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-number"><?= number_format($stats['pending']) ?></div>
            <div class="stat-label">Pendentes</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--info-color), #2563eb);">
                <i class="fas fa-cog"></i>
            </div>
            <div class="stat-number"><?= number_format($stats['processing']) ?></div>
            <div class="stat-label">Processando</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--success-color), #059669);">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-number"><?= number_format($stats['completed']) ?></div>
            <div class="stat-label">Concluídos</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--danger-color), #dc2626);">
                <i class="fas fa-times"></i>
            </div>
            <div class="stat-number"><?= number_format($stats['cancelled']) ?></div>
            <div class="stat-label">Cancelados</div>
        </div>
    </div>
</div>

<!-- Orders List -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-shopping-cart me-2"></i>Pedidos (<?= number_format($totalOrders) ?>)
            </h5>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex">
                    <input type="search" name="search" class="form-control form-control-sm" 
                           placeholder="Buscar pedidos..." value="<?= htmlspecialchars($search) ?>">
                    <?php if ($statusFilter): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-sm btn-outline-primary ms-2">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <select class="form-select form-select-sm" onchange="filterOrders(this.value)">
                    <option value="">Todos os Status</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pendentes</option>
                    <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processando</option>
                    <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Enviados</option>
                    <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Entregues</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelados</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Nenhum pedido encontrado</h5>
                <p class="text-muted">Os pedidos dos clientes aparecerão aqui.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Itens</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?= $order['id'] ?></strong></td>
                                <td>
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($order['user_name'] ?? 'Cliente') ?></h6>
                                        <?php if ($order['user_email']): ?>
                                            <small class="text-muted"><?= htmlspecialchars($order['user_email']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= $order['item_count'] ?> item(s)</span>
                                </td>
                                <td>
                                    <strong class="text-success">
                                        R$ <?= number_format($order['total_amount'] ?? 0, 2, ',', '.') ?>
                                    </strong>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <select name="status" class="form-select form-select-sm" 
                                                onchange="this.form.submit()" style="width: auto;">
                                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>
                                                Pendente
                                            </option>
                                            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>
                                                Processando
                                            </option>
                                            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>
                                                Concluído
                                            </option>
                                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>
                                                Cancelado
                                            </option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                data-bs-toggle="tooltip" title="Ver Detalhes"
                                                onclick="viewOrder(<?= $order['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" 
                                                data-bs-toggle="tooltip" title="Editar Pedido"
                                                onclick="editOrder(<?= $order['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-info" 
                                                data-bs-toggle="tooltip" title="Imprimir"
                                                onclick="printOrder(<?= $order['id'] ?>)">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <?php if (in_array($order['status'], ['pending', 'cancelled'])): ?>
                                        <button type="button" class="btn btn-outline-danger" 
                                                data-bs-toggle="tooltip" title="Excluir Pedido"
                                                onclick="deleteOrder(<?= $order['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">Anterior</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>">Próximo</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-shopping-cart me-2"></i>Detalhes do Pedido
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Edit Modal -->
<div class="modal fade" id="orderEditModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Editar Pedido
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderEditContent">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterOrders(status) {
    const url = new URL(window.location);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    url.searchParams.delete('page');
    window.location = url;
}

function viewOrder(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    const content = document.getElementById('orderDetailsContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Load order details
    fetch('ajax/get_order_details.php?id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro ao carregar detalhes: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erro de conexão. Tente novamente.
                </div>
            `;
        });
}

function editOrder(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderEditModal'));
    const content = document.getElementById('orderEditContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Load order edit form
    fetch('ajax/get_order_edit.php?id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro ao carregar formulário: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erro de conexão. Tente novamente.
                </div>
            `;
        });
}

function deleteOrder(orderId) {
    if (!confirm('Tem certeza que deseja excluir este pedido? Esta ação não pode ser desfeita.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    
    fetch('ajax/delete_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Pedido excluído com sucesso!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('Erro: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Erro de conexão. Tente novamente.', 'error');
    });
}

function printOrder(orderId) {
    // Open order details in a new window for printing
    const printWindow = window.open(`print_order.php?id=${orderId}`, '_blank', 'width=800,height=600');
    if (!printWindow) {
        showToast('Por favor, permita pop-ups para imprimir o pedido', 'warning');
    }
}

// Toast notification function
function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : (type === 'error' ? 'danger' : type)} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : (type === 'error' ? 'exclamation-triangle' : 'info')} me-2"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 5000);
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
