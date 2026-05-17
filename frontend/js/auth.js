/**
 * Authentication Module
 * Handles login, registration, password management, and profile
 * Updated for enterprise schema with first_name/last_name
 */

class AuthManager {
    constructor() {
        this.initForms();
    }

    initForms() {
        this.setupLoginForm();
        this.setupRegisterForm();
        this.setupProfileForm();
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
                    app.state.isAdmin = response.user.role === 'Admin';
                    app.state.isModerator = ['Admin', 'Moderator'].includes(response.user.role);
                    app.updateNavigation();

                    app.showToast(`Welcome back, ${response.user.first_name || response.user.username}!`, 'success');

                    // Redirect to intended page or home
                    const params = new URLSearchParams(window.location.search);
                    const redirect = params.get('redirect') || 'index.html';

                    setTimeout(() => {
                        window.location.href = redirect;
                    }, 500);

                } else {
                    app.showToast(response.message || 'Login failed.', 'error');
                }

            } catch (error) {
                app.showToast(error.message || 'Login failed. Please try again.', 'error');
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
            const data = Object.fromEntries(formData);

            // Add default role
            data.role = 'User';

            try {
                const response = await app.apiRequest('register.php', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });

                if (response.success) {
                    app.showToast('Registration successful! Please log in.', 'success');
                    registerForm.reset();

                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 1500);
                } else {
                    app.showToast(response.message || 'Registration failed.', 'error');

                    // Highlight specific field errors if provided
                    if (response.errors) {
                        Object.entries(response.errors).forEach(([field, message]) => {
                            const input = registerForm.querySelector(`[name="${field}"]`);
                            if (input) {
                                this.showFieldError(input, message);
                            }
                        });
                    }
                }

            } catch (error) {
                app.showToast(error.message || 'Registration failed. Please try again.', 'error');
            } finally {
                this.setLoading(submitBtn, false);
            }
        });

        // Real-time validation
        this.setupRealTimeValidation(registerForm);
    }

    /**
     * Setup profile update form
     */
    setupProfileForm() {
        const profileForm = document.getElementById('profile-form');
        if (!profileForm) return;

        // Load current profile data
        this.loadProfileData(profileForm);

        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = profileForm.querySelector('button[type="submit"]');
            this.setLoading(submitBtn, true);

            const formData = new FormData(profileForm);
            const data = Object.fromEntries(formData);

            // Remove empty fields
            Object.keys(data).forEach(key => {
                if (data[key] === '') data[key] = null;
            });

            try {
                const response = await app.apiRequest('profile.php', {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });

                if (response.success) {
                    app.showToast('Profile updated successfully!', 'success');

                    // Update stored user data
                    if (app.state.user) {
                        Object.assign(app.state.user, data);
                        localStorage.setItem('user_data', JSON.stringify(app.state.user));
                        app.updateNavigation();
                    }
                } else {
                    app.showToast(response.message || 'Update failed.', 'error');
                }

            } catch (error) {
                app.showToast(error.message || 'Failed to update profile.', 'error');
            } finally {
                this.setLoading(submitBtn, false);
            }
        });
    }

    /**
     * Setup password change form
     */
    setupPasswordChangeForm() {
        const passwordForm = document.getElementById('password-form');
        if (!passwordForm) return;

        passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!this.validatePasswordForm(passwordForm)) return;

            const submitBtn = passwordForm.querySelector('button[type="submit"]');
            this.setLoading(submitBtn, true);

            const formData = new FormData(passwordForm);

            try {
                const response = await app.apiRequest('profile.php?action=password', {
                    method: 'PUT',
                    body: JSON.stringify(Object.fromEntries(formData))
                });

                if (response.success) {
                    app.showToast('Password changed successfully!', 'success');
                    passwordForm.reset();
                } else {
                    app.showToast(response.message || 'Password change failed.', 'error');
                }

            } catch (error) {
                app.showToast(error.message || 'Failed to change password.', 'error');
            } finally {
                this.setLoading(submitBtn, false);
            }
        });
    }

    /**
     * Load profile data into form
     */
    async loadProfileData(form) {
        try {
            const response = await app.apiRequest('profile.php');

            if (response.success && response.profile) {
                const profile = response.profile;

                Object.entries(profile).forEach(([key, value]) => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input && value !== null) {
                        if (input.type === 'select-one') {
                            input.value = value;
                        } else {
                            input.value = value;
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Failed to load profile:', error);
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

        const fields = {
            'username': {
                value: form.querySelector('#username')?.value?.trim(),
                rules: [
                    { test: v => v.length >= 3, message: 'Username must be at least 3 characters.' },
                    { test: v => v.length <= 50, message: 'Username must not exceed 50 characters.' },
                    { test: v => /^[a-zA-Z0-9_]+$/.test(v), message: 'Only letters, numbers, and underscores allowed.' }
                ]
            },
            'email': {
                value: form.querySelector('#email')?.value?.trim(),
                rules: [
                    { test: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v), message: 'Please enter a valid email address.' }
                ]
            },
            'first_name': {
                value: form.querySelector('#first_name')?.value?.trim(),
                rules: [
                    { test: v => v.length > 0, message: 'First name is required.' },
                    { test: v => v.length <= 50, message: 'First name must not exceed 50 characters.' }
                ]
            },
            'last_name': {
                value: form.querySelector('#last_name')?.value?.trim(),
                rules: [
                    { test: v => v.length > 0, message: 'Last name is required.' },
                    { test: v => v.length <= 50, message: 'Last name must not exceed 50 characters.' }
                ]
            },
            'password': {
                value: form.querySelector('#password')?.value,
                rules: [
                    { test: v => v.length >= 8, message: 'Password must be at least 8 characters.' },
                    { test: v => /^(?=.*[A-Za-z])(?=.*\d)/.test(v), message: 'Must contain at least one letter and one number.' }
                ]
            },
            'confirm_password': {
                value: form.querySelector('#confirm_password')?.value,
                rules: [
                    { test: v => v === form.querySelector('#password')?.value, message: 'Passwords do not match.' }
                ]
            }
        };

        Object.entries(fields).forEach(([fieldName, field]) => {
            const input = form.querySelector(`#${fieldName}`);
            if (!input) return;

            for (const rule of field.rules) {
                if (!rule.test(field.value)) {
                    this.showFieldError(input, rule.message);
                    isValid = false;
                    break;
                }
            }
        });

        return isValid;
    }

    /**
     * Validate password change form
     */
    validatePasswordForm(form) {
        let isValid = true;
        this.clearErrors(form);

        const currentPassword = form.querySelector('#current_password');
        const newPassword = form.querySelector('#new_password');
        const confirmPassword = form.querySelector('#confirm_new_password');

        if (!currentPassword.value) {
            this.showFieldError(currentPassword, 'Current password is required.');
            isValid = false;
        }

        if (!newPassword.value || newPassword.value.length < 8) {
            this.showFieldError(newPassword, 'New password must be at least 8 characters.');
            isValid = false;
        }

        if (newPassword.value !== confirmPassword.value) {
            this.showFieldError(confirmPassword, 'New passwords do not match.');
            isValid = false;
        }

        return isValid;
    }

    /**
     * Setup real-time validation
     */
    setupRealTimeValidation(form) {
        const fields = ['username', 'email', 'first_name', 'last_name', 'password', 'confirm_password'];

        fields.forEach(fieldName => {
            const field = form.querySelector(`#${fieldName}`);
            if (!field) return;

            field.addEventListener('blur', () => {
                // Re-validate on blur
                if (fieldName === 'confirm_password') {
                    const password = form.querySelector('#password');
                    if (field.value && password.value && field.value !== password.value) {
                        this.showFieldError(field, 'Passwords do not match.');
                    } else {
                        this.clearFieldError(field);
                    }
                }
            });

            field.addEventListener('input', () => {
                this.clearFieldError(field);
            });
        });
    }

    /**
     * Show field-level error
     */
    showFieldError(input, message) {
        input.classList.add('error');
        input.setAttribute('aria-invalid', 'true');

        let errorEl = input.parentElement.querySelector('.form-error-message');
        if (!errorEl) {
            errorEl = document.createElement('span');
            errorEl.className = 'form-error-message';
            errorEl.setAttribute('role', 'alert');
            input.parentElement.appendChild(errorEl);
        }

        errorEl.textContent = message;
        errorEl.classList.add('visible');
    }

    /**
     * Clear field error
     */
    clearFieldError(input) {
        input.classList.remove('error');
        input.removeAttribute('aria-invalid');

        const errorEl = input.parentElement.querySelector('.form-error-message');
        if (errorEl) {
            errorEl.classList.remove('visible');
            errorEl.textContent = '';
        }
    }

    /**
     * Clear all form errors
     */
    clearErrors(form) {
        form.querySelectorAll('.error').forEach(el => {
            el.classList.remove('error');
            el.removeAttribute('aria-invalid');
        });
        form.querySelectorAll('.form-error-message').forEach(el => {
            el.classList.remove('visible');
            el.textContent = '';
        });
    }

    /**
     * Set loading state on button
     */
    setLoading(button, isLoading) {
        if (!button) return;

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