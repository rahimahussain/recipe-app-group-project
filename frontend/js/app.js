/**
 * Recipe App - Main Application Module
 * Handles global app state, navigation, routing, and UI components
 * Updated for enterprise schema with role-based access, profiles, and collections
 */

class RecipeApp {
    constructor() {
        this.apiBaseUrl = 'http://localhost/recipe-app/backend/api';
        this.state = {
            user: null,
            token: null,
            isAuthenticated: false,
            isAdmin: false,
            isModerator: false
        };

        // Debounced search timer
        this.searchTimer = null;

        this.init();
    }

    /**
     * Initialize the application
     */
    init() {
        this.updateCopyrightYear();
        this.checkAuthState();
        this.setupNavigation();
        this.setupMobileMenu();
        this.setupGlobalEventListeners();
        this.setupSearchAutocomplete();
        this.loadCurrentPage();
    }


    /**
     * Setup mobile menu toggle
     */
    setupMobileMenu() {
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const mainNav = document.querySelector('.main-nav');

        if (mobileMenuBtn && mainNav) {
            // Remove any existing listeners (prevent duplicates)
            const newBtn = mobileMenuBtn.cloneNode(true);
            mobileMenuBtn.parentNode.replaceChild(newBtn, mobileMenuBtn);

            newBtn.addEventListener('click', () => {
                const isOpen = mainNav.classList.toggle('open');
                newBtn.setAttribute('aria-expanded', isOpen);
                newBtn.innerHTML = isOpen ? '✕' : '☰';
            });

            // Close menu on outside click
            document.addEventListener('click', (e) => {
                if (!newBtn.contains(e.target) && !mainNav.contains(e.target)) {
                    mainNav.classList.remove('open');
                    newBtn.setAttribute('aria-expanded', 'false');
                    newBtn.innerHTML = '☰';
                }
            });

            // Close menu on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && mainNav.classList.contains('open')) {
                    mainNav.classList.remove('open');
                    newBtn.setAttribute('aria-expanded', 'false');
                    newBtn.innerHTML = '☰';
                    newBtn.focus();
                }
            });
        }
    }

    /**
     * Update copyright year to current year
     */
    updateCopyrightYear() {
        const yearElements = document.querySelectorAll('#current-year');
        const currentYear = new Date().getFullYear();

        yearElements.forEach(element => {
            element.textContent = currentYear;
        });
    }

    /**
     * Check if user is authenticated
     */
    checkAuthState() {
        const token = localStorage.getItem('auth_token');
        const user = JSON.parse(localStorage.getItem('user_data') || 'null');

        if (token && user) {
            this.state.token = token;
            this.state.user = user;
            this.state.isAuthenticated = true;
            this.state.isAdmin = user.role === 'Admin';
            this.state.isModerator = user.role === 'Moderator' || user.role === 'Admin';

            // Verify token is still valid
            this.verifyToken();
        }

        this.updateNavigation();
    }

    /**
     * Verify token validity with server
     */
    async verifyToken() {
        try {
            const response = await this.apiRequest('profile.php');
            if (!response.success) {
                this.clearAuth();
            }
        } catch (error) {
            // Token invalid, clear auth
            if (error.message.includes('401') || error.message.includes('Authentication')) {
                this.clearAuth();
            }
        }
    }

    /**
     * Clear authentication state
     */
    clearAuth() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        this.state.user = null;
        this.state.token = null;
        this.state.isAuthenticated = false;
        this.state.isAdmin = false;
        this.state.isModerator = false;
        this.updateNavigation();
    }

    /**
     * Setup responsive navigation
     */
    setupNavigation() {
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const mainNav = document.querySelector('.main-nav');

        if (mobileMenuBtn && mainNav) {
            mobileMenuBtn.addEventListener('click', () => {
                const isOpen = mainNav.classList.toggle('open');
                mobileMenuBtn.setAttribute('aria-expanded', isOpen);
                mobileMenuBtn.innerHTML = isOpen ? '✕' : '☰';
            });

            // Close menu on outside click
            document.addEventListener('click', (e) => {
                if (!mobileMenuBtn.contains(e.target) && !mainNav.contains(e.target)) {
                    mainNav.classList.remove('open');
                    mobileMenuBtn.setAttribute('aria-expanded', 'false');
                    mobileMenuBtn.innerHTML = '☰';
                }
            });

            // Close menu on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && mainNav.classList.contains('open')) {
                    mainNav.classList.remove('open');
                    mobileMenuBtn.setAttribute('aria-expanded', 'false');
                    mobileMenuBtn.innerHTML = '☰';
                    mobileMenuBtn.focus();
                }
            });
        }
    }

    /**
     * Setup search autocomplete
     */
    setupSearchAutocomplete() {
        const searchInput = document.getElementById('search-query');
        const suggestionsContainer = document.getElementById('search-suggestions');

        if (!searchInput || !suggestionsContainer) return;

        searchInput.addEventListener('input', () => {
            clearTimeout(this.searchTimer);

            const query = searchInput.value.trim();

            if (query.length < 2) {
                suggestionsContainer.innerHTML = '';
                suggestionsContainer.style.display = 'none';
                return;
            }

            this.searchTimer = setTimeout(async () => {
                try {
                    const response = await this.apiRequest(
                        `search.php?q=${encodeURIComponent(query)}&suggest=true&limit=5`
                    );

                    if (response.success && response.suggestions.length > 0) {
                        suggestionsContainer.innerHTML = response.suggestions
                            .map(s => `
                                <a href="recipe-details.html?id=${s.id}" class="suggestion-item">
                                    <span class="suggestion-icon">🍳</span>
                                    <span>${this.escapeHtml(s.title)}</span>
                                </a>
                            `)
                            .join('');
                        suggestionsContainer.style.display = 'block';
                    } else {
                        suggestionsContainer.style.display = 'none';
                    }
                } catch (error) {
                    console.error('Search suggestion error:', error);
                }
            }, 300);
        });

        // Hide suggestions on outside click
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });

        // Handle keyboard navigation
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                suggestionsContainer.style.display = 'none';
            }
        });
    }

    /**
     * Setup global event listeners
     */
    setupGlobalEventListeners() {
        // Logout handler
        document.addEventListener('click', (e) => {
            if (e.target.matches('#logout-btn, .logout-link')) {
                e.preventDefault();
                this.logout();
            }
        });

        // Handle session expiry notifications
        window.addEventListener('storage', (e) => {
            if (e.key === 'auth_token' && !e.newValue) {
                this.clearAuth();
                this.showToast('Session expired. Please log in again.', 'warning');
            }
        });
    }

    /**
     * Load content based on current page
     */
    loadCurrentPage() {
        const path = window.location.pathname;
        const page = path.split('/').pop() || 'index.html';

        // Highlight active nav link
        document.querySelectorAll('.nav-link').forEach(link => {
            const href = link.getAttribute('href');
            if (href === page || (page === '' && href === 'index.html')) {
                link.classList.add('active');
            }
        });
    }

    /**
     * Update navigation based on auth state
     */
    updateNavigation() {
        const userMenu = document.querySelector('.user-menu');
        if (!userMenu) return;

        if (this.state.isAuthenticated && this.state.user) {
            const displayName = this.state.user.first_name || this.state.user.username;

            userMenu.innerHTML = `
                <a href="favourites.html" class="nav-link">
                    <span class="nav-icon">❤️</span> Favourites
                </a>
                <div class="user-dropdown">
                    <button class="nav-link user-dropdown-btn" aria-haspopup="true" aria-expanded="false">
                        <span class="user-avatar">
                            ${this.state.user.profile_image_url
                ? `<img src="${this.escapeHtml(this.state.user.profile_image_url)}" alt="${this.escapeHtml(displayName)}">`
                : '👤'}
                        </span>
                        ${this.escapeHtml(displayName)}
                        ${this.state.isAdmin ? '<span class="badge badge-admin">Admin</span>' : ''}
                        ${this.state.user.role === 'Chef' ? '<span class="badge badge-chef">Chef</span>' : ''}
                    </button>
                    <div class="user-dropdown-menu" role="menu">
                        <a href="account.html" role="menuitem">👤 My Profile</a>
                        <a href="favourites.html" role="menuitem">❤️ My Favourites</a>
                        ${this.state.user.role === 'Chef' || this.state.isAdmin ? `
                            <a href="recipes.html?author=${this.state.user.id}" role="menuitem">📝 My Recipes</a>
                        ` : ''}
                        ${this.state.isModerator ? `
                            <a href="moderation.html" role="menuitem">🛡️ Moderation</a>
                        ` : ''}
                        <hr>
                        <button id="logout-btn" class="dropdown-item" role="menuitem">🚪 Logout</button>
                    </div>
                </div>
            `;

            // Setup dropdown toggle
            this.setupUserDropdown();
        } else {
            userMenu.innerHTML = `
                <a href="login.html" class="btn btn-outline btn-sm">Login</a>
                <a href="register.html" class="btn btn-primary btn-sm">Register</a>
            `;
        }
    }

    /**
     * Setup user dropdown menu
     */
    setupUserDropdown() {
        const dropdownBtn = document.querySelector('.user-dropdown-btn');
        const dropdownMenu = document.querySelector('.user-dropdown-menu');

        if (!dropdownBtn || !dropdownMenu) return;

        dropdownBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = dropdownMenu.classList.toggle('open');
            dropdownBtn.setAttribute('aria-expanded', isOpen);
        });

        document.addEventListener('click', (e) => {
            if (!dropdownBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('open');
                dropdownBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /**
     * Make API request
     */
    async apiRequest(endpoint, options = {}) {
        // Handle endpoints that already include query strings
        const url = endpoint.startsWith('http')
            ? endpoint
            : `${this.apiBaseUrl}/${endpoint}`;

        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };

        // Add auth token if available
        if (this.state.token) {
            defaultOptions.headers['Authorization'] = `Bearer ${this.state.token}`;
        }

        const fetchOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...(options.headers || {})
            }
        };

        // Don't set Content-Type for FormData
        if (fetchOptions.body instanceof FormData) {
            delete fetchOptions.headers['Content-Type'];
        }

        try {
            const response = await fetch(url, fetchOptions);

            // Handle 401 Unauthorized
            if (response.status === 401) {
                if (this.state.isAuthenticated) {
                    this.clearAuth();
                    this.showToast('Session expired. Please log in again.', 'warning');

                    // Redirect to login if not already there
                    if (!window.location.pathname.includes('login.html')) {
                        setTimeout(() => {
                            window.location.href = `login.html?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`;
                        }, 1500);
                    }
                }
                throw new Error('Authentication required');
            }

            // Handle 403 Forbidden
            if (response.status === 403) {
                throw new Error('You do not have permission to perform this action.');
            }

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `Request failed with status ${response.status}`);
            }

            return data;

        } catch (error) {
            console.error('API Request Error:', error.message, url);

            // Network errors
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                throw new Error('Network error. Please check your connection.');
            }

            throw error;
        }
    }

    /**
     * Logout user
     */
    async logout() {
        try {
            // Attempt server-side logout
            if (this.state.token) {
                await this.apiRequest('logout.php', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.state.token}`
                    }
                });
            }
        } catch (error) {
            console.warn('Server logout failed, clearing local state');
        }

        this.clearAuth();
        this.showToast('Logged out successfully.', 'info');

        // Redirect to home page
        window.location.href = 'index.html';
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        const container = document.querySelector('.toast-container') || this.createToastContainer();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'polite');

        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-message">${this.escapeHtml(message)}</span>
            <button class="toast-close" aria-label="Dismiss">×</button>
        `;

        container.appendChild(toast);

        // Dismiss on click
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => this.dismissToast(toast));

        // Auto dismiss after 4 seconds
        const timer = setTimeout(() => this.dismissToast(toast), 4000);
        toast.dataset.timer = timer;

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('toast-visible');
        });
    }

    /**
     * Dismiss a toast notification
     */
    dismissToast(toast) {
        clearTimeout(parseInt(toast.dataset.timer));
        toast.classList.remove('toast-visible');
        toast.addEventListener('transitionend', () => toast.remove());
        setTimeout(() => toast.remove(), 300);
    }

    /**
     * Create toast container if it doesn't exist
     */
    createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container';
        container.setAttribute('aria-live', 'polite');
        document.body.appendChild(container);
        return container;
    }

    /**
     * Show modal dialog
     */
    showModal(title, content, buttons = []) {
        // Remove existing modal
        const existingModal = document.querySelector('.modal-overlay');
        if (existingModal) existingModal.remove();

        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-label', title);

        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">${this.escapeHtml(title)}</h2>
                    <button class="modal-close" aria-label="Close modal">×</button>
                </div>
                <div class="modal-body">${content}</div>
                ${buttons.length > 0 ? `
                    <div class="modal-footer">
                        ${buttons.map(btn => `
                            <button class="btn ${btn.class || 'btn-secondary'}" data-action="${btn.action || ''}">
                                ${btn.text}
                            </button>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
        `;

        document.body.appendChild(modal);

        // Focus trap and close handlers
        const closeBtn = modal.querySelector('.modal-close');
        closeBtn.addEventListener('click', () => modal.remove());

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });

        document.addEventListener('keydown', function closeOnEscape(e) {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', closeOnEscape);
            }
        });

        // Setup button actions
        buttons.forEach(btn => {
            if (btn.action && btn.onClick) {
                const btnEl = modal.querySelector(`[data-action="${btn.action}"]`);
                if (btnEl) {
                    btnEl.addEventListener('click', () => {
                        btn.onClick();
                        modal.remove();
                    });
                }
            }
        });

        // Focus first focusable element
        const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (firstFocusable) firstFocusable.focus();
    }

    /**
     * Show loading state
     */
    showLoading(container, message = 'Loading...') {
        if (typeof container === 'string') {
            container = document.querySelector(container);
        }

        if (container) {
            container.innerHTML = `
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p>${this.escapeHtml(message)}</p>
                </div>
            `;
        }
    }

    /**
     * Show empty state
     */
    showEmptyState(container, message, icon = '📭') {
        if (typeof container === 'string') {
            container = document.querySelector(container);
        }

        if (container) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">${icon}</div>
                    <p>${this.escapeHtml(message)}</p>
                </div>
            `;
        }
    }

    /**
     * Format date string
     */
    formatDate(dateString) {
        if (!dateString) return 'N/A';

        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins} min ago`;
        if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
        if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;

        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-GB', options);
    }

    /**
     * Format relative time
     */
    formatRelativeTime(dateString) {
        return this.formatDate(dateString);
    }

    /**
     * Generate star rating HTML
     */
    generateStars(rating, maxStars = 5) {
        const numRating = parseFloat(rating) || 0;
        const fullStars = Math.floor(numRating);
        const hasHalf = numRating % 1 >= 0.25 && numRating % 1 < 0.75;
        const hasThreeQuarter = numRating % 1 >= 0.75;
        const emptyStars = maxStars - fullStars - (hasHalf ? 1 : 0) - (hasThreeQuarter ? 1 : 0);

        let stars = '';
        stars += '★'.repeat(fullStars);
        if (hasThreeQuarter) stars += '★';
        if (hasHalf) stars += '½';
        stars += '☆'.repeat(Math.max(0, emptyStars));

        return `<span class="star-rating" aria-label="${numRating.toFixed(1)} out of ${maxStars} stars">${stars}</span>`;
    }

    /**
     * Generate difficulty badge
     */
    generateDifficultyBadge(difficulty) {
        const badges = {
            'Easy': 'badge-easy',
            'Medium': 'badge-medium',
            'Hard': 'badge-hard'
        };

        return `<span class="badge ${badges[difficulty] || ''}">${this.escapeHtml(difficulty)}</span>`;
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * Truncate text
     */
    truncate(str, length = 100) {
        if (!str || str.length <= length) return str || '';
        return str.substring(0, length).trim() + '...';
    }

    /**
     * Get URL parameter
     */
    getUrlParam(param) {
        const params = new URLSearchParams(window.location.search);
        return params.get(param);
    }

    /**
     * Check if user is logged in, redirect if not
     */
    requireAuth() {
        if (!this.state.isAuthenticated) {
            this.showToast('Please log in to continue.', 'warning');
            window.location.href = `login.html?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`;
            return false;
        }
        return true;
    }

    /**
     * Require specific role
     */
    requireRole(roles) {
        if (!this.requireAuth()) return false;

        const allowedRoles = Array.isArray(roles) ? roles : [roles];

        if (!allowedRoles.includes(this.state.user.role)) {
            this.showToast('You do not have permission to access this page.', 'error');
            window.location.href = 'index.html';
            return false;
        }

        return true;
    }

    /**
     * Debounce function
     */
    debounce(func, delay = 300) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => func.apply(this, args), delay);
        };
    }
}

// Initialize the app
const app = new RecipeApp();

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RecipeApp;
}