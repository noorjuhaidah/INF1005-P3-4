<?php
// cart/checkout.php
$page_title   = 'Checkout';
$current_page = 'cart';
require_once __DIR__ . '/../includes/header.php';

require_login();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function table_exists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = ?
    ");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function table_has_column(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
    ");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

$cart  = get_cart();
$total = cart_total();

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

        // Build INSERT for orders with schema-safe columns
        $columns = ['user_id'];
        $values  = [$_SESSION['user_id']];

        if (table_has_column($pdo, 'orders', 'total_amount')) {
            $columns[] = 'total_amount';
            $values[]  = $total;
        } elseif (table_has_column($pdo, 'orders', 'total_price')) {
            $columns[] = 'total_price';
            $values[]  = $total;
        } elseif (table_has_column($pdo, 'orders', 'total')) {
            $columns[] = 'total';
            $values[]  = $total;
        } elseif (table_has_column($pdo, 'orders', 'amount')) {
            $columns[] = 'amount';
            $values[]  = $total;
        } else {
            throw new RuntimeException('Orders table has no total column.');
        }

        if (table_has_column($pdo, 'orders', 'status')) {
            $columns[] = 'status';
            $values[]  = 'submitted';
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO orders (" . implode(', ', $columns) . ") VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        $order_id = (int)$pdo->lastInsertId();

        // Optional: save order line items if table exists
        if (table_exists($pdo, 'order_items')) {
            $hasName  = table_has_column($pdo, 'order_items', 'item_name');
            $hasQty   = table_has_column($pdo, 'order_items', 'qty');
            $qtyCol   = $hasQty ? 'qty' : 'quantity';
            $hasPrice = table_has_column($pdo, 'order_items', 'unit_price');

            foreach ($cart as $item_id => $item) {
                $itemCols = ['order_id', 'item_id', $qtyCol];
                $itemVals = [$order_id, (int)$item_id, (int)$item['qty']];

                if ($hasPrice) {
                    $itemCols[] = 'unit_price';
                    $itemVals[] = (float)$item['price'];
                }
                if ($hasName) {
                    $itemCols[] = 'item_name';
                    $itemVals[] = $item['name'];
                }

                $ph = implode(', ', array_fill(0, count($itemCols), '?'));
                $itemSql = "INSERT INTO order_items (" . implode(', ', $itemCols) . ") VALUES ($ph)";
                $itemStmt = $pdo->prepare($itemSql);
                $itemStmt->execute($itemVals);
            }
        }

        // Award loyalty points (if your schema supports it)
        $earned = (int)floor($total * POINTS_PER_DOLLAR);

        if ($earned > 0) {
            if (table_exists($pdo, 'points_transactions')) {
                $txn = $pdo->prepare("
                    INSERT INTO points_transactions (user_id, order_id, txn_type, points_delta, note)
                    VALUES (?, ?, 'earn', ?, 'Points from checkout')
                ");
                $txn->execute([$_SESSION['user_id'], $order_id, $earned]);
            }

            if (table_has_column($pdo, 'users', 'points')) {
                $up = $pdo->prepare("UPDATE users SET points = COALESCE(points, 0) + ? WHERE user_id = ?");
                $up->execute([$earned, $_SESSION['user_id']]);
            }
        }

        $_SESSION['cart'] = [];

        $pdo->commit();

        set_flash('success', 'Order placed successfully! Order #' . $order_id);
        redirect(APP_URL . '/customer/order_history.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
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
            <p class="text-muted">Your cart is empty.</p>
            <a href="<?= APP_URL ?>/menu.php" class="ld-btn-primary">Browse Menu</a>
        <?php else: ?>
            <div class="card ld-card p-4 mb-3">
                <h2 class="h5 mb-3">Order summary</h2>
                <?php foreach ($cart as $item): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?= e($item['name']) ?> x <?= (int)$item['qty'] ?></span>
                        <span><?= format_price($item['price'] * $item['qty']) ?></span>
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
                <a href="<?= APP_URL ?>/cart/cart.php" class="ld-btn-outline ms-2">Back to cart</a>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
