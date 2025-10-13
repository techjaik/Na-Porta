<?php
// Working User Profile Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';

// Simple functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
if (!is_logged_in()) {
    header('Location: ../auth/login-working.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gender = $_POST['gender'] ?? '';
        
        if (empty($name) || empty($email)) {
            $error = 'Nome e email são obrigatórios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email inválido.';
        } else {
            try {
                // Check if email is already used by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                if ($stmt->fetchColumn()) {
                    $error = 'Este email já está sendo usado por outro usuário.';
                } else {
                    // Check if gender column exists
                    $has_gender_column = false;
                    try {
                        $check_stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'gender'");
                        $has_gender_column = $check_stmt->rowCount() > 0;
                    } catch (Exception $e) {
                        // Ignore error
                    }
                    
                    // Update user profile with or without gender
                    if ($has_gender_column) {
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET name = ?, email = ?, cpf = ?, phone = ?, gender = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $success_result = $stmt->execute([$name, $email, $cpf ?: null, $phone ?: null, $gender ?: null, $_SESSION['user_id']]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET name = ?, email = ?, cpf = ?, phone = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $success_result = $stmt->execute([$name, $email, $cpf ?: null, $phone ?: null, $_SESSION['user_id']]);
                    }
                    
                    if ($success_result) {
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $email;
                        if ($has_gender_column) {
                            $success = 'Perfil atualizado com sucesso!';
                        } else {
                            $success = 'Perfil atualizado com sucesso! (Nota: Para usar o campo gênero, execute o script de atualização do banco de dados)';
                        }
                    } else {
                        $error = 'Erro ao atualizar perfil.';
                    }
                }
            } catch (Exception $e) {
                $error = 'Erro no banco de dados: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Todos os campos de senha são obrigatórios.';
        } elseif (strlen($new_password) < 6) {
            $error = 'A nova senha deve ter pelo menos 6 caracteres.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'A confirmação da nova senha não confere.';
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $stored_password = $stmt->fetchColumn();
                
                if (!password_verify($current_password, $stored_password)) {
                    $error = 'Senha atual incorreta.';
                } else {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    
                    if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                        $success = 'Senha alterada com sucesso!';
                    } else {
                        $error = 'Erro ao alterar senha.';
                    }
                }
            } catch (Exception $e) {
                $error = 'Erro no banco de dados: ' . $e->getMessage();
            }
        }
    }
}

// Get user data
$user = null;
$has_gender_column = false;
try {
    // Check if gender column exists
    $check_stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'gender'");
    $has_gender_column = $check_stmt->rowCount() > 0;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: ../auth/login-working.php');
        exit();
    }
} catch (Exception $e) {
    $error = "Erro ao carregar perfil.";
}

