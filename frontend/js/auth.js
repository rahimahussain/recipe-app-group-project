/**
 * Authentication Module
 * Handles login and registration forms
 */

class AuthManager {
    constructor() {
        this.initForms();
    }

    initForms() {
        this.setupLoginForm();
        this.setupRegisterForm();
        this.handleRedirect();
    }

    /**
     * Setup login form
     */
    setupLoginForm() {
        const loginForm = document.getElementById('login-form');
        if (!loginForm) return;

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!this.validateLoginForm(loginForm)) return;

            const submitBtn = loginForm.querySelector('button[type="submit"]');
            this.setLoading(submitBtn, true);

            const formData = new FormData(loginForm);

            try {
                const response = await app.apiRequest('login.php', {
                    method: 'POST',
                    body: JSON.stringify(Object.fromEntries(formData))
                });

                if (response.success) {
                    // Store auth data
                    localStorage.setItem('auth_token', response.token);
                    localStorage.setItem('user_data', JSON.stringify(response.user));

                    app.state.token = response.token;
                    app.state.user = response.user;
                    app.state.isAuthenticated = true;
                    app.updateNavigation();

                    app.showToast(response.message, 'success');

                    // Redirect to intended page or home
                    const params = new URLSearchParams(window.location.search);
                    const redirect = params.get('redirect') || 'index.html';
                    setTimeout(() => {
                        window.location.href = redirect;
                    }, 500);

                } else {
                    app.showToast(response.message, 'error');
                }

            } catch (error) {
                app.showToast('Login failed. Please try again.', 'error');
            } finally {
                this.setLoading(submitBtn, false);
            }
        });
    }

    /**
     * Setup registration form
     */
    setupRegisterForm() {
        const registerForm = document.getElementById('register-form');
        if (!registerForm) return;

        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!this.validateRegisterForm(registerForm)) return;

            const submitBtn = registerForm.querySelector('button[type="submit"]');
            this.setLoading(submitBtn, true);

            const formData = new FormData(registerForm);

            try {
                const response = await app.apiRequest('register.php', {
                    method: 'POST',
                    body: JSON.stringify(Object.fromEntries(formData))
                });

                if (response.success) {
                    app.showToast(response.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 1000);
                } else {
                    app.showToast(response.message, 'error');
                }

            } catch (error) {
                app.showToast('Registration failed. Please try again.', 'error');
            } finally {
                this.setLoading(submitBtn, false);
            }
        });

        // Real-time password match validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');

        if (password && confirmPassword) {
            confirmPassword.addEventListener('input', () => {
                const errorEl = document.getElementById('confirm_password-error');
                if (password.value !== confirmPassword.value) {
                    confirmPassword.classList.add('error');
                    if (errorEl) {
                        errorEl.textContent = 'Passwords do not match.';
                        errorEl.classList.add('visible');
                    }
                } else {
                    confirmPassword.classList.remove('error');
                    if (errorEl) errorEl.classList.remove('visible');
                }
            });
        }
    }

    /**
     * Validate login form
     */
    validateLoginForm(form) {
        let isValid = true;
        this.clearErrors(form);

        const username = form.querySelector('#username');
        const password = form.querySelector('#password');

        if (!username.value.trim()) {
            this.showFieldError(username, 'Username or email is required.');
            isValid = false;
        }

        if (!password.value) {
            this.showFieldError(password, 'Password is required.');
            isValid = false;
        }

        return isValid;
    }

    /**
     * Validate registration form
     */
    validateRegisterForm(form) {
        let isValid = true;
        this.clearErrors(form);

        const username = form.querySelector('#username');
        const email = form.querySelector('#email');
        const password = form.querySelector('#password');
        const confirmPassword = form.querySelector('#confirm_password');

        // Username validation
        const usernameValue = username.value.trim();
        const usernameRegex = /^[a-zA-Z0-9_]+$/;

        if (!usernameValue) {
            this.showFieldError(username, 'Username is required.');
            isValid = false;
        } else if (usernameValue.length < 3 || usernameValue.length > 50) {
            this.showFieldError(username, 'Username must be 3-50 characters.');
            isValid = false;
        } else if (!usernameRegex.test(usernameValue)) {
            this.showFieldError(username, 'Only letters, numbers, and underscores allowed.');
            isValid = false;
        }

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value)) {
            this.showFieldError(email, 'Please enter a valid email address.');
            isValid = false;
        }

        // Password validation
        if (password.value.length < 8) {
            this.showFieldError(password, 'Password must be at least 8 characters.');
            isValid = false;
        } else if (!/(?=.*[A-Za-z])(?=.*\d)/.test(password.value)) {
            this.showFieldError(password, 'Must contain at least one letter and one number.');
            isValid = false;
        }

        // Confirm password
        if (password.value !== confirmPassword.value) {
            this.showFieldError(confirmPassword, 'Passwords do not match.');
            isValid = false;
        }

        return isValid;
    }

    /**
     * Show field-level error
     */
    showFieldError(input, message) {
        input.classList.add('error');
        const errorEl = document.getElementById(`${input.id}-error`) ||
            input.parentElement.querySelector('.form-error-message');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.add('visible');
        }
    }

    /**
     * Clear all form errors
     */
    clearErrors(form) {
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        form.querySelectorAll('.form-error-message').forEach(el => {
            el.classList.remove('visible');
            el.textContent = '';
        });
    }

    /**
     * Set loading state on button
     */
    setLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.innerHTML = '<span class="spinner"></span> Loading...';
        } else {
            button.disabled = false;
            button.textContent = button.dataset.originalText || 'Submit';
        }
    }

    /**
     * Handle redirect after login
     */
    handleRedirect() {
        const params = new URLSearchParams(window.location.search);
        const redirect = params.get('redirect');

        if (redirect) {
            const loginForm = document.getElementById('login-form');
            if (loginForm) {
                const redirectInput = document.createElement('input');
                redirectInput.type = 'hidden';
                redirectInput.name = 'redirect';
                redirectInput.value = redirect;
                loginForm.appendChild(redirectInput);
            }
        }
    }
}

// Initialize auth manager
const authManager = new AuthManager();