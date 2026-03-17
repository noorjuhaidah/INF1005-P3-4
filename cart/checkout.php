<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$page_title = 'Checkout';
$current_page = 'cart';
require_once __DIR__ . '/../includes/header.php';

require_login();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$cart = get_cart();

try {
    $total = cart_total();
} catch (Throwable $e) {
    error_log('Checkout cart_total error: ' . $e->getMessage());
    set_flash('danger', 'Cart data is invalid. Please re-add your items.');
    $_SESSION['cart'] = [];
    redirect(APP_URL . '/cart/cart.php');
}


// Handle place order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        set_flash('danger', 'Invalid request. Please refresh and try again.');
        redirect(APP_URL . '/cart/cart.php');
    }

    if (empty($cart)) {
        set_flash('warning', 'Your cart is empty.');
        redirect(APP_URL . '/cart/cart.php');
    }

    try {
        $pdo->beginTransaction();

        // orders table (based on your admin/orders.php)
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, total_amount, status)
            VALUES (?, ?, 'submitted')
        ");
        $stmt->execute([$_SESSION['user_id'], $total]);

        $order_id = (int)$pdo->lastInsertId();

        // Optional: insert order_items only if table exists and fits your schema
        // (Skip for now to avoid breaking checkout on schema mismatch)

        // Clear cart
        $_SESSION['cart'] = [];

        $pdo->commit();

        set_flash('success', 'Order placed successfully! Order #' . $order_id);
        redirect(APP_URL . '/customer/order_history.php');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Checkout error: ' . $e->getMessage());
        set_flash('danger', 'Could not place order. Please try again.');
        redirect(APP_URL . '/cart/cart.php');
    }
}
?>

<section class="ld-section-sm">
    <div class="container" style="max-width: 760px;">
        <h1 class="ld-section-title mb-3">Checkout</h1>

        <?php if (empty($cart)): ?>
            <div class="card ld-card p-4">
                <p class="mb-3">Your cart is empty.</p>
                <a href="<?= APP_URL ?>/menu.php" class="ld-btn-primary">Browse Menu</a>
            </div>
        <?php else: ?>
            <div class="card ld-card p-4 mb-3">
                <h2 class="h5 mb-3">Order Summary</h2>

                <?php foreach ($cart as $item): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?= e($item['name']) ?> x <?= (int)$item['qty'] ?></span>
                        <?php
                        $rawPrice = $item['price'] ?? 0;
                        if (is_string($rawPrice)) $rawPrice = preg_replace('/[^0-9.\-]/', '', $rawPrice);
                        $price = is_numeric($rawPrice) ? (float)$rawPrice : 0.0;
                        $qty = is_numeric($item['qty'] ?? 0) ? (int)$item['qty'] : 0;
                        ?>
                        <span><?= format_price($price * $qty) ?></span>

                    </div>
                <?php endforeach; ?>

                <hr>
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total</span>
                    <span><?= format_price($total) ?></span>
                </div>
            </div>

            <form method="POST" action="<?= APP_URL ?>/cart/checkout.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <button type="submit" class="ld-btn-primary">Place Order</button>
                <a href="<?= APP_URL ?>/cart/cart.php" class="ld-btn-outline ms-2">Back to Cart</a>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