$welcome = isset($_GET['welcome']);
$page_title = 'Meu Perfil - Na Porta';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1976d2;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), #1e88e5);
            color: white;
            padding: 60px 0;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border: 3px solid white;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
        }
        
        .btn-primary:hover {
            background: #1565c0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../home-fixed.php">
                <i class="fas fa-home me-2"></i>Na Porta
            </a>
            
            <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../home-fixed.php">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../products.php">Produtos</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>Carrinho (0)
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="profile-working.php">
                                <i class="fas fa-user me-2"></i>Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="orders-working.php">
                                <i class="fas fa-box me-2"></i>Pedidos
                            </a></li>
                            <li><a class="dropdown-item" href="addresses.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Endereços
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Welcome Message -->
    <?php if ($welcome): ?>
    <div class="alert alert-success alert-dismissible fade show m-0" role="alert">
        <div class="container">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Bem-vindo ao Na Porta!</strong> Sua conta foi criada com sucesso.
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Profile Header -->
    <section class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <div class="profile-avatar rounded-circle d-inline-flex align-items-center justify-content-center">
                        <i class="fas fa-user fa-3x"></i>
                    </div>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-2"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <p class="mb-1">
                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                        <?php if ($user['email_verified']): ?>
                        <i class="fas fa-check-circle ms-2" title="Email verificado"></i>
                        <?php else: ?>
                        <span class="badge bg-warning ms-2">Email não verificado</span>
                        <?php endif; ?>
                    </p>
                    <?php if ($user['phone']): ?>
                    <p class="mb-1">
                        <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($user['phone']); ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($has_gender_column && !empty($user['gender'])): ?>
                    <p class="mb-1">
                        <i class="fas fa-venus-mars me-2"></i>
                        <?php 
                        $gender_labels = [
                            'male' => 'Masculino',
                            'female' => 'Feminino', 
                            'other' => 'Outro',
                            'prefer_not_to_say' => 'Prefiro não dizer'
                        ];
                        echo $gender_labels[$user['gender']] ?? $user['gender'];
                        ?>
                    </p>
                    <?php endif; ?>
                    <p class="mb-0">
                        <i class="fas fa-calendar me-2"></i>Membro desde <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Profile Content -->
    <section class="py-5">
        <div class="container">
            <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!$has_gender_column): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Nova funcionalidade disponível!</strong> 
                Para ativar o campo de gênero, execute o script de atualização do banco de dados: 
                <a href="../../update-gender-field.php" class="alert-link">Clique aqui para atualizar</a>
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="row g-4">
                <!-- Quick Actions -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bolt me-2 text-primary"></i>Ações Rápidas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="../products-working.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>Fazer Pedido
                                </a>
                                <a href="orders-working.php" class="btn btn-outline-primary">
                                    <i class="fas fa-box me-2"></i>Meus Pedidos
                                </a>
                                <a href="addresses.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-map-marker-alt me-2"></i>Endereços
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user me-2 text-primary"></i>Informações da Conta
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Nome Completo</label>
                                        <p class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Email</label>
                                        <p class="fw-bold"><?php echo htmlspecialchars($user['email']); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">CPF</label>
                                        <p class="fw-bold"><?php echo $user['cpf'] ? htmlspecialchars($user['cpf']) : 'Não informado'; ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Telefone</label>
                                        <p class="fw-bold"><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Não informado'; ?></p>
                                    </div>
                                    <?php if ($has_gender_column): ?>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Gênero</label>
                                        <p class="fw-bold">
                                            <?php 
                                            if (!empty($user['gender'])) {
                                                $gender_labels = [
                                                    'male' => 'Masculino',
                                                    'female' => 'Feminino', 
                                                    'other' => 'Outro',
                                                    'prefer_not_to_say' => 'Prefiro não dizer'
                                                ];
                                                echo $gender_labels[$user['gender']] ?? $user['gender'];
                                            } else {
                                                echo 'Não informado';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary" data-mdb-toggle="modal" data-mdb-target="#editProfileModal">
                                    <i class="fas fa-edit me-2"></i>Editar Perfil
                                </button>
                                <button class="btn btn-outline-secondary" data-mdb-toggle="modal" data-mdb-target="#changePasswordModal">
                                    <i class="fas fa-key me-2"></i>Alterar Senha
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clock me-2 text-primary"></i>Atividade Recente
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h6>Nenhuma atividade recente</h6>
                                <p class="text-muted">Suas compras e atividades aparecerão aqui.</p>
                                <a href="../products-working.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>Fazer Primeiro Pedido
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-home me-2"></i>Na Porta</h5>
                    <p class="text-muted">Essenciais domésticos na sua porta</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">© 2024 Na Porta. Todos os direitos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Editar Perfil
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_name" class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_cpf" class="form-label">CPF</label>
                                <input type="text" class="form-control" id="edit_cpf" name="cpf" 
                                       value="<?php echo htmlspecialchars($user['cpf'] ?? ''); ?>" 
                                       placeholder="000.000.000-00" maxlength="14">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_phone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                       placeholder="(11) 99999-9999" maxlength="15">
                            </div>
                        </div>
                        
                        <?php if ($has_gender_column): ?>
                        <div class="mb-3">
                            <label for="edit_gender" class="form-label">Gênero</label>
                            <select class="form-select" id="edit_gender" name="gender">
                                <option value="">Selecione (opcional)</option>
                                <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Masculino</option>
                                <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Feminino</option>
                                <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Outro</option>
                                <option value="prefer_not_to_say" <?php echo ($user['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefiro não dizer</option>
                            </select>
                        </div>
                        <?php endif; ?>
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
    
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-key me-2"></i>Alterar Senha
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Senha Atual *</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nova Senha *</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   minlength="6" required>
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Nova Senha *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   minlength="6" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key me-2"></i>Alterar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // CPF mask
        document.getElementById('edit_cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });

        // Phone mask
        document.getElementById('edit_phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = e.target.value;
            
            if (newPassword !== confirmPassword) {
                e.target.setCustomValidity('As senhas não coincidem');
            } else {
                e.target.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
