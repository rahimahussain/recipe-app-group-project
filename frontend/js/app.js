/**
 * Recipe App - Main Application Module
 * Handles global app state, navigation, routing, and UI components
 */

class RecipeApp {
    constructor() {
        this.apiBaseUrl = 'http://localhost/recipe-app/backend/api';
        this.state = {
            user: null,
            token: null,
            isAuthenticated: false
        };

        this.init();
    }

    /**
     * Initialize the application
     */
    init() {
        this.checkAuthState();
        this.setupNavigation();
        this.setupMobileMenu();
        this.setupGlobalEventListeners();
        this.loadCurrentPage();
    }

    /**
     * Check if user is authenticated (check session/localStorage)
     */
    checkAuthState() {
        const token = localStorage.getItem('auth_token');
        const user = JSON.parse(localStorage.getItem('user_data') || 'null');

        if (token && user) {
            this.state.token = token;
            this.state.user = user;
            this.state.isAuthenticated = true;
        }

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
        }
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

        if (this.state.isAuthenticated) {
            userMenu.innerHTML = `
                <a href="favourites.html" class="nav-link">❤️ Favourites</a>
                <a href="account.html" class="nav-link">👤 ${this.escapeHtml(this.state.user.username)}</a>
                <button id="logout-btn" class="btn btn-ghost btn-sm">Logout</button>
            `;
        } else {
            userMenu.innerHTML = `
                <a href="login.html" class="btn btn-outline btn-sm">Login</a>
                <a href="register.html" class="btn btn-primary btn-sm">Register</a>
            `;
        }
    }

    /**
     * Make API request
     */
    async apiRequest(endpoint, options = {}) {
        const url = `${this.apiBaseUrl}/${endpoint}`;

        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
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
            const data = await response.json();

            if (!response.ok && data.message) {
                throw new Error(data.message);
            }

            return data;

        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    /**
     * Logout user
     */
    async logout() {
        try {
            await this.apiRequest('logout.php', { method: 'POST' });
        } catch (error) {
            // Continue with local logout regardless
        }

        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        this.state.user = null;
        this.state.token = null;
        this.state.isAuthenticated = false;
        this.updateNavigation();

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
        toast.textContent = message;

        container.appendChild(toast);

        // Auto remove after 4 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    /**
     * Create toast container if it doesn't exist
     */
    createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }

    /**
     * Format date string
     */
    formatDate(dateString) {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('en-GB', options);
    }

    /**
     * Generate star rating HTML
     */
    generateStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalf = rating % 1 >= 0.5;
        const emptyStars = 5 - fullStars - (hasHalf ? 1 : 0);

        return (
            '★'.repeat(fullStars) +
            (hasHalf ? '½' : '') +
            '☆'.repeat(emptyStars)
        );
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * Check if user is logged in, redirect if not
     */
    requireAuth() {
        if (!this.state.isAuthenticated) {
            this.showToast('Please log in to continue.', 'error');
            window.location.href = `login.html?redirect=${encodeURIComponent(window.location.pathname)}`;
            return false;
        }
        return true;
    }
}

// Initialize the app
const app = new RecipeApp();