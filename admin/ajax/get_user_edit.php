<?php
/**
 * AJAX endpoint to get user edit form
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$userId = intval($_GET['id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de usuário inválido']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get user details
    $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        exit;
    }
    
    // Build HTML form
    ob_start();
    ?>
    <form id="editUserForm" onsubmit="return submitUserEdit(event)">
        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Nome Completo *</label>
                <input type="text" name="name" class="form-control" required 
                       value="<?= htmlspecialchars($user['name']) ?>">
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" required readonly
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
                <label class="form-label">CPF/CNPJ</label>
                <input type="text" name="cpf_cnpj" class="form-control" 
                       value="<?= htmlspecialchars($user['cpf_cnpj'] ?? '') ?>"
                       placeholder="000.000.000-00">
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Gênero</label>
                <select name="gender" class="form-control">
                    <option value="">Selecione...</option>
                    <option value="masculino" <?= ($user['gender'] ?? '') === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                    <option value="feminino" <?= ($user['gender'] ?? '') === 'feminino' ? 'selected' : '' ?>>Feminino</option>
                    <option value="outro" <?= ($user['gender'] ?? '') === 'outro' ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Data de Nascimento</label>
                <input type="date" name="birth_date" class="form-control" 
                       value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>">
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Endereço Completo</label>
            <textarea name="address" class="form-control" rows="2" 
                      placeholder="Rua, número, complemento"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Cidade</label>
                <input type="text" name="city" class="form-control" 
                       value="<?= htmlspecialchars($user['city'] ?? '') ?>"
                       placeholder="São Paulo">
            </div>
            
            <div class="col-md-3 mb-3">
                <label class="form-label">Estado</label>
                <input type="text" name="state" class="form-control" 
                       value="<?= htmlspecialchars($user['state'] ?? '') ?>"
                       placeholder="SP" maxlength="2">
            </div>
            
            <div class="col-md-3 mb-3">
                <label class="form-label">CEP</label>
                <input type="text" name="zip_code" class="form-control" 
                       value="<?= htmlspecialchars($user['zip_code'] ?? '') ?>"
                       placeholder="00000-000">
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" id="isActive"
                           <?= $user['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isActive">
                        Usuário Ativo
                    </label>
                </div>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label text-muted">Cadastrado em</label>
                <div class="fw-medium"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-save me-2"></i>Salvar Alterações
            </button>
        </div>
    </form>
    
    <script>
    function submitUserEdit(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';
        
        fetch('ajax/update_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and refresh page
                bootstrap.Modal.getInstance(document.getElementById('userEditModal')).hide();
                showToast('Usuário atualizado com sucesso!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast('Erro: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Erro de conexão. Tente novamente.', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Salvar Alterações';
        });
        
        return false;
    }
    
    // Apply masks
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
    
    document.querySelector('input[name="cpf_cnpj"]').addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length <= 11) {
            // CPF format
            if (value.length >= 9) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2})/, '$1.$2.$3-$4');
            } else if (value.length >= 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{0,3})/, '$1.$2.$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{3})(\d{0,3})/, '$1.$2');
            }
        } else {
            // CNPJ format
            if (value.length >= 12) {
                value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2})/, '$1.$2.$3/$4-$5');
            } else if (value.length >= 8) {
                value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{0,4})/, '$1.$2.$3/$4');
            } else if (value.length >= 5) {
                value = value.replace(/(\d{2})(\d{3})(\d{0,3})/, '$1.$2.$3');
            } else if (value.length >= 2) {
                value = value.replace(/(\d{2})(\d{0,3})/, '$1.$2');
            }
        }
        this.value = value;
    });
    
    document.querySelector('input[name="zip_code"]').addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length >= 5) {
            value = value.replace(/(\d{5})(\d{0,3})/, '$1-$2');
        }
        this.value = value;
    });
    </script>
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}
?>
