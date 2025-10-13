<?php
// User Addresses Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';

// Simple functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../auth/login-working.php');
    exit();
}

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_address') {
        $title = trim($_POST['title'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if (empty($title) || empty($address)) {
            $error = "Por favor, preencha todos os campos obrigatórios.";
        } else {
            try {
                // If this is default, unset other defaults
                if ($is_default) {
                    $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                }
                
                // Add new address
                $stmt = $pdo->prepare("
                    INSERT INTO user_addresses (user_id, title, address, is_default, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                
                if ($stmt->execute([$_SESSION['user_id'], $title, $address, $is_default])) {
                    $success = "Endereço adicionado com sucesso!";
                } else {
                    $error = "Erro ao adicionar endereço.";
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'edit_address') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if (empty($title) || empty($address) || $id <= 0) {
            $error = "Por favor, preencha todos os campos obrigatórios.";
        } else {
            try {
                // If this is default, unset other defaults
                if ($is_default) {
                    $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND id != ?");
                    $stmt->execute([$_SESSION['user_id'], $id]);
                }
                
                // Update address
                $stmt = $pdo->prepare("
                    UPDATE user_addresses 
                    SET title = ?, address = ?, is_default = ?, updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                
                if ($stmt->execute([$title, $address, $is_default, $id, $_SESSION['user_id']])) {
                    $success = "Endereço atualizado com sucesso!";
                } else {
                    $error = "Erro ao atualizar endereço.";
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'delete_address') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $error = "ID inválido.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
                if ($stmt->execute([$id, $_SESSION['user_id']])) {
                    $success = "Endereço excluído com sucesso!";
                } else {
                    $error = "Erro ao excluir endereço.";
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'set_default') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $error = "ID inválido.";
        } else {
            try {
                // Unset all defaults
                $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                // Set new default
                $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
                if ($stmt->execute([$id, $_SESSION['user_id']])) {
                    $success = "Endereço padrão definido com sucesso!";
                } else {
                    $error = "Erro ao definir endereço padrão.";
                }
            } catch (Exception $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
}

// Get user addresses
$addresses = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Ignore errors if table doesn't exist yet
}

$page_title = 'Meus Endereços - Na Porta';
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
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
        }
        
        .btn-primary:hover {
            background: #1565c0;
        }
        
        .address-card {
            transition: all 0.3s ease;
        }
        
        .address-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .default-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../home-fixed.php">
                <i class="fas fa-home me-2"></i>Na Porta
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-mdb-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile-working.php">
                            <i class="fas fa-user me-2"></i>Perfil
                        </a></li>
                        <li><a class="dropdown-item" href="orders-working.php">
                            <i class="fas fa-shopping-bag me-2"></i>Pedidos
                        </a></li>
                        <li><a class="dropdown-item active" href="addresses.php">
                            <i class="fas fa-map-marker-alt me-2"></i>Endereços
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Sair
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <section class="py-5">
        <div class="container">
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-map-marker-alt me-2"></i>Meus Endereços</h2>
                <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#addAddressModal">
                    <i class="fas fa-plus me-2"></i>Novo Endereço
                </button>
            </div>

            <!-- Addresses Grid -->
            <?php if (empty($addresses)): ?>
            <div class="text-center py-5">
                <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                <h5>Nenhum endereço cadastrado</h5>
                <p class="text-muted">Adicione seus endereços para facilitar suas compras</p>
                <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#addAddressModal">
                    <i class="fas fa-plus me-2"></i>Adicionar Primeiro Endereço
                </button>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($addresses as $address): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card address-card h-100 border-0 shadow-sm position-relative">
                        <?php if ($address['is_default']): ?>
                        <span class="badge bg-success default-badge">
                            <i class="fas fa-star me-1"></i>Padrão
                        </span>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                <?php echo htmlspecialchars($address['title']); ?>
                            </h5>
                            <p class="card-text text-muted">
                                <?php echo nl2br(htmlspecialchars($address['address'])); ?>
                            </p>
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                Adicionado em <?php echo date('d/m/Y', strtotime($address['created_at'])); ?>
                            </small>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100">
                                <?php if (!$address['is_default']): ?>
                                <button class="btn btn-outline-success btn-sm" 
                                        onclick="setDefault(<?php echo $address['id']; ?>)"
                                        title="Definir como padrão">
                                    <i class="fas fa-star"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-outline-primary btn-sm" 
                                        onclick="editAddress(<?php echo htmlspecialchars(json_encode($address)); ?>)"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="deleteAddress(<?php echo $address['id']; ?>, '<?php echo htmlspecialchars($address['title']); ?>')"
                                        title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus me-2"></i>Novo Endereço
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_address">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Título do Endereço *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   placeholder="Ex: Casa, Trabalho, Casa da Mãe..." required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Endereço Completo *</label>
                            <textarea class="form-control" id="address" name="address" rows="4" 
                                      placeholder="Rua, número, bairro, cidade, CEP..." required></textarea>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                            <label class="form-check-label" for="is_default">
                                <i class="fas fa-star text-warning me-1"></i>Definir como endereço padrão
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Endereço
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Address Modal -->
    <div class="modal fade" id="editAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Editar Endereço
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_address">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Título do Endereço *</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_address" class="form-label">Endereço Completo *</label>
                            <textarea class="form-control" id="edit_address" name="address" rows="4" required></textarea>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_default" name="is_default">
                            <label class="form-check-label" for="edit_is_default">
                                <i class="fas fa-star text-warning me-1"></i>Definir como endereço padrão
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Address Modal -->
    <div class="modal fade" id="deleteAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-trash me-2"></i>Excluir Endereço
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_address">
                        <input type="hidden" name="id" id="delete_id">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atenção!</strong> Esta ação não pode ser desfeita.
                        </div>
                        
                        <p>Tem certeza que deseja excluir o endereço <strong id="delete_title"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Excluir Endereço
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Set Default Modal -->
    <div class="modal fade" id="setDefaultModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title text-success">
                            <i class="fas fa-star me-2"></i>Definir Endereço Padrão
                        </h5>
                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="set_default">
                        <input type="hidden" name="id" id="default_id">
                        
                        <p>Definir este endereço como padrão? Ele será usado automaticamente em suas compras.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-star me-2"></i>Definir como Padrão
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // Edit address function
        function editAddress(address) {
            document.getElementById('edit_id').value = address.id;
            document.getElementById('edit_title').value = address.title;
            document.getElementById('edit_address').value = address.address;
            document.getElementById('edit_is_default').checked = address.is_default == 1;
            
            const modal = new mdb.Modal(document.getElementById('editAddressModal'));
            modal.show();
        }
        
        // Delete address function
        function deleteAddress(id, title) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_title').textContent = title;
            
            const modal = new mdb.Modal(document.getElementById('deleteAddressModal'));
            modal.show();
        }
        
        // Set default function
        function setDefault(id) {
            document.getElementById('default_id').value = id;
            
            const modal = new mdb.Modal(document.getElementById('setDefaultModal'));
            modal.show();
        }
    </script>
</body>
</html>
