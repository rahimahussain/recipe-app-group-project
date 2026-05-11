/**
 * Form Validation Module
 * Provides reusable validation functions
 */

class FormValidator {
    constructor() {
        this.rules = {
            required: {
                test: (value) => value && value.toString().trim().length > 0,
                message: 'This field is required.'
            },
            email: {
                test: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
                message: 'Please enter a valid email address.'
            },
            minLength: {
                test: (value, min) => value && value.length >= min,
                message: (min) => `Must be at least ${min} characters.`
            },
            maxLength: {
                test: (value, max) => !value || value.length <= max,
                message: (max) => `Must not exceed ${max} characters.`
            },
            password: {
                test: (value) => /^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(value),
                message: 'Password must be at least 8 characters with one letter and one number.'
            },
            match: {
                test: (value, matchValue) => value === matchValue,
                message: 'Values do not match.'
            },
            username: {
                test: (value) => /^[a-zA-Z0-9_]{3,50}$/.test(value),
                message: 'Username must be 3-50 characters (letters, numbers, underscores).'
            },
            url: {
                test: (value) => {
                    if (!value) return true;
                    try { new URL(value); return true; } catch { return false; }
                },
                message: 'Please enter a valid URL.'
            },
            number: {
                test: (value) => !isNaN(value) && value !== '',
                message: 'Please enter a valid number.'
            },
            min: {
                test: (value, min) => parseFloat(value) >= min,
                message: (min) => `Must be at least ${min}.`
            },
            max: {
                test: (value, max) => parseFloat(value) <= max,
                message: (max) => `Must not exceed ${max}.`
            }
        };
    }

    /**
     * Validate a form field
     */
    validateField(field, rules) {
        const value = field.value;
        let isValid = true;
        let errorMessage = '';

        for (const [rule, param] of Object.entries(rules)) {
            const ruleConfig = this.rules[rule];
            if (!ruleConfig) continue;

            const testValue = param === true ? value : this.rules[rule].test(value, param);

            if (typeof testValue === 'boolean' ? !testValue : !this.rules[rule].test(value, testValue)) {
                isValid = false;
                errorMessage = typeof ruleConfig.message === 'function'
                    ? ruleConfig.message(param)
                    : ruleConfig.message;
                break;
            }
        }

        this.showFieldValidation(field, isValid, errorMessage);
        return isValid;
    }

    /**
     * Validate entire form
     */
    validateForm(form, fieldRules) {
        let isValid = true;

        Object.entries(fieldRules).forEach(([fieldName, rules]) => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                const fieldValid = this.validateField(field, rules);
                if (!fieldValid) isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Show/hide field validation state
     */
    showFieldValidation(field, isValid, message) {
        const errorEl = field.parentElement.querySelector('.form-error-message') ||
            document.getElementById(`${field.id}-error`);

        if (!isValid && message) {
            field.classList.add('error');
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.add('visible');
            }
        } else {
            field.classList.remove('error');
            if (errorEl) {
                errorEl.classList.remove('visible');
            }
        }
    }

    /**
     * Setup real-time validation on a form
     */
    setupRealTimeValidation(form, fieldRules) {
        Object.entries(fieldRules).forEach(([fieldName, rules]) => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (!field) return;

            field.addEventListener('blur', () => {
                this.validateField(field, rules);
            });

            field.addEventListener('input', () => {
                // Clear error on input if field becomes valid
                if (!field.classList.contains('error')) return;
                this.validateField(field, rules);
            });
        });
    }
}

// Global form validator instance
const formValidator = new FormValidator();