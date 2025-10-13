// Admin Panel JavaScript

// Global Variables
let adminData = {};
let currentPage = 1;
let isProcessing = false;

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    initializeAdminPanel();
});

// Initialize Admin Panel
function initializeAdminPanel() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-mdb-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new mdb.Tooltip(tooltipTriggerEl);
    });

    // Initialize modals
    const modalList = [].slice.call(document.querySelectorAll('.modal'));
    modalList.map(function (modalEl) {
        return new mdb.Modal(modalEl);
    });

    // Initialize form validation
    initializeFormValidation();
    
    // Initialize data tables
    initializeDataTables();
    
    // Initialize file uploads
    initializeFileUploads();
    
    // Initialize charts (if needed)
    initializeCharts();
    
    // Initialize real-time updates
    initializeRealTimeUpdates();
}

// Form Validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Focus on first invalid field
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
            form.classList.add('was-validated');
        }, false);
    });
}

// Data Tables
function initializeDataTables() {
    const tables = document.querySelectorAll('.admin-table');
    
    tables.forEach(table => {
        // Add sorting functionality
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                sortTable(table, header.dataset.sort);
            });
        });
        
        // Add search functionality
        const searchInput = table.parentElement.querySelector('.table-search');
        if (searchInput) {
            searchInput.addEventListener('input', debounce(() => {
                filterTable(table, searchInput.value);
            }, 300));
        }
    });
}

// Sort Table
function sortTable(table, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isAscending = table.dataset.sortDirection !== 'asc';
    
    rows.sort((a, b) => {
        const aValue = a.querySelector(`[data-sort="${column}"]`)?.textContent.trim() || '';
        const bValue = b.querySelector(`[data-sort="${column}"]`)?.textContent.trim() || '';
        
        if (isNumeric(aValue) && isNumeric(bValue)) {
            return isAscending ? 
                parseFloat(aValue) - parseFloat(bValue) : 
                parseFloat(bValue) - parseFloat(aValue);
        }
        
        return isAscending ? 
            aValue.localeCompare(bValue) : 
            bValue.localeCompare(aValue);
    });
    
    // Update table
    rows.forEach(row => tbody.appendChild(row));
    table.dataset.sortDirection = isAscending ? 'asc' : 'desc';
    
    // Update sort indicators
    table.querySelectorAll('th[data-sort]').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    const currentHeader = table.querySelector(`th[data-sort="${column}"]`);
    currentHeader.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
}

// Filter Table
function filterTable(table, searchTerm) {
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());
        row.style.display = matches ? '' : 'none';
    });
}

// File Uploads
function initializeFileUploads() {
    const uploadAreas = document.querySelectorAll('.file-upload-area');
    
    uploadAreas.forEach(area => {
        const input = area.querySelector('input[type="file"]');
        const preview = area.querySelector('.upload-preview');
        
        // Click to upload
        area.addEventListener('click', () => input.click());
        
        // Drag and drop
        area.addEventListener('dragover', (e) => {
            e.preventDefault();
            area.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', () => {
            area.classList.remove('dragover');
        });
        
        area.addEventListener('drop', (e) => {
            e.preventDefault();
            area.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                handleFileUpload(input, preview);
            }
        });
        
        // File input change
        input.addEventListener('change', () => {
            handleFileUpload(input, preview);
        });
    });
}

// Handle File Upload
function handleFileUpload(input, preview) {
    const file = input.files[0];
    if (!file) return;
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showAdminNotification('Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.', 'error');
        return;
    }
    
    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        showAdminNotification('Arquivo muito grande. Máximo 5MB.', 'error');
        return;
    }
    
    // Show preview
    if (preview) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;">`;
        };
        reader.readAsDataURL(file);
    }
}

// Charts (placeholder for future implementation)
function initializeCharts() {
    // Chart.js or other charting library integration
    const chartElements = document.querySelectorAll('.admin-chart');
    
    chartElements.forEach(element => {
        const chartType = element.dataset.chartType;
        const chartData = JSON.parse(element.dataset.chartData || '{}');
        
        // Initialize chart based on type
        switch (chartType) {
            case 'line':
                createLineChart(element, chartData);
                break;
            case 'bar':
                createBarChart(element, chartData);
                break;
            case 'pie':
                createPieChart(element, chartData);
                break;
        }
    });
}

// Real-time Updates
function initializeRealTimeUpdates() {
    // Check for real-time update elements
    const updateElements = document.querySelectorAll('[data-realtime]');
    
    if (updateElements.length > 0) {
        // Set up periodic updates
        setInterval(() => {
            updateRealTimeData();
        }, 30000); // Update every 30 seconds
    }
}

// Update Real-time Data
function updateRealTimeData() {
    fetch('/Na%20Porta/admin/api/realtime-data.php')
        .then(response => response.json())
        .then(data => {
            // Update pending orders count
            const pendingOrders = document.querySelector('[data-realtime="pending-orders"]');
            if (pendingOrders && data.pending_orders !== undefined) {
                pendingOrders.textContent = data.pending_orders;
            }
            
            // Update low stock count
            const lowStock = document.querySelector('[data-realtime="low-stock"]');
            if (lowStock && data.low_stock !== undefined) {
                lowStock.textContent = data.low_stock;
            }
            
            // Update today's revenue
            const todayRevenue = document.querySelector('[data-realtime="today-revenue"]');
            if (todayRevenue && data.today_revenue !== undefined) {
                todayRevenue.textContent = formatCurrency(data.today_revenue);
            }
        })
        .catch(error => {
            console.error('Real-time update error:', error);
        });
}

