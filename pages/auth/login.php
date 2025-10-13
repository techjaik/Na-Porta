<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$page_title = 'Entrar';
$page_description = 'Faça login na sua conta Na Porta';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(SITE_URL . '/pages/account/profile.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Tente novamente.';
    } else {
        // Validate input
        if (empty($email) || empty($password)) {
            $error = 'Por favor, preencha todos os campos.';
        } elseif (!validate_email($email)) {
            $error = 'Email inválido.';
        } else {
            try {
                // Check user credentials
                $stmt = $pdo->prepare("
                    SELECT id, name, email, password, is_active, email_verified 
                    FROM users 
                    WHERE email = ?
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    if (!$user['is_active']) {
                        $error = 'Sua conta está desativada. Entre em contato conosco.';
                    } elseif (!$user['email_verified']) {
                        $error = 'Por favor, verifique seu email antes de fazer login.';
                    } else {
                        // Login successful
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        
                        // Set remember me cookie
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                            
                            // Store token in database (you'd need a remember_tokens table)
                        }
                        
                        // Merge cart if user had items before login
                        $session_id = session_id();
                        $stmt = $pdo->prepare("
                            UPDATE cart_items 
                            SET user_id = ?, session_id = NULL 
                            WHERE session_id = ?
                        ");
                        $stmt->execute([$user['id'], $session_id]);
                        
                        // Redirect to intended page or profile
                        $redirect_url = $_SESSION['redirect_after_login'] ?? SITE_URL . '/pages/account/profile.php';
                        unset($_SESSION['redirect_after_login']);
                        
                        flash_message('success', 'Login realizado com sucesso! Bem-vindo(a), ' . $user['name'] . '!');
                        redirect($redirect_url);
                    }
                } else {
                    $error = 'Email ou senha incorretos.';
                }
            } catch (PDOException $e) {
                $error = 'Erro interno. Tente novamente mais tarde.';
                error_log('Login error: ' . $e->getMessage());
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-custom">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Entrar
                        </h2>
                        <p class="text-muted">Acesse sua conta Na Porta</p>
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
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Email
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                   required autocomplete="email">
                            <div class="invalid-feedback">
                                Por favor, insira um email válido.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Senha
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" 
                                       required autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password-toggle"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Por favor, insira sua senha.
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Lembrar de mim
                            </label>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Entrar
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <a href="forgot-password.php" class="text-decoration-none">
                                <i class="fas fa-key me-1"></i>Esqueci minha senha
                            </a>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0">Ainda não tem uma conta?</p>
                        <a href="register.php" class="btn btn-outline-primary mt-2">
                            <i class="fas fa-user-plus me-2"></i>Criar Conta
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Quick Access -->
            <div class="text-center mt-4">
                <p class="text-muted mb-2">Acesso rápido:</p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="../products.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-shopping-cart me-1"></i>Continuar Comprando
                    </a>
                    <a href="../contact.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-headset me-1"></i>Suporte
                    </a>
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

// Auto-focus on email field
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('email').focus();
});
</script>

<?php include '../../includes/footer.php'; ?>
