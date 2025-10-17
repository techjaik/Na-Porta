<?php
/**
 * Direct Checkout - Minimal Implementation
 * This bypasses all complex logic and directly processes orders
 */

session_start();
require_once __DIR__ . '/config/database.php';

$success = '';
$error = '';

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        // Get form data
        $street = trim($_POST['street'] ?? '');
        $cep = trim($_POST['cep'] ?? '');
        $neighborhood = trim($_POST['neighborhood'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $complement = trim($_POST['complement'] ?? '');
        $payment_method = $_POST['payment_method'] ?? 'dinheiro';
        
        // Validate required fields
        if (empty($street) || empty($cep) || empty($neighborhood) || empty($city) || empty($state)) {
            $error = 'Por favor, preencha todos os campos obrigatórios.';
        } else {
            // Build address
            $address = $street;
            if (!empty($complement)) $address .= ', ' . $complement;
            if (!empty($neighborhood)) $address .= ', ' . $neighborhood;
            if (!empty($city)) $address .= ', ' . $city;
            if (!empty($state)) $address .= ' - ' . $state;
            if (!empty($cep)) $address .= ', CEP: ' . $cep;
            
            // Create tables if they don't exist
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL DEFAULT 0,
                    total_amount DECIMAL(10,2) NOT NULL,
                    delivery_address TEXT NOT NULL,
                    payment_method VARCHAR(50) NOT NULL DEFAULT 'dinheiro',
                    status VARCHAR(20) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS order_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    product_id INT NOT NULL DEFAULT 1,
                    quantity INT NOT NULL DEFAULT 1,
                    price DECIMAL(10,2) NOT NULL DEFAULT 10.00,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Insert order directly
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, total_amount, delivery_address, payment_method, status)
                VALUES (0, 25.50, ?, ?, 'pending')
            ");
            
            if ($stmt->execute([$address, $payment_method])) {
                $orderId = $pdo->lastInsertId();
                
                // Insert sample order item
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (?, 1, 2, 12.75)
                ");
                $stmt->execute([$orderId]);
                
                $success = "✅ Pedido #$orderId criado com sucesso! Total: R$ 25,50";
            } else {
                $error = 'Erro ao criar pedido. Tente novamente.';
            }
        }
        
    } catch (Exception $e) {
        error_log("Direct checkout error: " . $e->getMessage());
        $error = 'Erro interno: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Direto - Na Porta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .checkout-card { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-primary { background: #6366f1; border-color: #6366f1; }
        .btn-primary:hover { background: #4f46e5; border-color: #4f46e5; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="checkout-card p-4">
                    <h2 class="mb-4">
                        <i class="fas fa-shopping-cart me-2"></i>Checkout Direto
                    </h2>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                        </div>
                        <div class="text-center">
                            <a href="/" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Voltar ao Início
                            </a>
                        </div>
                    <?php else: ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <h5><i class="fas fa-map-marker-alt me-2"></i>Endereço de Entrega</h5>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="street" class="form-label">Rua e Número *</label>
                                    <input type="text" name="street" id="street" class="form-control" 
                                           placeholder="Ex: Rua das Flores, 123" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="cep" class="form-label">CEP *</label>
                                    <input type="text" name="cep" id="cep" class="form-control" 
                                           placeholder="00000-000" maxlength="9" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="neighborhood" class="form-label">Bairro *</label>
                                    <input type="text" name="neighborhood" id="neighborhood" class="form-control" 
                                           placeholder="Ex: Centro" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="complement" class="form-label">Complemento</label>
                                    <input type="text" name="complement" id="complement" class="form-control" 
                                           placeholder="Ex: Apto 101, Bloco A">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="city" class="form-label">Cidade *</label>
                                    <input type="text" name="city" id="city" class="form-control" 
                                           placeholder="Ex: São Paulo" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="state" class="form-label">Estado *</label>
                                    <select name="state" id="state" class="form-control" required>
                                        <option value="">Selecione...</option>
                                        <option value="SP">São Paulo</option>
                                        <option value="RJ">Rio de Janeiro</option>
                                        <option value="MG">Minas Gerais</option>
                                        <option value="RS">Rio Grande do Sul</option>
                                        <option value="PR">Paraná</option>
                                        <option value="SC">Santa Catarina</option>
                                        <option value="BA">Bahia</option>
                                        <option value="GO">Goiás</option>
                                        <option value="PE">Pernambuco</option>
                                        <option value="CE">Ceará</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Forma de Pagamento *</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           value="dinheiro" id="dinheiro" checked required>
                                    <label class="form-check-label" for="dinheiro">
                                        <i class="fas fa-money-bill-wave me-2"></i>Dinheiro (Recomendado)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           value="pix" id="pix" required>
                                    <label class="form-check-label" for="pix">
                                        <i class="fas fa-qrcode me-2"></i>PIX
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-4 p-3 bg-light rounded">
                                <h6><i class="fas fa-shopping-bag me-2"></i>Resumo do Pedido</h6>
                                <div class="d-flex justify-content-between">
                                    <span>Produtos de exemplo</span>
                                    <span>R$ 25,50</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Taxa de entrega</span>
                                    <span class="text-success">Grátis</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total</span>
                                    <span>R$ 25,50</span>
                                </div>
                            </div>
                            
                            <button type="submit" name="place_order" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-check me-2"></i>Finalizar Pedido
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
