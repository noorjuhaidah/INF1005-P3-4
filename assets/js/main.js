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

// -------------------------------------------------------------
// 1. Bootstrap form validation
//    Adds .was-validated to any form with class .needs-validation
//    on submit, triggering Bootstrap's built-in error styles.
// -------------------------------------------------------------
(function () {
    const forms = document.querySelectorAll('.needs-validation, form[data-inline-validate="true"]');

    function getFeedbackElement(field) {
        let feedback = field.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            return feedback;
        }

        const group = field.closest('.input-group');
        if (group && group.nextElementSibling && group.nextElementSibling.classList.contains('invalid-feedback')) {
            return group.nextElementSibling;
        }

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

    function setFieldValidityUI(field, forceShow) {
        if (!field.willValidate) return;

        const feedback = getFeedbackElement(field);
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

    function checkMatch() {
        if (cpw.value && pw.value !== cpw.value) {
            cpw.setCustomValidity('Passwords do not match.');
        } else {
            cpw.setCustomValidity('');
        }
    }
    pw.addEventListener('input', checkMatch);
    cpw.addEventListener('input', checkMatch);
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
