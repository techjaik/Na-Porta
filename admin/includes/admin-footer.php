        </main>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Admin Common Scripts -->
    <script>
        // Common admin functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.parentElement) {
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 300);
                    }
                }, 5000);
            });
            
            // Confirm delete actions
            const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const message = this.dataset.confirmDelete || 'Tem certeza que deseja excluir este item?';
                    if (!confirm(message)) {
                        e.preventDefault();
                    }
                });
            });
            
            // Loading states for forms
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
                    }
                });
            });
            
            // Tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; opacity: 0; transition: opacity 0.3s ease;';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
            document.body.appendChild(toast);
            
            // Fade in
            setTimeout(() => toast.style.opacity = '1', 100);
            
            // Auto remove
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }
            }, 4000);
        }
    </script>
</body>
</html>
