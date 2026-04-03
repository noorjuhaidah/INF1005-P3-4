(function () {
    'use strict';

    const noResults  = document.getElementById('noResults');
    const searchBox  = document.getElementById('menuSearch');
    const filterBtns = document.querySelectorAll('.ld-filter-btn');
    if (!filterBtns.length && !document.querySelector('.add-to-cart-form')) return;

    let activeFilter = 'all';

    function applyFilters() {
        const query = searchBox ? searchBox.value.toLowerCase().trim() : '';
        let visible = 0;

        document.querySelectorAll('.menu-item-col').forEach(function (col) {
            const matchesCat = activeFilter === 'all' || col.dataset.category === activeFilter;
            const matchesSearch = !query || col.dataset.name.includes(query);

            if (matchesCat && matchesSearch) {
                col.classList.remove('hidden');
                visible++;
            } else {
                col.classList.add('hidden');
            }
        });

        if (noResults) noResults.classList.toggle('d-none', visible > 0);
    }

    filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterBtns.forEach(function (b) {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            activeFilter = this.dataset.filter;
            applyFilters();
        });
    });

    let timer;
    if (searchBox) {
        searchBox.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(applyFilters, 200);
        });
    }

    document.querySelectorAll('.add-to-cart-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const btn = form.querySelector('button[type="submit"]');
            const originalHtml = btn ? btn.innerHTML : '';

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            }

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    const badge = document.querySelector('.ld-badge');
                    if (badge) {
                        badge.textContent = data.cart_count;
                        badge.classList.remove('d-none');
                    }

                    if (btn) {
                        btn.innerHTML = '<i class="bi bi-check-lg"></i> Added!';
                        btn.style.background = '#2ecc71';
                        setTimeout(function () {
                            btn.innerHTML = originalHtml;
                            btn.style.background = '';
                            btn.disabled = false;
                        }, 1500);
                    }
                } else {
                    alert(data.message || 'Could not add item. Please try again.');
                    if (btn) {
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                }
            })
            .catch(function () {
                alert('Something went wrong. Please try again.');
                if (btn) {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            });
        });
    });
})();
