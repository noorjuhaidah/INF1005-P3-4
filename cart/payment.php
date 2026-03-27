<?php
// =============================================================
// cart/payment.php - Mock payment page and order placement
// =============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login();

$pending = $_SESSION['pending_checkout'] ?? null;
if (!$pending || empty($pending['cart']) || (int)($pending['user_id'] ?? 0) !== (int)$_SESSION['user_id']) {
    set_flash('warning', 'No pending checkout found. Please review your cart again.');
    redirect(APP_URL . '/cart/checkout.php');
}

$userId = (int)$pending['user_id'];
$cart = $pending['cart'];
$subtotal = (float)$pending['subtotal'];
$pointsBefore = (int)$pending['points_before'];
$applyRedeem = !empty($pending['redeem_points']);
$pointsRedeemed = (int)$pending['points_redeemed'];
$discountApplied = (float)$pending['discount_applied'];
$finalTotal = (float)$pending['final_total'];

$orderItemColumns = [];
try {
    $colStmt = $pdo->query("SHOW COLUMNS FROM order_items");
    foreach ($colStmt->fetchAll() as $column) {
        if (!empty($column['Field'])) {
            $orderItemColumns[] = $column['Field'];
        }
    }
} catch (PDOException $e) {
    $orderItemColumns = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(APP_URL . '/cart/payment.php');

    $cardName = trim($_POST['card_name'] ?? '');
    $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $expiry = trim($_POST['expiry'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');

    if ($cardName === '' ||
        !preg_match('/^\d{16}$/', $cardNumber) ||
        !preg_match('/^\d{2}\/\d{2}$/', $expiry) ||
        !preg_match('/^\d{3}$/', $cvv)) {
        set_old_input([
            'card_name' => $cardName,
            'card_number' => $_POST['card_number'] ?? '',
            'expiry' => $expiry,
        ]);
        set_flash('danger', 'Please enter valid payment details.');
        redirect(APP_URL . '/cart/payment.php');
    }

    try {
        $pdo->beginTransaction();

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
            'paid',
            $subtotal,
            $pointsRedeemed,
            $discountApplied,
            $finalTotal,
            null,
        ]);
        $orderId = (int)$pdo->lastInsertId();

        $itemFieldMap = [
            'order_id'   => null,
            'item_id'    => null,
            'item_name'  => null,
            'unit_price' => null,
            'quantity'   => null,
            'qty'        => null,
            'subtotal'   => null,
        ];

        $itemInsertColumns = [];
        foreach (array_keys($itemFieldMap) as $fieldName) {
            if (in_array($fieldName, $orderItemColumns, true)) {
                $itemInsertColumns[] = $fieldName;
            }
        }

        if (empty($itemInsertColumns)) {
            throw new PDOException('order_items table has no supported columns.');
        }

        $itemPlaceholders = implode(', ', array_fill(0, count($itemInsertColumns), '?'));
        $itemStmt = $pdo->prepare("
            INSERT INTO order_items (" . implode(', ', $itemInsertColumns) . ")
            VALUES (" . $itemPlaceholders . ")
        ");

        foreach ($cart as $itemId => $item) {
            $rawPrice = $item['price'] ?? 0;
            if (is_string($rawPrice)) {
                $rawPrice = preg_replace('/[^0-9.\-]/', '', $rawPrice);
            }
            $unitPrice = is_numeric($rawPrice) ? (float)$rawPrice : 0.0;
            $qty = is_numeric($item['qty'] ?? 0) ? (int)$item['qty'] : 0;

            if ($qty <= 0) {
                continue;
            }

            $rowData = [
                'order_id'   => $orderId,
                'item_id'    => (int)$itemId,
                'item_name'  => $item['name'] ?? 'Item',
                'unit_price' => $unitPrice,
                'quantity'   => $qty,
                'qty'        => $qty,
                'subtotal'   => round($unitPrice * $qty, 2),
            ];

            $itemValues = [];
            foreach ($itemInsertColumns as $columnName) {
                $itemValues[] = $rowData[$columnName];
            }

            $itemStmt->execute($itemValues);
        }

        if ($applyRedeem) {
            $redeemed = redeem_points($pdo, $userId, $orderId);
            if (!$redeemed) {
                $pdo->rollBack();
                set_flash('danger', 'Your points balance changed. Please refresh and try again.');
                redirect(APP_URL . '/cart/checkout.php');
            }
        }

        $pdo->commit();

        award_points($pdo, $userId, $orderId, $finalTotal);

        $stmt = $pdo->prepare("SELECT points FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $freshPoints = $stmt->fetchColumn();
        $_SESSION['points'] = $freshPoints !== false ? (int)$freshPoints : 0;
 

        $_SESSION['cart'] = [];
        unset($_SESSION['pending_checkout']);
        clear_old_input();

        $maskedCard = '**** **** **** ' . substr($cardNumber, -4);
        $earned = (int)floor($finalTotal * POINTS_PER_DOLLAR);
        $successMsg = 'Payment successful on ' . $maskedCard . '. Order #' . $orderId . ' placed successfully!';
        if ($applyRedeem) {
            $successMsg .= ' You redeemed ' . POINTS_REDEEM_AMOUNT . ' points for ' . format_price(POINTS_REDEEM_VALUE) . ' off.';
        }
        if ($earned > 0) {
            $successMsg .= ' You earned ' . $earned . ' point' . ($earned !== 1 ? 's' : '') . '.';
        }

        set_flash('success', $successMsg);
        redirect(APP_URL . '/customer/order_history.php');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_old_input([
            'card_name' => $cardName,
            'card_number' => $_POST['card_number'] ?? '',
            'expiry' => $expiry,
        ]);
        error_log('Payment checkout error: ' . $e->getMessage());
        set_flash('danger', 'Payment failed: ' . $e->getMessage());
        redirect(APP_URL . '/cart/payment.php');
    }
}

$page_title = 'Payment';
$current_page = 'cart';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="ld-section-sm">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-lg-7">
                <div class="card ld-card p-4">
                    <h1 class="ld-section-title mb-3">Payment</h1>
                    <p class="text-muted mb-4">Mock payment page for project demo. No real payment is processed.</p>

                    <form method="POST" action="<?= APP_URL ?>/cart/payment.php" class="row g-3">
                        <?php csrf_field(); ?>

                        <div class="col-12">
                            <label class="form-label" for="card_name">Cardholder name</label>
                            <input
                                type="text"
                                id="card_name"
                                name="card_name"
                                class="form-control"
                                value="<?= e(old_input('card_name')) ?>"
                                required
                            >
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="card_number">Card number</label>
                            <input
                                type="text"
                                id="card_number"
                                name="card_number"
                                class="form-control"
                                inputmode="numeric"
                                maxlength="19"
                                placeholder="1234 5678 9012 3456"
                                value="<?= e(old_input('card_number')) ?>"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="expiry">Expiry</label>
                            <input
                                type="text"
                                id="expiry"
                                name="expiry"
                                class="form-control"
                                placeholder="MM/YY"
                                maxlength="5"
                                value="<?= e(old_input('expiry')) ?>"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="cvv">CVV</label>
                            <input
                                type="password"
                                id="cvv"
                                name="cvv"
                                class="form-control"
                                inputmode="numeric"
                                maxlength="3"
                                placeholder="123"
                                required
                            >
                        </div>

                        <div class="col-12 d-flex gap-2 flex-wrap mt-3">
                            <button type="submit" class="ld-btn-primary">
                                <i class="bi bi-lock-fill me-1" aria-hidden="true"></i>
                                Pay <?= format_price($finalTotal) ?>
                            </button>
                            <a href="<?= APP_URL ?>/cart/checkout.php" class="ld-btn-outline">Back to checkout</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card ld-card p-4">
                    <h2 class="h5 mb-3">Payment summary</h2>

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
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span><?= format_price($subtotal) ?></span>
                    </div>

                    <?php if ($applyRedeem): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Rewards discount</span>
                            <span>-<?= format_price($discountApplied) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between fw-bold fs-5 mt-3">
                        <span>Total to pay</span>
                        <span><?= format_price($finalTotal) ?></span>
                    </div>

                    <p class="text-muted small mt-3 mb-0">
                        After successful payment, your order will be marked as paid and submitted.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
