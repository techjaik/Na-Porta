<?php
/**
 * Na Porta - Admin Dashboard
 */

require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$pageTitle = 'Dashboard';
$pageSubtitle = 'Visão geral do sistema';

// Get dashboard statistics
$stats = [];
try {
    $stats['users'] = $db->fetch("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'] ?? 0;
    $stats['products'] = $db->fetch("SELECT COUNT(*) as count FROM products WHERE is_active = 1")['count'] ?? 0;
    $stats['categories'] = $db->fetch("SELECT COUNT(*) as count FROM categories WHERE is_active = 1")['count'] ?? 0;
    $stats['orders'] = $db->fetch("SELECT COUNT(*) as count FROM orders")['count'] ?? 0;
    
    // Recent orders
    $recentOrders = $db->fetchAll("
        SELECT o.*, u.name as user_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    
    // Top products
    $topProducts = $db->fetchAll("
        SELECT p.name, p.price, COUNT(oi.id) as order_count
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        WHERE p.is_active = 1
        GROUP BY p.id
        ORDER BY order_count DESC
        LIMIT 5
    ");
    
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--success-color), #059669);">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?= number_format($stats['users']) ?></div>
            <div class="stat-label">Usuários Ativos</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--info-color), #2563eb);">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-number"><?= number_format($stats['products']) ?></div>
            <div class="stat-label">Produtos</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning-color), #d97706);">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-number"><?= number_format($stats['orders']) ?></div>
            <div class="stat-label">Pedidos</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--secondary-color), #7c3aed);">
                <i class="fas fa-tags"></i>
            </div>
            <div class="stat-number"><?= number_format($stats['categories']) ?></div>
            <div class="stat-label">Categorias</div>
        </div>
    </div>
</div>

<!-- Recent Orders & Top Products -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>Pedidos Recentes
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        Nenhum pedido encontrado
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><strong>#<?= $order['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($order['user_name'] ?? 'Cliente') ?></td>
                                        <td>R$ <?= number_format($order['total_amount'] ?? 0, 2, ',', '.') ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $order['status'] === 'completed' ? 'success' : 
                                                ($order['status'] === 'pending' ? 'warning' : 'secondary') 
                                            ?>">
                                                <?= ucfirst($order['status'] ?? 'pending') ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-star me-2"></i>Produtos Populares
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($topProducts)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                        <small>Nenhum produto encontrado</small>
                    </div>
                <?php else: ?>
                    <?php foreach ($topProducts as $product): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-1"><?= htmlspecialchars($product['name']) ?></h6>
                                <small class="text-muted">R$ <?= number_format($product['price'], 2, ',', '.') ?></small>
                            </div>
                            <span class="badge bg-primary"><?= $product['order_count'] ?> vendas</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
