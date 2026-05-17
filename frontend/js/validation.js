/**
 * Form Validation Module
 * Provides comprehensive, reusable validation functions for all forms
 * Updated for enterprise schema with additional field types
 */

class FormValidator {
    constructor() {
        this.rules = {
            // ============================================
            // Core Validation Rules
            // ============================================
            required: {
                test: (value) => {
                    if (value === null || value === undefined) return false;
                    if (typeof value === 'string') return value.trim().length > 0;
                    if (Array.isArray(value)) return value.length > 0;
                    return true;
                },
                message: 'This field is required.'
            },

            requiredTrue: {
                test: (value) => value === true || value === 'true' || value === '1',
                message: 'You must accept this to continue.'
            },

            // ============================================
            // String Validation Rules
            // ============================================
            email: {
                test: (value) => {
                    if (!value) return true;
                    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value.trim());
                },
                message: 'Please enter a valid email address.'
            },

            username: {
                test: (value) => {
                    if (!value) return false;
                    return /^[a-zA-Z0-9_]{3,50}$/.test(value.trim());
                },
                message: 'Username must be 3-50 characters (only letters, numbers, and underscores).'
            },

            minLength: {
                test: (value, min) => {
                    if (!value) return false;
                    return value.trim().length >= min;
                },
                message: (min) => `Must be at least ${min} characters.`
            },

            maxLength: {
                test: (value, max) => {
                    if (!value) return true;
                    return value.trim().length <= max;
                },
                message: (max) => `Must not exceed ${max} characters.`
            },

            pattern: {
                test: (value, pattern) => {
                    if (!value) return true;
                    return new RegExp(pattern).test(value);
                },
                message: 'Please match the requested format.'
            },

            noSpecialChars: {
                test: (value) => {
                    if (!value) return true;
                    return /^[a-zA-Z0-9\s\-_.,!?()]+$/.test(value);
                },
                message: 'Special characters are not allowed.'
            },

            alphabetic: {
                test: (value) => {
                    if (!value) return true;
                    return /^[a-zA-Z\s\-']+$/.test(value.trim());
                },
                message: 'Only letters, spaces, hyphens, and apostrophes allowed.'
            },

            // ============================================
            // Name Validation (for first_name, last_name)
            // ============================================
            name: {
                test: (value) => {
                    if (!value) return false;
                    const trimmed = value.trim();
                    return trimmed.length >= 1 &&
                        trimmed.length <= 50 &&
                        /^[a-zA-Z\s\-']+$/.test(trimmed);
                },
                message: 'Name must be 1-50 characters (letters, spaces, hyphens, apostrophes only).'
            },

            // ============================================
            // Password Validation Rules
            // ============================================
            password: {
                test: (value) => {
                    if (!value) return false;
                    // At least 8 characters, one letter, one number
                    return /^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(value);
                },
                message: 'Password must be at least 8 characters with at least one letter and one number.'
            },

            strongPassword: {
                test: (value) => {
                    if (!value) return false;
                    // Strong: 8+ chars, upper, lower, number, special
                    return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(value);
                },
                message: 'Password must be at least 8 characters with uppercase, lowercase, number, and special character.'
            },

            matchField: {
                test: (value, matchValue) => value === matchValue,
                message: 'Values do not match.'
            },

            // ============================================
            // Numeric Validation Rules
            // ============================================
            number: {
                test: (value) => {
                    if (value === '' || value === null || value === undefined) return true;
                    return !isNaN(parseFloat(value)) && isFinite(value);
                },
                message: 'Please enter a valid number.'
            },

            integer: {
                test: (value) => {
                    if (value === '' || value === null || value === undefined) return true;
                    return Number.isInteger(Number(value));
                },
                message: 'Please enter a whole number.'
            },

            min: {
                test: (value, min) => {
                    if (value === '' || value === null || value === undefined) return true;
                    return parseFloat(value) >= min;
                },
                message: (min) => `Must be at least ${min}.`
            },

            max: {
                test: (value, max) => {
                    if (value === '' || value === null || value === undefined) return true;
                    return parseFloat(value) <= max;
                },
                message: (max) => `Must not exceed ${max}.`
            },

            range: {
                test: (value, [min, max]) => {
                    if (value === '' || value === null || value === undefined) return true;
                    const num = parseFloat(value);
                    return num >= min && num <= max;
                },
                message: ([min, max]) => `Must be between ${min} and ${max}.`
            },

            positive: {
                test: (value) => {
                    if (value === '' || value === null || value === undefined) return true;
                    return parseFloat(value) > 0;
                },
                message: 'Must be a positive number.'
            },

            nonNegative: {
                test: (value) => {
                    if (value === '' || value === null || value === undefined) return true;
                    return parseFloat(value) >= 0;
                },
                message: 'Must be zero or greater.'
            },

            // ============================================
            // URL & Phone Validation
            // ============================================
            url: {
                test: (value) => {
                    if (!value || value.trim() === '') return true;
                    try {
                        const url = new URL(value.trim());
                        return ['http:', 'https:'].includes(url.protocol);
                    } catch {
                        return false;
                    }
                },
                message: 'Please enter a valid URL (http:// or https://).'
            },

            phone: {
                test: (value) => {
                    if (!value || value.trim() === '') return true;
                    return /^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/.test(value.trim());
                },
                message: 'Please enter a valid phone number.'
            },

            // ============================================
            // Date Validation
            // ============================================
            date: {
                test: (value) => {
                    if (!value) return true;
                    return !isNaN(Date.parse(value));
                },
                message: 'Please enter a valid date.'
            },

            futureDate: {
                test: (value) => {
                    if (!value) return true;
                    const date = new Date(value);
                    return !isNaN(date) && date > new Date();
                },
                message: 'Date must be in the future.'
            },

            // ============================================
            // Dietary Preference Validation
            // ============================================
            dietaryPreference: {
                test: (value) => {
                    if (!value || value === 'None' || value === '') return true;
                    const valid = ['Vegetarian', 'Vegan', 'Non-vegetarian', 'None'];
                    return valid.includes(value);
                },
                message: 'Please select a valid dietary preference.'
            },

            // ============================================
            // ENUM Validation Rules
            // ============================================
            difficulty: {
                test: (value) => {
                    if (!value) return true;
                    return ['Easy', 'Medium', 'Hard'].includes(value);
                },
                message: 'Difficulty must be Easy, Medium, or Hard.'
            },

            visibility: {
                test: (value) => {
                    if (!value) return true;
                    return ['Public', 'Private', 'Draft'].includes(value);
                },
                message: 'Visibility must be Public, Private, or Draft.'
            },

            role: {
                test: (value) => {
                    if (!value) return true;
                    return ['Admin', 'Moderator', 'Chef', 'User'].includes(value);
                },
                message: 'Invalid role specified.'
            },

            accountStatus: {
                test: (value) => {
                    if (!value) return true;
                    return ['Active', 'Locked', 'Disabled', 'Pending'].includes(value);
                },
                message: 'Invalid account status.'
            },

            // ============================================
            // Rating Validation
            // ============================================
            rating: {
                test: (value) => {
                    if (value === '' || value === null || value === undefined) return true;
                    const num = parseInt(value);
                    return num >= 1 && num <= 5;
                },
                message: 'Rating must be between 1 and 5 stars.'
            }
        };

        // Error message cache for i18n support
        this.customMessages = {};
    }

    /**
     * Set custom error messages for specific fields
     */
    setCustomMessages(fieldName, messages) {
        this.customMessages[fieldName] = messages;
    }

    /**
     * Get error message for a field and rule
     */
    getErrorMessage(fieldName, ruleName, param) {
        // Check custom messages first
        if (this.customMessages[fieldName] && this.customMessages[fieldName][ruleName]) {
            return this.customMessages[fieldName][ruleName];
        }

        const rule = this.rules[ruleName];
        if (!rule) return 'Invalid value.';

        return typeof rule.message === 'function' ? rule.message(param) : rule.message;
    }

    /**
     * Validate a single form field against specified rules
     * @param {HTMLElement} field - The form field element
     * @param {Object} rules - Object of rule names and their parameters
     * @returns {boolean} - Whether the field is valid
     */
    validateField(field, rules) {
        const value = this.getFieldValue(field);
        let isValid = true;
        let errorMessage = '';

        for (const [ruleName, param] of Object.entries(rules)) {
            const rule = this.rules[ruleName];

            if (!rule) {
                console.warn(`Unknown validation rule: ${ruleName}`);
                continue;
            }

            let testResult;

            try {
                // Handle special cases
                if (ruleName === 'matchField') {
                    const matchField = document.querySelector(`[name="${param}"]`) ||
                        document.getElementById(param);
                    const matchValue = matchField ? matchField.value : param;
                    testResult = rule.test(value, matchValue);
                } else if (ruleName === 'required' && param === false) {
                    // Skip required check if not required
                    testResult = true;
                } else {
                    testResult = rule.test(value, param);
                }
            } catch (e) {
                console.error(`Validation error for rule ${ruleName}:`, e);
                testResult = false;
            }

            if (!testResult) {
                isValid = false;
                errorMessage = this.getErrorMessage(field.name || field.id, ruleName, param);
                break;
            }
        }

        this.showFieldValidation(field, isValid, errorMessage);
        return isValid;
    }

    /**
     * Get field value handling different input types
     */
    getFieldValue(field) {
        if (!field) return '';

        switch (field.type) {
            case 'checkbox':
                if (field.name.endsWith('[]')) {
                    // Handle array of checkboxes
                    const checkboxes = document.querySelectorAll(`[name="${field.name}"]:checked`);
                    return Array.from(checkboxes).map(cb => cb.value);
                }
                return field.checked ? field.value || true : false;

            case 'radio':
                if (field.checked) return field.value;
                const checkedRadio = document.querySelector(`[name="${field.name}"]:checked`);
                return checkedRadio ? checkedRadio.value : '';

            case 'select-multiple':
                return Array.from(field.selectedOptions).map(opt => opt.value);

            case 'number':
            case 'range':
                return field.value === '' ? '' : parseFloat(field.value);

            default:
                return field.value;
        }
    }

    /**
     * Validate an entire form against field rules
     * @param {HTMLFormElement} form - The form element
     * @param {Object} fieldRules - Map of field names to their validation rules
     * @returns {Object} - { isValid, errors }
     */
    validateForm(form, fieldRules) {
        let isValid = true;
        const errors = {};

        // Clear previous errors
        this.clearFormErrors(form);

        Object.entries(fieldRules).forEach(([fieldName, rules]) => {
            // Handle array fields (e.g., 'categories[]')
            const fields = form.querySelectorAll(`[name="${fieldName}"]`);

            if (fields.length === 0) {
                console.warn(`Field not found in form: ${fieldName}`);
                return;
            }

            if (fields.length > 1 && fieldName.endsWith('[]')) {
                // Validate at least one checkbox checked if required
                const hasRequired = rules.required;
                if (hasRequired) {
                    const anyChecked = Array.from(fields).some(f => f.checked);
                    if (!anyChecked) {
                        isValid = false;
                        errors[fieldName] = this.rules.required.message;
                        const firstField = fields[0];
                        this.showFieldValidation(firstField, false, this.rules.required.message);
                    }
                }
            } else {
                const field = fields[0];
                const fieldValid = this.validateField(field, rules);
                if (!fieldValid) {
                    isValid = false;
                    errors[fieldName] = field.parentElement.querySelector('.form-error-message')?.textContent || 'Invalid value.';
                }
            }
        });

        return { isValid, errors };
    }

    /**
     * Show or hide field validation state
     */
    showFieldValidation(field, isValid, message) {
        if (!field) return;

        // Find or create error element
        let errorEl = this.getErrorElement(field);

        if (!isValid && message) {
            // Mark field as invalid
            field.classList.add('error');
            field.setAttribute('aria-invalid', 'true');

            // Show error message
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.add('visible');
                errorEl.setAttribute('role', 'alert');
            }

            // Announce to screen readers
            this.announceError(field, message);
        } else {
            // Clear error state
            field.classList.remove('error');
            field.removeAttribute('aria-invalid');

            if (errorEl) {
                errorEl.textContent = '';
                errorEl.classList.remove('visible');
                errorEl.removeAttribute('role');
            }
        }
    }

