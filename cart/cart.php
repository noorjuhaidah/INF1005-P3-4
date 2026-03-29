<?php
// =============================================================
// cart/cart.php — LazyDrip Shopping Cart
// Shows all items in the session cart.
// Allows quantity updates and item removal.
// Links to checkout.php to place the order.
// =============================================================

$page_title   = 'Your Cart';
$current_page = 'cart';
require_once __DIR__ . '/../includes/header.php';

// Must be logged in to view cart
require_login();

// -------------------------------------------------------------
// CSRF token
// -------------------------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// -------------------------------------------------------------
// Get cart from session
// -------------------------------------------------------------
$cart  = get_cart();
$total = cart_total();
?>

<!-- ============================================================
     PAGE HEADER
     ============================================================ -->
<section class="ld-cart-hero">
    <div class="container">
        <h1 class="ld-section-title mb-1">
            <i class="bi bi-bag me-2" aria-hidden="true"></i>Your Cart
        </h1>
        <p class="text-muted">
            <?= count($cart) ?> item type<?= count($cart) !== 1 ? 's' : '' ?> in your cart
        </p>
    </div>
</section>

<!-- ============================================================
     CART CONTENT
     ============================================================ -->
<section class="ld-section-sm">
    <div class="container">
        <div id="cart-status" class="visually-hidden" role="status" aria-live="polite" aria-atomic="true"></div>

        <?php if (empty($cart)): ?>
        <!-- Empty cart state -->
        <div class="text-center py-5">
            <i class="bi bi-bag-x fs-1 text-muted" aria-hidden="true"></i>
            <h2 class="mt-3 h5">Your cart is empty</h2>
            <p class="text-muted">Looks like you haven't added anything yet.</p>
            <a href="<?= APP_URL ?>/menu.php" class="ld-btn-primary mt-2">
                Browse Menu
            </a>
        </div>

        <?php else: ?>
        <div class="row g-4">

            <!-- ------------------------------------------------
                 LEFT: Cart items list
                 ------------------------------------------------ -->
            <div class="col-lg-8">
                <div class="ld-cart-card">

                    <?php foreach ($cart as $item_id => $item): ?>
                    <?php
                        $item_key = (string)$item_id;
                        $item_dom_suffix = md5($item_key);
                    ?>
                    <div class="ld-cart-row" id="cart-row-<?= $item_dom_suffix ?>">

                        <!-- Item info -->
                        <div class="ld-cart-info">
                            <div class="ld-cart-icon">
                                <i class="bi bi-cup-hot" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="ld-cart-name"><?= e($item['name']) ?></p>
                                <p class="ld-cart-unit-price">
                                    <?= format_price($item['price']) ?> each
                                </p>
                            </div>
                        </div>

                        <!-- Qty update form -->
                        <form
                            action="update_cart.php"
                            method="POST"
                            class="update-cart-form d-flex align-items-center gap-2"
                            aria-label="Update quantity for <?= e($item['name']) ?>"
                        >
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="item_id"    value="<?= (int)$item_id ?>">
                            <input type="hidden" name="cart_key"   value="<?= e($item_key) ?>">
                            <input type="hidden" name="action"     value="update">

                            <div class="qty-wrapper d-flex align-items-center border rounded-pill px-2">
                                <button type="button" class="qty-decrease ld-qty-btn"
                                        aria-label="Decrease quantity for <?= e($item['name']) ?>">
                                    <i class="bi bi-dash" aria-hidden="true"></i>
                                </button>
                                <?php $qty_input_id = 'qty-' . $item_dom_suffix; ?>
                                <label for="<?= $qty_input_id ?>" class="visually-hidden">
                                    Quantity for <?= e($item['name']) ?>
                                </label>
                                <input
                                    type="number"
                                    id="<?= $qty_input_id ?>"
                                    name="qty"
                                    class="qty-input"
                                    value="<?= (int)$item['qty'] ?>"
                                    min="1" max="10"
                                    style="width:2.2rem;text-align:center;border:none;background:transparent;font-weight:600;"
                                >
                                <button type="button" class="qty-increase ld-qty-btn"
                                        aria-label="Increase quantity for <?= e($item['name']) ?>">
                                    <i class="bi bi-plus" aria-hidden="true"></i>
                                </button>
                            </div>

                            <button type="submit" class="ld-btn-outline ld-cart-update-btn"
                                    aria-label="Update quantity for <?= e($item['name']) ?>">
                                Update
                            </button>
                        </form>

                        <!-- Item subtotal -->
                        <p class="ld-cart-subtotal" id="subtotal-<?= $item_dom_suffix ?>">
                            <?= format_price($item['price'] * $item['qty']) ?>
                        </p>

                        <!-- Remove button -->
                        <form
                            action="update_cart.php"
                            method="POST"
                            class="remove-cart-form"
                            aria-label="Remove <?= e($item['name']) ?> from cart"
                        >
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="item_id"    value="<?= (int)$item_id ?>">
                            <input type="hidden" name="cart_key"   value="<?= e($item_key) ?>">
                            <input type="hidden" name="action"     value="remove">

                            <button
                                type="submit"
                                class="ld-remove-btn"
                                aria-label="Remove <?= e($item['name']) ?>"
                            >
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                            </button>
                        </form>

                    </div><!-- /.ld-cart-row -->

                    <?php if (array_key_last($cart) !== $item_id): ?>
                    <hr class="ld-cart-divider">
                    <?php endif; ?>

                    <?php endforeach; ?>

                </div><!-- /.ld-cart-card -->

                <!-- Continue shopping link -->
                <a href="<?= APP_URL ?>/menu.php" class="ld-back-link mt-3 d-inline-block">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Continue Shopping
                </a>
            </div>

            <!-- ------------------------------------------------
                 RIGHT: Order summary
                 ------------------------------------------------ -->
            <div class="col-lg-4">
                <div class="ld-summary-card">
                    <h2 class="ld-summary-title">Order Summary</h2>

                    <div class="ld-summary-items" aria-label="Items in your cart">
                        <?php foreach ($cart as $item): ?>
                        <div class="ld-summary-item-row">
                            <span class="ld-summary-item-name"><?= e($item['name']) ?></span>
                            <span class="ld-summary-item-qty">x<?= (int)$item['qty'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>

                    <div class="ld-summary-row">
                        <span class="text-muted">Subtotal</span>
                        <span id="cart-total"><?= format_price($total) ?></span>
                    </div>

                    <div class="ld-summary-row">
                        <span class="text-muted">Pickup</span>
                        <span class="text-success fw-semibold">Free</span>
                    </div>

                    <hr>

                    <div class="ld-summary-row ld-summary-total">
                        <span>Total</span>
                        <span id="cart-total-final"><?= format_price($total) ?></span>
                    </div>

                    <a href="<?= APP_URL ?>/cart/checkout.php" class="ld-btn-primary w-100 text-center mt-4 d-block">
                        Proceed to Checkout
                        <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                    </a>

                    <p class="text-muted text-center small mt-3">
                        <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                        Secure checkout
                    </p>
                </div>
            </div>

        </div><!-- /.row -->
        <?php endif; ?>

    </div>
</section>

<div class="ld-modal-backdrop" id="ld-cart-confirm" hidden>
    <div class="ld-modal-card" role="dialog" aria-modal="true" aria-labelledby="ld-cart-confirm-title" aria-describedby="ld-cart-confirm-text">
        <h3 id="ld-cart-confirm-title" class="ld-modal-title">Remove item</h3>
        <p id="ld-cart-confirm-text" class="ld-modal-text">Are you sure you want to remove this item from your cart?</p>
        <div class="ld-modal-actions">
            <button type="button" class="ld-btn-outline" id="ld-cart-confirm-cancel">Cancel</button>
            <button type="button" class="ld-btn-danger" id="ld-cart-confirm-ok">
                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Remove
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     PAGE CSS
     ============================================================ -->
<style>
.ld-cart-hero {
    background: linear-gradient(135deg, var(--ld-blue-light) 0%, #fff 70%);
    padding: 3rem 0 1.5rem;
}

/* Cart card */
.ld-cart-card {
    background: #fff;
    border-radius: var(--ld-radius);
    box-shadow: var(--ld-shadow);
    padding: 1.5rem;
}

/* Each cart row */
.ld-cart-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 0.75rem 0;
}
.ld-cart-divider { margin: 0; opacity: 0.1; }

/* Item info */
.ld-cart-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    min-width: 160px;
}
.ld-cart-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    background: var(--ld-blue-light);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; color: var(--ld-blue-dark);
    flex-shrink: 0;
}
.ld-cart-name {
    font-weight: 600; margin: 0; font-size: 0.95rem;
}
.ld-cart-unit-price {
    font-size: 0.8rem; color: var(--ld-muted); margin: 0;
}

