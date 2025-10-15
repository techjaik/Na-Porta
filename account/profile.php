<?php
/**
 * Na Porta - User Profile Page
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$auth = new Auth();
$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Redirect to login if not authenticated
if (!$user) {
    header('Location: ../auth/login.php?redirect=account/profile.php');
    exit();
}

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $cpf_cnpj = trim($_POST['cpf_cnpj'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    
    if (empty($name)) {
        $error = 'Nome é obrigatório.';
    } else {
        try {
            // First, check which columns exist in the users table
            $pdo = $db->getConnection();
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $existingColumns = array_column($columns, 'Field');
            
            // Build dynamic query based on existing columns
            $updateFields = ['name = ?'];
            $updateValues = [$name];
            
            // Add optional fields only if they exist in the table
            $optionalFields = [
                'phone' => $phone,
                'cpf_cnpj' => $cpf_cnpj,
                'gender' => $gender,
                'birth_date' => $birth_date ?: null,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zip_code' => $zip_code
            ];
            
            foreach ($optionalFields as $field => $value) {
                if (in_array($field, $existingColumns)) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $value;
                }
            }
            
            // Add updated_at if it exists
            if (in_array('updated_at', $existingColumns)) {
                $updateFields[] = "updated_at = NOW()";
            }
            
            // Add user ID for WHERE clause
            $updateValues[] = $user['id'];
            
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $db->query($sql, $updateValues);
            $success = 'Perfil atualizado com sucesso!';
            
            // Update session data
            $_SESSION['user_name'] = $name;
            if (in_array('phone', $existingColumns)) {
                $_SESSION['user_phone'] = $phone;
            }
            
            // Refresh user data
            $user = $auth->getCurrentUser();
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error = 'Erro ao atualizar perfil. Por favor, tente novamente. Se o problema persistir, entre em contato com o suporte.';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Todos os campos de senha são obrigatórios.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'As senhas não coincidem.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'A nova senha deve ter pelo menos 6 caracteres.';
    } else {
        try {
            // Verify current password
            $userData = $db->fetch("SELECT password FROM users WHERE id = ?", [$user['id']]);
            if (!$userData) {
                $error = 'Usuário não encontrado.';
            } elseif (!password_verify($currentPassword, $userData['password'])) {
                $error = 'Senha atual incorreta.';
            } else {
                // Update password with dynamic query
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Check if updated_at column exists
                $pdo = $db->getConnection();
                $stmt = $pdo->query("DESCRIBE users");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $existingColumns = array_column($columns, 'Field');
                
                if (in_array('updated_at', $existingColumns)) {
                    $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
                } else {
                    $sql = "UPDATE users SET password = ? WHERE id = ?";
                }
                
                $db->query($sql, [$hashedPassword, $user['id']]);
                $success = 'Senha alterada com sucesso!';
            }
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            $error = 'Erro ao alterar senha. Por favor, tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Na Porta</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --gray-50: #f9fafb;
            --gray-800: #1f2937;
            --border-radius: 12px;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-800);
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .profile-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 3rem 0;
        }
        
        .page-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-home me-2"></i>Na Porta
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../products.php">Produtos</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="../cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning" id="cart-count">
                                0
                            </span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($user['name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="profile.php">Meu Perfil</a></li>
                            <li><a class="dropdown-item" href="orders.php">Meus Pedidos</a></li>
                            <li><a class="dropdown-item" href="../account.php">Minha Conta</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php">Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title mb-2">Meu Perfil</h1>
                    <p class="lead mb-0">Gerencie suas informações pessoais</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Profile Content -->
    <section class="py-5">
        <div class="container">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-6 mb-4">
                    <div class="profile-card p-4">
                        <h5 class="mb-4">
                            <i class="fas fa-user me-2"></i>Informações Pessoais
                        </h5>
                        
                        <form method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nome Completo *</label>
                                    <input type="text" name="name" class="form-control" required 
                                           value="<?= htmlspecialchars($user['name']) ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" readonly 
                                           value="<?= htmlspecialchars($user['email']) ?>">
                                    <small class="text-muted">O email não pode ser alterado</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Telefone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                           placeholder="(11) 99999-9999">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">CPF/CNPJ</label>
                                    <input type="text" name="cpf_cnpj" class="form-control" 
                                           value="<?= htmlspecialchars($user['cpf_cnpj'] ?? '') ?>"
                                           placeholder="000.000.000-00">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gênero</label>
                                    <select name="gender" class="form-control">
                                        <option value="">Selecione...</option>
                                        <option value="masculino" <?= ($user['gender'] ?? '') === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                                        <option value="feminino" <?= ($user['gender'] ?? '') === 'feminino' ? 'selected' : '' ?>>Feminino</option>
                                        <option value="outro" <?= ($user['gender'] ?? '') === 'outro' ? 'selected' : '' ?>>Outro</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Data de Nascimento</label>
                                    <input type="date" name="birth_date" class="form-control" 
                                           value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Endereço Completo</label>
                                <textarea name="address" class="form-control" rows="2" 
                                          placeholder="Rua, número, complemento"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Cidade</label>
                                    <input type="text" name="city" class="form-control" 
                                           value="<?= htmlspecialchars($user['city'] ?? '') ?>"
                                           placeholder="São Paulo">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Estado</label>
                                    <input type="text" name="state" class="form-control" 
                                           value="<?= htmlspecialchars($user['state'] ?? '') ?>"
                                           placeholder="SP">
                                </div>
                                
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">CEP</label>
                                    <input type="text" name="zip_code" class="form-control" 
                                           value="<?= htmlspecialchars($user['zip_code'] ?? '') ?>"
                                           placeholder="00000-000">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Salvar Alterações
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="col-lg-6 mb-4">
                    <div class="profile-card p-4">
                        <h5 class="mb-4">
                            <i class="fas fa-lock me-2"></i>Alterar Senha
                        </h5>
                        
                        <form method="POST">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label">Senha Atual *</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nova Senha *</label>
                                <input type="password" name="new_password" class="form-control" required
                                       minlength="6" id="newPassword">
                                <small class="text-muted">Mínimo 6 caracteres</small>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Confirmar Nova Senha *</label>
                                <input type="password" name="confirm_password" class="form-control" required
                                       id="confirmPassword">
                                <div id="passwordMatch" class="mt-1"></div>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Alterar Senha
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Account Statistics -->
            <div class="row">
                <div class="col-12">
                    <div class="profile-card p-4">
                        <h5 class="mb-4">
                            <i class="fas fa-chart-bar me-2"></i>Estatísticas da Conta
                        </h5>
                        
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="p-3">
                                    <i class="fas fa-shopping-bag fa-2x text-primary mb-2"></i>
                                    <h4 class="mb-0">0</h4>
                                    <small class="text-muted">Total de Pedidos</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3">
                                    <i class="fas fa-heart fa-2x text-danger mb-2"></i>
                                    <h4 class="mb-0">0</h4>
                                    <small class="text-muted">Favoritos</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3">
                                    <i class="fas fa-star fa-2x text-warning mb-2"></i>
                                    <h4 class="mb-0">5.0</h4>
                                    <small class="text-muted">Avaliação</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3">
                                    <i class="fas fa-calendar fa-2x text-success mb-2"></i>
                                    <h4 class="mb-0"><?= isset($user['created_at']) && $user['created_at'] ? date('d/m/Y', strtotime($user['created_at'])) : date('d/m/Y') ?></h4>
                                    <small class="text-muted">Membro desde</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password match checker
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = this.value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (newPassword === confirmPassword) {
                matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>Senhas coincidem</small>';
            } else {
                matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Senhas não coincidem</small>';
            }
        });
        
        // Phone mask
        document.querySelector('input[name="phone"]').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 7) {
                value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            }
            this.value = value;
        });
        
        // CPF/CNPJ mask
        document.querySelector('input[name="cpf_cnpj"]').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length <= 11) {
                // CPF format
                if (value.length >= 9) {
                    value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2})/, '$1.$2.$3-$4');
                } else if (value.length >= 6) {
                    value = value.replace(/(\d{3})(\d{3})(\d{0,3})/, '$1.$2.$3');
                } else if (value.length >= 3) {
                    value = value.replace(/(\d{3})(\d{0,3})/, '$1.$2');
                }
            } else {
                // CNPJ format
                if (value.length >= 12) {
                    value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2})/, '$1.$2.$3/$4-$5');
                } else if (value.length >= 8) {
                    value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{0,4})/, '$1.$2.$3/$4');
                } else if (value.length >= 5) {
                    value = value.replace(/(\d{2})(\d{3})(\d{0,3})/, '$1.$2.$3');
                } else if (value.length >= 2) {
                    value = value.replace(/(\d{2})(\d{0,3})/, '$1.$2');
                }
            }
            this.value = value;
        });
        
        // CEP mask
        document.querySelector('input[name="zip_code"]').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 5) {
                value = value.replace(/(\d{5})(\d{0,3})/, '$1-$2');
            }
            this.value = value;
        });
        
        // Update cart count
        function updateCartCount() {
            fetch('../api/cart.php?action=count')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-count').textContent = data.count || 0;
                });
        }
        
        // Load cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>
