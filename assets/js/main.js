/**
 * Sanjivani University MAP Management System
 * Main JavaScript functionality
 */

// Global configuration
const APP_CONFIG = {
    API_BASE_URL: '',
    MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
    ALLOWED_FILE_TYPES: {
        certificate: ['application/pdf', 'image/jpeg', 'image/png'],
        proof: ['image/jpeg', 'image/png']
    },
    TOAST_DURATION: 5000
};

// Utility functions
const Utils = {
    /**
     * Format file size in human readable format
     */
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    /**
     * Validate file type and size
     */
    validateFile: function(file, allowedTypes, maxSize = APP_CONFIG.MAX_FILE_SIZE) {
        if (!allowedTypes.includes(file.type)) {
            return {
                valid: false,
                message: 'Invalid file type. Allowed types: ' + allowedTypes.join(', ')
            };
        }
        
        if (file.size > maxSize) {
            return {
                valid: false,
                message: 'File size too large. Maximum size: ' + this.formatFileSize(maxSize)
            };
        }
        
        return { valid: true };
    },

    /**
     * Show toast notification
     */
    showToast: function(message, type = 'info', duration = APP_CONFIG.TOAST_DURATION) {
        const toastContainer = this.getOrCreateToastContainer();
        const toastId = 'toast-' + Date.now();
        
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${this.getToastIcon(type)} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: duration });
        
        toast.show();
        
        // Remove from DOM after hide
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    },

    /**
     * Get or create toast container
     */
    getOrCreateToastContainer: function() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        return container;
    },

    /**
     * Get appropriate icon for toast type
     */
    getToastIcon: function(type) {
        const icons = {
            success: 'check-circle',
            danger: 'exclamation-triangle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    },

    /**
     * Debounce function
     */
    debounce: function(func, wait, immediate) {
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
    },

    /**
     * Format date
     */
    formatDate: function(dateString, format = 'DD MMM YYYY') {
        const date = new Date(dateString);
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        };
        return date.toLocaleDateString('en-US', options);
    },

    /**
     * Calculate percentage
     */
    calculatePercentage: function(value, total) {
        if (total === 0) return 0;
        return Math.round((value / total) * 100 * 100) / 100;
    }
};

