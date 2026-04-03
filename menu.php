<?php
// Shows the menu, category filters, and add-to-cart forms.
//                category_id, image_path, is_available

$page_title = 'Menu';
$current_page = 'menu';
$page_styles = <<<'CSS'
<style>
    .ld-menu-hero {
        background: linear-gradient(135deg, var(--ld-blue-light) 0%, #fff 70%);
        padding: 4rem 0 2.5rem;
    }

    .ld-search-wrap {
        position: relative;
        width: min(100%, 26.25rem);
    }

    .ld-search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--ld-muted);
        pointer-events: none;
    }

    .ld-search-input {
        padding-left: 2.5rem;
        border-radius: 999px;
        border: 2px solid var(--ld-blue-light);
        font-size: 0.95rem;
        transition: border-color 0.2s;
    }

    .ld-search-input:focus {
        border-color: var(--ld-blue-dark);
        box-shadow: 0 0 0 0.2rem rgba(126, 200, 227, 0.25);
    }

    .ld-filter-tabs {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .ld-filter-btn {
        padding: 0.35rem 1.1rem;
        border-radius: 999px;
        border: 2px solid var(--ld-blue-light);
        background: #fff;
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--ld-muted);
        cursor: pointer;
        transition: all 0.2s;
    }

    .ld-filter-btn:hover,
    .ld-filter-btn.active {
        background: #1F6F93;
        border-color: #1F6F93;
        color: #fff;
    }

    .ld-menu-img {
        width: 100%;
        height: auto;
        aspect-ratio: 4 / 3;
        object-fit: cover;
        background-color: var(--ld-blue-light);
    }

    .ld-menu-item-name {
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: 0.4rem;
    }

    .ld-price {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--ld-charcoal);
    }

    .ld-menu-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.65rem;
        flex-wrap: wrap;
    }

    .ld-menu-actions .ld-price {
        flex: 0 0 auto;
    }

    .add-to-cart-form {
        margin-left: auto;
        max-width: 100%;
    }

    .ld-qty-btn {
        background: none;
        border: none;
        padding: 0 0.25rem;
        min-width: 44px;
        min-height: 44px;
        color: var(--ld-muted);
        cursor: pointer;
        font-size: 1rem;
        line-height: 1;
        transition: color 0.15s;
    }

    .ld-qty-btn:hover {
        color: var(--ld-blue-dark);
    }

    .ld-add-btn {
        font-size: 0.85rem;
        padding: 0.4rem 1rem;
        white-space: nowrap;
    }

    .menu-item-col {
        transition: opacity 0.25s, transform 0.25s;
    }

    .menu-item-col.hidden {
        display: none;
    }

    @media (max-width: 1399.98px) {
        .ld-menu-actions {
            align-items: stretch;
        }

        .ld-menu-actions .ld-price {
            width: 100%;
        }

        .add-to-cart-form {
            width: 100%;
            justify-content: space-between;
            margin-left: 0;
        }

        .ld-menu-actions>.ld-add-btn {
            width: 100%;
            text-align: center;
        }
    }
</style>
CSS;
require_once __DIR__ . '/includes/header.php';

// Create a token for the add-to-cart forms.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Load the available menu items and their categories.
try {
    $stmt = $pdo->query(
        "SELECT m.item_id,
                m.item_name,
                m.description,
                m.price,
                m.image_path,
                m.is_available,
                c.category_name
           FROM menu_items m
           JOIN categories c ON m.category_id = c.category_id
          WHERE m.is_available = 1
          ORDER BY c.category_name ASC, m.item_name ASC"
    );
    $all_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Menu fetch error: ' . $e->getMessage());
    $all_items = [];
}

// Build the category list for the filter buttons.
$categories = [];
foreach ($all_items as $item) {
    if (!in_array($item['category_name'], $categories)) {
        $categories[] = $item['category_name'];
    }
}
?>

<section class="ld-menu-hero">
    <div class="container text-center">
        <p class="ld-chip mb-3">Fresh &amp; made to order ☕</p>
        <h1 class="ld-hero-title mb-2">Our Menu</h1>
        <p class="ld-hero-subtitle mx-auto">
            Slow-crafted drinks for every mood. Order ahead, pick up with Grab.
        </p>

        <!-- Search the menu by item name. -->
        <div class="ld-search-wrap mt-4 mx-auto">
            <i class="bi bi-search ld-search-icon" aria-hidden="true"></i>
            <input type="search" id="menuSearch" class="form-control ld-search-input" placeholder="Search drinks…"
                aria-label="Search menu items" autocomplete="off">
        </div>
    </div>
</section>

