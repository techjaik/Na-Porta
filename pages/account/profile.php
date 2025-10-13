<?php
// Redirect to working version
$query_string = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: profile-working.php' . $query_string);
exit();

$error = '';
$success = '';

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        flash_message('error', 'Usuário não encontrado.');
        redirect(SITE_URL . '/pages/auth/logout.php');
    }
} catch (PDOException $e) {
    error_log('Profile fetch error: ' . $e->getMessage());
    $error = 'Erro ao carregar perfil.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Tente novamente.';
    } else {
        if ($action === 'update_profile') {
            $name = sanitize_input($_POST['name'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $cpf = sanitize_input($_POST['cpf'] ?? '');
            $phone = sanitize_input($_POST['phone'] ?? '');
            $birth_date = $_POST['birth_date'] ?? '';
            
            // Validate input
            if (empty($name) || empty($email)) {
                $error = 'Nome e email são obrigatórios.';
            } elseif (!validate_email($email)) {
                $error = 'Email inválido.';
            } elseif (!empty($cpf) && !validate_cpf($cpf)) {
                $error = 'CPF inválido.';
            } else {
                try {
                    // Check if email is already used by another user
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $_SESSION['user_id']]);
                    
                    if ($stmt->fetch()) {
                        $error = 'Este email já está sendo usado por outro usuário.';
                    } else {
                        // Check if CPF is already used by another user
                        if (!empty($cpf)) {
                            $cpf_clean = preg_replace('/[^0-9]/', '', $cpf);
                            $stmt = $pdo->prepare("SELECT id FROM users WHERE cpf = ? AND id != ?");
                            $stmt->execute([$cpf_clean, $_SESSION['user_id']]);
                            
                            if ($stmt->fetch()) {
                                $error = 'Este CPF já está sendo usado por outro usuário.';
                            }
                        }
                        
                        if (!$error) {
                            // Update user profile
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET name = ?, email = ?, cpf = ?, phone = ?, birth_date = ?, updated_at = NOW()
                                WHERE id = ?
                            ");
                            
                            $cpf_clean = !empty($cpf) ? preg_replace('/[^0-9]/', '', $cpf) : null;
                            $phone_clean = !empty($phone) ? preg_replace('/[^0-9]/', '', $phone) : null;
                            $birth_date_clean = !empty($birth_date) ? $birth_date : null;
                            
                            if ($stmt->execute([$name, $email, $cpf_clean, $phone_clean, $birth_date_clean, $_SESSION['user_id']])) {
                                // Update session data
                                $_SESSION['user_name'] = $name;
                                $_SESSION['user_email'] = $email;
                                
                                // Refresh user data
                                $user['name'] = $name;
                                $user['email'] = $email;
                                $user['cpf'] = $cpf_clean;
                                $user['phone'] = $phone_clean;
                                $user['birth_date'] = $birth_date_clean;
                                
                                $success = 'Perfil atualizado com sucesso!';
                            } else {
                                $error = 'Erro ao atualizar perfil. Tente novamente.';
                            }
                        }
                    }
                } catch (PDOException $e) {
                    $error = 'Erro interno. Tente novamente mais tarde.';
                    error_log('Profile update error: ' . $e->getMessage());
                }
            }
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate input
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = 'Todos os campos de senha são obrigatórios.';
            } elseif (strlen($new_password) < 8) {
                $error = 'A nova senha deve ter pelo menos 8 caracteres.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'A confirmação da nova senha não confere.';
            } elseif (!password_verify($current_password, $user['password'])) {
                $error = 'Senha atual incorreta.';
            } else {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    
                    if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                        $success = 'Senha alterada com sucesso!';
                    } else {
                        $error = 'Erro ao alterar senha. Tente novamente.';
                    }
                } catch (PDOException $e) {
                    $error = 'Erro interno. Tente novamente mais tarde.';
                    error_log('Password change error: ' . $e->getMessage());
                }
            }
        }
    }
}

