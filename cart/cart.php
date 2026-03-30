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
$csrf = csrf_token();

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
                    <div class="ld-cart-row" id="cart-row-<?= (int)$item_id ?>">

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
                            action="<?= APP_URL ?>/cart/update_cart.php"
                            method="POST"
                            class="update-cart-form d-flex align-items-center gap-2"
                            aria-label="Update quantity for <?= e($item['name']) ?>"
                        >
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="item_id"    value="<?= (int)$item_id ?>">
                            <input type="hidden" name="action"     value="update">

                            <div class="qty-wrapper d-flex align-items-center border rounded-pill px-2">
                                <button type="button" class="qty-decrease ld-qty-btn"
                                        aria-label="Decrease quantity for <?= e($item['name']) ?>">
                                    <i class="bi bi-dash" aria-hidden="true"></i>
                                </button>
                                <?php $qty_input_id = 'qty-' . (int)$item_id; ?>
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
                                Update <?= e($item['name']) ?>
                            </button>
                        </form>

                        <!-- Item subtotal -->
                        <p class="ld-cart-subtotal" id="subtotal-<?= (int)$item_id ?>">
                            <?= format_price($item['price'] * $item['qty']) ?>
                        </p>

                        <!-- Remove button -->
                        <form
                            action="<?= APP_URL ?>/cart/update_cart.php"
                            method="POST"
                            class="remove-cart-form"
                            aria-label="Remove <?= e($item['name']) ?> from cart"
                        >
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="item_id"    value="<?= (int)$item_id ?>">
                            <input type="hidden" name="action"     value="remove">

                            <button
                                type="submit"
                                class="ld-remove-btn"
                                aria-label="Remove <?= e($item['name']) ?>"
                                data-confirm="Remove <?= e($item['name']) ?> from your cart?"
                            >
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                            </button>
                        </form>

                    </div><!-- /.ld-cart-row -->

                    <?php if (!array_key_last($cart) !== $item_id): ?>
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

<<<<<<< Updated upstream
=======
<div class="ld-modal-backdrop" id="ld-cart-confirm" hidden>
    <div class="ld-modal-card" role="dialog" aria-modal="true" aria-labelledby="ld-cart-confirm-title" aria-describedby="ld-cart-confirm-text" tabindex="-1">
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

>>>>>>> Stashed changes
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
    font-size: 1.1rem; padding: 0.25rem;
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
.ld-summary-total {
    font-weight: 700;
    font-size: 1.1rem;
}

/* Fade out removed row */
.ld-cart-row.removing {
    opacity: 0;
    transition: opacity 0.3s;
}

@media (max-width: 576px) {
    .ld-cart-row { gap: 0.5rem; }
    .ld-cart-subtotal { min-width: auto; }
}
</style>

<!-- ============================================================
     PAGE JAVASCRIPT
     AJAX quantity update + remove, live total update
     ============================================================ -->
<script>
(function () {
    'use strict';

    // ---------------------------------------------------------
    // AJAX helper — sends a form via fetch(), returns JSON
    // ---------------------------------------------------------
    function postForm(url, formData) {
        return fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) { return res.json(); });
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

<<<<<<< Updated upstream
=======
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
    let confirmLastActiveElement = null;
    let confirmPreviousBodyOverflow = '';

    function setPageRegionsHidden(hidden) {
        const regions = document.querySelectorAll('header, main, footer');
        regions.forEach(function (region) {
            if (!confirmModal || confirmModal.contains(region)) return;
            if (hidden) {
                region.setAttribute('aria-hidden', 'true');
            } else {
                region.removeAttribute('aria-hidden');
            }
        });
    }

    function getModalFocusableElements() {
        if (!confirmModal) return [];
        return Array.from(confirmModal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'))
            .filter(function (el) {
                return !el.hasAttribute('disabled') && !el.getAttribute('aria-hidden');
            });
    }

    function closeConfirmModal(result) {
        if (confirmModal) {
            confirmModal.hidden = true;
        }
        document.body.style.overflow = confirmPreviousBodyOverflow;
        setPageRegionsHidden(false);
        if (confirmLastActiveElement && typeof confirmLastActiveElement.focus === 'function' && document.contains(confirmLastActiveElement)) {
            confirmLastActiveElement.focus();
        }
        confirmLastActiveElement = null;
        if (confirmResolver) {
            confirmResolver(result);
            confirmResolver = null;
        }
    }

    function openConfirmModal(message) {
        return new Promise(function (resolve) {
            confirmResolver = resolve;
            confirmLastActiveElement = document.activeElement;
            confirmPreviousBodyOverflow = document.body.style.overflow;
            if (confirmText) confirmText.textContent = message;
            if (confirmModal) {
                confirmModal.hidden = false;
                setPageRegionsHidden(true);
                document.body.style.overflow = 'hidden';
            }

            const focusables = getModalFocusableElements();
            if (focusables.length > 0) {
                (confirmCancel || focusables[0]).focus();
            } else if (confirmModal) {
                confirmModal.focus();
            }
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

        confirmModal.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab') return;
            const focusables = getModalFocusableElements();
            if (focusables.length === 0) return;

            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            const active = document.activeElement;

            if (e.shiftKey && active === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && active === last) {
                e.preventDefault();
                first.focus();
            }
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && confirmModal && !confirmModal.hidden) {
            closeConfirmModal(false);
        }
    });

>>>>>>> Stashed changes
    // ---------------------------------------------------------
    // Handle quantity UPDATE forms
    // ---------------------------------------------------------
    document.querySelectorAll('.update-cart-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const btn  = form.querySelector('button[type="submit"]');
            const itemId = form.querySelector('[name="item_id"]').value;

            if (btn) { btn.disabled = true; btn.textContent = '…'; }

            postForm(form.action, new FormData(form))
            .then(function (data) {
                if (data.success) {
                    // Update subtotal for this row
                    const sub = document.getElementById('subtotal-' + itemId);
                    if (sub) sub.textContent = '$' + parseFloat(data.item_subtotal).toFixed(2);
                    updateTotalDisplay(data.cart_total);
                    announceCartStatus(data.message || 'Cart updated.');

                    // Update navbar badge
                    const badge = document.querySelector('.ld-badge');
                    if (badge) badge.textContent = data.cart_count;

                    if (btn) { btn.disabled = false; btn.textContent = 'Update'; }
                } else {
                    alert(data.message || 'Could not update cart.');
                    if (btn) { btn.disabled = false; btn.textContent = 'Update'; }
                }
            })
            .catch(function () {
                alert('Something went wrong. Please try again.');
                if (btn) { btn.disabled = false; btn.textContent = 'Update'; }
            });
        });
    });

    // ---------------------------------------------------------
    // Handle REMOVE forms
    // ---------------------------------------------------------
    document.querySelectorAll('.remove-cart-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const itemId = form.querySelector('[name="item_id"]').value;
            const row    = document.getElementById('cart-row-' + itemId);

            // Confirm dialog (re-use data-confirm pattern from main.js)
            const btn = form.querySelector('button[type="submit"]');
            const msg = btn ? btn.dataset.confirm : 'Remove this item?';
            if (!confirm(msg)) return;

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
                alert('Something went wrong. Please try again.');
            });
        });
    });

})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
