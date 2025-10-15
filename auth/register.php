<?php
/**
 * Na Porta - User Registration Page
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->getCurrentUser()) {
    $redirect = $_GET['redirect'] ?? 'index.php';
    header('Location: ../' . $redirect);
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $cpf_cnpj = trim($_POST['cpf_cnpj'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um email válido.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($password !== $confirmPassword) {
        $error = 'As senhas não coincidem.';
    } else {
        // Try to register user
        if ($auth->registerUser($name, $email, $password, $phone, $cpf_cnpj, $gender)) {
            $success = 'Conta criada com sucesso! Você já está logado.';
            // Auto-login after registration
            $redirect = $_GET['redirect'] ?? 'index.php';
            header('refresh:2;url=../' . $redirect);
        } else {
            $error = 'Este email já está em uso. Tente fazer login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Na Porta</title>
    
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
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .register-header h3 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .register-body {
            padding: 2rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            color: white;
        }
        
        .btn-register:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
            color: white;
        }
        
        .password-strength {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 18px;
        }
        
        .back-link:hover {
            color: white;
        }
        
        .terms-text {
            font-size: 12px;
            color: #666;
            line-height: 1.4;
        }
        
        .form-check-custom {
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
            background-color: #fff;
        }
        
        .form-check-custom:hover {
            border-color: var(--primary-color);
            background-color: #f8f9ff;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .form-check-custom input[type="radio"]:checked + .form-check-label {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .form-check-custom:has(input[type="radio"]:checked) {
            border-color: var(--primary-color);
            background-color: #f0f4ff;
        }
        
        .form-text {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .gender-section {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <a href="../index.php" class="back-link">
        <i class="fas fa-arrow-left me-2"></i>Voltar ao Site
    </a>
    
    <div class="register-card">
        <div class="register-header">
            <h3>Crie sua conta</h3>
            <p class="mb-0">Junte-se à Na Porta e comece a comprar</p>
        </div>
        
        <div class="register-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <br><small>Redirecionando...</small>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label">Nome Completo *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" name="name" class="form-control" required 
                                   placeholder="Seu nome completo" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label">Email *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" name="email" class="form-control" required 
                                   placeholder="seu@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Telefone</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-phone"></i>
                            </span>
                            <input type="tel" name="phone" class="form-control" 
                                   placeholder="(11) 99999-9999" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">CPF/CNPJ</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-id-card"></i>
                            </span>
                            <input type="text" name="cpf_cnpj" class="form-control" 
                                   placeholder="000.000.000-00" value="<?= htmlspecialchars($_POST['cpf_cnpj'] ?? '') ?>">
                        </div>
                        <small class="form-text text-muted">CPF para pessoa física ou CNPJ para empresa</small>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="gender-section">
                        <label class="form-label mb-3 fw-semibold">Gênero</label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-check-custom">
                                    <input class="form-check-input" type="radio" name="gender" value="masculino" 
                                           id="masculino" <?= ($_POST['gender'] ?? '') === 'masculino' ? 'checked' : '' ?>>
                                    <label class="form-check-label d-flex align-items-center justify-content-center text-center" for="masculino">
                                        <div>
                                            <i class="fas fa-mars fa-2x mb-2 text-primary d-block"></i>
                                            <span class="fw-medium">Masculino</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-check-custom">
                                    <input class="form-check-input" type="radio" name="gender" value="feminino" 
                                           id="feminino" <?= ($_POST['gender'] ?? '') === 'feminino' ? 'checked' : '' ?>>
                                    <label class="form-check-label d-flex align-items-center justify-content-center text-center" for="feminino">
                                        <div>
                                            <i class="fas fa-venus fa-2x mb-2 text-danger d-block"></i>
                                            <span class="fw-medium">Feminino</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-check-custom">
                                    <input class="form-check-input" type="radio" name="gender" value="outro" 
                                           id="outro" <?= ($_POST['gender'] ?? '') === 'outro' ? 'checked' : '' ?>>
                                    <label class="form-check-label d-flex align-items-center justify-content-center text-center" for="outro">
                                        <div>
                                            <i class="fas fa-genderless fa-2x mb-2 text-secondary d-block"></i>
                                            <span class="fw-medium">Outro</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label">Senha *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="password" class="form-control" required 
                                   placeholder="Mínimo 6 caracteres" id="password">
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label">Confirmar Senha *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="confirm_password" class="form-control" required 
                                   placeholder="Digite a senha novamente" id="confirmPassword">
                        </div>
                        <div id="passwordMatch" class="mt-1"></div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label terms-text" for="terms">
                            Eu concordo com os <a href="#" class="text-decoration-none">Termos de Uso</a> 
                            e <a href="#" class="text-decoration-none">Política de Privacidade</a>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-register" id="submitBtn">
                    <i class="fas fa-user-plus me-2"></i>Criar Conta
                </button>
            </form>
            
            <div class="text-center mt-4">
                <p class="mb-0">Já tem uma conta?</p>
                <a href="login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" 
                   class="text-decoration-none fw-bold">
                    Faça login aqui
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 6) strength++;
            else feedback.push('pelo menos 6 caracteres');
            
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            let strengthText = '';
            let strengthClass = '';
            
            if (strength < 2) {
                strengthText = 'Fraca';
                strengthClass = 'strength-weak';
            } else if (strength < 4) {
                strengthText = 'Média';
                strengthClass = 'strength-medium';
            } else {
                strengthText = 'Forte';
                strengthClass = 'strength-strong';
            }
            
            strengthDiv.innerHTML = `<span class="${strengthClass}">Força da senha: ${strengthText}</span>`;
        });
        
        // Password match checker
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
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
    </script>
</body>
</html>