    /**
     * Find or create error message element for a field
     */
    getErrorElement(field) {
        // Try finding by ID pattern
        let errorEl = document.getElementById(`${field.id}-error`);

        if (!errorEl) {
            // Try finding in parent element
            errorEl = field.parentElement.querySelector('.form-error-message');
        }

        if (!errorEl) {
            // Try finding in closest form group
            const formGroup = field.closest('.form-group');
            if (formGroup) {
                errorEl = formGroup.querySelector('.form-error-message');
            }
        }

        if (!errorEl) {
            // Create new error element
            errorEl = document.createElement('span');
            errorEl.className = 'form-error-message';
            errorEl.id = `${field.id || field.name}-error`;

            // Insert after the field
            if (field.parentElement) {
                field.parentElement.appendChild(errorEl);
            }
        }

        return errorEl;
    }

    /**
     * Announce validation error to screen readers
     */
    announceError(field, message) {
        let announcer = document.getElementById('validation-announcer');

        if (!announcer) {
            announcer = document.createElement('div');
            announcer.id = 'validation-announcer';
            announcer.setAttribute('aria-live', 'assertive');
            announcer.setAttribute('aria-atomic', 'true');
            announcer.className = 'sr-only';
            document.body.appendChild(announcer);
        }

        // Clear and set message
        announcer.textContent = '';
        setTimeout(() => {
            announcer.textContent = `${this.getFieldLabel(field)}: ${message}`;
        }, 100);
    }

