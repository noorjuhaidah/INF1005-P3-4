<?php
// =============================================================
// cart/checkout.php - Review order and proceed to payment
// =============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login();

$cart = get_cart();
$userId = (int)$_SESSION['user_id'];

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

$canRedeem = $currentPoints >= POINTS_REDEEM_AMOUNT;
$cartSubtotal = cart_total();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(APP_URL . '/cart/checkout.php');

    if (empty($cart)) {
        set_flash('warning', 'Your cart is empty.');
        redirect(APP_URL . '/cart/cart.php');
    }

    $wantsRedeem = isset($_POST['redeem_points']) && $_POST['redeem_points'] === '1';
    $applyRedeem = $wantsRedeem && $canRedeem;
    $discount = $applyRedeem ? POINTS_REDEEM_VALUE : 0.0;
    $finalTotal = max(0.0, $cartSubtotal - $discount);

    $_SESSION['pending_checkout'] = [
        'user_id' => $userId,
        'cart' => $cart,
        'subtotal' => $cartSubtotal,
        'points_before' => $currentPoints,
        'redeem_points' => $applyRedeem,
        'points_redeemed' => $applyRedeem ? POINTS_REDEEM_AMOUNT : 0,
        'discount_applied' => $discount,
        'final_total' => $finalTotal,
    ];

    redirect(APP_URL . '/cart/payment.php');
}

$previewTotal = $canRedeem
    ? max(0.0, $cartSubtotal - POINTS_REDEEM_VALUE)
    : $cartSubtotal;

$page_title = 'Checkout';
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
            <div class="card ld-card p-4 mb-3">
                <h2 class="h5 mb-3">Order summary</h2>

                <?php foreach ($cart as $item): ?>
                    <?php
                    $rawPrice = $item['price'] ?? 0;
                    if (is_string($rawPrice)) {
                        $rawPrice = preg_replace('/[^0-9.\-]/', '', $rawPrice);
                    }
                    $price = is_numeric($rawPrice) ? (float)$rawPrice : 0.0;
                    $qty = is_numeric($item['qty'] ?? 0) ? (int)$item['qty'] : 0;
                    ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?= e($item['name']) ?> x <?= $qty ?></span>
                        <span><?= format_price($price * $qty) ?></span>
                    </div>
                <?php endforeach; ?>

                <hr>
                <div class="d-flex justify-content-between">
                    <span>Subtotal</span>
                    <span><?= format_price($cartSubtotal) ?></span>
                </div>

                <?php if ($canRedeem): ?>
                    <div class="d-flex justify-content-between text-success small mt-1" id="discount-row" style="display:none!important;">
                        <span>Rewards discount (<?= POINTS_REDEEM_AMOUNT ?> pts)</span>
                        <span>-<?= format_price(POINTS_REDEEM_VALUE) ?></span>
                    </div>
                <?php endif; ?>

                <hr>
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total</span>
                    <span id="total-display"><?= format_price($cartSubtotal) ?></span>
                </div>
            </div>

            <form method="POST" action="<?= APP_URL ?>/cart/checkout.php" id="checkout-form">
                <?php csrf_field(); ?>

            <?php if ($canRedeem): ?>
                <fieldset class="card ld-card p-4 mb-3" style="border-left: 4px solid var(--ld-blue-dark);">
                    <div class="flex-grow-1">
                        <legend class="h6 fw-semibold mb-1">Apply rewards points</legend>
                        <p class="small fw-semibold mb-1">You have <?= number_format($currentPoints) ?> points</p>
                        <p class="text-muted small mb-2">
                            Redeem <?= POINTS_REDEEM_AMOUNT ?> points for <?= format_price(POINTS_REDEEM_VALUE) ?> off.
                            Your total would become <?= format_price($previewTotal) ?>.
                        </p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="redeem_toggle" name="redeem_points" value="1">
                            <label class="form-check-label fw-semibold small" for="redeem_toggle">
                                Use my <?= POINTS_REDEEM_AMOUNT ?> points
                            </label>
                        </div>
                    </div>
                </fieldset>
            <?php else: ?>
                <div class="card p-4 mb-3" style="background: var(--ld-blue-light); border: none; border-radius: var(--ld-radius);">
                    <p class="mb-1 small fw-semibold">
                        <i class="bi bi-star me-1" aria-hidden="true"></i>
                        You have <?= number_format($currentPoints) ?> points
                    </p>
                    <p class="text-muted small mb-0">
                        Earn <?= number_format(POINTS_REDEEM_AMOUNT - $currentPoints) ?> more points to unlock a
                        <?= format_price(POINTS_REDEEM_VALUE) ?> reward.
                    </p>
                </div>
            <?php endif; ?>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="ld-btn-primary">
                        <i class="bi bi-credit-card me-1" aria-hidden="true"></i>
                        Proceed to Payment
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
    const toggle = document.getElementById('redeem_toggle');
    const totalEl = document.getElementById('total-display');
    const discRow = document.getElementById('discount-row');
    const subtotal = <?= json_encode($cartSubtotal) ?>;
    const discount = <?= json_encode((float)POINTS_REDEEM_VALUE) ?>;
    const fmt = v => '$' + v.toFixed(2);

    if (!toggle) return;

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
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
