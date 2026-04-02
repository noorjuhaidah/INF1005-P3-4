// =============================================================
// assets/js/main.js
// LazyDrip — Custom Client-Side JavaScript
// Loaded on every page via footer.php
// =============================================================

'use strict';

// -------------------------------------------------------------
// Shared inline notice helper
// Usage: window.ldShowNotice('Message', 'warning')
// Levels: primary, secondary, success, danger, warning, info
// -------------------------------------------------------------
window.ldShowNotice = function (message, level) {
    const host = document.getElementById('flash-container') || document.querySelector('main') || document.body;
    const notice = document.createElement('div');
    notice.className = 'alert alert-' + (level || 'info') + ' alert-dismissible fade show mt-2';
    notice.setAttribute('role', 'status');
    notice.setAttribute('aria-live', 'polite');
    notice.innerHTML =
        '<span></span>' +
        '<button type="button" class="btn-close" aria-label="Close"></button>';
    notice.querySelector('span').textContent = message;
    host.prepend(notice);

    const closeBtn = notice.querySelector('.btn-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            notice.remove();
        });
    }

    setTimeout(function () {
        if (notice.isConnected) notice.remove();
    }, 4000);
};

// Shared feedback resolver for regular and input-group fields.
function ldGetFeedbackElement(field, createIfMissing) {
    let feedback = field.nextElementSibling;
    if (feedback && feedback.classList.contains('invalid-feedback')) {
        return feedback;
    }

    const group = field.closest('.input-group');
    if (group && group.nextElementSibling && group.nextElementSibling.classList.contains('invalid-feedback')) {
        return group.nextElementSibling;
    }

    if (!createIfMissing) return null;

    const created = document.createElement('div');
    created.className = 'invalid-feedback';
    created.dataset.runtimeFeedback = 'true';

    if (group) {
        group.insertAdjacentElement('afterend', created);
    } else {
        field.insertAdjacentElement('afterend', created);
    }

    return created;
}

// -------------------------------------------------------------
// 1. Bootstrap form validation
//    Adds .was-validated to any form with class .needs-validation
//    on submit, triggering Bootstrap's built-in error styles.
// -------------------------------------------------------------
(function () {
    const forms = document.querySelectorAll('.needs-validation, form[data-inline-validate="true"]');

    function setFieldValidityUI(field, forceShow) {
        if (!field.willValidate) return;

        const feedback = ldGetFeedbackElement(field, true);
        const isValid = field.checkValidity();
        if (isValid) {
            field.classList.remove('is-invalid');
            if (feedback.dataset.runtimeFeedback === 'true') {
                feedback.textContent = '';
            }
            return;
        }

        if (!forceShow && !field.dataset.touched) return;

        field.classList.add('is-invalid');
        feedback.textContent = field.validationMessage || 'Please check this field.';
    }

    forms.forEach(function (form) {
        if (form.dataset.inlineValidate === 'true') {
            form.setAttribute('novalidate', 'novalidate');
        }

        const fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(function (field) {
            field.addEventListener('input', function () {
                field.dataset.touched = 'true';
                setFieldValidityUI(field, false);
            });

            field.addEventListener('change', function () {
                field.dataset.touched = 'true';
                setFieldValidityUI(field, false);
            });

            field.addEventListener('blur', function () {
                field.dataset.touched = 'true';
                setFieldValidityUI(field, true);
            });
        });

        form.addEventListener('submit', function (event) {
            let invalidCount = 0;

            fields.forEach(function (field) {
                field.dataset.touched = 'true';
                setFieldValidityUI(field, true);
                if (field.willValidate && !field.checkValidity()) {
                    invalidCount += 1;
                }
            });

            if (invalidCount > 0 || !form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });
})();

// -------------------------------------------------------------
// 2. Password confirmation check
//    If a form has #password and #confirm_password,
//    show an inline error if they don't match on blur.
// -------------------------------------------------------------
(function () {
    const pw  = document.getElementById('password');
    const cpw = document.getElementById('confirm_password');
    if (!pw || !cpw) return;

    const minLen = parseInt(pw.getAttribute('minlength'), 10) || 8;

    function refreshFieldUI(field) {
        if (!field.willValidate) return;

        const feedback = ldGetFeedbackElement(field, false);
        if (!feedback) return;

        if (field.checkValidity()) {
            field.classList.remove('is-invalid');
            if (
                feedback.dataset.runtimeFeedback === 'true' ||
                feedback.textContent.indexOf('Password must be at least') === 0 ||
                feedback.textContent.indexOf('Confirm password must be at least') === 0 ||
                feedback.textContent.trim() === 'Passwords do not match.'
            ) {
                feedback.textContent = '';
            }
            return;
        }

        if (!field.dataset.touched) return;

        field.classList.add('is-invalid');
        feedback.textContent = field.validationMessage || 'Please check this field.';
    }

    function syncPasswordValidity() {
        let confirmError = '';

        if (pw.value && pw.value.length < minLen) {
            pw.setCustomValidity('Password must be at least ' + minLen + ' characters.');
        } else {
            pw.setCustomValidity('');
        }

        if (cpw.value) {
            if (cpw.value.length < minLen) {
                confirmError = 'Confirm password must be at least ' + minLen + ' characters.';
            } else if (pw.value && pw.value.length >= minLen && pw.value !== cpw.value) {
                confirmError = 'Passwords do not match.';
            }
        }

        cpw.setCustomValidity(confirmError);

        refreshFieldUI(pw);
        refreshFieldUI(cpw);
    }

    [pw, cpw].forEach(function (field) {
        field.addEventListener('input', function () {
            field.dataset.touched = 'true';
            syncPasswordValidity();
        });
        field.addEventListener('blur', function () {
            field.dataset.touched = 'true';
            syncPasswordValidity();
        });
    });
})();

// -------------------------------------------------------------
// 2b. Password visibility toggles
//     Supports register/login fields when matching IDs exist.
// -------------------------------------------------------------
(function () {
    function bindPasswordToggle(buttonId, inputId, iconId) {
        const button = document.getElementById(buttonId);
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!button || !input || !icon) return;

        button.addEventListener('click', function () {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
        });
    }

    bindPasswordToggle('togglePassword', 'password', 'toggleIcon');
    bindPasswordToggle('toggleConfirmPassword', 'confirm_password', 'toggleConfirmIcon');
})();

// -------------------------------------------------------------
// 3. Quantity spinner for cart
//    + / - buttons around an <input type="number" class="qty-input">
// -------------------------------------------------------------
document.querySelectorAll('.qty-decrease').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const input = this.closest('.qty-wrapper')?.querySelector('.qty-input');
        if (input) {
            const min = parseInt(input.min) || 1;
            if (parseInt(input.value) > min) input.value = parseInt(input.value) - 1;
        }
    });
});
document.querySelectorAll('.qty-increase').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const input = this.closest('.qty-wrapper')?.querySelector('.qty-input');
        if (input) {
            const max = parseInt(input.max) || 99;
            if (parseInt(input.value) < max) input.value = parseInt(input.value) + 1;
        }
    });
});

// -------------------------------------------------------------
// 4. Auto-dismiss flash messages after 5 seconds
// -------------------------------------------------------------
(function () {
    const alerts = document.querySelectorAll('#flash-container .alert');
    if (typeof bootstrap === 'undefined' || !bootstrap.Alert) return;
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
})();

// -------------------------------------------------------------
// 5. Confirm dialogs for destructive actions
//    Add data-confirm="Your message here" to any button or link.
// -------------------------------------------------------------
document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
        if (!confirm(this.dataset.confirm)) {
            e.preventDefault();
        }
    });
});
