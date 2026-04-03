<?php

// Collects mock payment details and places the order.

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login();

// Read the checkout details saved in the previous step.
$pending = $_SESSION['pending_checkout'] ?? null;
if (!$pending || empty($pending['cart']) || (int) ($pending['user_id'] ?? 0) !== (int) $_SESSION['user_id']) {
    set_flash('warning', 'No pending checkout found. Please review your cart again.');
    redirect(APP_URL . '/cart/checkout.php');
}

$userId = (int) $pending['user_id'];
$cart = $pending['cart'];
$subtotal = (float) $pending['subtotal'];
$pointsBefore = (int) $pending['points_before'];
$applyRedeem = !empty($pending['redeem_points']);
$pointsRedeemed = (int) $pending['points_redeemed'];
$discountApplied = (float) $pending['discount_applied'];
$finalTotal = (float) $pending['final_total'];

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

// Validate the mock payment form and create the order.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(APP_URL . '/cart/payment.php');

    $_SESSION['field_errors'] = [];

    $cardName = trim($_POST['card_name'] ?? '');
    $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $expiry = trim($_POST['expiry'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');

    if ($cardName === '') {
        $_SESSION['field_errors']['card_name'] = 'Please enter the cardholder name.';
    }

    if (!preg_match('/^\d{16}$/', $cardNumber)) {
        $_SESSION['field_errors']['card_number'] = 'Enter a valid 16-digit card number.';
    }

    if (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
        $_SESSION['field_errors']['expiry'] = 'Enter expiry in MM/YY format.';
    } else {
        [$expMonth, $expYearShort] = array_map('intval', explode('/', $expiry));
        $currentYearShort = (int) date('y');
        $currentMonth = (int) date('m');

        if ($expMonth < 1 || $expMonth > 12) {
            $_SESSION['field_errors']['expiry'] = 'Expiry month must be between 01 and 12.';
        } elseif ($expYearShort < $currentYearShort || ($expYearShort === $currentYearShort && $expMonth < $currentMonth)) {
            $_SESSION['field_errors']['expiry'] = 'Card expiry date cannot be in the past.';
        }
    }

    if (!preg_match('/^\d{3}$/', $cvv)) {
        $_SESSION['field_errors']['cvv'] = 'Enter a valid 3-digit CVV.';
    }

    if (!empty($_SESSION['field_errors'])) {
        set_old_input([
            'card_name' => $cardName,
            'card_number' => $_POST['card_number'] ?? '',
            'expiry' => $expiry,
        ]);
        set_flash('danger', 'Please correct the highlighted payment details.');
        redirect(APP_URL . '/cart/payment.php');
    }

    try {
        // Save the order and related records together.
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
        $orderId = (int) $pdo->lastInsertId();

        // Build the order_items insert from the columns available in the table.
        $itemFieldMap = [
            'order_id' => null,
            'item_id' => null,
            'item_name' => null,
            'unit_price' => null,
            'quantity' => null,
            'qty' => null,
            'subtotal' => null,
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
            $unitPrice = is_numeric($rawPrice) ? (float) $rawPrice : 0.0;
            $qty = is_numeric($item['qty'] ?? 0) ? (int) $item['qty'] : 0;

            if ($qty <= 0) {
                continue;
            }

            $rowData = [
                'order_id' => $orderId,
                'item_id' => (int) $itemId,
                'item_name' => $item['name'] ?? 'Item',
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'qty' => $qty,
                'subtotal' => round($unitPrice * $qty, 2),
            ];

            $itemValues = [];
            foreach ($itemInsertColumns as $columnName) {
                $itemValues[] = $rowData[$columnName];
            }

            $itemStmt->execute($itemValues);
        }

        // Deduct points only after the order has been created.
        if ($applyRedeem) {
            $redeemed = redeem_points($pdo, $userId, $orderId);
            if (!$redeemed) {
                $pdo->rollBack();
                set_flash('danger', 'Your points balance changed. Please refresh and try again.');
                redirect(APP_URL . '/cart/checkout.php');
            }
        }

        $pdo->commit();

        // Update the points balance after the order is confirmed.
        award_points($pdo, $userId, $orderId, $finalTotal);

        $stmt = $pdo->prepare("SELECT points FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $freshPoints = $stmt->fetchColumn();
        $_SESSION['points'] = $freshPoints !== false ? (int) $freshPoints : 0;

        $_SESSION['cart'] = [];
        unset($_SESSION['pending_checkout']);
        unset($_SESSION['field_errors']);
        clear_old_input();

        $maskedCard = '**** **** **** ' . substr($cardNumber, -4);
        $earned = (int) floor($finalTotal * POINTS_PER_DOLLAR);
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
        $_SESSION['field_errors']['card_number'] = 'We could not process payment right now. Please try again.';
        error_log('Payment checkout error: ' . $e->getMessage());
        set_flash('danger', 'Payment failed. Please try again.');
        redirect(APP_URL . '/cart/payment.php');
    }
}

$page_title = 'Payment';
$current_page = 'cart';
require_once __DIR__ . '/../includes/header.php';

$field_errors = $_SESSION['field_errors'] ?? [];
unset($_SESSION['field_errors']);
?>

<section class="ld-section-sm">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-lg-7">
                <div class="card ld-card p-4">
                    <h1 class="ld-section-title mb-3">
                        <i class="fa-solid fa-credit-card me-2" aria-hidden="true"></i>Payment
                    </h1>
                    <p class="text-muted mb-4">Mock payment page for project demo. No real payment is processed.</p>

                    <form method="POST" action="<?= APP_URL ?>/cart/payment.php" class="row g-3">
                        <?php csrf_field(); ?>

                        <div class="col-12">
                            <label class="form-label" for="card_name">
                                <i class="fa-solid fa-user me-1" aria-hidden="true"></i>Cardholder name <span class="text-danger"
                                    aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="card_name" name="card_name"
                                class="form-control <?= !empty($field_errors['card_name']) ? 'is-invalid' : '' ?>"
                                value="<?= e(old_input('card_name')) ?>" autocomplete="cc-name"
                                <?= !empty($field_errors['card_name']) ? 'aria-describedby="card_name_error"' : '' ?>
                                required>
                            <div id="card_name_error" class="invalid-feedback">
                                <?= e($field_errors['card_name'] ?? 'Please enter the cardholder name.') ?></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="card_number">
                                <i class="fa-regular fa-credit-card me-1" aria-hidden="true"></i>Card number <span class="text-danger"
                                    aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="card_number" name="card_number"
                                class="form-control <?= !empty($field_errors['card_number']) ? 'is-invalid' : '' ?>"
                                value="<?= e(old_input('card_number')) ?>" inputmode="numeric" autocomplete="cc-number"
                                maxlength="19" placeholder="1234 5678 9012 3456"
                                <?= !empty($field_errors['card_number']) ? 'aria-describedby="card_number_error"' : '' ?>
                                required>
                            <div id="card_number_error" class="invalid-feedback">
                                <?= e($field_errors['card_number'] ?? 'Enter your 16-digit card number.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="expiry">
                                <i class="fa-regular fa-calendar me-1" aria-hidden="true"></i>Expiry (MM/YY) <span class="text-danger"
                                    aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="expiry" name="expiry"
                                class="form-control <?= !empty($field_errors['expiry']) ? 'is-invalid' : '' ?>"
                                value="<?= e(old_input('expiry')) ?>" inputmode="numeric" autocomplete="cc-exp"
                                maxlength="5" placeholder="12/28"
                                <?= !empty($field_errors['expiry']) ? 'aria-describedby="expiry_error"' : '' ?> required>
                            <div id="expiry_error" class="invalid-feedback">
                                <?= e($field_errors['expiry'] ?? 'Enter expiry in MM/YY format.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="cvv">
                                <i class="fa-solid fa-lock me-1" aria-hidden="true"></i>CVV <span class="text-danger"
                                    aria-hidden="true">*</span>
                            </label>
                            <input type="password" id="cvv" name="cvv"
                                class="form-control <?= !empty($field_errors['cvv']) ? 'is-invalid' : '' ?>" inputmode="numeric"
                                autocomplete="cc-csc" maxlength="3" placeholder="123"
                                <?= !empty($field_errors['cvv']) ? 'aria-describedby="cvv_error"' : '' ?> required>
                            <div id="cvv_error" class="invalid-feedback">
                                <?= e($field_errors['cvv'] ?? 'Enter your 3-digit CVV.') ?></div>
                        </div>

                        <div class="col-12 d-flex gap-2 flex-wrap mt-3">
                            <button type="submit" class="ld-btn-primary">
                                <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Pay and place order
                            </button>
                            <a href="<?= APP_URL ?>/cart/checkout.php" class="ld-btn-outline">Back to checkout</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Show the final amount again before payment is submitted. -->
            <div class="col-lg-5">
                <div class="card ld-card p-4">
                    <h2 class="h5 mb-3">Payment summary</h2>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span><?= format_price($subtotal) ?></span>
                    </div>

                    <?php if ($applyRedeem): ?>
                        <div class="d-flex justify-content-between text-success mb-2">
                            <span>Rewards discount</span>
                            <span>-<?= format_price($discountApplied) ?></span>
                        </div>
                        <div class="small text-muted mb-2">Using <?= number_format($pointsRedeemed) ?> points</div>
                    <?php else: ?>
                        <div class="small text-muted mb-2">
                            You currently have <?= number_format($pointsBefore) ?> points.
                        </div>
                    <?php endif; ?>

                    <hr>

                    <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                        <span>Total to pay</span>
                        <span><?= format_price($finalTotal) ?></span>
                    </div>

                    <div class="small text-muted">
                        This order will earn you <?= number_format((int) floor($finalTotal * POINTS_PER_DOLLAR)) ?> point<?= ((int) floor($finalTotal * POINTS_PER_DOLLAR)) !== 1 ? 's' : '' ?>.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    (function () {
        const cardNumber = document.getElementById('card_number');
        const expiry = document.getElementById('expiry');

        if (cardNumber) {
            cardNumber.addEventListener('input', function () {
                const digits = this.value.replace(/\D/g, '').slice(0, 16);
                this.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
            });
        }

        if (expiry) {
            expiry.addEventListener('input', function () {
                let digits = this.value.replace(/\D/g, '').slice(0, 4);
                if (digits.length > 2) {
                    digits = digits.slice(0, 2) + '/' + digits.slice(2);
                }
                this.value = digits;
            });
        }
    })();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
