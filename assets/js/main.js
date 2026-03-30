// =============================================================
// assets/js/main.js
// LazyDrip — Custom Client-Side JavaScript
// Loaded on every page via footer.php
// =============================================================

'use strict';

// -------------------------------------------------------------
// 1. Bootstrap form validation
//    Adds .was-validated to any form with class .needs-validation
//    on submit, triggering Bootstrap's built-in error styles.
// -------------------------------------------------------------
(function () {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
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
            const min = Number.parseInt(input.min, 10) || 1;
            const current = Number.parseInt(input.value, 10) || min;
            if (current > min) input.value = current - 1;
        }
    });
});
document.querySelectorAll('.qty-increase').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const input = this.closest('.qty-wrapper')?.querySelector('.qty-input');
        if (input) {
            const max = Number.parseInt(input.max, 10) || 99;
            const current = Number.parseInt(input.value, 10) || 1;
            if (current < max) input.value = current + 1;
        }
    });
});

// -------------------------------------------------------------
// 4. Auto-dismiss flash messages after 5 seconds
// -------------------------------------------------------------
(function () {
    const alerts = document.querySelectorAll('#flash-container .alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            if (!alert.isConnected) return;
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
