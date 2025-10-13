    </main>

    <!-- Admin Footer -->
    <footer class="admin-footer bg-light border-top mt-5">
        <div class="container-fluid py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small class="text-muted">
                        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. 
                        Painel Administrativo v1.0
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        <i class="fas fa-user-shield me-1"></i>
                        Logado como: <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                        (<?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'admin'); ?>)
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Loading Overlay -->
    <div id="admin-loading" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
         style="background: rgba(255,255,255,0.9); z-index: 9999; display: none !important;">
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="text-muted">Processando...</p>
        </div>
    </div>

    <!-- Scripts -->
    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <!-- Admin JavaScript -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/admin.js"></script>

    <script>
        // Admin Loading Functions
        function showAdminLoading() {
            document.getElementById('admin-loading').style.display = 'flex';
        }

        function hideAdminLoading() {
            document.getElementById('admin-loading').style.display = 'none';
        }

        // Auto-hide loading on page load
        window.addEventListener('load', hideAdminLoading);

        // Confirm delete actions
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('confirm-delete') || 
                e.target.closest('.confirm-delete')) {
                if (!confirm('Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.')) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        // Auto-refresh for real-time data
        function autoRefreshData() {
            const refreshElements = document.querySelectorAll('[data-auto-refresh]');
            refreshElements.forEach(element => {
                const interval = parseInt(element.dataset.autoRefresh) * 1000;
                if (interval > 0) {
                    setInterval(() => {
                        location.reload();
                    }, interval);
                }
            });
        }

        // Initialize auto-refresh
        document.addEventListener('DOMContentLoaded', autoRefreshData);

        // Notification function
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

        // Export functions for global use
        window.AdminPanel = {
            showLoading: showAdminLoading,
            hideLoading: hideAdminLoading,
            showNotification: showAdminNotification
        };
    </script>

    <?php if (isset($extra_admin_js)): ?>
        <?php echo $extra_admin_js; ?>
    <?php endif; ?>
</body>
</html>