    /**
     * Get human-readable field label
     */
    getFieldLabel(field) {
        // Try finding associated label
        if (field.id) {
            const label = document.querySelector(`label[for="${field.id}"]`);
            if (label) return label.textContent.trim();
        }

        // Try finding label in parent
        const label = field.closest('.form-group')?.querySelector('label');
        if (label) return label.textContent.trim();

        // Fallback to field name/id
        return field.name || field.id || 'This field';
    }

    /**
     * Clear all form errors
     */
    clearFormErrors(form) {
        form.querySelectorAll('.error').forEach(el => {
            el.classList.remove('error');
            el.removeAttribute('aria-invalid');
        });

        form.querySelectorAll('.form-error-message.visible').forEach(el => {
            el.classList.remove('visible');
            el.textContent = '';
        });
    }

    /**
     * Setup real-time validation on form fields
     */
    setupRealTimeValidation(form, fieldRules) {
        Object.entries(fieldRules).forEach(([fieldName, rules]) => {
            const fields = form.querySelectorAll(`[name="${fieldName}"]`);

            fields.forEach(field => {
                // Validate on blur (when user leaves field)
                field.addEventListener('blur', () => {
                    this.validateField(field, rules);
                });

                // Clear error on input if field was invalid
                field.addEventListener('input', () => {
                    if (field.classList.contains('error')) {
                        this.validateField(field, rules);
                    }
                });

                // Clear error on change for selects and checkboxes
                field.addEventListener('change', () => {
                    if (field.classList.contains('error')) {
                        this.validateField(field, rules);
                    }
                });
            });
        });
    }

