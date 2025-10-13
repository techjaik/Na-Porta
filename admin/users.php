<?php
// Admin Users Page
// Disable error display for production
error_reporting(0);
ini_set('display_errors', 0);

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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_status') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        if ($user_id > 0) {
            try {
                // Toggle user status
                $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
                if ($stmt->execute([$user_id])) {
                    $success = "Status do usuário atualizado com sucesso!";
                } else {
                    $error = "Erro ao atualizar status do usuário.";
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'delete_user') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        if ($user_id > 0) {
            try {
                // Check if user has orders
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $order_count = $stmt->fetchColumn();
                
                if ($order_count > 0) {
                    $error = "Não é possível excluir usuário com pedidos associados.";
                } else {
                    // Delete user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmt->execute([$user_id])) {
                        $success = "Usuário excluído com sucesso!";
                    } else {
                        $error = "Erro ao excluir usuário.";
                    }
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'edit_user') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($user_id <= 0) {
            $error = "ID de usuário inválido.";
        } elseif (empty($name) || empty($email)) {
            $error = "Por favor, preencha todos os campos obrigatórios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Por favor, insira um email válido.";
        } else {
            try {
                // Check if email already exists for another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetchColumn()) {
                    $error = "Este email já está sendo usado por outro usuário.";
                } else {
                    // Update user (with or without password)
                    if (!empty($password)) {
                        if (strlen($password) < 6) {
                            $error = "A senha deve ter pelo menos 6 caracteres.";
                        } else {
                            // Update with new password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET name = ?, email = ?, cpf = ?, password = ?, gender = ?, is_active = ?, updated_at = NOW()
                                WHERE id = ?
                            ");
                            
                            if ($stmt->execute([$name, $email, $cpf ?: null, $hashed_password, $gender ?: null, $is_active, $user_id])) {
                                $success = "Usuário atualizado com sucesso!";
                            } else {
                                $error = "Erro ao atualizar usuário.";
                            }
                        }
                    } else {
                        // Update without changing password
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET name = ?, email = ?, cpf = ?, gender = ?, is_active = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        
                        if ($stmt->execute([$name, $email, $cpf ?: null, $gender ?: null, $is_active, $user_id])) {
                            $success = "Usuário atualizado com sucesso!";
                        } else {
                            $error = "Erro ao atualizar usuário.";
                        }
                    }
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'add_user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name) || empty($email) || empty($password)) {
            $error = "Por favor, preencha todos os campos obrigatórios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Por favor, insira um email válido.";
        } elseif (strlen($password) < 6) {
            $error = "A senha deve ter pelo menos 6 caracteres.";
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn()) {
                    $error = "Este email já está cadastrado.";
                } else {
                    // Hash password and create user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, email, cpf, password, gender, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    if ($stmt->execute([$name, $email, $cpf ?: null, $hashed_password, $gender ?: null, $is_active])) {
                        $success = "Usuário criado com sucesso!";
                    } else {
                        $error = "Erro ao criar usuário.";
                    }
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, is_active, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        $users_export = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=usuarios_' . date('Y-m-d_H-i-s') . '.csv');
        
        // Create file pointer
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add CSV headers
        fputcsv($output, ['ID', 'Nome', 'Email', 'Status', 'Data de Cadastro'], ';');
        
        // Add data rows
        foreach ($users_export as $user) {
            fputcsv($output, [
                $user['id'],
                $user['name'],
                $user['email'],
                $user['is_active'] ? 'Ativo' : 'Inativo',
                date('d/m/Y H:i:s', strtotime($user['created_at']))
            ], ';');
        }
        
        fclose($output);
        exit();
    } catch (Exception $e) {
        $error = "Erro ao exportar usuários: " . $e->getMessage();
    }
}

// Get users from database with additional info
$users = [];
try {
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COALESCE(order_stats.total_orders, 0) as total_orders,
               COALESCE(address_stats.total_addresses, 0) as total_addresses,
               COALESCE(order_stats.total_spent, 0) as total_spent,
               order_stats.last_order_date
        FROM users u 
        LEFT JOIN (
            SELECT user_id,
                   COUNT(*) as total_orders,
                   SUM(total_amount) as total_spent,
                   MAX(created_at) as last_order_date
            FROM orders 
            GROUP BY user_id
        ) order_stats ON u.id = order_stats.user_id
        LEFT JOIN (
            SELECT user_id,
                   COUNT(*) as total_addresses
            FROM user_addresses 
            GROUP BY user_id
        ) address_stats ON u.id = address_stats.user_id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erro ao carregar usuários: " . $e->getMessage();
}

$page_title = 'Usuários';
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
        
        /* Clean styling - no unwanted elements */
        .container-fluid,
        .main-content {
            background: #f8f9fa;
            border: none;
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
                <a class="nav-link" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i>Pedidos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="users.php">
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
                <h5 class="mb-0">Gerenciar Usuários</h5>
                
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
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2>Usuários</h2>
                    <p class="text-muted">Gerencie os usuários cadastrados</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group">
                        <a href="?export=csv" class="btn btn-outline-primary">
                            <i class="fas fa-download me-2"></i>Exportar
                        </a>
                        <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#addUserModal">
                            <i class="fas fa-user-plus me-2"></i>Novo Usuário
                        </button>
                    </div>
                </div>
            </div>

            <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Users Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($users)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5>Nenhum usuário encontrado</h5>
                        <p class="text-muted">Os usuários cadastrados aparecerão aqui</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Email</th>
                                    <th>CPF</th>
                                    <th>Gênero</th>
                                    <th>Pedidos</th>
                                    <th>Total Gasto</th>
                                    <th>Status</th>
                                    <th>Cadastro</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">
                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                                <br>
                                                <small class="text-muted">ID: <?php echo $user['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($user['cpf'])) {
                                            // Format CPF for display
                                            $cpf = $user['cpf'];
                                            if (strlen($cpf) == 11) {
                                                $formatted_cpf = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                                                echo "<small class='text-muted'>{$formatted_cpf}</small>";
                                            } else {
                                                echo "<small class='text-muted'>{$cpf}</small>";
                                            }
                                        } else {
                                            echo "<span class='text-muted'>-</span>";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($user['gender']) {
                                            $gender_labels = [
                                                'male' => 'M',
                                                'female' => 'F', 
                                                'other' => 'O',
                                                'prefer_not_to_say' => '-'
                                            ];
                                            $gender_colors = [
                                                'male' => 'primary',
                                                'female' => 'danger', 
                                                'other' => 'warning',
                                                'prefer_not_to_say' => 'secondary'
                                            ];
                                            $label = $gender_labels[$user['gender']] ?? '?';
                                            $color = $gender_colors[$user['gender']] ?? 'secondary';
                                            echo "<span class='badge bg-{$color}' title='{$user['gender']}'>{$label}</span>";
                                        } else {
                                            echo "<span class='text-muted'>-</span>";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $user['total_orders']; ?> pedido(s)</span>
                                    </td>
                                    <td>
                                        <strong>R$ <?php echo number_format($user['total_spent'] ?: 0, 2, ',', '.'); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $user['is_active'] ? 'Ativo' : 'Inativo'; ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="Ver detalhes"
                                                    onclick="viewUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" title="Editar"
                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-<?php echo $user['is_active'] ? 'danger' : 'success'; ?>" 
                                                    title="<?php echo $user['is_active'] ? 'Desativar' : 'Ativar'; ?>"
                                                    onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>', <?php echo $user['is_active'] ? 'true' : 'false'; ?>)">
                                                <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" title="Excluir usuário"
                                                    onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
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

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>Detalhes Completos do Usuário
                    </h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- User Info Tabs -->
                    <ul class="nav nav-tabs" id="userDetailsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="personal-tab" data-mdb-toggle="tab" 
                                    data-mdb-target="#personal" type="button" role="tab">
                                <i class="fas fa-user me-2"></i>Informações Pessoais
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="addresses-tab" data-mdb-toggle="tab" 
                                    data-mdb-target="#addresses" type="button" role="tab">
                                <i class="fas fa-map-marker-alt me-2"></i>Endereços
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="orders-tab" data-mdb-toggle="tab" 
                                    data-mdb-target="#orders" type="button" role="tab">
                                <i class="fas fa-shopping-cart me-2"></i>Histórico de Pedidos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="system-tab" data-mdb-toggle="tab" 
                                    data-mdb-target="#system" type="button" role="tab">
                                <i class="fas fa-cogs me-2"></i>Informações do Sistema
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3" id="userDetailsTabContent">
                        <!-- Personal Information Tab -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="fas fa-id-card me-2"></i>Dados Básicos</h6>
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <td><strong>ID:</strong></td>
                                                    <td id="view_id"></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Nome:</strong></td>
                                                    <td id="view_name"></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Email:</strong></td>
                                                    <td id="view_email"></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>CPF:</strong></td>
                                                    <td id="view_cpf"></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Status:</strong></td>
                                                    <td id="view_status"></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="fas fa-chart-bar me-2"></i>Estatísticas</h6>
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <td><strong>Total de Pedidos:</strong></td>
                                                    <td id="view_total_orders"></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total Gasto:</strong></td>
                                                    <td id="view_total_spent"></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Endereços Salvos:</strong></td>
                                                    <td id="view_total_addresses"></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Último Pedido:</strong></td>
                                                    <td id="view_last_order"></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Addresses Tab -->
                        <div class="tab-pane fade" id="addresses" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6><i class="fas fa-map-marker-alt me-2"></i>Endereços Cadastrados</h6>
                                <button class="btn btn-primary btn-sm" onclick="addAddressForUser()">
                                    <i class="fas fa-plus me-2"></i>Adicionar Endereço
                                </button>
                            </div>
                            <div id="user_addresses_list">
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                    <p class="text-muted mt-2">Carregando endereços...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Orders Tab -->
                        <div class="tab-pane fade" id="orders" role="tabpanel">
                            <h6><i class="fas fa-shopping-cart me-2"></i>Histórico de Pedidos</h6>
                            <div id="user_orders_list">
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                    <p class="text-muted mt-2">Carregando pedidos...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Information Tab -->
                        <div class="tab-pane fade" id="system" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="fas fa-clock me-2"></i>Datas Importantes</h6>
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <td><strong>Cadastro:</strong></td>
                                                    <td id="view_created"></td>
                                                </tr>
                                                <tr id="view_updated_row" style="display: none;">
                                                    <td><strong>Última Atualização:</strong></td>
                                                    <td id="view_updated"></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="fas fa-database me-2"></i>Dados Técnicos</h6>
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <td><strong>ID no Banco:</strong></td>
                                                    <td id="view_db_id"></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Senha:</strong></td>
                                                    <td><span class="badge bg-secondary">Criptografada</span></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-warning">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="fas fa-tools me-2"></i>Ações de Administrador</h6>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-warning" onclick="editUserFromView()">
                                            <i class="fas fa-edit me-2"></i>Editar Usuário
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="toggleStatusFromView()">
                                            <i class="fas fa-toggle-on me-2"></i>Alterar Status
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="resetUserPassword()">
                                            <i class="fas fa-key me-2"></i>Resetar Senha
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" onclick="deleteUserFromView()">
                                            <i class="fas fa-trash me-2"></i>Excluir Usuário
                                        </button>
                                    </div>
                                </div>
                            </div>
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

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Editar Usuário
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_cpf" class="form-label">CPF</label>
                            <input type="text" class="form-control" id="edit_cpf" name="cpf" 
                                   placeholder="000.000.000-00" maxlength="14">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
                            <small class="text-muted">Deixe em branco para manter a senha atual</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_gender" class="form-label">Gênero</label>
                            <select class="form-select" id="edit_gender" name="gender">
                                <option value="">Não informado</option>
                                <option value="male">Masculino</option>
                                <option value="female">Feminino</option>
                                <option value="other">Outro</option>
                                <option value="prefer_not_to_say">Prefiro não dizer</option>
                            </select>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">
                                <i class="fas fa-check-circle text-success me-1"></i>Usuário Ativo
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toggle Status Modal -->
    <div class="modal fade" id="toggleStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title text-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Alterar Status
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" id="toggle_user_id">
                        
                        <p>Tem certeza que deseja <strong id="toggle_action"></strong> o usuário <strong id="toggle_user_name"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-check me-2"></i>Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-trash me-2"></i>Excluir Usuário
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atenção!</strong> Esta ação não pode ser desfeita.
                        </div>
                        
                        <p>Tem certeza que deseja excluir o usuário <strong id="delete_user_name"></strong>?</p>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Nota:</strong> Usuários com pedidos associados não podem ser excluídos.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Excluir Usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user-plus me-2"></i>Novo Usuário
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_user">
                        
                        <div class="mb-3">
                            <label for="add_name" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" id="add_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="add_email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="add_email" name="email" required>
                            <small class="text-muted">O email será usado para login</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="add_cpf" class="form-label">CPF</label>
                            <input type="text" class="form-control" id="add_cpf" name="cpf" 
                                   placeholder="000.000.000-00" maxlength="14">
                        </div>
                        
                        <div class="mb-3">
                            <label for="add_password" class="form-label">Senha *</label>
                            <input type="password" class="form-control" id="add_password" name="password" required minlength="6">
                            <small class="text-muted">Mínimo de 6 caracteres</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="add_gender" class="form-label">Gênero</label>
                            <select class="form-select" id="add_gender" name="gender">
                                <option value="">Não informado</option>
                                <option value="male">Masculino</option>
                                <option value="female">Feminino</option>
                                <option value="other">Outro</option>
                                <option value="prefer_not_to_say">Prefiro não dizer</option>
                            </select>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="add_is_active" name="is_active" checked>
                            <label class="form-check-label" for="add_is_active">
                                <i class="fas fa-check-circle text-success me-1"></i>Usuário Ativo
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Criar Usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        let currentUser = null; // Store current user data for modal actions
        
        // View user function
        function viewUser(user) {
            currentUser = user; // Store for other actions
            
            // Personal Information Tab
            document.getElementById('view_id').textContent = user.id;
            document.getElementById('view_name').textContent = user.name;
            document.getElementById('view_email').textContent = user.email;
            
            // Format and display CPF
            const cpfElement = document.getElementById('view_cpf');
            if (user.cpf && user.cpf.length === 11) {
                const formattedCpf = user.cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                cpfElement.textContent = formattedCpf;
            } else if (user.cpf) {
                cpfElement.textContent = user.cpf;
            } else {
                cpfElement.textContent = 'Não informado';
            }
            
            document.getElementById('view_status').innerHTML = user.is_active == 1 ? 
                '<span class="badge bg-success">Ativo</span>' : 
                '<span class="badge bg-danger">Inativo</span>';
            
            // Statistics
            document.getElementById('view_total_orders').textContent = user.total_orders || '0';
            document.getElementById('view_total_spent').textContent = user.total_spent ? 
                'R$ ' + parseFloat(user.total_spent).toLocaleString('pt-BR', {minimumFractionDigits: 2}) : 
                'R$ 0,00';
            document.getElementById('view_total_addresses').textContent = user.total_addresses || '0';
            document.getElementById('view_last_order').textContent = user.last_order_date ? 
                new Date(user.last_order_date).toLocaleDateString('pt-BR') : 
                'Nunca';
            
            // System Information Tab
            document.getElementById('view_created').textContent = new Date(user.created_at).toLocaleDateString('pt-BR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('view_db_id').textContent = user.id;
            
            // Show updated date if exists
            if (user.updated_at && user.updated_at !== user.created_at) {
                document.getElementById('view_updated').textContent = new Date(user.updated_at).toLocaleDateString('pt-BR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                document.getElementById('view_updated_row').style.display = 'table-row';
            } else {
                document.getElementById('view_updated_row').style.display = 'none';
            }
            
            // Load addresses when tab is clicked
            loadUserAddresses(user.id);
            
            // Load orders when tab is clicked
            loadUserOrders(user.id);
            
            const modal = new mdb.Modal(document.getElementById('viewUserModal'));
            modal.show();
        }
        
        // Load user addresses
        function loadUserAddresses(userId) {
            fetch(`get_user_addresses.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('user_addresses_list');
                    if (data.addresses && data.addresses.length > 0) {
                        let html = '';
                        data.addresses.forEach(address => {
                            html += `
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title">
                                                    <i class="fas fa-map-marker-alt me-2"></i>${address.title}
                                                    ${address.is_default == 1 ? '<span class="badge bg-success ms-2">Padrão</span>' : ''}
                                                </h6>
                                                <p class="card-text text-muted">${address.address}</p>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Criado em ${new Date(address.created_at).toLocaleDateString('pt-BR')}
                                                </small>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-warning" onclick="editAddress(${address.id})" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="deleteAddress(${address.id})" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div class="text-center py-4">
                                <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                <h6>Nenhum endereço cadastrado</h6>
                                <p class="text-muted">Este usuário ainda não possui endereços salvos.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('user_addresses_list').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erro ao carregar endereços: ${error.message}
                        </div>
                    `;
                });
        }
        
        // Load user orders
        function loadUserOrders(userId) {
            fetch(`get_user_orders.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('user_orders_list');
                    if (data.orders && data.orders.length > 0) {
                        let html = '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Pedido</th><th>Data</th><th>Itens</th><th>Total</th><th>Status</th><th>Ações</th></tr></thead><tbody>';
                        
                        data.orders.forEach(order => {
                            const statusColors = {
                                'pending': 'warning',
                                'confirmed': 'info',
                                'preparing': 'primary',
                                'delivering': 'info',
                                'delivered': 'success',
                                'cancelled': 'danger'
                            };
                            const statusLabels = {
                                'pending': 'Pendente',
                                'confirmed': 'Confirmado',
                                'preparing': 'Preparando',
                                'delivering': 'Em Entrega',
                                'delivered': 'Entregue',
                                'cancelled': 'Cancelado'
                            };
                            
                            html += `
                                <tr>
                                    <td><strong>#${order.id}</strong></td>
                                    <td>${new Date(order.created_at).toLocaleDateString('pt-BR')}</td>
                                    <td><span class="badge bg-secondary">${order.item_count} item(s)</span></td>
                                    <td><strong>R$ ${parseFloat(order.total_amount).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</strong></td>
                                    <td><span class="badge bg-${statusColors[order.status] || 'secondary'}">${statusLabels[order.status] || order.status}</span></td>
                                    <td>
                                        <a href="orders.php?order_id=${order.id}" class="btn btn-outline-primary btn-sm" title="Ver detalhes">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        html += '</tbody></table></div>';
                        
                        if (data.count > 10) {
                            html += `<div class="text-center mt-3"><small class="text-muted">Mostrando os 10 pedidos mais recentes de ${data.count} total(is)</small></div>`;
                        }
                        
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <h6>Nenhum pedido encontrado</h6>
                                <p class="text-muted">Este usuário ainda não fez nenhum pedido.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('user_orders_list').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erro ao carregar pedidos: ${error.message}
                        </div>
                    `;
                });
        }
        
        // Edit user function
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_email').value = user.email;
            
            // Format CPF for editing
            const cpfField = document.getElementById('edit_cpf');
            if (user.cpf && user.cpf.length === 11) {
                const formattedCpf = user.cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                cpfField.value = formattedCpf;
            } else {
                cpfField.value = user.cpf || '';
            }
            
            document.getElementById('edit_password').value = ''; // Always clear password
            document.getElementById('edit_gender').value = user.gender || '';
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            
            const modal = new mdb.Modal(document.getElementById('editUserModal'));
            modal.show();
        }
        
        // Delete user function
        function deleteUser(userId, userName) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_user_name').textContent = userName;
            
            const modal = new mdb.Modal(document.getElementById('deleteUserModal'));
            modal.show();
        }
        
        // Toggle user status function
        function toggleUserStatus(userId, userName, isActive) {
            document.getElementById('toggle_user_id').value = userId;
            document.getElementById('toggle_user_name').textContent = userName;
            document.getElementById('toggle_action').textContent = isActive ? 'desativar' : 'ativar';
            
            const modal = new mdb.Modal(document.getElementById('toggleStatusModal'));
            modal.show();
        }
        
        // Functions for actions from view modal
        function editUserFromView() {
            if (currentUser) {
                // Close view modal first
                const viewModal = mdb.Modal.getInstance(document.getElementById('viewUserModal'));
                viewModal.hide();
                
                // Open edit modal after a short delay
                setTimeout(() => {
                    editUser(currentUser);
                }, 300);
            }
        }
        
        function deleteUserFromView() {
            if (currentUser) {
                // Close view modal first
                const viewModal = mdb.Modal.getInstance(document.getElementById('viewUserModal'));
                viewModal.hide();
                
                // Open delete modal after a short delay
                setTimeout(() => {
                    deleteUser(currentUser.id, currentUser.name);
                }, 300);
            }
        }
        
        function toggleStatusFromView() {
            if (currentUser) {
                // Close view modal first
                const viewModal = mdb.Modal.getInstance(document.getElementById('viewUserModal'));
                viewModal.hide();
                
                // Open toggle status modal after a short delay
                setTimeout(() => {
                    toggleUserStatus(currentUser.id, currentUser.name, currentUser.is_active == 1);
                }, 300);
            }
        }
        
        // Address management functions
        function addAddressForUser() {
            if (currentUser) {
                const title = prompt('Título do endereço (ex: Casa, Trabalho):');
                if (title) {
                    const address = prompt('Endereço completo:');
                    if (address) {
                        // Here you would normally send to server
                        alert('Funcionalidade de adicionar endereço será implementada em breve.');
                    }
                }
            }
        }
        
        function editAddress(addressId) {
            alert(`Editar endereço ID: ${addressId} - Funcionalidade será implementada em breve.`);
        }
        
        function deleteAddress(addressId) {
            if (confirm('Tem certeza que deseja excluir este endereço?')) {
                alert(`Excluir endereço ID: ${addressId} - Funcionalidade será implementada em breve.`);
            }
        }
        
        // Reset user password
        function resetUserPassword() {
            if (currentUser) {
                const newPassword = prompt(`Resetar senha para ${currentUser.name}.\nDigite a nova senha (mínimo 6 caracteres):`);
                if (newPassword && newPassword.length >= 6) {
                    if (confirm(`Confirma o reset da senha para ${currentUser.name}?`)) {
                        // Create form and submit
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.style.display = 'none';
                        
                        const actionInput = document.createElement('input');
                        actionInput.name = 'action';
                        actionInput.value = 'edit_user';
                        
                        const userIdInput = document.createElement('input');
                        userIdInput.name = 'user_id';
                        userIdInput.value = currentUser.id;
                        
                        const nameInput = document.createElement('input');
                        nameInput.name = 'name';
                        nameInput.value = currentUser.name;
                        
                        const emailInput = document.createElement('input');
                        emailInput.name = 'email';
                        emailInput.value = currentUser.email;
                        
                        const passwordInput = document.createElement('input');
                        passwordInput.name = 'password';
                        passwordInput.value = newPassword;
                        
                        const activeInput = document.createElement('input');
                        activeInput.name = 'is_active';
                        activeInput.type = 'checkbox';
                        activeInput.checked = currentUser.is_active == 1;
                        
                        form.appendChild(actionInput);
                        form.appendChild(userIdInput);
                        form.appendChild(nameInput);
                        form.appendChild(emailInput);
                        form.appendChild(passwordInput);
                        if (currentUser.is_active == 1) {
                            form.appendChild(activeInput);
                        }
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                } else if (newPassword !== null) {
                    alert('A senha deve ter pelo menos 6 caracteres.');
                }
            }
        }
        
        // CPF formatting for admin forms
        function formatCPF(input) {
            let value = input.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            input.value = value;
        }
        
        // Clear forms when modals are closed
        document.addEventListener('DOMContentLoaded', function() {
            // Add CPF formatting to admin forms
            const editCpfField = document.getElementById('edit_cpf');
            const addCpfField = document.getElementById('add_cpf');
            
            if (editCpfField) {
                editCpfField.addEventListener('input', function() {
                    formatCPF(this);
                });
            }
            
            if (addCpfField) {
                addCpfField.addEventListener('input', function() {
                    formatCPF(this);
                });
            }
            
            const addUserModal = document.getElementById('addUserModal');
            addUserModal.addEventListener('hidden.mdb.modal', function() {
                // Clear form fields
                document.getElementById('add_name').value = '';
                document.getElementById('add_email').value = '';
                document.getElementById('add_cpf').value = '';
                document.getElementById('add_password').value = '';
                document.getElementById('add_gender').value = '';
                document.getElementById('add_is_active').checked = true;
            });
            
            const editUserModal = document.getElementById('editUserModal');
            editUserModal.addEventListener('hidden.mdb.modal', function() {
                // Clear password field
                document.getElementById('edit_password').value = '';
            });
            
            // Reset to first tab when modal opens
            const viewUserModal = document.getElementById('viewUserModal');
            viewUserModal.addEventListener('shown.mdb.modal', function() {
                // Activate first tab
                const firstTab = document.getElementById('personal-tab');
                const firstTabPane = document.getElementById('personal');
                
                // Remove active from all tabs
                document.querySelectorAll('#userDetailsTabs .nav-link').forEach(tab => {
                    tab.classList.remove('active');
                });
                document.querySelectorAll('#userDetailsTabContent .tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
                
                // Activate first tab
                firstTab.classList.add('active');
                firstTabPane.classList.add('show', 'active');
            });
        });
    </script>
</body>
</html>
