<?php
// Redirect to working version
$query_string = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: orders-working.php' . $query_string);
exit();

// Get pagination parameters
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = ORDERS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build WHERE clause
$where_conditions = ['user_id = ?'];
$params = [$_SESSION['user_id']];

if ($status_filter) {
    $where_conditions[] = 'status = ?';
    $params[] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = 'DATE(created_at) >= ?';
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = 'DATE(created_at) <= ?';
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Get total count
    $count_sql = "SELECT COUNT(*) FROM orders WHERE $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_orders = $stmt->fetchColumn();
    
    // Get orders
    $sql = "
        SELECT * FROM orders 
        WHERE $where_clause 
        ORDER BY created_at DESC 
        LIMIT $per_page OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $orders = [];
    $total_orders = 0;
    error_log('Orders fetch error: ' . $e->getMessage());
}

// Calculate pagination
$total_pages = ceil($total_orders / $per_page);

// Status options for filter
$status_options = [
    'pending' => 'Pendente',
    'processing' => 'Processando',
    'shipped' => 'Enviado',
    'delivered' => 'Entregue',
    'cancelled' => 'Cancelado'
];

include '../../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../home.php">Início</a></li>
            <li class="breadcrumb-item"><a href="profile.php">Minha Conta</a></li>
            <li class="breadcrumb-item active">Pedidos</li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="fw-bold">
                <i class="fas fa-box text-primary me-2"></i>
                Meus Pedidos
            </h1>
            <p class="text-muted">Acompanhe o status e histórico dos seus pedidos</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="../products.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Fazer Novo Pedido
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Todos os Status</option>
                        <?php foreach ($status_options as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $status_filter === $value ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Data Inicial</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Data Final</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i>Filtrar
                    </button>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Orders List -->
    <?php if (empty($orders)): ?>
    <div class="text-center py-5">
        <i class="fas fa-box-open fa-4x text-muted mb-4"></i>
        <h3>Nenhum pedido encontrado</h3>
        <p class="text-muted mb-4">
            <?php if ($status_filter || $date_from || $date_to): ?>
                Tente ajustar os filtros ou fazer uma nova busca.
            <?php else: ?>
                Você ainda não fez nenhum pedido. Que tal começar agora?
            <?php endif; ?>
        </p>
        <a href="../products.php" class="btn btn-primary">
            <i class="fas fa-shopping-cart me-2"></i>Fazer Primeiro Pedido
        </a>
    </div>
    
    <?php else: ?>
    
    <!-- Orders Count -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0">
            <?php echo $total_orders; ?> pedido<?php echo $total_orders != 1 ? 's' : ''; ?> encontrado<?php echo $total_orders != 1 ? 's' : ''; ?>
        </p>
    </div>
    
    <!-- Orders Cards -->
    <div class="row g-4">
        <?php foreach ($orders as $order): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h6 class="mb-0">
                                <i class="fas fa-receipt me-2"></i>
                                Pedido #<?php echo htmlspecialchars($order['order_number']); ?>
                            </h6>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                            </small>
                        </div>
                        <div class="col-md-3">
                            <?php
                            $status_classes = [
                                'pending' => 'warning',
                                'processing' => 'info',
                                'shipped' => 'primary',
                                'delivered' => 'success',
                                'cancelled' => 'danger'
                            ];
                            $status_class = $status_classes[$order['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $status_class; ?>">
                                <?php echo $status_options[$order['status']] ?? $order['status']; ?>
                            </span>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <strong class="text-primary">
                                <?php echo format_currency($order['total_amount']); ?>
                            </strong>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="mb-2">Endereço de Entrega:</h6>
                            <p class="text-muted mb-2">
                                <?php echo htmlspecialchars($order['delivery_name']); ?><br>
                                <?php echo htmlspecialchars($order['delivery_street']); ?>, 
                                <?php echo htmlspecialchars($order['delivery_number']); ?>
                                <?php if ($order['delivery_complement']): ?>
                                - <?php echo htmlspecialchars($order['delivery_complement']); ?>
                                <?php endif; ?><br>
                                <?php echo htmlspecialchars($order['delivery_neighborhood']); ?> - 
                                <?php echo htmlspecialchars($order['delivery_city']); ?>/<?php echo htmlspecialchars($order['delivery_state']); ?>
                                <br>CEP: <?php echo format_cep($order['delivery_cep']); ?>
                            </p>
                            
                            <?php if ($order['estimated_delivery']): ?>
                            <p class="mb-2">
                                <i class="fas fa-truck text-primary me-2"></i>
                                <strong>Previsão de Entrega:</strong> 
                                <?php echo date('d/m/Y', strtotime($order['estimated_delivery'])); ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if ($order['notes']): ?>
                            <p class="mb-2">
                                <i class="fas fa-sticky-note text-warning me-2"></i>
                                <strong>Observações:</strong> <?php echo htmlspecialchars($order['notes']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4">
                            <h6 class="mb-2">Resumo do Pedido:</h6>
                            <div class="small">
                                <div class="d-flex justify-content-between">
                                    <span>Subtotal:</span>
                                    <span><?php echo format_currency($order['subtotal']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Entrega:</span>
                                    <span>
                                        <?php if ($order['delivery_fee'] > 0): ?>
                                            <?php echo format_currency($order['delivery_fee']); ?>
                                        <?php else: ?>
                                            <span class="text-success">Grátis</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if ($order['discount_amount'] > 0): ?>
                                <div class="d-flex justify-content-between text-success">
                                    <span>Desconto:</span>
                                    <span>-<?php echo format_currency($order['discount_amount']); ?></span>
                                </div>
                                <?php endif; ?>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total:</span>
                                    <span><?php echo format_currency($order['total_amount']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-secondary me-2">
                                <?php echo ucfirst($order['payment_method']); ?>
                            </span>
                            <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                <?php 
                                $payment_status_labels = [
                                    'pending' => 'Pagamento Pendente',
                                    'paid' => 'Pago',
                                    'failed' => 'Falha no Pagamento',
                                    'refunded' => 'Reembolsado'
                                ];
                                echo $payment_status_labels[$order['payment_status']] ?? $order['payment_status'];
                                ?>
                            </span>
                        </div>
                        
                        <div class="btn-group" role="group">
                            <a href="order.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>Ver Detalhes
                            </a>
                            
                            <?php if ($order['status'] === 'delivered'): ?>
                            <button class="btn btn-outline-success btn-sm" onclick="reorderItems(<?php echo $order['id']; ?>)">
                                <i class="fas fa-redo me-1"></i>Pedir Novamente
                            </button>
                            <?php endif; ?>
                            
                            <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                            <button class="btn btn-outline-danger btn-sm" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Navegação de pedidos" class="mt-5">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                    <i class="fas fa-chevron-left"></i> Anterior
                </a>
            </li>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                    Próxima <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<script>
function reorderItems(orderId) {
    if (confirm('Deseja adicionar todos os itens deste pedido ao seu carrinho?')) {
        fetch('/Na%20Porta/api/reorder.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                NaPorta.showNotification('Itens adicionados ao carrinho!', 'success');
                // Update cart count if function exists
                if (typeof updateCartUI === 'function') {
                    updateCartUI(data.cart_count, data.cart_total);
                }
            } else {
                NaPorta.showNotification(data.message || 'Erro ao adicionar itens', 'error');
            }
        })
        .catch(error => {
            NaPorta.showNotification('Erro ao processar solicitação', 'error');
        });
    }
}

function cancelOrder(orderId) {
    if (confirm('Tem certeza que deseja cancelar este pedido?')) {
        fetch('/Na%20Porta/api/cancel-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                NaPorta.showNotification('Pedido cancelado com sucesso', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                NaPorta.showNotification(data.message || 'Erro ao cancelar pedido', 'error');
            }
        })
        .catch(error => {
            NaPorta.showNotification('Erro ao processar solicitação', 'error');
        });
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
