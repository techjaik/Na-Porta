<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

$page_title = 'Carrinho de Compras';
$page_description = 'Revise seus produtos e finalize sua compra';

// Get cart items
$cart_items = [];
$cart_total = 0;
$delivery_fee = DELIVERY_FEE;
$free_delivery_threshold = FREE_DELIVERY_THRESHOLD;

try {
    if (is_logged_in()) {
        $stmt = $pdo->prepare("
            SELECT ci.*, p.name, p.price, p.stock_quantity, p.slug, pi.image_path, c.name as category_name
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE ci.user_id = ? AND p.is_active = 1
            ORDER BY ci.created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $session_id = session_id();
        $stmt = $pdo->prepare("
            SELECT ci.*, p.name, p.price, p.stock_quantity, p.slug, pi.image_path, c.name as category_name
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE ci.session_id = ? AND p.is_active = 1
            ORDER BY ci.created_at DESC
        ");
        $stmt->execute([$session_id]);
    }
    
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    foreach ($cart_items as $item) {
        $cart_total += $item['price'] * $item['quantity'];
    }
    
} catch (PDOException $e) {
    error_log('Cart page error: ' . $e->getMessage());
}

// Calculate final totals
$subtotal = $cart_total;
$final_delivery_fee = $subtotal >= $free_delivery_threshold ? 0 : $delivery_fee;
$final_total = $subtotal + $final_delivery_fee;

include '../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="home.php">Início</a></li>
            <li class="breadcrumb-item"><a href="products.php">Produtos</a></li>
            <li class="breadcrumb-item active">Carrinho</li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">
                <i class="fas fa-shopping-cart text-primary me-2"></i>
                Carrinho de Compras
            </h1>
            <p class="text-muted">Revise seus produtos antes de finalizar a compra</p>
        </div>
    </div>
    
    <?php if (empty($cart_items)): ?>
    <!-- Empty Cart -->
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                <h3>Seu carrinho está vazio</h3>
                <p class="text-muted mb-4">Que tal adicionar alguns produtos essenciais?</p>
                <a href="products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>Adicionar Produtos
                </a>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Cart Content -->
    <div class="row">
        <!-- Cart Items -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Produtos (<?php echo count($cart_items); ?>)
                    </h5>
                    <button class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                        <i class="fas fa-trash me-1"></i>Limpar Carrinho
                    </button>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="border-bottom p-3" data-cart-item="<?php echo $item['product_id']; ?>">
                        <div class="row align-items-center">
                            <!-- Product Image -->
                            <div class="col-md-2 col-3">
                                <img src="<?php echo SITE_URL . '/uploads/' . ($item['image_path'] ?: 'placeholder.jpg'); ?>" 
                                     class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     style="max-height: 80px; object-fit: cover;">
                            </div>
                            
                            <!-- Product Info -->
                            <div class="col-md-4 col-9">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($item['category_name']); ?></small>
                                <div class="mt-1">
                                    <span class="fw-bold text-success">
                                        <?php echo format_currency($item['price']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Quantity Controls -->
                            <div class="col-md-3 col-6">
                                <div class="input-group input-group-sm">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="form-control text-center" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock_quantity']; ?>"
                                           onchange="updateQuantity(<?php echo $item['product_id']; ?>, this.value)">
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] + 1; ?>)"
                                            <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : ''; ?>>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <small class="text-muted">
                                    Estoque: <?php echo $item['stock_quantity']; ?>
                                </small>
                            </div>
                            
                            <!-- Item Total & Actions -->
                            <div class="col-md-3 col-6 text-end">
                                <div class="fw-bold text-primary mb-2" data-item-total="<?php echo $item['product_id']; ?>">
                                    <?php echo format_currency($item['price'] * $item['quantity']); ?>
                                </div>
                                <button class="btn btn-outline-danger btn-sm remove-from-cart" 
                                        data-product-id="<?php echo $item['product_id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Continue Shopping -->
            <div class="mt-3">
                <a href="products.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Continuar Comprando
                </a>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 100px;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calculator me-2"></i>Resumo do Pedido
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span class="cart-total"><?php echo format_currency($subtotal); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Entrega:</span>
                        <span class="delivery-fee">
                            <?php if ($final_delivery_fee > 0): ?>
                                <?php echo format_currency($final_delivery_fee); ?>
                            <?php else: ?>
                                <span class="text-success">Grátis</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if ($subtotal < $free_delivery_threshold && $subtotal > 0): ?>
                    <div class="alert alert-info py-2 px-3 mb-3">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Faltam <?php echo format_currency($free_delivery_threshold - $subtotal); ?> 
                            para frete grátis!
                        </small>
                    </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="text-primary final-total"><?php echo format_currency($final_total); ?></strong>
                    </div>
                    
                    <!-- Checkout Button -->
                    <?php if (is_logged_in()): ?>
                    <div class="d-grid">
                        <a href="checkout.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-credit-card me-2"></i>Finalizar Compra
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="d-grid gap-2">
                        <a href="auth/login.php?redirect=<?php echo urlencode('/pages/checkout.php'); ?>" 
                           class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Entrar para Comprar
                        </a>
                        <a href="auth/register.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-2"></i>Criar Conta
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Security Info -->
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt text-success me-1"></i>
                            Compra 100% segura
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div class="card mt-3">
                <div class="card-body text-center">
                    <h6 class="mb-3">Formas de Pagamento</h6>
                    <div class="d-flex justify-content-center align-items-center gap-3">
                        <i class="fab fa-pix fa-2x text-success" title="PIX"></i>
                        <i class="fas fa-credit-card fa-2x text-primary" title="Cartão"></i>
                        <i class="fas fa-barcode fa-2x text-secondary" title="Boleto"></i>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        PIX, Cartão de Crédito e Boleto
                    </small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function updateQuantity(productId, quantity) {
    if (quantity < 1) {
        if (confirm('Deseja remover este produto do carrinho?')) {
            NaPorta.removeFromCart(productId);
        }
        return;
    }
    
    NaPorta.updateCartQuantity(productId, quantity);
}

function clearCart() {
    if (confirm('Tem certeza que deseja limpar todo o carrinho?')) {
        fetch('/Na%20Porta/api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'clear'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                NaPorta.showNotification(data.message || 'Erro ao limpar carrinho', 'error');
            }
        })
        .catch(error => {
            NaPorta.showNotification('Erro ao limpar carrinho', 'error');
        });
    }
}

// Update totals when cart changes
function updateCartTotals(cartTotal, deliveryFee, finalTotal) {
    document.querySelector('.cart-total').textContent = NaPorta.formatCurrency(cartTotal);
    
    const deliveryElement = document.querySelector('.delivery-fee');
    if (deliveryFee > 0) {
        deliveryElement.innerHTML = NaPorta.formatCurrency(deliveryFee);
    } else {
        deliveryElement.innerHTML = '<span class="text-success">Grátis</span>';
    }
    
    document.querySelector('.final-total').textContent = NaPorta.formatCurrency(finalTotal);
}

// Override cart functions to update totals
const originalUpdateCart = NaPorta.updateCartQuantity;
NaPorta.updateCartQuantity = function(productId, quantity) {
    originalUpdateCart(productId, quantity).then(() => {
        // Recalculate totals (in a real app, this would come from the server)
        setTimeout(() => location.reload(), 500);
    });
};

const originalRemoveFromCart = NaPorta.removeFromCart;
NaPorta.removeFromCart = function(productId) {
    originalRemoveFromCart(productId).then(() => {
        setTimeout(() => location.reload(), 500);
    });
};
</script>

<?php include '../includes/footer.php'; ?>
