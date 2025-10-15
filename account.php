<?php
/**
 * Na Porta - User Account Page
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$auth = new Auth();
$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Redirect to login if not authenticated
if (!$user) {
    header('Location: auth/login.php?redirect=account.php');
    exit();
}

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name)) {
        $error = 'Nome é obrigatório.';
    } else {
        try {
            $db->query("UPDATE users SET name = ?, phone = ?, updated_at = NOW() WHERE id = ?", 
                      [$name, $phone, $user['id']]);
            $success = 'Perfil atualizado com sucesso!';
            // Refresh user data
            $user = $auth->getCurrentUser();
        } catch (Exception $e) {
            $error = 'Erro ao atualizar perfil: ' . $e->getMessage();
        }
    }
}

// Get user orders
$orders = [];
try {
    $orders = $db->fetchAll("
        SELECT o.*, COUNT(oi.id) as item_count
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC 
        LIMIT 10
    ", [$user['id']]);
} catch (Exception $e) {
    error_log("Account orders error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - Na Porta</title>
    
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
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-800);
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: var(--shadow-lg);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .account-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
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
        
        .nav-tabs .nav-link {
            border: none;
            color: var(--gray-800);
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
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
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Produtos</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning" id="cart-count">
                                0
                            </span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($user['name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="account.php">Minha Conta</a></li>
                            <li><a class="dropdown-item" href="auth/logout.php">Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title mb-2">Minha Conta</h1>
                    <p class="lead mb-0">Gerencie seu perfil e acompanhe seus pedidos</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Account Content -->
    <section class="py-5">
        <div class="container">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Account Tabs -->
            <ul class="nav nav-tabs mb-4" id="accountTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                        <i class="fas fa-user me-2"></i>Perfil
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">
                        <i class="fas fa-shopping-bag me-2"></i>Pedidos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="addresses-tab" data-bs-toggle="tab" data-bs-target="#addresses" type="button" role="tab">
                        <i class="fas fa-map-marker-alt me-2"></i>Endereços
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="accountTabsContent">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="account-card p-4">
                                <h5 class="mb-4">
                                    <i class="fas fa-edit me-2"></i>Editar Perfil
                                </h5>
                                
                                <form method="POST">
                                    <input type="hidden" name="update_profile" value="1">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nome Completo *</label>
                                            <input type="text" name="name" class="form-control" required 
                                                   value="<?= htmlspecialchars($user['name']) ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" readonly 
                                                   value="<?= htmlspecialchars($user['email']) ?>">
                                            <small class="text-muted">O email não pode ser alterado</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Telefone</label>
                                            <input type="tel" name="phone" class="form-control" 
                                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                                   placeholder="(11) 99999-9999">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Membro desde</label>
                                            <input type="text" class="form-control" readonly 
                                                   value="<?= isset($user['created_at']) && $user['created_at'] ? date('d/m/Y', strtotime($user['created_at'])) : date('d/m/Y') ?>">
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Salvar Alterações
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="account-card p-4">
                                <h6 class="mb-3">Estatísticas da Conta</h6>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total de Pedidos:</span>
                                    <strong><?= count($orders) ?></strong>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Status da Conta:</span>
                                    <span class="badge bg-success">Ativa</span>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <span>Último Login:</span>
                                    <small class="text-muted">Agora</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Tab -->
                <div class="tab-pane fade" id="orders" role="tabpanel">
                    <div class="account-card p-4">
                        <h5 class="mb-4">
                            <i class="fas fa-shopping-bag me-2"></i>Meus Pedidos
                        </h5>
                        
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Nenhum pedido encontrado</h6>
                                <p class="text-muted">Você ainda não fez nenhum pedido.</p>
                                <a href="products.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>Começar a Comprar
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Data</th>
                                            <th>Itens</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td><strong>#<?= $order['id'] ?></strong></td>
                                                <td><?= isset($order['created_at']) && $order['created_at'] ? date('d/m/Y', strtotime($order['created_at'])) : 'N/A' ?></td>
                                                <td><?= $order['item_count'] ?> item(s)</td>
                                                <td>
                                                    <strong class="text-success">
                                                        R$ <?= number_format($order['total_amount'] ?? 0, 2, ',', '.') ?>
                                                    </strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $order['status'] === 'completed' ? 'success' : 
                                                        ($order['status'] === 'pending' ? 'warning' : 'secondary') 
                                                    ?>">
                                                        <?= ucfirst($order['status'] ?? 'pending') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewOrder(<?= $order['id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Addresses Tab -->
                <div class="tab-pane fade" id="addresses" role="tabpanel">
                    <div class="account-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">
                                <i class="fas fa-map-marker-alt me-2"></i>Meus Endereços
                            </h5>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                <i class="fas fa-plus me-1"></i>Adicionar Endereço
                            </button>
                        </div>

                        <div id="addressesList">
                            <!-- Addresses will be loaded here -->
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Carregando endereços...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Adicionar Endereço
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addAddressForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome do Endereço *</label>
                                <input type="text" class="form-control" name="name" required
                                       placeholder="Ex: Casa, Trabalho, Mãe">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CEP *</label>
                                <input type="text" class="form-control" name="cep" required
                                       placeholder="00000-000" maxlength="9">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Rua *</label>
                                <input type="text" class="form-control" name="street" required
                                       placeholder="Nome da rua">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Número *</label>
                                <input type="text" class="form-control" name="number" required
                                       placeholder="123">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Complemento</label>
                                <input type="text" class="form-control" name="complement"
                                       placeholder="Apto, Bloco, etc.">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bairro *</label>
                                <input type="text" class="form-control" name="neighborhood" required
                                       placeholder="Nome do bairro">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Cidade *</label>
                                <input type="text" class="form-control" name="city" required
                                       placeholder="Nome da cidade">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Estado *</label>
                                <select class="form-control" name="state" required>
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

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_default" id="isDefault">
                            <label class="form-check-label" for="isDefault">
                                Definir como endereço padrão
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Salvar Endereço
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewOrder(orderId) {
            alert('Funcionalidade de detalhes do pedido em desenvolvimento');
        }
        
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
        
        // Update cart count
        function updateCartCount() {
            fetch('api/cart.php?action=count')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-count').textContent = data.count || 0;
                });
        }
        
        // Load cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            loadAddresses();

            // Handle address form submission
            document.getElementById('addAddressForm').addEventListener('submit', function(e) {
                e.preventDefault();

                // Prevent multiple submissions
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn.disabled) return;

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';

                const formData = new FormData(this);
                const addressData = {
                    action: 'add',
                    name: formData.get('name'),
                    cep: formData.get('cep'),
                    street: formData.get('street'),
                    number: formData.get('number'),
                    complement: formData.get('complement'),
                    neighborhood: formData.get('neighborhood'),
                    city: formData.get('city'),
                    state: formData.get('state'),
                    is_default: formData.get('is_default') ? 1 : 0
                };

                console.log('Sending address data:', addressData);

                fetch('simple_address_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(addressData)
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed response:', data);

                        if (data.success) {
                            // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('addAddressModal'));
                            modal.hide();

                            // Reset form
                            document.getElementById('addAddressForm').reset();

                            // Reload addresses
                            loadAddresses();

                            showAlert('Endereço adicionado com sucesso!', 'success');
                        } else {
                            showAlert(data.message || 'Erro ao adicionar endereço', 'danger');
                            if (data.debug) {
                                console.error('Debug info:', data.debug);
                            }
                        }
                    } catch (parseError) {
                        console.error('JSON parse error:', parseError);
                        console.error('Response text:', text);
                        showAlert('Erro de resposta do servidor', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showAlert('Erro de conexão: ' + error.message, 'danger');
                })
                .finally(() => {
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Salvar Endereço';
                });
            });

            // Format CEP input
            document.querySelector('input[name="cep"]').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 5) {
                    value = value.substring(0, 5) + '-' + value.substring(5, 8);
                }
                e.target.value = value;
            });
        });

        // Address Management Functions
        function loadAddresses() {
            fetch('simple_address_api.php')
                .then(response => response.json())
                .then(data => {
                    const addressesList = document.getElementById('addressesList');

                    if (data.success && data.addresses && data.addresses.length > 0) {
                        let html = '';
                        data.addresses.forEach(address => {
                            const isDefault = address.is_default == 1;
                            html += `
                                <div class="address-card mb-3 p-3 border rounded ${isDefault ? 'border-primary bg-light' : ''}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <h6 class="mb-0 me-2">${address.name}</h6>
                                                ${isDefault ? '<span class="badge bg-primary">Padrão</span>' : ''}
                                            </div>
                                            <p class="mb-1 text-muted">
                                                ${address.street}, ${address.number}
                                                ${address.complement ? ', ' + address.complement : ''}
                                            </p>
                                            <p class="mb-1 text-muted">
                                                ${address.neighborhood}, ${address.city}/${address.state}
                                            </p>
                                            <p class="mb-0 text-muted">CEP: ${address.cep}</p>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                ${!isDefault ? '<li><a class="dropdown-item" href="#" onclick="setDefaultAddress(' + address.id + ')"><i class="fas fa-star me-2"></i>Definir como Padrão</a></li>' : ''}
                                                <li><a class="dropdown-item" href="#" onclick="editAddress(' + address.id + ')"><i class="fas fa-edit me-2"></i>Editar</a></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteAddress(' + address.id + ')"><i class="fas fa-trash me-2"></i>Excluir</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        addressesList.innerHTML = html;
                    } else {
                        addressesList.innerHTML = `
                            <div class="text-center py-5">
                                <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Nenhum endereço cadastrado</h6>
                                <p class="text-muted">Adicione seu primeiro endereço para facilitar suas compras.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading addresses:', error);
                    document.getElementById('addressesList').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erro ao carregar endereços. Tente novamente.
                        </div>
                    `;
                });
        }

        function setDefaultAddress(addressId) {
            fetch('api/addresses.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'set_default',
                    address_id: addressId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadAddresses(); // Reload addresses
                    showAlert('Endereço padrão definido com sucesso!', 'success');
                } else {
                    showAlert(data.message || 'Erro ao definir endereço padrão', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Erro ao definir endereço padrão', 'danger');
            });
        }

        function deleteAddress(addressId) {
            if (confirm('Tem certeza que deseja excluir este endereço?')) {
                fetch('api/addresses.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        address_id: addressId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadAddresses(); // Reload addresses
                        showAlert('Endereço excluído com sucesso!', 'success');
                    } else {
                        showAlert(data.message || 'Erro ao excluir endereço', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Erro ao excluir endereço', 'danger');
                });
            }
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);

            // Auto dismiss after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>
