(function () {
    'use strict';

    const toggle = document.getElementById('redeem_toggle');
    const totalEl = document.getElementById('total-display');
    const discRow = document.getElementById('discount-row');

    if (!toggle || !totalEl || !discRow) return;

    const subtotal = parseFloat(totalEl.dataset.subtotal || '0');
    const discount = parseFloat(totalEl.dataset.discount || '0');

    function fmt(value) {
        return '$' + value.toFixed(2);
    }

    toggle.addEventListener('change', function () {
        if (this.checked) {
            totalEl.textContent = fmt(Math.max(0, subtotal - discount));
            discRow.style.removeProperty('display');
        } else {
            totalEl.textContent = fmt(subtotal);
            discRow.style.display = 'none';
        }
    });
})();
