<?php
// Redirect to working version
header('Location: index-fixed.php');
exit();

$page_title = 'Dashboard Administrativo';

// Get dashboard statistics
$stats = [
    'total_users' => 0,
    'total_products' => 0,
    'total_orders' => 0,
    'total_revenue' => 0,
    'pending_orders' => 0,
    'low_stock_products' => 0,
    'today_orders' => 0,
    'today_revenue' => 0
];

try {
    // Total users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_active = 1");
    $stmt->execute();
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Total products
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE is_active = 1");
    $stmt->execute();
    $stats['total_products'] = $stmt->fetchColumn();
    
    // Total orders and revenue
    $stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(total_amount), 0) FROM orders");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_NUM);
    $stats['total_orders'] = $result[0];
    $stats['total_revenue'] = $result[1];
    
    // Pending orders
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'processing')");
    $stmt->execute();
    $stats['pending_orders'] = $stmt->fetchColumn();
    
    // Low stock products
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level AND is_active = 1");
    $stmt->execute();
    $stats['low_stock_products'] = $stmt->fetchColumn();
    
    // Today's orders and revenue
    $stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_NUM);
    $stats['today_orders'] = $result[0];
    $stats['today_revenue'] = $result[1];
    
} catch (PDOException $e) {
    error_log('Dashboard stats error: ' . $e->getMessage());
}

// Get recent orders
$recent_orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as user_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Recent orders error: ' . $e->getMessage());
}

// Get low stock products
$low_stock_products = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.stock_quantity <= p.min_stock_level AND p.is_active = 1 
        ORDER BY p.stock_quantity ASC 
        LIMIT 10
    ");
    $stmt->execute();
    $low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Low stock products error: ' . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">
                <i class="fas fa-tachometer-alt text-primary me-2"></i>
                Dashboard
            </h1>
            <p class="text-muted">Visão geral do sistema Na Porta</p>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <!-- Total Users -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo number_format($stats['total_users']); ?></h4>
                            <p class="mb-0">Usuários Ativos</p>
                        </div>
                        <div class="text-white-50">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-primary border-0">
                    <a href="users.php" class="text-white text-decoration-none">
                        <small>Ver todos <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Total Products -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo number_format($stats['total_products']); ?></h4>
                            <p class="mb-0">Produtos Ativos</p>
                        </div>
                        <div class="text-white-50">
                            <i class="fas fa-box fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-success border-0">
                    <a href="products.php" class="text-white text-decoration-none">
                        <small>Gerenciar <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Total Orders -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo number_format($stats['total_orders']); ?></h4>
                            <p class="mb-0">Total de Pedidos</p>
                            <small class="text-white-75">Hoje: <?php echo $stats['today_orders']; ?></small>
                        </div>
                        <div class="text-white-50">
                            <i class="fas fa-shopping-cart fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-info border-0">
                    <a href="orders.php" class="text-white text-decoration-none">
                        <small>Ver pedidos <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Total Revenue -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo format_currency($stats['total_revenue']); ?></h4>
                            <p class="mb-0">Receita Total</p>
                            <small class="text-white-75">Hoje: <?php echo format_currency($stats['today_revenue']); ?></small>
                        </div>
                        <div class="text-white-50">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-warning border-0">
                    <a href="reports.php" class="text-white text-decoration-none">
                        <small>Ver relatórios <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alert Cards -->
    <div class="row g-4 mb-5">
        <!-- Pending Orders Alert -->
        <?php if ($stats['pending_orders'] > 0): ?>
        <div class="col-md-6">
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">Pedidos Pendentes</h5>
                    <p class="mb-2">Você tem <?php echo $stats['pending_orders']; ?> pedido(s) aguardando processamento.</p>
                    <a href="orders.php?status=pending" class="btn btn-warning btn-sm">
                        <i class="fas fa-eye me-1"></i>Ver Pedidos
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Low Stock Alert -->
        <?php if ($stats['low_stock_products'] > 0): ?>
        <div class="col-md-6">
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">Estoque Baixo</h5>
                    <p class="mb-2"><?php echo $stats['low_stock_products']; ?> produto(s) com estoque baixo.</p>
                    <a href="products.php?filter=low_stock" class="btn btn-danger btn-sm">
                        <i class="fas fa-boxes me-1"></i>Ver Produtos
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Pedidos Recentes
                    </h5>
                    <a href="orders.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>Ver Todos
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_orders)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhum pedido encontrado</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['user_name'] ?: 'N/A'); ?></td>
                                    <td>
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
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo format_currency($order['total_amount']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="order.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
        
        <!-- Low Stock Products -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Estoque Baixo
                    </h5>
                    <a href="products.php?filter=low_stock" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-boxes me-1"></i>Ver Todos
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($low_stock_products)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted">Todos os produtos com estoque adequado</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($low_stock_products as $product): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($product['category_name']); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-danger">
                                    <?php echo $product['stock_quantity']; ?> unidades
                                </span>
                                <br>
                                <small class="text-muted">
                                    Min: <?php echo $product['min_stock_level']; ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Ações Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="products.php?action=add" class="btn btn-success w-100">
                                <i class="fas fa-plus me-2"></i>Adicionar Produto
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="categories.php" class="btn btn-primary w-100">
                                <i class="fas fa-tags me-2"></i>Gerenciar Categorias
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="coupons.php?action=add" class="btn btn-warning w-100">
                                <i class="fas fa-ticket-alt me-2"></i>Criar Cupom
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="reports.php" class="btn btn-info w-100">
                                <i class="fas fa-chart-bar me-2"></i>Ver Relatórios
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
