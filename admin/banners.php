<?php
// Promotional Banners Management
session_start();
require_once '../config/database.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_banner') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $link_url = trim($_POST['link_url'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        
        if (empty($title)) {
            $error = 'Título é obrigatório.';
        } elseif (!isset($_FILES['banner_file']) || $_FILES['banner_file']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Por favor, selecione um arquivo.';
        } else {
            $file = $_FILES['banner_file'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = 'Tipo de arquivo não permitido. Use imagens (JPG, PNG, GIF, WebP) ou vídeos (MP4, WebM).';
            } elseif ($file['size'] > 50 * 1024 * 1024) { // 50MB limit
                $error = 'Arquivo muito grande. Máximo 50MB.';
            } else {
                $upload_dir = '../uploads/banners/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('banner_') . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                $db_file_path = 'uploads/banners/' . $file_name;
                
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $file_type = strpos($file['type'], 'video') !== false ? 'video' : 'image';
                    
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO promotional_banners (title, description, file_path, file_type, link_url, is_active, sort_order) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        if ($stmt->execute([$title, $description, $db_file_path, $file_type, $link_url, $is_active, $sort_order])) {
                            $success = 'Banner adicionado com sucesso!';
                        } else {
                            $error = 'Erro ao salvar no banco de dados.';
                        }
                    } catch (Exception $e) {
                        $error = 'Erro: ' . $e->getMessage();
                    }
                } else {
                    $error = 'Erro ao fazer upload do arquivo.';
                }
            }
        }
    }
    
    elseif ($action === 'delete_banner') {
        $banner_id = (int)($_POST['banner_id'] ?? 0);
        
        if ($banner_id > 0) {
            try {
                // Get file path before deleting
                $stmt = $pdo->prepare("SELECT file_path FROM promotional_banners WHERE id = ?");
                $stmt->execute([$banner_id]);
                $banner = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($banner) {
                    // Delete from database
                    $stmt = $pdo->prepare("DELETE FROM promotional_banners WHERE id = ?");
                    if ($stmt->execute([$banner_id])) {
                        // Delete file
                        $file_path = '../' . $banner['file_path'];
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                        $success = 'Banner excluído com sucesso!';
                    } else {
                        $error = 'Erro ao excluir banner.';
                    }
                } else {
                    $error = 'Banner não encontrado.';
                }
            } catch (Exception $e) {
                $error = 'Erro: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'toggle_status') {
        $banner_id = (int)($_POST['banner_id'] ?? 0);
        
        if ($banner_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE promotional_banners SET is_active = NOT is_active WHERE id = ?");
                if ($stmt->execute([$banner_id])) {
                    $success = 'Status do banner atualizado!';
                } else {
                    $error = 'Erro ao atualizar status.';
                }
            } catch (Exception $e) {
                $error = 'Erro: ' . $e->getMessage();
            }
        }
    }
}