/* Subtotal */
.ld-cart-subtotal {
    font-weight: 700; font-size: 1rem;
    min-width: 70px; text-align: right; margin: 0;
}

/* Qty buttons */
.ld-qty-btn {
    background: none; border: none; padding: 0 0.25rem;
    min-width: 44px;
    min-height: 44px;
    color: var(--ld-muted); cursor: pointer;
    font-size: 1rem; line-height: 1; transition: color 0.15s;
}
.ld-qty-btn:hover { color: var(--ld-blue-dark); }

/* Update button */
.ld-cart-update-btn {
    font-size: 0.78rem;
    padding: 0.25rem 0.75rem;
    white-space: nowrap;
}

/* Remove button */
.ld-remove-btn {
    background: none; border: none;
    color: #ccc; cursor: pointer;
    font-size: 1.1rem;
    min-width: 44px;
    min-height: 44px;
    padding: 0.25rem;
    transition: color 0.2s;
}
.ld-remove-btn:hover { color: #e74c3c; }

/* Back link */
.ld-back-link {
    color: var(--ld-muted);
    font-size: 0.88rem;
}
.ld-back-link:hover { color: var(--ld-blue-dark); }

/* Summary card */
.ld-summary-card {
    background: #fff;
    border-radius: var(--ld-radius);
    box-shadow: var(--ld-shadow);
    padding: 1.75rem;
    position: sticky;
    top: 80px;
}
.ld-summary-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 1.25rem;
}
.ld-summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
}
.ld-summary-items {
    margin-bottom: 0.75rem;
}
.ld-summary-item-row {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.45rem;
    font-size: 0.9rem;
}
.ld-summary-item-name {
    color: var(--ld-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 75%;
}
.ld-summary-item-qty {
    font-weight: 600;
}
.ld-summary-total {
    font-weight: 700;
    font-size: 1.1rem;
}

/* Remove-confirm modal */
.ld-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1200;
    background: rgba(18, 31, 43, 0.42);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.ld-modal-card {
    width: min(420px, 100%);
    background: #fff;
    border-radius: var(--ld-radius);
    box-shadow: 0 14px 34px rgba(20, 40, 56, 0.24);
    border: 1px solid rgba(43, 122, 158, 0.16);
    padding: 1.2rem;
}
.ld-modal-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #1b2733;
}
.ld-modal-text {
    margin: 0.6rem 0 1rem;
    color: var(--ld-muted);
    font-size: 0.94rem;
}
.ld-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.6rem;
}
.ld-btn-danger {
    border: none;
    border-radius: 999px;
    padding: 0.5rem 1rem;
    font-weight: 600;
    background: linear-gradient(135deg, #d94848 0%, #c63737 100%);
    color: #fff;
    box-shadow: 0 6px 14px rgba(198, 55, 55, 0.28);
    transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
}
.ld-btn-danger:hover {
    filter: brightness(0.98);
    transform: translateY(-1px);
    box-shadow: 0 8px 16px rgba(198, 55, 55, 0.34);
}
.ld-btn-danger:focus-visible {
    outline: 3px solid rgba(217, 72, 72, 0.28);
    outline-offset: 2px;
}

/* Fade out removed row */
.ld-cart-row.removing {
    opacity: 0;
    transition: opacity 0.3s;
}

@media (max-width: 576px) {
    .ld-cart-row { gap: 0.5rem; }
    .ld-cart-info { min-width: 0; }
    .ld-cart-subtotal { min-width: auto; }
}

@media (max-width: 991.98px) {
    .ld-summary-card {
        position: static;
        top: auto;
    }
}
</style>

<!-- ============================================================
     PAGE JAVASCRIPT
     AJAX quantity update + remove, live total update
     ============================================================ -->
<script>
(function () {
    'use strict';

    // Keep update buttons as non-JS fallback, but hide them when JS is available.
    document.querySelectorAll('.ld-cart-update-btn').forEach(function (btn) {
        btn.classList.add('d-none');
    });

    // ---------------------------------------------------------
    // AJAX helper — sends a form via fetch(), returns JSON
    // ---------------------------------------------------------
    function postForm(url, formData) {
        return fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(function (res) {
            return res.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (err) {
                    throw new Error('Invalid server response');
                }
            });
        });
    }

    // ---------------------------------------------------------
    // Update cart total display
    // ---------------------------------------------------------
    function updateTotalDisplay(newTotal) {
        const formatted = '$' + parseFloat(newTotal).toFixed(2);
        const t1 = document.getElementById('cart-total');
        const t2 = document.getElementById('cart-total-final');
        if (t1) t1.textContent = formatted;
        if (t2) t2.textContent = formatted;
    }

    // ---------------------------------------------------------
    // Announce cart updates for assistive tech users
    // ---------------------------------------------------------
    function announceCartStatus(message) {
        const live = document.getElementById('cart-status');
        if (!live) return;
        live.textContent = '';
        setTimeout(function () {
            live.textContent = message;
        }, 20);
    }

    // Fallback to normal form post if AJAX path fails.
    function submitFormNormally(form) {
        form.submit();
    }

    // ---------------------------------------------------------
    // Custom remove confirmation modal
    // ---------------------------------------------------------
    const confirmModal = document.getElementById('ld-cart-confirm');
    const confirmText = document.getElementById('ld-cart-confirm-text');
    const confirmOk = document.getElementById('ld-cart-confirm-ok');
    const confirmCancel = document.getElementById('ld-cart-confirm-cancel');
    let confirmResolver = null;

    function closeConfirmModal(result) {
        if (confirmModal) {
            confirmModal.hidden = true;
        }
        if (confirmResolver) {
            confirmResolver(result);
            confirmResolver = null;
        }
    }

    function openConfirmModal(message) {
        return new Promise(function (resolve) {
            confirmResolver = resolve;
            if (confirmText) confirmText.textContent = message;
            if (confirmModal) confirmModal.hidden = false;
            if (confirmCancel) confirmCancel.focus();
        });
    }

    if (confirmCancel) {
        confirmCancel.addEventListener('click', function () {
            closeConfirmModal(false);
        });
    }
    if (confirmOk) {
        confirmOk.addEventListener('click', function () {
            closeConfirmModal(true);
        });
    }
    if (confirmModal) {
        confirmModal.addEventListener('click', function (e) {
            if (e.target === confirmModal) {
                closeConfirmModal(false);
            }
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && confirmModal && !confirmModal.hidden) {
            closeConfirmModal(false);
        }
    });

    // ---------------------------------------------------------
    // Handle quantity UPDATE forms
    // ---------------------------------------------------------
    document.querySelectorAll('.update-cart-form').forEach(function (form) {
        let autoUpdateTimer = null;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const btn  = form.querySelector('button[type="submit"]');
            const itemDomKey = form.querySelector('[name="cart_key"]').value;

            if (form.dataset.updating === '1') return;
            form.dataset.updating = '1';

            if (btn) { btn.disabled = true; btn.textContent = '…'; }

            postForm(form.action, new FormData(form))
            .then(function (data) {
                if (data.success) {
                    // Update subtotal for this row
                    const sub = document.getElementById('subtotal-' + itemDomKey);
                    if (sub) sub.textContent = '$' + parseFloat(data.item_subtotal).toFixed(2);
                    updateTotalDisplay(data.cart_total);
                    announceCartStatus(data.message || 'Cart updated.');

                    // Update navbar badge
                    const badge = document.querySelector('.ld-badge');
                    if (badge) badge.textContent = data.cart_count;

                    if (btn) { btn.disabled = false; btn.textContent = 'Update'; }
                    form.dataset.updating = '0';
                } else {
                    alert(data.message || 'Could not update cart.');
                    if (btn) { btn.disabled = false; btn.textContent = 'Update'; }
                    form.dataset.updating = '0';
                }
            })
            .catch(function () {
                if (btn) { btn.disabled = false; btn.textContent = 'Update'; }
                form.dataset.updating = '0';
                submitFormNormally(form);
            });
        });

        const qtyInput = form.querySelector('.qty-input');
        if (qtyInput) {
            qtyInput.addEventListener('change', function () {
                form.requestSubmit();
            });
        }

        ['.qty-decrease', '.qty-increase'].forEach(function (selector) {
            const spinnerBtn = form.querySelector(selector);
            if (!spinnerBtn || !qtyInput) return;

            spinnerBtn.addEventListener('click', function () {
                const before = qtyInput.value;
                if (autoUpdateTimer) clearTimeout(autoUpdateTimer);

                autoUpdateTimer = setTimeout(function () {
                    if (qtyInput.value !== before) {
                        form.requestSubmit();
                    }
                }, 10);
            });
        });
    });

    // ---------------------------------------------------------
    // Handle REMOVE forms
    // ---------------------------------------------------------
    document.querySelectorAll('.remove-cart-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const itemDomKey = form.querySelector('[name="cart_key"]').value;
            const row    = document.getElementById('cart-row-' + itemDomKey);
            const btn = form.querySelector('button[type="submit"]');
            const rowName = row ? row.querySelector('.ld-cart-name') : null;
            const itemName = rowName ? rowName.textContent.trim() : 'this item';
            const msg = 'Remove ' + itemName + ' from your cart?';

            openConfirmModal(msg).then(function (confirmed) {
                if (!confirmed) return;

                postForm(form.action, new FormData(form))
            .then(function (data) {
                if (data.success) {
                    // Fade out and remove the row
                    if (row) {
                        row.classList.add('removing');
                        setTimeout(function () { row.remove(); }, 300);
                    }
                    updateTotalDisplay(data.cart_total);
                    announceCartStatus(data.message || 'Item removed from cart.');

                    // Update navbar badge
                    const badge = document.querySelector('.ld-badge');
                    if (badge) badge.textContent = data.cart_count;

                    // If cart is now empty, reload to show empty state
                    if (data.cart_count === 0) {
                        setTimeout(function () { location.reload(); }, 400);
                    }
                } else {
                    alert(data.message || 'Could not remove item.');
                }
            })
            .catch(function () {
                submitFormNormally(form);
            });
            });
        });
    });

})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