    /**
     * Validate on form submission with scroll to first error
     */
    setupSubmitValidation(form, fieldRules) {
        form.addEventListener('submit', (e) => {
            const { isValid, errors } = this.validateForm(form, fieldRules);

            if (!isValid) {
                e.preventDefault();

                // Scroll to first error
                const firstError = form.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstError.focus();
                }

                // Show summary message
                const errorCount = Object.keys(errors).length;
                const summaryMessage = errorCount === 1
                    ? 'Please fix the error below.'
                    : `Please fix the ${errorCount} errors below.`;

                this.showFormSummary(form, summaryMessage, 'error');
            }
        });
    }

    /**
     * Show form-level summary message
     */
    showFormSummary(form, message, type = 'error') {
        let summary = form.querySelector('.form-summary');

        if (!summary) {
            summary = document.createElement('div');
            summary.className = 'form-summary';
            summary.setAttribute('role', 'alert');
            form.insertBefore(summary, form.firstChild);
        }

        summary.className = `form-summary alert alert-${type}`;
        summary.textContent = message;

        // Auto-hide after 5 seconds
        setTimeout(() => {
            summary.style.opacity = '0';
            summary.style.transition = 'opacity 0.3s';
            setTimeout(() => summary.remove(), 300);
        }, 5000);
    }

    /**
     * Add custom validation rule
     */
    addRule(name, testFn, message) {
        this.rules[name] = {
            test: testFn,
            message: message
        };
    }

    /**
     * Get validation rules for registration form
     */
    getRegisterFormRules() {
        return {
            'first_name': { required: true, name: true, minLength: 1, maxLength: 50 },
            'last_name': { required: true, name: true, minLength: 1, maxLength: 50 },
            'username': { required: true, username: true },
            'email': { required: true, email: true, maxLength: 100 },
            'password': { required: true, password: true },
            'confirm_password': { required: true, matchField: 'password' },
            'dietary_preference': { dietaryPreference: true },
            'phone_number': { phone: true, maxLength: 30 }
        };
    }

    /**
     * Get validation rules for login form
     */
    getLoginFormRules() {
        return {
            'username': { required: true },
            'password': { required: true }
        };
    }

    /**
     * Get validation rules for profile update form
     */
    getProfileFormRules() {
        return {
            'first_name': { required: true, name: true, maxLength: 50 },
            'last_name': { required: true, name: true, maxLength: 50 },
            'bio': { maxLength: 500 },
            'dietary_preference': { dietaryPreference: true },
            'phone_number': { phone: true, maxLength: 30 },
            'profile_image_url': { url: true, maxLength: 500 }
        };
    }

    /**
     * Get validation rules for password change form
     */
    getPasswordChangeFormRules() {
        return {
            'current_password': { required: true },
            'new_password': { required: true, password: true },
            'confirm_new_password': { required: true, matchField: 'new_password' }
        };
    }

    /**
     * Get validation rules for recipe creation form
     */
    getCreateRecipeFormRules() {
        return {
            'title': { required: true, maxLength: 255 },
            'description': { maxLength: 2000 },
            'prep_time_minutes': { required: true, nonNegative: true, integer: true },
            'cook_time_minutes': { required: true, nonNegative: true, integer: true },
            'servings': { required: true, positive: true, integer: true, min: 1, max: 100 },
            'difficulty': { required: true, difficulty: true },
            'cuisine_type': { maxLength: 100 },
            'visibility': { required: true, visibility: true },
            'calories': { nonNegative: true, integer: true }
        };
    }

    /**
     * Get validation rules for rating form
     */
    getRatingFormRules() {
        return {
            'overall_rating': { required: true, rating: true },
            'comment': { maxLength: 500 }
        };
    }
}

// Create global form validator instance
const formValidator = new FormValidator();

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormValidator;
}