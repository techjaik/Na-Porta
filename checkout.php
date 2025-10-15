<?php
/**
 * Na Porta - Checkout Page
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$auth = new Auth();
$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Redirect to login if not authenticated
if (!$user) {
    header('Location: auth/login.php?redirect=checkout.php');
    exit();
}

$success = '';
$error = '';

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $address = trim($_POST['address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (empty($address) || empty($payment_method)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            // Get cart items for both logged-in users and sessions
            if ($user) {
                $cartItems = $db->fetchAll("
                    SELECT ci.*, p.name, p.price
                    FROM cart_items ci
                    JOIN products p ON ci.product_id = p.id
                    WHERE ci.user_id = ? AND p.is_active = 1
                ", [$user['id']]);
            } else {
                $sessionId = session_id();
                $cartItems = $db->fetchAll("
                    SELECT ci.*, p.name, p.price
                    FROM cart_items ci
                    JOIN products p ON ci.product_id = p.id
                    WHERE ci.session_id = ? AND p.is_active = 1
                ", [$sessionId]);
            }
            
            if (empty($cartItems)) {
                $error = 'Seu carrinho está vazio.';
            } else {
                // Calculate total
                $total = 0;
                foreach ($cartItems as $item) {
                    $total += $item['price'] * $item['quantity'];
                }
                
                // Create order
                $db->query("
                    INSERT INTO orders (user_id, total_amount, delivery_address, payment_method, status, created_at) 
                    VALUES (?, ?, ?, ?, 'pending', NOW())
                ", [$user['id'], $total, $address, $payment_method]);
                
                $orderId = $db->lastInsertId();
                
                // Create order items
                foreach ($cartItems as $item) {
                    $db->query("
                        INSERT INTO order_items (order_id, product_id, quantity, price, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ", [$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                }
                
                // Clear cart for both user types
                if ($user) {
                    $db->query("DELETE FROM cart_items WHERE user_id = ?", [$user['id']]);
                } else {
                    $sessionId = session_id();
                    $db->query("DELETE FROM cart_items WHERE session_id = ?", [$sessionId]);
                }
                
                // Redirect to success page
                header('Location: order-success.php?order=' . $orderId);
                exit();
            }
        } catch (Exception $e) {
            $error = 'Erro ao processar pedido: ' . $e->getMessage();
        }
    }
}

// Get cart items for display (support both user types)
$cartItems = [];
$cartTotal = 0;
try {
    if ($user) {
        $cartItems = $db->fetchAll("
            SELECT ci.*, p.name, p.price, p.image_url as image
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.user_id = ? AND p.is_active = 1
        ", [$user['id']]);
    } else {
        $sessionId = session_id();
        $cartItems = $db->fetchAll("
            SELECT ci.*, p.name, p.price, p.image_url as image
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.session_id = ? AND p.is_active = 1
        ", [$sessionId]);
    }

    foreach ($cartItems as $item) {
        $cartTotal += $item['price'] * $item['quantity'];
    }
} catch (Exception $e) {
    error_log("Checkout error: " . $e->getMessage());
}

// Redirect if cart is empty
if (empty($cartItems)) {
    header('Location: cart.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Pedido - Na Porta</title>
    
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
            --warning-color: #f59e0b;
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
        
        .checkout-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: none;
        }

        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .payment-option {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .payment-option:hover {
            border-color: var(--primary-color);
            background-color: #f8faff;
        }

        .payment-option.recommended {
            border-color: var(--success-color);
            background-color: #f0fdf4;
        }

        .payment-option input[type="radio"]:checked + label {
            color: var(--primary-color);
            font-weight: 600;
        }

        .payment-option.recommended input[type="radio"]:checked + label {
            color: var(--success-color);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .payment-option.recommended .form-check-input:checked {
            background-color: var(--success-color);
            border-color: var(--success-color);
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-home me-2"></i>Na Porta
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($user['name']) ?>
                </span>
            </div>
        </div>
    </nav>
    
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title mb-2">Finalizar Pedido</h1>
                    <p class="lead mb-0">Confirme seus dados e finalize sua compra</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-white-50">
                        <i class="fas fa-credit-card fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Checkout Content -->
    <section class="py-5">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Order Form -->
                <div class="col-lg-8 mb-4">
                    <div class="checkout-card p-4">
                        <h5 class="mb-4">
                            <i class="fas fa-shipping-fast me-2"></i>Dados de Entrega
                        </h5>
                        
                        <form method="POST">
                            <input type="hidden" name="place_order" value="1">
                            
                            <div class="mb-4">
                                <h6 class="mb-3">
                                    <i class="fas fa-map-marker-alt me-2"></i>Endereço de Entrega
                                </h6>

                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">Rua e Número *</label>
                                        <input type="text" name="street" class="form-control" required
                                               placeholder="Ex: Rua das Flores, 123">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">CEP *</label>
                                        <input type="text" name="cep" class="form-control" required
                                               placeholder="00000-000" maxlength="9">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Bairro *</label>
                                        <input type="text" name="neighborhood" class="form-control" required
                                               placeholder="Ex: Centro">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Complemento</label>
                                        <input type="text" name="complement" class="form-control"
                                               placeholder="Ex: Apto 101, Bloco A">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">Cidade *</label>
                                        <input type="text" name="city" class="form-control" required
                                               placeholder="Ex: São Paulo">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Estado *</label>
                                        <select name="state" class="form-control" required>
                                            <option value="">Selecione...</option>
                                            <option value="AC">Acre</option>
                                            <option value="AL">Alagoas</option>
                                            <option value="AP">Amapá</option>
                                            <option value="AM">Amazonas</option>
                                            <option value="BA">Bahia</option>
                                            <option value="CE">Ceará</option>
                                            <option value="DF">Distrito Federal</option>
                                            <option value="ES">Espírito Santo</option>
                                            <option value="GO">Goiás</option>
                                            <option value="MA">Maranhão</option>
                                            <option value="MT">Mato Grosso</option>
                                            <option value="MS">Mato Grosso do Sul</option>
                                            <option value="MG">Minas Gerais</option>
                                            <option value="PA">Pará</option>
                                            <option value="PB">Paraíba</option>
                                            <option value="PR">Paraná</option>
                                            <option value="PE">Pernambuco</option>
                                            <option value="PI">Piauí</option>
                                            <option value="RJ">Rio de Janeiro</option>
                                            <option value="RN">Rio Grande do Norte</option>
                                            <option value="RS">Rio Grande do Sul</option>
                                            <option value="RO">Rondônia</option>
                                            <option value="RR">Roraima</option>
                                            <option value="SC">Santa Catarina</option>
                                            <option value="SP" selected>São Paulo</option>
                                            <option value="SE">Sergipe</option>
                                            <option value="TO">Tocantins</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Hidden field to combine address for database -->
                                <input type="hidden" name="address" id="combined_address">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Forma de Pagamento *</label>
                                <div class="payment-methods">
                                    <div class="form-check payment-option recommended">
                                        <input class="form-check-input" type="radio" name="payment_method"
                                               value="dinheiro" id="dinheiro" required checked>
                                        <label class="form-check-label" for="dinheiro">
                                            <i class="fas fa-money-bill-wave me-2 text-success"></i>
                                            <strong>Dinheiro (Recomendado)</strong>
                                            <small class="d-block text-muted">Pagamento na entrega</small>
                                        </label>
                                    </div>

                                    <div class="form-check payment-option">
                                        <input class="form-check-input" type="radio" name="payment_method"
                                               value="cartao_debito" id="cartao_debito">
                                        <label class="form-check-label" for="cartao_debito">
                                            <i class="fas fa-credit-card me-2 text-primary"></i>
                                            <strong>Cartão de Débito</strong>
                                            <small class="d-block text-muted">Pagamento na entrega</small>
                                        </label>
                                    </div>

                                    <div class="form-check payment-option">
                                        <input class="form-check-input" type="radio" name="payment_method"
                                               value="pix" id="pix">
                                        <label class="form-check-label" for="pix">
                                            <i class="fas fa-qrcode me-2 text-info"></i>
                                            <strong>PIX</strong>
                                            <small class="d-block text-muted">Pagamento na entrega</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Importante:</strong> Todos os pagamentos são realizados na entrega.
                                    Tenha o valor exato ou cartão em mãos.
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="cart.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Voltar ao Carrinho
                                </a>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-check me-2"></i>Finalizar Pedido
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="checkout-card p-4">
                        <h5 class="mb-4">
                            <i class="fas fa-receipt me-2"></i>Resumo do Pedido
                        </h5>
                        
                        <div class="mb-3">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <small class="fw-bold"><?= htmlspecialchars($item['name']) ?></small>
                                        <br>
                                        <small class="text-muted"><?= $item['quantity'] ?>x R$ <?= number_format($item['price'], 2, ',', '.') ?></small>
                                    </div>
                                    <span>R$ <?= number_format($item['price'] * $item['quantity'], 2, ',', '.') ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>R$ <?= number_format($cartTotal, 2, ',', '.') ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Taxa de Entrega:</span>
                            <span class="text-success">Grátis</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-success">R$ <?= number_format($cartTotal, 2, ',', '.') ?></strong>
                        </div>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Pagamento seguro e protegido
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Combine address fields before form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const street = document.querySelector('input[name="street"]').value;
            const cep = document.querySelector('input[name="cep"]').value;
            const neighborhood = document.querySelector('input[name="neighborhood"]').value;
            const complement = document.querySelector('input[name="complement"]').value;
            const city = document.querySelector('input[name="city"]').value;
            const state = document.querySelector('select[name="state"]').value;

            // Combine all address fields
            let fullAddress = street;
            if (complement) fullAddress += ', ' + complement;
            fullAddress += ', ' + neighborhood;
            fullAddress += ', ' + city + ' - ' + state;
            fullAddress += ', CEP: ' + cep;

            // Set the combined address
            document.getElementById('combined_address').value = fullAddress;
        });

        // Format CEP input
        document.querySelector('input[name="cep"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 5) {
                value = value.substring(0, 5) + '-' + value.substring(5, 8);
            }
            e.target.value = value;
        });

        // Payment method selection styling
        document.querySelectorAll('input[name="payment_method"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                // Remove active class from all options
                document.querySelectorAll('.payment-option').forEach(function(option) {
                    option.classList.remove('active');
                });

                // Add active class to selected option
                this.closest('.payment-option').classList.add('active');
            });
        });

        // Auto-select cash payment (already checked in HTML)
        document.addEventListener('DOMContentLoaded', function() {
            const cashOption = document.querySelector('#dinheiro');
            if (cashOption && cashOption.checked) {
                cashOption.closest('.payment-option').classList.add('active');
            }
        });
    </script>
</body>
</html>
