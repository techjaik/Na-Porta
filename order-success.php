<?php
/**
 * Na Porta - Order Success Page
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$auth = new Auth();
$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Redirect to login if not authenticated
if (!$user) {
    header('Location: auth/login.php');
    exit();
}

$orderId = intval($_GET['order'] ?? 0);
$order = null;

if ($orderId > 0) {
    try {
        $order = $db->fetch("
            SELECT * FROM orders 
            WHERE id = ? AND user_id = ?
        ", [$orderId, $user['id']]);
    } catch (Exception $e) {
        error_log("Order success error: " . $e->getMessage());
    }
}

if (!$order) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - Na Porta</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --gray-50: #f9fafb;
            --gray-800: #1f2937;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-800);
        }
        
        .success-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            width: 100%;
            margin: 2rem;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background: var(--success-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .order-details {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #4f46e5);
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 0.75rem 2rem;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            font-weight: 500;
            padding: 0.75rem 2rem;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check fa-3x text-white"></i>
            </div>
            
            <h2 class="mb-3" style="font-family: 'Poppins', sans-serif; font-weight: 700; color: var(--success-color);">
                Pedido Confirmado!
            </h2>
            
            <p class="lead text-muted mb-4">
                Seu pedido foi recebido com sucesso e está sendo preparado para entrega.
            </p>
            
            <div class="order-details">
                <div class="row">
                    <div class="col-6">
                        <strong>Número do Pedido:</strong><br>
                        <span class="text-primary">#<?= $order['id'] ?></span>
                    </div>
                    <div class="col-6">
                        <strong>Total:</strong><br>
                        <span class="text-success">R$ <?= number_format($order['total_amount'], 2, ',', '.') ?></span>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="row">
                    <div class="col-6">
                        <strong>Status:</strong><br>
                        <span class="badge bg-warning">Pendente</span>
                    </div>
                    <div class="col-6">
                        <strong>Pagamento:</strong><br>
                        <span class="text-capitalize"><?= htmlspecialchars($order['payment_method']) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Próximos Passos:</strong><br>
                Você receberá uma ligação em breve para confirmar o endereço e horário de entrega.
            </div>
            
            <div class="d-flex gap-3 justify-content-center">
                <a href="account.php" class="btn btn-outline-primary">
                    <i class="fas fa-user me-2"></i>Minha Conta
                </a>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i>Continuar Comprando
                </a>
            </div>
            
            <div class="mt-4">
                <a href="index.php" class="text-decoration-none text-muted">
                    <i class="fas fa-home me-1"></i>Voltar ao Início
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Confetti Animation -->
    <script>
        // Simple confetti effect
        function createConfetti() {
            const colors = ['#6366f1', '#8b5cf6', '#10b981', '#f59e0b'];
            
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.style.cssText = `
                        position: fixed;
                        width: 10px;
                        height: 10px;
                        background: ${colors[Math.floor(Math.random() * colors.length)]};
                        left: ${Math.random() * 100}vw;
                        top: -10px;
                        border-radius: 50%;
                        pointer-events: none;
                        z-index: 9999;
                        animation: fall ${2 + Math.random() * 3}s linear forwards;
                    `;
                    
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => confetti.remove(), 5000);
                }, i * 100);
            }
        }
        
        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Start confetti on page load
        document.addEventListener('DOMContentLoaded', createConfetti);
    </script>
</body>
</html>
