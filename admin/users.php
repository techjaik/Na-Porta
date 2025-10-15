<?php
/**
 * Na Porta - Admin Users Management
 */

require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$pageTitle = 'Usuários';
$pageSubtitle = 'Gerenciar usuários do sistema';

$success = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_status') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 0);
        
        if ($user_id > 0) {
            try {
                $db->query("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?", [$is_active, $user_id]);
                $success = "Status do usuário atualizado com sucesso!";
            } catch (Exception $e) {
                $error = "Erro ao atualizar usuário: " . $e->getMessage();
            }
        }
    }
    
    if ($action === 'update_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $cpf_cnpj = trim($_POST['cpf_cnpj'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $birth_date = $_POST['birth_date'] ?? '';
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $zip_code = trim($_POST['zip_code'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($user_id > 0 && !empty($name)) {
            try {
                $db->query("UPDATE users SET name = ?, phone = ?, cpf_cnpj = ?, gender = ?, birth_date = ?, address = ?, city = ?, state = ?, zip_code = ?, is_active = ?, updated_at = NOW() WHERE id = ?", 
                          [$name, $phone, $cpf_cnpj, $gender, $birth_date ?: null, $address, $city, $state, $zip_code, $is_active, $user_id]);
                $success = "Usuário atualizado com sucesso!";
            } catch (Exception $e) {
                $error = "Erro ao atualizar usuário: " . $e->getMessage();
            }
        }
    }
}

// Get users with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');

$users = [];
$totalUsers = 0;
try {
    $whereClause = '';
    $params = [];
    
    if ($search) {
        $whereClause = "WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? OR cpf_cnpj LIKE ? OR city LIKE ?";
        $params = ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"];
    }
    
    // Get total count
    $totalUsers = $db->fetch("SELECT COUNT(*) as count FROM users $whereClause", $params)['count'] ?? 0;
    
    // Get users
    $users = $db->fetchAll("
        SELECT u.*, 
               COUNT(DISTINCT o.id) as order_count,
               MAX(o.created_at) as last_order
        FROM users u 
        LEFT JOIN orders o ON u.id = o.user_id
        $whereClause
        GROUP BY u.id
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?
    ", array_merge($params, [$limit, $offset]));
    
} catch (Exception $e) {
    $error = "Erro ao carregar usuários: " . $e->getMessage();
}

$totalPages = ceil($totalUsers / $limit);

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

<!-- Users Statistics -->
<div class="row mb-4">
    <?php
    try {
        $stats = [
            'total' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
            'active' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'] ?? 0,
            'new_today' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")['count'] ?? 0,
            'with_orders' => $db->fetch("SELECT COUNT(DISTINCT user_id) as count FROM orders")['count'] ?? 0,
        ];
    } catch (Exception $e) {
        $stats = ['total' => 0, 'active' => 0, 'new_today' => 0, 'with_orders' => 0];
    }
    ?>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?= number_format($stats['total']) ?></div>
            <div class="stat-label">Total de Usuários</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--success-color), #059669);">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-number"><?= number_format($stats['active']) ?></div>
            <div class="stat-label">Usuários Ativos</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--info-color), #2563eb);">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="stat-number"><?= number_format($stats['new_today']) ?></div>
            <div class="stat-label">Novos Hoje</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning-color), #d97706);">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-number"><?= number_format($stats['with_orders']) ?></div>
            <div class="stat-label">Com Pedidos</div>
        </div>
    </div>
</div>

<!-- Users List -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-users me-2"></i>Usuários (<?= number_format($totalUsers) ?>)
            </h5>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex">
                    <input type="search" name="search" class="form-control form-control-sm" 
                           placeholder="Buscar usuários..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-primary ms-2">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Nenhum usuário encontrado</h5>
                <p class="text-muted">
                    <?= $search ? 'Tente uma busca diferente.' : 'Os usuários registrados aparecerão aqui.' ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>Contato</th>
                            <th>Nascimento</th>
                            <th>Pedidos</th>
                            <th>Status</th>
                            <th>Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong>#<?= $user['id'] ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px;">
                                                <span class="text-white fw-bold">
                                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($user['name']) ?></h6>
                                            <small class="text-muted">
                                                Membro desde <?= date('M Y', strtotime($user['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="mb-1">
                                            <i class="fas fa-envelope me-1 text-muted"></i>
                                            <?= htmlspecialchars($user['email']) ?>
                                        </div>
                                        <?php if ($user['phone']): ?>
                                            <div>
                                                <i class="fas fa-phone me-1 text-muted"></i>
                                                <?= htmlspecialchars($user['phone']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (isset($user['birth_date']) && $user['birth_date']): ?>
                                        <div class="fw-medium"><?= date('d/m/Y', strtotime($user['birth_date'])) ?></div>
                                        <small class="text-muted">
                                            <?php 
                                            $birthDate = new DateTime($user['birth_date']);
                                            $today = new DateTime();
                                            $age = $today->diff($birthDate)->y;
                                            echo $age . ' anos';
                                            ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-secondary"><?= $user['order_count'] ?> pedido(s)</span>
                                        <?php if ($user['last_order']): ?>
                                            <small class="d-block text-muted mt-1">
                                                Último: <?= date('d/m/Y', strtotime($user['last_order'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="is_active" value="<?= $user['is_active'] ? 0 : 1 ?>">
                                        <button type="submit" class="btn btn-sm <?= $user['is_active'] ? 'btn-success' : 'btn-secondary' ?>"
                                                onclick="return confirm('Tem certeza que deseja alterar o status deste usuário?')">
                                            <?= $user['is_active'] ? 'Ativo' : 'Inativo' ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                data-bs-toggle="tooltip" title="Ver Detalhes"
                                                onclick="viewUser(<?= $user['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" 
                                                data-bs-toggle="tooltip" title="Editar Usuário"
                                                onclick="editUser(<?= $user['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" 
                                                data-bs-toggle="tooltip" title="Ver Pedidos"
                                                onclick="viewUserOrders(<?= $user['id'] ?>)">
                                            <i class="fas fa-shopping-cart"></i>
                                        </button>
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
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Anterior</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Próximo</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user me-2"></i>Detalhes do Usuário
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Edit Modal -->
<div class="modal fade" id="userEditModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Editar Usuário
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userEditContent">
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
function viewUser(userId) {
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    const content = document.getElementById('userDetailsContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Load user details
    fetch('ajax/get_user_details.php?id=' + userId)
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

function editUser(userId) {
    const modal = new bootstrap.Modal(document.getElementById('userEditModal'));
    const content = document.getElementById('userEditContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Load user edit form
    fetch('ajax/get_user_edit.php?id=' + userId)
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

function viewUserOrders(userId) {
    // Redirect to orders page filtered by user
    window.location.href = 'orders.php?user_id=' + userId;
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
