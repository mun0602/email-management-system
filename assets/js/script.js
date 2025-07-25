/**
 * Email Management System JavaScript
 * Chức năng JavaScript chung cho toàn bộ hệ thống
 */

class EmailManagement {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initTooltips();
        this.setupCopyButtons();
        this.setupConfirmModals();
        this.setupFormValidation();
    }

    setupEventListeners() {
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', () => {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.classList.contains('alert-dismissible')) {
                        const closeBtn = alert.querySelector('.btn-close');
                        if (closeBtn) closeBtn.click();
                    }
                }, 5000);
            });
        });

        // Real-time search functionality
        const searchInputs = document.querySelectorAll('[data-search]');
        searchInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                this.performSearch(e.target.value, e.target.dataset.search);
            });
        });

        // Auto-refresh functionality
        if (document.querySelector('[data-auto-refresh]')) {
            setInterval(() => {
                this.refreshStats();
            }, 30000); // Refresh every 30 seconds
        }
    }

    initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    setupCopyButtons() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('copy-btn') || e.target.closest('.copy-btn')) {
                e.preventDefault();
                const btn = e.target.classList.contains('copy-btn') ? e.target : e.target.closest('.copy-btn');
                const text = btn.dataset.copy;
                
                if (text) {
                    this.copyToClipboard(text, btn);
                }
            }
        });
    }

    async copyToClipboard(text, button = null) {
        try {
            await navigator.clipboard.writeText(text);
            
            if (button) {
                const originalText = button.innerHTML;
                const originalClass = button.className;
                
                button.innerHTML = '<i class="bi bi-check"></i> Đã sao chép!';
                button.classList.add('copied', 'btn-success');
                button.classList.remove('btn-outline-secondary', 'btn-outline-primary');
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.className = originalClass;
                }, 2000);
            }
            
            this.showToast('Đã sao chép vào clipboard!', 'success');
        } catch (err) {
            console.error('Copy failed:', err);
            this.showToast('Không thể sao chép. Vui lòng thử lại!', 'error');
        }
    }

    setupConfirmModals() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('confirm-action') || e.target.closest('.confirm-action')) {
                e.preventDefault();
                const element = e.target.classList.contains('confirm-action') ? e.target : e.target.closest('.confirm-action');
                const message = element.dataset.message || 'Bạn có chắc chắn muốn thực hiện hành động này?';
                const action = element.dataset.action || element.href;
                
                this.showConfirmModal(message, action);
            }
        });
    }

    showConfirmModal(message, action) {
        const modal = document.getElementById('confirmModal');
        const messageElement = document.getElementById('confirmMessage');
        const confirmButton = document.getElementById('confirmAction');
        
        if (modal && messageElement && confirmButton) {
            messageElement.textContent = message;
            
            // Remove previous event listeners
            const newConfirmButton = confirmButton.cloneNode(true);
            confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
            
            // Add new event listener
            newConfirmButton.addEventListener('click', () => {
                if (action.startsWith('http') || action.startsWith('/')) {
                    window.location.href = action;
                } else if (typeof window[action] === 'function') {
                    window[action]();
                }
                bootstrap.Modal.getInstance(modal).hide();
            });
            
            new bootstrap.Modal(modal).show();
        }
    }

    setupFormValidation() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    }

    showToast(message, type = 'info') {
        const toastContainer = document.querySelector('.toast-container');
        const toastElement = document.getElementById('toastMessage');
        
        if (!toastElement) return;

        const iconMap = {
            success: 'bi-check-circle text-success',
            error: 'bi-exclamation-triangle text-danger',
            warning: 'bi-exclamation-triangle text-warning',
            info: 'bi-info-circle text-info'
        };

        const icon = toastElement.querySelector('.toast-header i');
        const body = toastElement.querySelector('.toast-body');
        
        if (icon && body) {
            icon.className = `bi ${iconMap[type] || iconMap.info} me-2`;
            body.textContent = message;
            
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
        }
    }

    showLoading() {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.style.display = 'flex';
        }
    }

    hideLoading() {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.style.display = 'none';
        }
    }

    async makeRequest(url, options = {}) {
        this.showLoading();
        
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                },
                ...options
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }
            
            return data;
        } catch (error) {
            this.showToast(error.message, 'error');
            throw error;
        } finally {
            this.hideLoading();
        }
    }

    performSearch(query, target) {
        const rows = document.querySelectorAll(`[data-searchable="${target}"]`);
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const match = text.includes(query.toLowerCase());
            row.style.display = match ? '' : 'none';
        });
    }

    async refreshStats() {
        try {
            const response = await this.makeRequest('/api/statistics.php');
            
            // Update stat cards
            Object.keys(response.stats).forEach(key => {
                const element = document.querySelector(`[data-stat="${key}"]`);
                if (element) {
                    element.textContent = response.stats[key];
                }
            });
            
        } catch (error) {
            console.error('Failed to refresh stats:', error);
        }
    }

    // Chart utilities
    createChart(ctx, type, data, options = {}) {
        return new Chart(ctx, {
            type: type,
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: type !== 'pie' && type !== 'doughnut' ? {
                    y: {
                        beginAtZero: true
                    }
                } : {},
                ...options
            }
        });
    }

    // Utility functions
    formatNumber(num) {
        return new Intl.NumberFormat('vi-VN').format(num);
    }

    formatDate(date, format = 'dd/MM/yyyy HH:mm') {
        return new Intl.DateTimeFormat('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(date));
    }

    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize the application
const app = new EmailManagement();

// Global functions for backward compatibility
function copyToClipboard(text, button) {
    app.copyToClipboard(text, button);
}

function showToast(message, type) {
    app.showToast(message, type);
}

function showConfirmModal(message, action) {
    app.showConfirmModal(message, action);
}

// Export for use in other scripts
window.EmailManagement = EmailManagement;
window.app = app;