// Form handling
const FormHandler = {
    /**
     * Initialize form handlers
     */
    init: function() {
        this.setupFileUploads();
        this.setupFormValidation();
        this.setupAjaxForms();
    },

    /**
     * Setup file upload handlers
     */
    setupFileUploads: function() {
        // File input change handlers
        document.addEventListener('change', function(e) {
            if (e.target.type === 'file') {
                FormHandler.handleFileSelection(e.target);
            }
        });

        // Drag and drop handlers
        document.addEventListener('dragover', function(e) {
            e.preventDefault();
            if (e.target.classList.contains('file-upload-area')) {
                e.target.classList.add('dragover');
            }
        });

        document.addEventListener('dragleave', function(e) {
            if (e.target.classList.contains('file-upload-area')) {
                e.target.classList.remove('dragover');
            }
        });

        document.addEventListener('drop', function(e) {
            e.preventDefault();
            if (e.target.classList.contains('file-upload-area')) {
                e.target.classList.remove('dragover');
                const fileInput = e.target.querySelector('input[type="file"]');
                if (fileInput && e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    FormHandler.handleFileSelection(fileInput);
                }
            }
        });
    },

    /**
     * Handle file selection
     */
    handleFileSelection: function(input) {
        const file = input.files[0];
        if (!file) return;

        const fileType = input.name === 'certificate' ? 'certificate' : 'proof';
        const allowedTypes = APP_CONFIG.ALLOWED_FILE_TYPES[fileType];
        
        const validation = Utils.validateFile(file, allowedTypes);
        
        if (!validation.valid) {
            Utils.showToast(validation.message, 'danger');
            input.value = '';
            return;
        }

        // Show file info
        this.displayFileInfo(input, file);
    },

    /**
     * Display file information
     */
    displayFileInfo: function(input, file) {
        const container = input.closest('.mb-3');
        let infoDiv = container.querySelector('.file-info');
        
        if (!infoDiv) {
            infoDiv = document.createElement('div');
            infoDiv.className = 'file-info mt-2';
            container.appendChild(infoDiv);
        }
        
        infoDiv.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-file me-2 text-success"></i>
                <span class="me-2">${file.name}</span>
                <span class="badge bg-secondary">${Utils.formatFileSize(file.size)}</span>
            </div>
        `;
    },

    /**
     * Setup form validation
     */
    setupFormValidation: function() {
        // Bootstrap validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Custom validation rules
        document.addEventListener('input', function(e) {
            if (e.target.type === 'email') {
                FormHandler.validateEmail(e.target);
            } else if (e.target.type === 'password') {
                FormHandler.validatePassword(e.target);
            }
        });
    },

    /**
     * Setup AJAX form submission
     */
    setupAjaxForms: function() {
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.classList.contains('ajax-form')) {
                e.preventDefault();
                FormHandler.submitAjaxForm(form);
            }
        });
    },

    /**
     * Submit form via AJAX
     */
    submitAjaxForm: function(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Utils.showToast(data.message, 'success');
                if (data.redirect) {
                    setTimeout(() => window.location.href = data.redirect, 1500);
                } else {
                    form.reset();
                }
            } else {
                Utils.showToast(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Utils.showToast('An error occurred. Please try again.', 'danger');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    },

    /**
     * Validate email
     */
    validateEmail: function(input) {
        const email = input.value;
        const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        
        if (email && !isValid) {
            input.setCustomValidity('Please enter a valid email address');
        } else {
            input.setCustomValidity('');
        }
    },

    /**
     * Validate password
     */
    validatePassword: function(input) {
        const password = input.value;
        const minLength = 6;
        
        if (password && password.length < minLength) {
            input.setCustomValidity(`Password must be at least ${minLength} characters long`);
        } else {
            input.setCustomValidity('');
        }
    }
};

// Data tables enhancement
const DataTableManager = {
    /**
     * Initialize enhanced data tables
     */
    init: function() {
        if (typeof $.fn.DataTable !== 'undefined') {
            this.setupDataTables();
        }
    },

    /**
     * Setup DataTables with custom configuration
     */
    setupDataTables: function() {
        $('.data-table').each(function() {
            const table = $(this);
            const config = {
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            };

            // Apply custom configuration if specified
            const customConfig = table.data('config');
            if (customConfig) {
                Object.assign(config, customConfig);
            }

            table.DataTable(config);
        });
    }
};

// Progress tracking
const ProgressTracker = {
    /**
     * Update progress bars with animation
     */
    updateProgress: function(selector, percentage, animated = true) {
        const progressBar = document.querySelector(selector + ' .progress-bar');
        if (progressBar) {
            if (animated) {
                progressBar.style.width = '0%';
                setTimeout(() => {
                    progressBar.style.width = percentage + '%';
                    progressBar.textContent = percentage + '%';
                }, 100);
            } else {
                progressBar.style.width = percentage + '%';
                progressBar.textContent = percentage + '%';
            }
        }
    },

    /**
     * Update multiple progress bars
     */
    updateMultipleProgress: function(progressData) {
        progressData.forEach(item => {
            this.updateProgress(item.selector, item.percentage, item.animated);
        });
    }
};

// Dashboard specific functionality
const Dashboard = {
    /**
     * Initialize dashboard
     */
    init: function() {
        this.loadDashboardData();
        this.setupRefreshHandlers();
        this.setupCharts();
    },

    /**
     * Load dashboard data
     */
    loadDashboardData: function() {
        fetch('api/get_dashboard_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateDashboardStats(data.data);
                }
            })
            .catch(error => {
                console.error('Error loading dashboard data:', error);
            });
    },

    /**
     * Update dashboard statistics
     */
    updateDashboardStats: function(data) {
        // Update based on user type
        if (data.rules && data.points) {
            this.updateStudentDashboard(data);
        } else if (data.department_stats) {
            this.updateCoordinatorDashboard(data);
        }
    },

    /**
     * Update student dashboard
     */
    updateStudentDashboard: function(data) {
        const rules = data.rules;
        const points = data.points;
        
        if (rules) {
            const pointsMap = {};
            points.forEach(p => {
                pointsMap[p.category] = p.earned_points;
            });
            
            // Update progress bars for each category
            ['A', 'B', 'C', 'D', 'E'].forEach(category => {
                const earned = pointsMap[category] || 0;
                const required = rules[category.toLowerCase() === 'a' ? 'technical' : 
                                      category.toLowerCase() === 'b' ? 'sports_cultural' :
                                      category.toLowerCase() === 'c' ? 'community_outreach' :
                                      category.toLowerCase() === 'd' ? 'innovation' : 'leadership'];
                
                const percentage = Utils.calculatePercentage(earned, required);
                ProgressTracker.updateProgress(`#category-${category}-progress`, percentage);
            });
        }
    },

    /**
     * Update coordinator dashboard
     */
    updateCoordinatorDashboard: function(data) {
        const stats = data.department_stats;
        
        // Update stats cards
        document.querySelector('#total-students').textContent = stats.total_students;
        document.querySelector('#pending-submissions').textContent = stats.pending_submissions;
        document.querySelector('#approved-submissions').textContent = stats.approved_submissions;
    },

    /**
     * Setup refresh handlers
     */
    setupRefreshHandlers: function() {
        const refreshBtn = document.querySelector('#refresh-dashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.loadDashboardData();
                Utils.showToast('Dashboard refreshed', 'success');
            });
        }
    },

    /**
     * Setup charts
     */
    setupCharts: function() {
        // Initialize Chart.js charts if present
        if (typeof Chart !== 'undefined') {
            this.initializeCharts();
        }
    },

    /**
     * Initialize Chart.js charts
     */
    initializeCharts: function() {
        // Common chart configuration
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;
        
        // Apply animations
        Chart.defaults.animation.duration = 1000;
        Chart.defaults.animation.easing = 'easeOutQuart';
    }
};