// Product Management
function addProduct() {
    window.location.href = '/Na%20Porta/admin/products.php?action=add';
}

function editProduct(productId) {
    window.location.href = `/Na%20Porta/admin/products.php?action=edit&id=${productId}`;
}

function deleteProduct(productId) {
    if (confirm('Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.')) {
        showAdminLoading();
        
        fetch('/Na%20Porta/admin/api/products.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: productId })
        })
        .then(response => response.json())
        .then(data => {
            hideAdminLoading();
            
            if (data.success) {
                showAdminNotification('Produto excluído com sucesso!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAdminNotification(data.message || 'Erro ao excluir produto', 'error');
            }
        })
        .catch(error => {
            hideAdminLoading();
            showAdminNotification('Erro ao excluir produto', 'error');
        });
    }
}

function toggleProductStatus(productId, currentStatus) {
    const newStatus = currentStatus === '1' ? '0' : '1';
    const action = newStatus === '1' ? 'ativar' : 'desativar';
    
    if (confirm(`Tem certeza que deseja ${action} este produto?`)) {
        fetch('/Na%20Porta/admin/api/products.php', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                id: productId, 
                action: 'toggle_status',
                status: newStatus 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAdminNotification(`Produto ${action === 'ativar' ? 'ativado' : 'desativado'} com sucesso!`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAdminNotification(data.message || 'Erro ao alterar status', 'error');
            }
        })
        .catch(error => {
            showAdminNotification('Erro ao alterar status do produto', 'error');
        });
    }
}

// Order Management
function updateOrderStatus(orderId, newStatus) {
    if (confirm(`Tem certeza que deseja alterar o status deste pedido para "${newStatus}"?`)) {
        showAdminLoading();
        
        fetch('/Na%20Porta/admin/api/orders.php', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                id: orderId, 
                action: 'update_status',
                status: newStatus 
            })
        })
        .then(response => response.json())
        .then(data => {
            hideAdminLoading();
            
            if (data.success) {
                showAdminNotification('Status do pedido atualizado com sucesso!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAdminNotification(data.message || 'Erro ao atualizar status', 'error');
            }
        })
        .catch(error => {
            hideAdminLoading();
            showAdminNotification('Erro ao atualizar status do pedido', 'error');
        });
    }
}

// User Management
function toggleUserStatus(userId, currentStatus) {
    const newStatus = currentStatus === '1' ? '0' : '1';
    const action = newStatus === '1' ? 'ativar' : 'desativar';
    
    if (confirm(`Tem certeza que deseja ${action} este usuário?`)) {
        fetch('/Na%20Porta/admin/api/users.php', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                id: userId, 
                action: 'toggle_status',
                status: newStatus 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAdminNotification(`Usuário ${action === 'ativar' ? 'ativado' : 'desativado'} com sucesso!`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAdminNotification(data.message || 'Erro ao alterar status', 'error');
            }
        })
        .catch(error => {
            showAdminNotification('Erro ao alterar status do usuário', 'error');
        });
    }
}

// Utility Functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('pt-BR');
}

function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString('pt-BR');
}

function isNumeric(str) {
    return !isNaN(str) && !isNaN(parseFloat(str));
}

function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        
        if (callNow) func.apply(context, args);
    };
}

function showAdminLoading() {
    const overlay = document.getElementById('admin-loading');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

function hideAdminLoading() {
    const overlay = document.getElementById('admin-loading');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function showAdminNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Bulk Actions
function selectAllItems(checkbox) {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

function updateBulkActions() {
    const selectedItems = document.querySelectorAll('.item-checkbox:checked');
    const bulkActions = document.querySelector('.bulk-actions');
    
    if (bulkActions) {
        bulkActions.style.display = selectedItems.length > 0 ? 'block' : 'none';
    }
}

function performBulkAction(action) {
    const selectedItems = Array.from(document.querySelectorAll('.item-checkbox:checked'))
        .map(cb => cb.value);
    
    if (selectedItems.length === 0) {
        showAdminNotification('Selecione pelo menos um item', 'warning');
        return;
    }
    
    if (confirm(`Tem certeza que deseja ${action} ${selectedItems.length} item(s)?`)) {
        showAdminLoading();
        
        fetch('/Na%20Porta/admin/api/bulk-actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                action: action,
                items: selectedItems 
            })
        })
        .then(response => response.json())
        .then(data => {
            hideAdminLoading();
            
            if (data.success) {
                showAdminNotification(`Ação "${action}" executada com sucesso!`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAdminNotification(data.message || 'Erro ao executar ação', 'error');
            }
        })
        .catch(error => {
            hideAdminLoading();
            showAdminNotification('Erro ao executar ação em lote', 'error');
        });
    }
}

// Export functions for global use
window.AdminPanel = {
    showLoading: showAdminLoading,
    hideLoading: hideAdminLoading,
    showNotification: showAdminNotification,
    formatCurrency: formatCurrency,
    formatDate: formatDate,
    formatDateTime: formatDateTime,
    addProduct: addProduct,
    editProduct: editProduct,
    deleteProduct: deleteProduct,
    toggleProductStatus: toggleProductStatus,
    updateOrderStatus: updateOrderStatus,
    toggleUserStatus: toggleUserStatus,
    selectAllItems: selectAllItems,
    updateBulkActions: updateBulkActions,
    performBulkAction: performBulkAction
};
