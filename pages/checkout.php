<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

$page_title = 'Finalizar Compra';
$page_description = 'Complete sua compra de forma rápida e segura';

// Require login
require_login();

// Get cart items
$cart_items = [];
$cart_total = 0;

try {
    $stmt = $pdo->prepare("
        SELECT ci.*, p.name, p.price, p.stock_quantity, p.sku
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ? AND p.is_active = 1
        ORDER BY ci.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cart_items as $item) {
        $cart_total += $item['price'] * $item['quantity'];
    }
} catch (PDOException $e) {
    error_log('Checkout error: ' . $e->getMessage());
}

// Redirect if cart is empty
if (empty($cart_items)) {
    flash_message('warning', 'Seu carrinho está vazio. Adicione produtos antes de finalizar a compra.');
    redirect(SITE_URL . '/pages/products.php');
}

// Get user addresses
$user_addresses = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $user_addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Address fetch error: ' . $e->getMessage());
}

// Calculate totals
$subtotal = $cart_total;
$delivery_fee = $subtotal >= FREE_DELIVERY_THRESHOLD ? 0 : DELIVERY_FEE;
$total = $subtotal + $delivery_fee;

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Tente novamente.';
    } else {
        $address_id = intval($_POST['address_id'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? '';
        $notes = sanitize_input($_POST['notes'] ?? '');
        
        // Validate address
        $selected_address = null;
        if ($address_id > 0) {
            foreach ($user_addresses as $addr) {
                if ($addr['id'] == $address_id) {
                    $selected_address = $addr;
                    break;
                }
            }
        }
        
        if (!$selected_address) {
            $error = 'Por favor, selecione um endereço de entrega.';
        } elseif (empty($payment_method)) {
            $error = 'Por favor, selecione uma forma de pagamento.';
        } else {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Generate order number
                $order_number = generate_order_number();
                
                // Create order
                $stmt = $pdo->prepare("
                    INSERT INTO orders (
                        user_id, order_number, status, payment_status, payment_method,
                        subtotal, delivery_fee, total_amount,
                        delivery_name, delivery_cep, delivery_street, delivery_number,
                        delivery_complement, delivery_neighborhood, delivery_city, delivery_state,
                        notes, estimated_delivery, created_at
                    ) VALUES (?, ?, 'pending', 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 2 DAY), NOW())
                ");
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $order_number,
                    $payment_method,
                    $subtotal,
                    $delivery_fee,
                    $total,
                    $selected_address['name'],
                    $selected_address['cep'],
                    $selected_address['street'],
                    $selected_address['number'],
                    $selected_address['complement'],
                    $selected_address['neighborhood'],
                    $selected_address['city'],
                    $selected_address['state'],
                    $notes
                ]);
                
                $order_id = $pdo->lastInsertId();
                
                // Add order items
                foreach ($cart_items as $item) {
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (
                            order_id, product_id, product_name, product_sku,
                            quantity, unit_price, total_price
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $item_total = $item['price'] * $item['quantity'];
                    $stmt->execute([
                        $order_id,
                        $item['product_id'],
                        $item['name'],
                        $item['sku'],
                        $item['quantity'],
                        $item['price'],
                        $item_total
                    ]);
                    
                    // Update product stock
                    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
                
                // Clear cart
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                // Commit transaction
                $pdo->commit();
                
                // Redirect to order confirmation
                flash_message('success', 'Pedido realizado com sucesso! Número do pedido: ' . $order_number);
                redirect(SITE_URL . '/pages/account/order.php?id=' . $order_id);
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Erro ao processar pedido. Tente novamente.';
                error_log('Order creation error: ' . $e->getMessage());
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="home.php">Início</a></li>
            <li class="breadcrumb-item"><a href="cart.php">Carrinho</a></li>
            <li class="breadcrumb-item active">Checkout</li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">
                <i class="fas fa-credit-card text-primary me-2"></i>
                Finalizar Compra
            </h1>
            <p class="text-muted">Complete sua compra de forma rápida e segura</p>
        </div>
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
            <!-- Checkout Form -->
            <div class="col-lg-8">
                <!-- Delivery Address -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Endereço de Entrega
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_addresses)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Você não possui endereços cadastrados. 
                            <a href="account/addresses.php" class="alert-link">Cadastre um endereço</a> 
                            antes de finalizar a compra.
                        </div>
                        <?php else: ?>
                        
                        <?php foreach ($user_addresses as $address): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="address_id" 
                                   id="address_<?php echo $address['id']; ?>" 
                                   value="<?php echo $address['id']; ?>"
                                   <?php echo $address['is_default'] ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="address_<?php echo $address['id']; ?>">
                                <div class="card">
                                    <div class="card-body py-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php echo htmlspecialchars($address['name']); ?>
                                                    <?php if ($address['is_default']): ?>
                                                    <span class="badge bg-primary ms-2">Padrão</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <p class="mb-1 text-muted">
                                                    <?php echo htmlspecialchars($address['street']); ?>, 
                                                    <?php echo htmlspecialchars($address['number']); ?>
                                                    <?php if ($address['complement']): ?>
                                                    - <?php echo htmlspecialchars($address['complement']); ?>
                                                    <?php endif; ?>
                                                </p>
                                                <p class="mb-0 text-muted">
                                                    <?php echo htmlspecialchars($address['neighborhood']); ?> - 
                                                    <?php echo htmlspecialchars($address['city']); ?>/<?php echo htmlspecialchars($address['state']); ?>
                                                    <br>CEP: <?php echo format_cep($address['cep']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="mt-3">
                            <a href="account/addresses.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-plus me-2"></i>Adicionar Novo Endereço
                            </a>
                        </div>
                        
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-credit-card me-2"></i>
                            Forma de Pagamento
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="pix" value="pix" checked required>
                                    <label class="form-check-label" for="pix">
                                        <div class="card">
                                            <div class="card-body text-center py-3">
                                                <i class="fab fa-pix fa-2x text-success mb-2"></i>
                                                <h6 class="mb-1">PIX</h6>
                                                <small class="text-muted">Aprovação instantânea</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="credit_card" value="credit_card" required>
                                    <label class="form-check-label" for="credit_card">
                                        <div class="card">
                                            <div class="card-body text-center py-3">
                                                <i class="fas fa-credit-card fa-2x text-primary mb-2"></i>
                                                <h6 class="mb-1">Cartão de Crédito</h6>
                                                <small class="text-muted">Parcelamento disponível</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Notes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-sticky-note me-2"></i>
                            Observações (Opcional)
                        </h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" name="notes" rows="3" 
                                  placeholder="Instruções especiais para entrega, ponto de referência, etc."></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 100px;">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-receipt me-2"></i>
                            Resumo do Pedido
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Order Items -->
                        <div class="mb-3">
                            <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="flex-grow-1">
                                    <small class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></small>
                                    <br>
                                    <small class="text-muted">Qtd: <?php echo $item['quantity']; ?></small>
                                </div>
                                <small class="fw-bold">
                                    <?php echo format_currency($item['price'] * $item['quantity']); ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <!-- Totals -->
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span><?php echo format_currency($subtotal); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Entrega:</span>
                            <span>
                                <?php if ($delivery_fee > 0): ?>
                                    <?php echo format_currency($delivery_fee); ?>
                                <?php else: ?>
                                    <span class="text-success">Grátis</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-primary"><?php echo format_currency($total); ?></strong>
                        </div>
                        
                        <!-- Place Order Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check me-2"></i>
                                Finalizar Pedido
                            </button>
                        </div>
                        
                        <!-- Security Info -->
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt text-success me-1"></i>
                                Seus dados estão protegidos
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Delivery Info -->
                <div class="card mt-3">
                    <div class="card-body text-center">
                        <h6 class="mb-3">
                            <i class="fas fa-truck text-primary me-2"></i>
                            Informações de Entrega
                        </h6>
                        <div class="row g-3 text-center">
                            <div class="col-6">
                                <i class="fas fa-clock text-success mb-2 d-block"></i>
                                <small class="fw-bold">Prazo</small>
                                <br>
                                <small class="text-muted">2-3 dias úteis</small>
                            </div>
                            <div class="col-6">
                                <i class="fas fa-shield-alt text-primary mb-2 d-block"></i>
                                <small class="fw-bold">Segurança</small>
                                <br>
                                <small class="text-muted">Entrega garantida</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.needs-validation');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            // Show first error
            const firstError = form.querySelector(':invalid');
            if (firstError) {
                firstError.focus();
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        form.classList.add('was-validated');
    }, false);
});
</script>

<?php include '../includes/footer.php'; ?>
