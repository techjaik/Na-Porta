<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$page_title = 'Criar Conta';
$page_description = 'Crie sua conta Na Porta e tenha acesso a ofertas exclusivas';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(SITE_URL . '/pages/account/profile.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $cpf = sanitize_input($_POST['cpf'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    $newsletter = isset($_POST['newsletter']);
    
    // Validate CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Tente novamente.';
    } else {
        // Validate input
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'Por favor, preencha todos os campos obrigatórios.';
        } elseif (!validate_email($email)) {
            $error = 'Email inválido.';
        } elseif (!empty($cpf) && !validate_cpf($cpf)) {
            $error = 'CPF inválido.';
        } elseif (strlen($password) < 8) {
            $error = 'A senha deve ter pelo menos 8 caracteres.';
        } elseif ($password !== $confirm_password) {
            $error = 'As senhas não coincidem.';
        } elseif (!$terms) {
            $error = 'Você deve aceitar os termos de uso e política de privacidade.';
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error = 'Este email já está cadastrado.';
                } else {
                    // Check if CPF already exists (if provided)
                    if (!empty($cpf)) {
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE cpf = ?");
                        $stmt->execute([$cpf]);
                        
                        if ($stmt->fetch()) {
                            $error = 'Este CPF já está cadastrado.';
                        }
                    }
                    
                    if (!$error) {
                        // Create user account
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $verification_token = bin2hex(random_bytes(32));
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO users (name, email, cpf, phone, password, verification_token, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())
                        ");
                        
                        $cpf_clean = !empty($cpf) ? preg_replace('/[^0-9]/', '', $cpf) : null;
                        $phone_clean = !empty($phone) ? preg_replace('/[^0-9]/', '', $phone) : null;
                        
                        if ($stmt->execute([$name, $email, $cpf_clean, $phone_clean, $hashed_password, $verification_token])) {
                            $user_id = $pdo->lastInsertId();
                            
                            // Subscribe to newsletter if requested
                            if ($newsletter) {
                                try {
                                    $stmt = $pdo->prepare("
                                        INSERT IGNORE INTO newsletter_subscriptions (email, subscribed_at) 
                                        VALUES (?, NOW())
                                    ");
                                    $stmt->execute([$email]);
                                } catch (PDOException $e) {
                                    // Newsletter subscription failed, but don't stop registration
                                    error_log('Newsletter subscription error: ' . $e->getMessage());
                                }
                            }
                            
                            // Send verification email (in a real app)
                            // send_verification_email($email, $verification_token);
                            
                            // Auto-login for better UX (skip email verification for demo)
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['user_name'] = $name;
                            $_SESSION['user_email'] = $email;
                            
                            // Mark email as verified for demo
                            $stmt = $pdo->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
                            $stmt->execute([$user_id]);
                            
                            flash_message('success', 'Conta criada com sucesso! Bem-vindo(a) ao Na Porta, ' . $name . '!');
                            redirect(SITE_URL . '/pages/account/profile.php');
                        } else {
                            $error = 'Erro ao criar conta. Tente novamente.';
                        }
                    }
                }
            } catch (PDOException $e) {
                $error = 'Erro interno. Tente novamente mais tarde.';
                error_log('Registration error: ' . $e->getMessage());
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-custom">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">
                            <i class="fas fa-user-plus me-2"></i>Criar Conta
                        </h2>
                        <p class="text-muted">Junte-se ao Na Porta e tenha seus essenciais sempre em casa</p>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user me-1"></i>Nome Completo *
                                </label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                                       required autocomplete="name" maxlength="100">
                                <div class="invalid-feedback">
                                    Por favor, insira seu nome completo.
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email *
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                       required autocomplete="email" maxlength="100">
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
                                       value="<?php echo htmlspecialchars($cpf ?? ''); ?>" 
                                       placeholder="000.000.000-00" maxlength="14"
                                       oninput="NaPorta.formatCPF(this)">
                                <small class="form-text text-muted">Opcional, mas recomendado para melhor experiência</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Telefone
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($phone ?? ''); ?>" 
                                       placeholder="(11) 99999-9999" maxlength="15"
                                       oninput="NaPorta.formatPhone(this)">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Senha *
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           required autocomplete="new-password" minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                        <i class="fas fa-eye" id="password-toggle"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">Mínimo 8 caracteres</small>
                                <div class="invalid-feedback">
                                    A senha deve ter pelo menos 8 caracteres.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Confirmar Senha *
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           required autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye" id="confirm_password-toggle"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    As senhas devem coincidir.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                Eu aceito os <a href="../terms.php" target="_blank">Termos de Uso</a> e 
                                <a href="../privacy.php" target="_blank">Política de Privacidade</a> *
                            </label>
                            <div class="invalid-feedback">
                                Você deve aceitar os termos para continuar.
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
                            <label class="form-check-label" for="newsletter">
                                Quero receber ofertas e novidades por email
                            </label>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Criar Conta
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0">Já tem uma conta?</p>
                        <a href="login.php" class="btn btn-outline-primary mt-2">
                            <i class="fas fa-sign-in-alt me-2"></i>Fazer Login
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Benefits -->
            <div class="row mt-4 g-3">
                <div class="col-md-4 text-center">
                    <div class="text-primary mb-2">
                        <i class="fas fa-truck fa-2x"></i>
                    </div>
                    <h6 class="fw-bold">Entrega Rápida</h6>
                    <small class="text-muted">Receba em casa com segurança</small>
                </div>
                <div class="col-md-4 text-center">
                    <div class="text-success mb-2">
                        <i class="fas fa-credit-card fa-2x"></i>
                    </div>
                    <h6 class="fw-bold">PIX Instantâneo</h6>
                    <small class="text-muted">Pagamento rápido e seguro</small>
                </div>
                <div class="col-md-4 text-center">
                    <div class="text-info mb-2">
                        <i class="fas fa-headset fa-2x"></i>
                    </div>
                    <h6 class="fw-bold">Suporte 24/7</h6>
                    <small class="text-muted">Estamos sempre aqui para ajudar</small>
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
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('As senhas não coincidem');
    } else {
        this.setCustomValidity('');
    }
});

// Auto-focus on name field
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('name').focus();
});
</script>

<?php include '../../includes/footer.php'; ?>
