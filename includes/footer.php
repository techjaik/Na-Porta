    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5">
        <div class="container py-5">
            <div class="row">
                <!-- Company Info -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-home me-2"></i>Na Porta
                    </h5>
                    <p class="text-light">
                        Essenciais domésticos entregues na sua porta. 
                        Água, gás, produtos de limpeza e mercearia com segurança e praticidade.
                    </p>
                    <div class="d-flex">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-whatsapp"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Links Rápidos</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/pages/home.php" class="text-light text-decoration-none">Início</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/products.php" class="text-light text-decoration-none">Produtos</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/about.php" class="text-light text-decoration-none">Sobre Nós</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/contact.php" class="text-light text-decoration-none">Contato</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/faq.php" class="text-light text-decoration-none">FAQ</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Categorias</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/pages/products.php?category=agua" class="text-light text-decoration-none">Água</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/products.php?category=gas" class="text-light text-decoration-none">Gás</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/products.php?category=limpeza" class="text-light text-decoration-none">Limpeza</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/products.php?category=mercearia" class="text-light text-decoration-none">Mercearia</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Contato</h6>
                    <div class="mb-2">
                        <i class="fas fa-phone me-2"></i>
                        <span>(11) 99999-9999</span>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-envelope me-2"></i>
                        <span>contato@naporta.com.br</span>
                    </div>
                    <div class="mb-3">
                        <i class="fas fa-clock me-2"></i>
                        <span>Seg-Sáb: 8h às 18h</span>
                    </div>

                    <!-- Newsletter -->
                    <h6 class="fw-bold mb-3">Newsletter</h6>
                    <form action="<?php echo SITE_URL; ?>/api/newsletter.php" method="POST" class="newsletter-form">
                        <div class="input-group">
                            <input type="email" class="form-control" name="email" placeholder="Seu e-mail" required>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <hr class="my-4">

            <!-- Bottom Footer -->
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Na Porta. Todos os direitos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?php echo SITE_URL; ?>/pages/privacy.php" class="text-light text-decoration-none me-3">Privacidade</a>
                    <a href="<?php echo SITE_URL; ?>/pages/terms.php" class="text-light text-decoration-none me-3">Termos</a>
                    <a href="<?php echo SITE_URL; ?>/pages/lgpd.php" class="text-light text-decoration-none">LGPD</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="back-to-top" class="btn btn-primary position-fixed bottom-0 end-0 m-3 rounded-circle" style="display: none; z-index: 1000;">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
         style="background: rgba(255,255,255,0.9); z-index: 9999; display: none !important;">
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="text-muted">Carregando...</p>
        </div>
    </div>

    <!-- Scripts -->
    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>

    <script>
        // LGPD Functions
        function acceptLGPD() {
            fetch('<?php echo SITE_URL; ?>/api/lgpd.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ consent: true })
            }).then(() => {
                document.getElementById('lgpd-banner').style.display = 'none';
            });
        }

        function declineLGPD() {
            fetch('<?php echo SITE_URL; ?>/api/lgpd.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ consent: false })
            }).then(() => {
                document.getElementById('lgpd-banner').style.display = 'none';
                alert('Você pode alterar suas preferências de cookies a qualquer momento nas configurações.');
            });
        }

        // Back to Top Button
        window.addEventListener('scroll', function() {
            const backToTop = document.getElementById('back-to-top');
            if (window.pageYOffset > 300) {
                backToTop.style.display = 'block';
            } else {
                backToTop.style.display = 'none';
            }
        });

        document.getElementById('back-to-top').addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Newsletter Form
        document.querySelector('.newsletter-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Obrigado por se inscrever em nossa newsletter!');
                    this.reset();
                } else {
                    alert(data.message || 'Erro ao se inscrever. Tente novamente.');
                }
            })
            .catch(error => {
                alert('Erro ao se inscrever. Tente novamente.');
            });
        });

        // Loading Overlay Functions
        function showLoading() {
            document.getElementById('loading-overlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        // Auto-hide loading on page load
        window.addEventListener('load', hideLoading);
    </script>

    <?php if (isset($extra_js)): ?>
        <?php echo $extra_js; ?>
    <?php endif; ?>
</body>
</html>
