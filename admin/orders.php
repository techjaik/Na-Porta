<?php
// Admin Orders Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Simple admin check
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

// Redirect if not logged in
if (!is_admin_logged_in()) {
    header('Location: login.php');
    exit();
}

// Get admin info
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';

$success = '';
$error = '';

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $order_id = (int)($_POST['order_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';
        
        if ($order_id > 0 && !empty($new_status)) {
            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$new_status, $order_id])) {
                    $success = "Status do pedido atualizado com sucesso!";
                } else {
                    $error = "Erro ao atualizar status do pedido.";
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'cancel_order') {
        $order_id = (int)($_POST['order_id'] ?? 0);
        
        if ($order_id > 0) {
            try {
                // Check if order can be cancelled
                $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
                $stmt->execute([$order_id]);
                $current_status = $stmt->fetchColumn();
                
                if ($current_status && in_array($current_status, ['pending', 'confirmed', 'preparing'])) {
                    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$order_id])) {
                        $success = "Pedido cancelado com sucesso!";
                    } else {
                        $error = "Erro ao cancelar pedido.";
                    }
                } else {
                    $error = "Este pedido não pode ser cancelado.";
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$payment_filter = $_GET['payment'] ?? '';
$search = $_GET['search'] ?? '';

// Get orders from database with filters
$orders = [];
$order_stats = ['pending' => 0, 'confirmed' => 0, 'preparing' => 0, 'delivering' => 0, 'delivered' => 0, 'cancelled' => 0];

try {
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($status_filter)) {
        $where_conditions[] = "o.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($payment_filter)) {
        $where_conditions[] = "o.payment_method = ?";
        $params[] = $payment_filter;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get orders with user details
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as user_name, u.email as user_email,
               COUNT(oi.id) as item_count
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        $where_clause
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order statistics
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $stmt->execute();
    $stats_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($stats_result as $stat) {
        if (isset($order_stats[$stat['status']])) {
            $order_stats[$stat['status']] = $stat['count'];
        }
    }
    
} catch (Exception $e) {
    $error = "Erro ao carregar pedidos: " . $e->getMessage();
}

$page_title = 'Pedidos';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Na Porta</title>
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1976d2;
            --sidebar-width: 250px;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: #2c3e50;
            color: white;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: #f8f9fa;
        }
        
        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 12px 20px;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .navbar-admin {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-3">
            <h4 class="text-center mb-4">
                <i class="fas fa-home me-2"></i>Na Porta
                <small class="d-block text-muted">Admin</small>
            </h4>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="index-fixed.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="products.php">
                    <i class="fas fa-box me-2"></i>Produtos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="categories.php">
                    <i class="fas fa-tags me-2"></i>Categorias
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i>Pedidos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="fas fa-users me-2"></i>Usuários
                </a>
            </li>
            <li class="nav-item mt-4">
                <hr class="text-muted">
                <a class="nav-link" href="../pages/home-fixed.php" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i>Ver Site
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout-simple.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Sair
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-admin">
            <div class="container-fluid">
                <h5 class="mb-0">Gerenciar Pedidos</h5>
                
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($admin_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout-simple.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="container-fluid p-4">
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h2>Pedidos</h2>
                    <p class="text-muted">Gerencie todos os pedidos da loja</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group">
                        <button class="btn btn-outline-primary" data-mdb-toggle="collapse" data-mdb-target="#filtersCollapse">
                            <i class="fas fa-filter me-2"></i>Filtros
                        </button>
                        <button class="btn btn-outline-success">
                            <i class="fas fa-download me-2"></i>Exportar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="collapse mb-4" id="filtersCollapse">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="">Todos os status</option>
                                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pendente</option>
                                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmado</option>
                                        <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparando</option>
                                        <option value="delivering" <?php echo $status_filter === 'delivering' ? 'selected' : ''; ?>>Em Entrega</option>
                                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Entregue</option>
                                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Forma de Pagamento</label>
                                    <select class="form-select" name="payment">
                                        <option value="">Todas as formas</option>
                                        <option value="money" <?php echo $payment_filter === 'money' ? 'selected' : ''; ?>>💰 Dinheiro</option>
                                        <option value="card" <?php echo $payment_filter === 'card' ? 'selected' : ''; ?>>💳 Cartão</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Buscar</label>
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="ID do pedido, nome ou email do cliente..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-2"></i>Filtrar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($status_filter) || !empty($payment_filter) || !empty($search)): ?>
                            <div class="mt-3">
                                <a href="orders.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times me-2"></i>Limpar Filtros
                                </a>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Order Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4 class="mb-0"><?php echo $order_stats['pending']; ?></h4>
                            <small class="text-muted">Pendentes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                                <i class="fas fa-truck"></i>
                            </div>
                            <h4 class="mb-0"><?php echo $order_stats['delivering']; ?></h4>
                            <small class="text-muted">Em Entrega</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                                <i class="fas fa-check"></i>
                            </div>
                            <h4 class="mb-0"><?php echo $order_stats['delivered']; ?></h4>
                            <small class="text-muted">Entregues</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                                <i class="fas fa-times"></i>
                            </div>
                            <h4 class="mb-0"><?php echo $order_stats['cancelled']; ?></h4>
                            <small class="text-muted">Cancelados</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h5>Nenhum pedido encontrado</h5>
                        <p class="text-muted">
                            <?php if (!empty($status_filter) || !empty($payment_filter) || !empty($search)): ?>
                                Nenhum pedido corresponde aos filtros aplicados.
                            <?php else: ?>
                                Os pedidos dos clientes aparecerão aqui quando começarem a comprar.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th>Itens</th>
                                    <th>Total</th>
                                    <th>Pagamento</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo $order['id']; ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['user_name'] ?? 'Cliente não encontrado'); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['user_email'] ?? ''); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $order['item_count']; ?> item(s)</span>
                                    </td>
                                    <td>
                                        <strong>R$ <?php echo number_format($order['total_amount'], 2, ',', '.'); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($order['payment_method'] === 'money'): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-money-bill me-1"></i>Dinheiro
                                        </span>
                                        <?php elseif ($order['payment_method'] === 'card'): ?>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-credit-card me-1"></i>Cartão
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'preparing' => 'primary',
                                            'delivering' => 'info',
                                            'delivered' => 'success',
                                            'cancelled' => 'danger'
                                        ];
                                        $status_labels = [
                                            'pending' => 'Pendente',
                                            'confirmed' => 'Confirmado',
                                            'preparing' => 'Preparando',
                                            'delivering' => 'Em Entrega',
                                            'delivered' => 'Entregue',
                                            'cancelled' => 'Cancelado'
                                        ];
                                        $color = $status_colors[$order['status']] ?? 'secondary';
                                        $label = $status_labels[$order['status']] ?? $order['status'];
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo $label; ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                            <br>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="Ver detalhes"
                                                    onclick="viewOrder(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" title="Alterar status"
                                                    onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if (in_array($order['status'], ['pending', 'confirmed', 'preparing'])): ?>
                                            <button class="btn btn-outline-danger" title="Cancelar pedido"
                                                    onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shopping-cart me-2"></i>Detalhes do Pedido
                    </h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informações do Pedido</h6>
                            <p><strong>ID:</strong> <span id="view_order_id"></span></p>
                            <p><strong>Status:</strong> <span id="view_order_status"></span></p>
                            <p><strong>Total:</strong> <span id="view_order_total"></span></p>
                            <p><strong>Forma de Pagamento:</strong> <span id="view_order_payment"></span></p>
                            <p><strong>Data:</strong> <span id="view_order_date"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Informações do Cliente</h6>
                            <p><strong>Nome:</strong> <span id="view_customer_name"></span></p>
                            <p><strong>Email:</strong> <span id="view_customer_email"></span></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <h6>Endereço de Entrega</h6>
                            <p id="view_delivery_address" class="text-muted"></p>
                        </div>
                    </div>
                    <div class="row" id="view_notes_section" style="display: none;">
                        <div class="col-12">
                            <h6>Observações</h6>
                            <p id="view_notes" class="text-muted"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Alterar Status do Pedido
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" id="status_order_id">
                        
                        <p>Alterar status do pedido <strong>#<span id="status_order_number"></span></strong>:</p>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Novo Status</label>
                            <select class="form-select" name="status" id="status" required>
                                <option value="pending">Pendente</option>
                                <option value="confirmed">Confirmado</option>
                                <option value="preparing">Preparando</option>
                                <option value="delivering">Em Entrega</option>
                                <option value="delivered">Entregue</option>
                                <option value="cancelled">Cancelado</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Atualizar Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // View order function
        function viewOrder(order) {
            document.getElementById('view_order_id').textContent = '#' + order.id;
            
            // Status with color
            const statusLabels = {
                'pending': 'Pendente',
                'confirmed': 'Confirmado',
                'preparing': 'Preparando',
                'delivering': 'Em Entrega',
                'delivered': 'Entregue',
                'cancelled': 'Cancelado'
            };
            const statusColors = {
                'pending': 'warning',
                'confirmed': 'info',
                'preparing': 'primary',
                'delivering': 'info',
                'delivered': 'success',
                'cancelled': 'danger'
            };
            const statusLabel = statusLabels[order.status] || order.status;
            const statusColor = statusColors[order.status] || 'secondary';
            document.getElementById('view_order_status').innerHTML = 
                `<span class="badge bg-${statusColor}">${statusLabel}</span>`;
            
            document.getElementById('view_order_total').textContent = 
                'R$ ' + parseFloat(order.total_amount).toLocaleString('pt-BR', {minimumFractionDigits: 2});
            
            // Payment method with icon
            let paymentText = order.payment_method;
            if (order.payment_method === 'money') {
                paymentText = '💰 Dinheiro';
            } else if (order.payment_method === 'card') {
                paymentText = '💳 Cartão';
            }
            document.getElementById('view_order_payment').textContent = paymentText;
            
            document.getElementById('view_order_date').textContent = 
                new Date(order.created_at).toLocaleString('pt-BR');
            
            document.getElementById('view_customer_name').textContent = order.user_name || 'Cliente não encontrado';
            document.getElementById('view_customer_email').textContent = order.user_email || '';
            
            document.getElementById('view_delivery_address').textContent = order.delivery_address || '';
            
            // Show/hide notes section
            if (order.notes && order.notes.trim()) {
                document.getElementById('view_notes').textContent = order.notes;
                document.getElementById('view_notes_section').style.display = 'block';
            } else {
                document.getElementById('view_notes_section').style.display = 'none';
            }
            
            const modal = new mdb.Modal(document.getElementById('viewOrderModal'));
            modal.show();
        }
        
        // Update status function
        function updateStatus(orderId, currentStatus) {
            document.getElementById('order_id').value = orderId;
            document.getElementById('status').value = currentStatus;
            
            const modal = new mdb.Modal(document.getElementById('updateStatusModal'));
            modal.show();
        }
        
        function cancelOrder(orderId) {
            if (confirm('Tem certeza que deseja cancelar este pedido? Esta ação não pode ser desfeita.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancel_order">
                    <input type="hidden" name="order_id" value="${orderId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
