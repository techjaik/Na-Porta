// Na Porta - Main JavaScript File

// Global Variables
let cart = [];
let isLoading = false;

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Initialize Application
function initializeApp() {
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
    
    // Initialize cart functionality
    initializeCart();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize address lookup
    initializeAddressLookup();
    
    // Initialize lazy loading
    initializeLazyLoading();
    
    // Initialize smooth scrolling
    initializeSmoothScrolling();
}

// Form Validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

// Cart Functionality
function initializeCart() {
    // Add to cart buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-to-cart')) {
            e.preventDefault();
            const productId = e.target.dataset.productId;
            const quantity = e.target.dataset.quantity || 1;
            addToCart(productId, quantity);
        }
        
        if (e.target.classList.contains('remove-from-cart')) {
            e.preventDefault();
            const productId = e.target.dataset.productId;
            removeFromCart(productId);
        }
        
        if (e.target.classList.contains('update-cart-quantity')) {
            const productId = e.target.dataset.productId;
            const quantity = e.target.value;
            updateCartQuantity(productId, quantity);
        }
    });
}

// Add to Cart Function
function addToCart(productId, quantity = 1) {
    if (isLoading) return;
    
    showLoading();
    
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: parseInt(quantity)
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            updateCartUI(data.cart_count, data.cart_total);
            showNotification('Produto adicionado ao carrinho!', 'success');
        } else {
            showNotification(data.message || 'Erro ao adicionar produto', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('Erro ao adicionar produto', 'error');
        console.error('Error:', error);
    });
}

// Remove from Cart Function
function removeFromCart(productId) {
    if (isLoading) return;
    
    showLoading();
    
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            updateCartUI(data.cart_count, data.cart_total);
            showNotification('Produto removido do carrinho', 'info');
            
            // Remove item from DOM if on cart page
            const cartItem = document.querySelector(`[data-cart-item="${productId}"]`);
            if (cartItem) {
                cartItem.remove();
            }
        } else {
            showNotification(data.message || 'Erro ao remover produto', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('Erro ao remover produto', 'error');
        console.error('Error:', error);
    });
}

// Update Cart Quantity
function updateCartQuantity(productId, quantity) {
    if (isLoading || quantity < 1) return;
    
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update',
            product_id: productId,
            quantity: parseInt(quantity)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartUI(data.cart_count, data.cart_total);
            
            // Update item total if on cart page
            const itemTotal = document.querySelector(`[data-item-total="${productId}"]`);
            if (itemTotal) {
                itemTotal.textContent = formatCurrency(data.item_total);
            }
        } else {
            showNotification(data.message || 'Erro ao atualizar quantidade', 'error');
        }
    })
    .catch(error => {
        showNotification('Erro ao atualizar quantidade', 'error');
        console.error('Error:', error);
    });
}

// Update Cart UI
function updateCartUI(count, total) {
    // Update cart count badge
    const cartBadge = document.querySelector('.cart-badge');
    if (cartBadge) {
        cartBadge.textContent = count;
        cartBadge.style.display = count > 0 ? 'flex' : 'none';
    }
    
    // Update cart total
    const cartTotal = document.querySelector('.cart-total');
    if (cartTotal) {
        cartTotal.textContent = formatCurrency(total);
    }
}

// Search Functionality
function initializeSearch() {
    const searchInput = document.querySelector('input[name="q"]');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3) {
                    performSearch(this.value);
                }
            }, 300);
        });
    }
}

// Perform Search
function performSearch(query) {
    fetch(`/Na%20Porta/api/search.php?q=${encodeURIComponent(query)}`)
    .then(response => response.json())
    .then(data => {
        displaySearchResults(data.results);
    })
    .catch(error => {
        console.error('Search error:', error);
    });
}