<section class="ld-section-sm">
    <div class="container">
        <h2 class="visually-hidden">Browse menu items</h2>

        <?php if (empty($all_items)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cup-hot fs-1 text-muted"></i>
                <p class="mt-3 text-muted">Menu coming soon — check back shortly!</p>
            </div>
                
        <?php else: ?>

            <!-- Filter the menu by category. -->
            <div class="ld-filter-tabs mb-4" role="group" aria-label="Filter by category">
                <button class="ld-filter-btn active" data-filter="all" aria-pressed="true">All</button>

                <?php foreach ($categories as $cat): ?>
                    <button class="ld-filter-btn" data-filter="<?= e(strtolower($cat)) ?>"
                        aria-pressed="false"><?= e(ucfirst($cat)) ?></button>
                <?php endforeach; ?>
            </div>

            <!-- Show this when no items match the current search or filter. -->
            <p id="noResults" class="text-center text-muted py-4 d-none" aria-live="polite">
                No drinks match your search. Try something else?
            </p>

            <!-- Menu item cards -->
            <div class="row g-4" id="menuGrid">

                <?php foreach ($all_items as $item):
                    $imgSrc = !empty($item['image_path'])
                        ? e(UPLOAD_URL . $item['image_path'])
                        : DEFAULT_IMG;
                    $catSlug = strtolower($item['category_name']);
                    ?>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-4 col-xxl-3 menu-item-col" data-category="<?= e($catSlug) ?>"
                        data-name="<?= e(strtolower($item['item_name'])) ?>">
                        <article class="ld-card h-100 d-flex flex-column" aria-label="<?= e($item['item_name']) ?>">

                            <img src="<?= $imgSrc ?>" alt="<?= e($item['item_name']) ?>" class="card-img-top ld-menu-img"
                                loading="lazy">

                            <div class="card-body d-flex flex-column p-4">

                                <span class="ld-chip mb-2" style="font-size:0.7rem;">
                                    <?= e(ucfirst($item['category_name'])) ?>
                                </span>

                                <h2 class="ld-menu-item-name"><?= e($item['item_name']) ?></h2>
                                <p class="text-muted small flex-grow-1">
                                    <?= e($item['description']) ?>
                                </p>

                                <div class="ld-menu-actions mt-3">
                                    <span class="ld-price">
                                        <?= format_price((float) $item['price']) ?>
                                    </span>

                                    <?php if (is_logged_in()): ?>
                                        <!-- Add this item to the cart. -->
                                        <form action="<?= APP_URL ?>/cart/add_to_cart.php" method="POST"
                                            class="d-flex align-items-center gap-2 add-to-cart-form"
                                            aria-label="Add <?= e($item['item_name']) ?> to cart">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <input type="hidden" name="item_id" value="<?= (int) $item['item_id'] ?>">
                                            <input type="hidden" name="item_name" value="<?= e($item['item_name']) ?>">
                                            <input type="hidden" name="item_price" value="<?= (float) $item['price'] ?>">

                                            <!-- Let the user adjust the quantity before adding. -->
                                            <div class="qty-wrapper d-flex align-items-center border rounded-pill px-2">
                                                <button type="button" class="qty-decrease ld-qty-btn"
                                                    aria-label="Decrease quantity">
                                                    <i class="bi bi-dash" aria-hidden="true"></i>
                                                </button>
                                                <input type="number" name="qty" class="qty-input" value="1" min="1" max="10"
                                                    aria-label="Quantity"
                                                    style="width:2rem;text-align:center;border:none;background:transparent;font-weight:600;">
                                                <button type="button" class="qty-increase ld-qty-btn"
                                                    aria-label="Increase quantity">
                                                    <i class="bi bi-plus" aria-hidden="true"></i>
                                                </button>
                                            </div>

                                            <button type="submit" class="ld-btn-primary ld-add-btn">
                                                <i class="bi bi-bag-plus me-1" aria-hidden="true"></i>Add
                                            </button>
                                        </form>

                                    <?php else: ?>
                                        <a href="<?= APP_URL ?>/auth/login.php" class="ld-btn-outline ld-add-btn">
                                            Log in to order
                                        </a>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>

            </div><!-- /#menuGrid -->
        <?php endif; ?>

    </div>
</section>


<!-- Menu page JavaScript -->
<script>
    (function () {
        'use strict';

        const noResults = document.getElementById('noResults');
        const searchBox = document.getElementById('menuSearch');
        const filterBtns = document.querySelectorAll('.ld-filter-btn');
        let activeFilter = 'all';

        // Apply both the selected category and the search text.
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

        // Category tab clicks
        filterBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                filterBtns.forEach(function (b) {
                    b.classList.remove('active');
                    b.setAttribute('aria-pressed', 'false');
                });
                this.classList.add('active');
                this.setAttribute('aria-pressed', 'true');
                activeFilter = this.dataset.filter;
                applyFilters();
            });
        });

        // Live search (debounced 200ms)
        let timer;
        if (searchBox) {
            searchBox.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(applyFilters, 200);
            });
        }

        // Add items to the cart without reloading the page.
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
                    .then(function (res) {
                        const contentType = res.headers.get('content-type') || '';
                        if (!res.ok) {
                            throw new Error('Request failed with status ' + res.status);
                        }
                        if (!contentType.includes('application/json')) {
                            throw new Error('Unexpected response format');
                        }
                        return res.json();
                    })
                    .then(function (data) {
                        if (data.success) {
                            // Update navbar cart badge
                            const badge = document.querySelector('.ld-badge');
                            if (badge) {
                                badge.textContent = data.cart_count;
                                badge.classList.remove('d-none');
                            }
                            // Flash green confirmation on the button
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
                            window.ldShowNotice(data.message || 'Could not add item. Please try again.', 'warning');
                            if (btn) { btn.innerHTML = originalHtml; btn.disabled = false; }
                        }
                    })
                    .catch(function (err) {
                        console.error('Add to cart failed:', err);
                        window.ldShowNotice('Something went wrong. Please try again.', 'danger');
                        if (btn) { btn.innerHTML = originalHtml; btn.disabled = false; }
                    });
            });
        });

    })();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