// Get user statistics
$stats = [
    'total_orders' => 0,
    'total_spent' => 0,
    'pending_orders' => 0
];

try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_spent,
            SUM(CASE WHEN status IN ('pending', 'processing') THEN 1 ELSE 0 END) as pending_orders
        FROM orders 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $stats = $result;
    }
} catch (PDOException $e) {
    error_log('Stats fetch error: ' . $e->getMessage());
}

include '../../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../home.php">Início</a></li>
            <li class="breadcrumb-item active">Meu Perfil</li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">
                <i class="fas fa-user text-primary me-2"></i>
                Meu Perfil
            </h1>
            <p class="text-muted">Gerencie suas informações pessoais e configurações</p>
        </div>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Profile Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                    <small class="text-muted">
                        Membro desde <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                    </small>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Estatísticas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-12">
                            <div class="border-bottom pb-2 mb-2">
                                <h4 class="text-primary mb-0"><?php echo $stats['total_orders']; ?></h4>
                                <small class="text-muted">Pedidos Realizados</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border-bottom pb-2 mb-2">
                                <h4 class="text-success mb-0"><?php echo format_currency($stats['total_spent']); ?></h4>
                                <small class="text-muted">Total Gasto</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <h4 class="text-warning mb-0"><?php echo $stats['pending_orders']; ?></h4>
                            <small class="text-muted">Pedidos Pendentes</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="fas fa-link me-2"></i>Links Rápidos
                    </h6>
                    <div class="d-grid gap-2">
                        <a href="orders.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-box me-2"></i>Meus Pedidos
                        </a>
                        <a href="addresses.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-map-marker-alt me-2"></i>Endereços
                        </a>
                        <a href="../products.php" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-shopping-cart me-2"></i>Continuar Comprando
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Content -->
        <div class="col-lg-9">
            <!-- Profile Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>Informações Pessoais
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user me-1"></i>Nome Completo *
                                </label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" 
                                       required maxlength="100">
                                <div class="invalid-feedback">
                                    Por favor, insira seu nome completo.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email *
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       required maxlength="100">
                                <div class="invalid-feedback">
                                    Por favor, insira um email válido.
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cpf" class="form-label">
                                    <i class="fas fa-id-card me-1"></i>CPF
                                </label>
                                <input type="text" class="form-control" id="cpf" name="cpf" 
                                       value="<?php echo $user['cpf'] ? format_cpf($user['cpf']) : ''; ?>" 
                                       placeholder="000.000.000-00" maxlength="14"
                                       oninput="NaPorta.formatCPF(this)">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Telefone
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo $user['phone'] ? format_phone($user['phone']) : ''; ?>" 
                                       placeholder="(11) 99999-9999" maxlength="15"
                                       oninput="NaPorta.formatPhone(this)">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="birth_date" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Data de Nascimento
                                </label>
                                <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                       value="<?php echo $user['birth_date']; ?>">
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-lock me-2"></i>Alterar Senha
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="current_password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Senha Atual *
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" 
                                           name="current_password" required>
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="togglePassword('current_password')">
                                        <i class="fas fa-eye" id="current_password-toggle"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, insira sua senha atual.
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">
                                    <i class="fas fa-key me-1"></i>Nova Senha *
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="togglePassword('new_password')">
                                        <i class="fas fa-eye" id="new_password-toggle"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">Mínimo 8 caracteres</small>
                                <div class="invalid-feedback">
                                    A nova senha deve ter pelo menos 8 caracteres.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-key me-1"></i>Confirmar Nova Senha *
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required>
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye" id="confirm_password-toggle"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    As senhas devem coincidir.
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Alterar Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggle = document.getElementById(fieldId + '-toggle');
    
    if (field.type === 'password') {
        field.type = 'text';
        toggle.classList.remove('fa-eye');
        toggle.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        toggle.classList.remove('fa-eye-slash');
        toggle.classList.add('fa-eye');
    }
}

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('As senhas não coincidem');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
