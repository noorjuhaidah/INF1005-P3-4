<?php
// =============================================================
// cart/checkout.php — Order checkout with rewards redemption
// =============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login();

$cart  = get_cart();
$userId = (int)$_SESSION['user_id'];
$csrf = csrf_token();

// ------------------------------------------------------------------
// Fetch current points from DB (never trust session for money/points)
// ------------------------------------------------------------------
$currentPoints = 0;
try {
    $stmt = $pdo->prepare("SELECT points FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        $currentPoints = (int)$row['points'];
    }
} catch (PDOException $e) {
    $currentPoints = 0;
}

$canRedeem    = $currentPoints >= POINTS_REDEEM_AMOUNT;
$cartSubtotal = cart_total();

// ------------------------------------------------------------------
// Handle POST — place order
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf(APP_URL . '/cart/checkout.php');

    if (empty($cart)) {
        set_flash('warning', 'Your cart is empty.');
        redirect(APP_URL . '/cart/cart.php');
    }

    // Re-validate redemption server-side (ignore what the form says if not eligible)
    $wantsRedeem  = isset($_POST['redeem_points']) && $_POST['redeem_points'] === '1';
    $applyRedeem  = $wantsRedeem && $canRedeem;

    // Compute final total — never let it go below $0.00
    $finalTotal = $cartSubtotal;
    if ($applyRedeem) {
        $finalTotal = max(0.0, $cartSubtotal - POINTS_REDEEM_VALUE);
    }

    try {
        $pdo->beginTransaction();

        // 1. Insert the order
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                user_id,
                status,
                payment_status,
                subtotal,
                points_redeemed,
                discount_applied,
                total_amount,
                special_requests
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            'submitted',
            'pending_verification',
            $cartSubtotal,
            $applyRedeem ? POINTS_REDEEM_AMOUNT : 0,
            $applyRedeem ? POINTS_REDEEM_VALUE : 0.00,
            $finalTotal,
            null,
        ]);
        $orderId = (int)$pdo->lastInsertId();

        // 2. Insert order_items (line-by-line snapshot)
        $itemStmt = $pdo->prepare("
            INSERT INTO order_items (order_id, item_id, item_name, unit_price, quantity, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($cart as $itemId => $item) {
            $rawPrice = $item['price'] ?? 0;
            if (is_string($rawPrice)) {
                $rawPrice = preg_replace('/[^0-9.\-]/', '', $rawPrice);
            }
            $unitPrice = is_numeric($rawPrice) ? (float)$rawPrice : 0.0;
            $qty       = is_numeric($item['qty'] ?? 0) ? (int)$item['qty'] : 0;

            if ($qty <= 0) continue;

            $itemStmt->execute([
                $orderId,
                (int)$itemId,
                $item['name'] ?? 'Item',
                $unitPrice,
                $qty,
                round($unitPrice * $qty, 2),
            ]);
        }

        // 3. Deduct points INSIDE the transaction so it rolls back on failure
        if ($applyRedeem) {
            $redeemed = redeem_points($pdo, $userId, $orderId);
            if (!$redeemed) {
                // Points were already spent elsewhere — abort
                $pdo->rollBack();
                set_flash('danger', 'Your points balance changed. Please refresh and try again.');
                redirect(APP_URL . '/cart/checkout.php');
            }
        }

        $pdo->commit();

        // 4. Award earned points AFTER commit (non-fatal if it fails)
        award_points($pdo, $userId, $orderId, $finalTotal);

        // 5. Refresh session points so navbar/dashboard shows the new balance
        $stmt = $pdo->prepare("SELECT points FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $freshPoints = $stmt->fetchColumn();
        $_SESSION['points'] = $freshPoints !== false ? (int)$freshPoints : 0;

        // 6. Clear cart
        $_SESSION['cart'] = [];

        $successMsg = 'Order #' . $orderId . ' placed successfully!';
        if ($applyRedeem) {
            $successMsg .= ' You redeemed ' . POINTS_REDEEM_AMOUNT . ' points for ' . format_price(POINTS_REDEEM_VALUE) . ' off.';
        }
        $earned = (int)floor($finalTotal * POINTS_PER_DOLLAR);
        if ($earned > 0) {
            $successMsg .= ' You earned ' . $earned . ' point' . ($earned !== 1 ? 's' : '') . '.';
        }

        set_flash('success', $successMsg);
        redirect(APP_URL . '/customer/order_history.php');

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Checkout error: ' . $e->getMessage());
        set_flash('danger', 'Could not place your order. Please try again.');
        redirect(APP_URL . '/cart/cart.php');
    }
}

// Preview: what the total would be if the user redeems
$previewTotal = $canRedeem
    ? max(0.0, $cartSubtotal - POINTS_REDEEM_VALUE)
    : $cartSubtotal;

$page_title   = 'Checkout';
$current_page = 'cart';
require_once __DIR__ . '/../includes/header.php';
?>


<section class="ld-section-sm">
    <div class="container" style="max-width: 760px;">
        <h1 class="ld-section-title mb-4">Checkout</h1>

        <?php if (empty($cart)): ?>
            <div class="card ld-card p-4">
                <p class="mb-3">Your cart is empty.</p>
                <a href="<?= APP_URL ?>/menu.php" class="ld-btn-primary">Browse menu</a>
            </div>

        <?php else: ?>

            <!-- ---- Order summary ---- -->
            <div class="card ld-card p-4 mb-3">
                <h2 class="h5 mb-3">Order summary</h2>

                <?php foreach ($cart as $item):
                    $rawPrice = $item['price'] ?? 0;
                    if (is_string($rawPrice)) $rawPrice = preg_replace('/[^0-9.\-]/', '', $rawPrice);
                    $price = is_numeric($rawPrice) ? (float)$rawPrice : 0.0;
                    $qty   = is_numeric($item['qty'] ?? 0) ? (int)$item['qty'] : 0;
                ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?= e($item['name']) ?> &times; <?= $qty ?></span>
                        <span><?= format_price($price * $qty) ?></span>
                    </div>
                <?php endforeach; ?>

                <hr>
                <div class="d-flex justify-content-between">
                    <span>Subtotal</span>
                    <span><?= format_price($cartSubtotal) ?></span>
                </div>

                <?php if ($canRedeem): ?>
                    <!-- Shown only when user checks the box — toggled by JS below -->
                    <div class="d-flex justify-content-between text-success small mt-1" id="discount-row" style="display:none!important;">
                        <span>Rewards discount (<?= POINTS_REDEEM_AMOUNT ?> pts)</span>
                        <span>&minus;<?= format_price(POINTS_REDEEM_VALUE) ?></span>
                    </div>
                <?php endif; ?>

                <hr>
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total</span>
                    <span id="total-display"><?= format_price($cartSubtotal) ?></span>
                </div>
            </div>

            <!-- ---- Rewards redemption panel ---- -->
            <?php if ($canRedeem): ?>
            <div class="card ld-card p-4 mb-3" style="border-left: 4px solid var(--ld-blue-dark);">
                <div class="d-flex align-items-start gap-3">
                    <div class="fs-2 mt-1" aria-hidden="true">🎁</div>
                    <div class="flex-grow-1">
                        <h2 class="h6 fw-semibold mb-1">You have <?= number_format($currentPoints) ?> points!</h2>
                        <p class="text-muted small mb-2">
                            Redeem <?= POINTS_REDEEM_AMOUNT ?> points now for
                            <strong><?= format_price(POINTS_REDEEM_VALUE) ?> off</strong>
                            this order. Your new total would be
                            <strong><?= format_price($previewTotal) ?></strong>.
                        </p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   id="redeem_toggle"
                                   name="redeem_points"
                                   value="1">
                            <label class="form-check-label fw-semibold small" for="redeem_toggle">
                                Yes, use my <?= POINTS_REDEEM_AMOUNT ?> points for <?= format_price(POINTS_REDEEM_VALUE) ?> off
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <?php elseif (!is_admin()): ?>
            <div class="card p-4 mb-3" style="background: var(--ld-blue-light); border: none; border-radius: var(--ld-radius);">
                <p class="mb-1 small fw-semibold">
                    <i class="bi bi-star me-1" aria-hidden="true"></i>
                    You have <?= number_format($currentPoints) ?> points
                </p>
                <p class="text-muted small mb-0">
                    <?php $needed = POINTS_REDEEM_AMOUNT - $currentPoints; ?>
                    Earn <?= number_format($needed) ?> more points to unlock a
                    <?= format_price(POINTS_REDEEM_VALUE) ?> reward.
                    This order earns you <?= (int)floor($cartSubtotal * POINTS_PER_DOLLAR) ?> point<?= floor($cartSubtotal * POINTS_PER_DOLLAR) !== 1.0 ? 's' : '' ?>.
                </p>
            </div>
            <?php endif; ?>

            <!-- ---- Place order form ---- -->
            <form method="POST" action="<?= APP_URL ?>/cart/checkout.php" id="checkout-form">
                <?php csrf_field(); ?>
                <!-- Hidden field mirrors the checkbox state for the POST -->
                <input type="hidden" name="redeem_points" value="0" id="redeem_hidden">

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="ld-btn-primary">
                        <i class="bi bi-bag-check me-1" aria-hidden="true"></i>
                        Place order
                    </button>
                    <a href="<?= APP_URL ?>/cart/cart.php" class="ld-btn-outline">Back to cart</a>
                </div>
            </form>

        <?php endif; ?>
    </div>
</section>

<?php if ($canRedeem): ?>
<script>
(function () {
    const toggle    = document.getElementById('redeem_toggle');
    const hidden    = document.getElementById('redeem_hidden');
    const totalEl   = document.getElementById('total-display');
    const discRow   = document.getElementById('discount-row');
    const subtotal  = <?= json_encode($cartSubtotal) ?>;
    const discount  = <?= json_encode((float)POINTS_REDEEM_VALUE) ?>;
    const fmt = v => '$' + v.toFixed(2);

    if (!toggle) return;

    toggle.addEventListener('change', function () {
        if (this.checked) {
            hidden.value    = '1';
            totalEl.textContent = fmt(Math.max(0, subtotal - discount));
            discRow.style.removeProperty('display');
        } else {
            hidden.value    = '0';
            totalEl.textContent = fmt(subtotal);
            discRow.style.display = 'none';
        }
    });
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