// Search and filter functionality
const SearchFilter = {
    /**
     * Initialize search and filter
     */
    init: function() {
        this.setupSearchHandlers();
        this.setupFilterHandlers();
    },

    /**
     * Setup search handlers
     */
    setupSearchHandlers: function() {
        const searchInputs = document.querySelectorAll('[data-search-target]');
        searchInputs.forEach(input => {
            input.addEventListener('input', Utils.debounce(function() {
                SearchFilter.performSearch(this);
            }, 300));
        });
    },

    /**
     * Setup filter handlers
     */
    setupFilterHandlers: function() {
        const filterSelects = document.querySelectorAll('[data-filter-target]');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                SearchFilter.performFilter(this);
            });
        });
    },

    /**
     * Perform search
     */
    performSearch: function(input) {
        const target = input.getAttribute('data-search-target');
        const searchTerm = input.value.toLowerCase();
        const items = document.querySelectorAll(target);
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    },

    /**
     * Perform filter
     */
    performFilter: function(select) {
        const target = select.getAttribute('data-filter-target');
        const filterValue = select.value;
        const filterAttribute = select.getAttribute('data-filter-attribute');
        const items = document.querySelectorAll(target);
        
        items.forEach(item => {
            if (!filterValue || item.getAttribute(filterAttribute) === filterValue) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }
};

// Document ready initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all modules
    FormHandler.init();
    DataTableManager.init();
    Dashboard.init();
    SearchFilter.init();
    
    // Initialize tooltips
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }
    
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('fade-in-up');
        }, index * 100);
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (alert.classList.contains('alert-dismissible')) {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        }
    });
});

// Export for global access
window.Utils = Utils;
window.FormHandler = FormHandler;
window.Dashboard = Dashboard;
window.ProgressTracker = ProgressTracker;
window.SearchFilter = SearchFilter;