// Get all banners
try {
    $stmt = $pdo->query("SELECT * FROM promotional_banners ORDER BY sort_order ASC, created_at DESC");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $banners = [];
    $error = 'Erro ao carregar banners: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Banners - Admin Na Porta</title>
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .banner-preview {
            max-width: 200px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload-area:hover {
            border-color: #007cba;
            background-color: #f8f9fa;
        }
        
        .file-upload-area.dragover {
            border-color: #007cba;
            background-color: #e3f2fd;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-home me-2"></i>Admin Na Porta
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../pages/home-fixed.php" target="_blank">
                    <i class="fas fa-external-link-alt me-1"></i>Ver Site
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2">
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-dashboard me-2"></i>Dashboard
                    </a>
                    <a href="banners.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-images me-2"></i>Banners Promocionais
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Usuários
                    </a>
                    <a href="orders.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart me-2"></i>Pedidos
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-images me-2"></i>Gerenciar Banners Promocionais</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBannerModal" data-bs-toggle="modal" data-bs-target="#addBannerModal" data-mdb-toggle="modal" data-mdb-target="#addBannerModal">
                        <i class="fas fa-plus me-2"></i>Novo Banner
                    </button>
                </div>

                <!-- Alerts -->
                <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Banners List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Banners Ativos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($banners)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-images fa-3x text-muted mb-3"></i>
                            <h5>Nenhum banner encontrado</h5>
                            <p class="text-muted">Clique em "Novo Banner" para adicionar o primeiro banner promocional.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Preview</th>
                                        <th>Título</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Ordem</th>
                                        <th>Criado</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($banners as $banner): ?>
                                    <tr>
                                        <td>
                                            <?php if ($banner['file_type'] === 'video'): ?>
                                            <video class="banner-preview" muted>
                                                <source src="../<?php echo htmlspecialchars($banner['file_path']); ?>" type="video/mp4">
                                            </video>
                                            <?php else: ?>
                                            <img src="../<?php echo htmlspecialchars($banner['file_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                                                 class="banner-preview">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($banner['title']); ?></strong>
                                            <?php if (!empty($banner['description'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($banner['description'], 0, 50)) . '...'; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $banner['file_type'] === 'video' ? 'danger' : 'primary'; ?>">
                                                <i class="fas fa-<?php echo $banner['file_type'] === 'video' ? 'video' : 'image'; ?> me-1"></i>
                                                <?php echo ucfirst($banner['file_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $banner['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $banner['is_active'] ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $banner['sort_order']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($banner['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-<?php echo $banner['is_active'] ? 'warning' : 'success'; ?>" 
                                                            title="<?php echo $banner['is_active'] ? 'Desativar' : 'Ativar'; ?>">
                                                        <i class="fas fa-<?php echo $banner['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                    </button>
                                                </form>
                                                <button class="btn btn-outline-danger" 
                                                        onclick="deleteBanner(<?php echo $banner['id']; ?>, '<?php echo htmlspecialchars($banner['title']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Banner Modal -->
    <div class="modal fade" id="addBannerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus me-2"></i>Novo Banner Promocional
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_banner">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Título *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="link_url" class="form-label">Link (URL)</label>
                            <input type="url" class="form-control" id="link_url" name="link_url" 
                                   placeholder="https://exemplo.com/produtos">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sort_order" class="form-label">Ordem de Exibição</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        Banner Ativo
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Arquivo (Imagem ou Vídeo) *</label>
                            <div class="file-upload-area" onclick="document.getElementById('banner_file').click()">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Clique para selecionar ou arraste o arquivo aqui</h5>
                                <p class="text-muted">Imagens: JPG, PNG, GIF, WebP | Vídeos: MP4, WebM | Máximo: 50MB</p>
                                <input type="file" id="banner_file" name="banner_file" accept="image/*,video/*" 
                                       style="display: none;" required>
                            </div>
                            <div id="file-preview" class="mt-3" style="display: none;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Banner
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Banner Modal -->
    <div class="modal fade" id="deleteBannerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-trash me-2"></i>Excluir Banner
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" data-mdb-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_banner">
                        <input type="hidden" name="banner_id" id="delete_banner_id">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atenção!</strong> Esta ação não pode ser desfeita.
                        </div>
                        
                        <p>Tem certeza que deseja excluir o banner <strong id="delete_banner_title"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Excluir Banner
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // File upload preview
        document.getElementById('banner_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('file-preview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const isVideo = file.type.startsWith('video/');
                    preview.innerHTML = isVideo ? 
                        `<video controls style="max-width: 300px; max-height: 200px;"><source src="${e.target.result}" type="${file.type}"></video>` :
                        `<img src="${e.target.result}" style="max-width: 300px; max-height: 200px; object-fit: cover;">`;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
        
        // Delete banner function
        function deleteBanner(bannerId, bannerTitle) {
            document.getElementById('delete_banner_id').value = bannerId;
            document.getElementById('delete_banner_title').textContent = bannerTitle;
            
            const modal = new mdb.Modal(document.getElementById('deleteBannerModal'));
            modal.show();
        }
        
        // Drag and drop functionality
        const uploadArea = document.querySelector('.file-upload-area');
        const fileInput = document.getElementById('banner_file');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
        
        // Ensure modal functionality works with both Bootstrap and MDBootstrap
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize modals manually if needed
            const addModalEl = document.getElementById('addBannerModal');
            const deleteModalEl = document.getElementById('deleteBannerModal');
            
            // Add click handler for the "Novo Banner" button
            const newBannerBtn = document.querySelector('[data-bs-target="#addBannerModal"]');
            if (newBannerBtn) {
                newBannerBtn.addEventListener('click', function() {
                    if (typeof mdb !== 'undefined' && mdb.Modal) {
                        const modal = new mdb.Modal(addModalEl);
                        modal.show();
                    } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modal = new bootstrap.Modal(addModalEl);
                        modal.show();
                    }
                });
            }
            
            // Add click handlers for cancel buttons
            document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(button => {
                button.addEventListener('click', function() {
                    const modalEl = this.closest('.modal');
                    if (modalEl) {
                        if (typeof mdb !== 'undefined' && mdb.Modal) {
                            const modalInstance = mdb.Modal.getInstance(modalEl);
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                        } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            const modalInstance = bootstrap.Modal.getInstance(modalEl);
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