// Display Search Results
function displaySearchResults(results) {
    const searchResults = document.querySelector('.search-results');
    if (!searchResults) return;
    
    if (results.length === 0) {
        searchResults.innerHTML = '<p class="text-muted">Nenhum produto encontrado.</p>';
        return;
    }
    
    let html = '<div class="row">';
    results.forEach(product => {
        html += `
            <div class="col-md-4 mb-4">
                <div class="card product-card">
                    <img src="${product.image}" class="card-img-top" alt="${product.name}">
                    <div class="card-body">
                        <h5 class="card-title">${product.name}</h5>
                        <p class="card-text">${product.short_description}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="product-price">${formatCurrency(product.price)}</span>
                            <button class="btn btn-primary add-to-cart" data-product-id="${product.id}">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    searchResults.innerHTML = html;
}

// Address Lookup (CEP)
function initializeAddressLookup() {
    const cepInputs = document.querySelectorAll('input[name="cep"]');
    
    cepInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            if (cep.length === 8) {
                lookupAddress(cep);
            }
        });
        
        // Format CEP as user types
        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
            }
            this.value = value;
        });
    });
}

// Lookup Address by CEP
function lookupAddress(cep) {
    showLoading();
    
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (!data.erro) {
            fillAddressFields(data);
        } else {
            showNotification('CEP não encontrado', 'warning');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('Erro ao buscar CEP', 'error');
        console.error('CEP lookup error:', error);
    });
}

// Fill Address Fields
function fillAddressFields(addressData) {
    const fields = {
        'street': addressData.logradouro,
        'neighborhood': addressData.bairro,
        'city': addressData.localidade,
        'state': addressData.uf
    };
    
    Object.keys(fields).forEach(fieldName => {
        const field = document.querySelector(`input[name="${fieldName}"]`);
        if (field && fields[fieldName]) {
            field.value = fields[fieldName];
        }
    });
    
    // Focus on number field
    const numberField = document.querySelector('input[name="number"]');
    if (numberField) {
        numberField.focus();
    }
}

// Lazy Loading
function initializeLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        images.forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
        });
    }
}

// Smooth Scrolling
function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Utility Functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(amount);
}

function showNotification(message, type = 'info') {
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

function showLoading() {
    isLoading = true;
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

function hideLoading() {
    isLoading = false;
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// CPF Validation and Formatting
function formatCPF(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.length > 11) {
        value = value.substring(0, 11);
    }
    
    if (value.length > 9) {
        value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    } else if (value.length > 6) {
        value = value.replace(/(\d{3})(\d{3})(\d{3})/, '$1.$2.$3');
    } else if (value.length > 3) {
        value = value.replace(/(\d{3})(\d{3})/, '$1.$2');
    }
    
    input.value = value;
}

function validateCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    
    if (cpf.length !== 11) return false;
    if (/^(\d)\1{10}$/.test(cpf)) return false;
    
    let sum = 0;
    for (let i = 0; i < 9; i++) {
        sum += parseInt(cpf.charAt(i)) * (10 - i);
    }
    
    let remainder = 11 - (sum % 11);
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(cpf.charAt(9))) return false;
    
    sum = 0;
    for (let i = 0; i < 10; i++) {
        sum += parseInt(cpf.charAt(i)) * (11 - i);
    }
    
    remainder = 11 - (sum % 11);
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(cpf.charAt(10))) return false;
    
    return true;
}

// Phone Formatting
function formatPhone(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.length > 11) {
        value = value.substring(0, 11);
    }
    
    if (value.length > 10) {
        value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (value.length > 6) {
        value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    } else if (value.length > 2) {
        value = value.replace(/(\d{2})(\d{4})/, '($1) $2');
    }
    
    input.value = value;
}

// Image Preview
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Debounce Function
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

// Export functions for global use
window.NaPorta = {
    addToCart,
    removeFromCart,
    updateCartQuantity,
    formatCurrency,
    showNotification,
    showLoading,
    hideLoading,
    formatCPF,
    validateCPF,
    formatPhone,
    previewImage,
    debounce
};
