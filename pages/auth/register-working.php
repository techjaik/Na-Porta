<?php
// Working User Registration Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';

// Simple functions
function sanitize_input($data) {
    return htmlspecialchars(trim(stripslashes($data)));
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function validate_cpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Check for known invalid CPFs
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    // Validate CPF algorithm
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ../account/profile-working.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $gender = sanitize_input($_POST['gender'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($password !== $confirm_password) {
        $error = 'As senhas não coincidem.';
    } elseif ($cpf && !validate_cpf($cpf)) {
        $error = 'CPF inválido.';
    } elseif (!$terms) {
        $error = 'Você deve aceitar os termos de uso.';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Este email já está cadastrado.';
            } else {
                // Check if CPF already exists (if provided)
                if ($cpf) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE cpf = ?");
                    $stmt->execute([$cpf]);
                    
                    if ($stmt->fetch()) {
                        $error = 'Este CPF já está cadastrado.';
                    }
                }
                
                if (!$error) {
                    // Create user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, email, password, cpf, phone, gender, is_active, email_verified, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, 1, 0, NOW())
                    ");
                    
                    if ($stmt->execute([$name, $email, $hashed_password, $cpf ?: null, $phone ?: null, $gender ?: null])) {
                        $user_id = $pdo->lastInsertId();
                        
                        // Auto login
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $email;
                        
                        // Redirect to working profile
                        header('Location: ../account/profile-working.php?welcome=1');
                        exit();
                    } else {
                        $error = 'Erro ao criar conta. Tente novamente.';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Erro no sistema. Tente novamente.';
        }
    }
}

$page_title = 'Cadastrar - Na Porta';
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
        
        .auth-container {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), #1e88e5);
            padding: 20px 0;
        }
        
        .auth-card {
            max-width: 500px;
            margin: 0 auto;
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
    <div class="auth-container d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card auth-card shadow-lg border-0">
                        <div class="card-body p-5">
                            <!-- Logo -->
                            <div class="text-center mb-4">
                                <h2 class="text-primary mb-2">
                                    <i class="fas fa-home me-2"></i>Na Porta
                                </h2>
                                <p class="text-muted">Crie sua conta</p>
                            </div>

                            <!-- Error/Success Messages -->
                            <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Registration Form -->
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Nome Completo *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                                                   required>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                                   required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="cpf" class="form-label">CPF</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-id-card"></i>
                                            </span>
                                            <input type="text" class="form-control" id="cpf" name="cpf" 
                                                   value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>" 
                                                   placeholder="000.000.000-00" maxlength="14">
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Telefone</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-phone"></i>
                                            </span>
                                            <input type="text" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                                   placeholder="(11) 99999-9999" maxlength="15">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="gender" class="form-label">Gênero</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-venus-mars"></i>
                                        </span>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="">Selecione (opcional)</option>
                                            <option value="male" <?php echo ($_POST['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Masculino</option>
                                            <option value="female" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Feminino</option>
                                            <option value="other" <?php echo ($_POST['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Outro</option>
                                            <option value="prefer_not_to_say" <?php echo ($_POST['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefiro não dizer</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Senha *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   minlength="6" required>
                                        </div>
                                        <small class="text-muted">Mínimo 6 caracteres</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">Confirmar Senha *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password" minlength="6" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        Aceito os <a href="#" class="text-decoration-none">Termos de Uso</a> 
                                        e <a href="#" class="text-decoration-none">Política de Privacidade</a>
                                    </label>
                                </div>

                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-user-plus me-2"></i>Criar Conta
                                    </button>
                                </div>
                            </form>

                            <!-- Links -->
                            <div class="text-center">
                                <p class="mb-0">
                                    Já tem uma conta? 
                                    <a href="login-working.php" class="text-decoration-none fw-bold">Entrar</a>
                                </p>
                                <hr class="my-3">
                                <a href="../home-fixed.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Voltar ao Site
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // CPF mask
        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });

        // Phone mask
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = e.target.value;
            
            if (password !== confirmPassword) {
                e.target.setCustomValidity('As senhas não coincidem');
            } else {
                e.target.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
