<?php
// Working User Login Page
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

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ../account/profile-working.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            // Check user credentials
            $stmt = $pdo->prepare("
                SELECT id, name, email, password, is_active, email_verified 
                FROM users 
                WHERE email = ? AND is_active = 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Update last login (optional)
                try {
                    $stmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                } catch (Exception $e) {
                    // Ignore update errors
                }
                
                // Set remember me cookie if requested
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                }
                
                // Redirect to profile or intended page
                $redirect = $_GET['redirect'] ?? '../home-fixed.php';
                header('Location: ' . $redirect);
                exit();
            } else {
                $error = 'Email ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $error = 'Erro no sistema. Tente novamente.';
        }
    }
}

$page_title = 'Entrar - Na Porta';
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
        }
        
        .auth-card {
            max-width: 400px;
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
                <div class="col-md-6 col-lg-5">
                    <div class="card auth-card shadow-lg border-0">
                        <div class="card-body p-5">
                            <!-- Logo -->
                            <div class="text-center mb-4">
                                <h2 class="text-primary mb-2">
                                    <i class="fas fa-home me-2"></i>Na Porta
                                </h2>
                                <p class="text-muted">Entre na sua conta</p>
                            </div>

                            <!-- Error/Success Messages -->
                            <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Login Form -->
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                               required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Senha</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                            <i class="fas fa-eye" id="toggleIcon"></i>
                                        </button>
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
                            </form>

                            <!-- Links -->
                            <div class="text-center">
                                <p class="mb-2">
                                    <a href="#" class="text-decoration-none">Esqueceu sua senha?</a>
                                </p>
                                <p class="mb-0">
                                    Não tem uma conta? 
                                    <a href="register-working.php" class="text-decoration-none fw-bold">Cadastre-se</a>
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
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
