<?php
/**
 * Na Porta - User Login Page
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
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        if ($auth->loginUser($email, $password)) {
            $redirect = $_GET['redirect'] ?? 'index.php';
            header('Location: ../' . $redirect);
            exit();
        } else {
            $error = 'Email ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Na Porta</title>
    
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
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-header h3 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .login-body {
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
        
        .btn-login {
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
        
        .btn-login:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            background: white;
            padding: 0 1rem;
            color: #666;
            font-size: 14px;
        }
        
        .social-login {
            display: flex;
            gap: 1rem;
        }
        
        .btn-social {
            flex: 1;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            color: #666;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-social:hover {
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
        }
        
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
    </style>
</head>
<body>
    <a href="../index.php" class="back-link">
        <i class="fas fa-arrow-left me-2"></i>Voltar ao Site
    </a>
    
    <div class="login-card">
        <div class="login-header">
            <h3>Bem-vindo de volta!</h3>
            <p class="mb-0">Entre na sua conta Na Porta</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" class="form-control" required 
                               placeholder="seu@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Senha</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" class="form-control" required 
                               placeholder="Sua senha">
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">
                            Lembrar de mim
                        </label>
                    </div>
                    <a href="#" class="text-decoration-none">Esqueceu a senha?</a>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Entrar
                </button>
            </form>
            
            <div class="divider">
                <span>ou</span>
            </div>
            
            <div class="text-center">
                <p class="mb-0">Não tem uma conta?</p>
                <a href="register.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" 
                   class="text-decoration-none fw-bold">
                    Cadastre-se gratuitamente
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
