<?php
// =============================================================
// customer/dashboard.php — Customer Dashboard
// Shows a quick summary of loyalty points + recent orders.
// =============================================================

$page_title   = 'Dashboard';
$current_page = 'dashboard';
require_once __DIR__ . '/../includes/header.php';

// Guest users are redirected to login.
require_login();

// -------------------------------------------------------------
// Loyalty points 
// -------------------------------------------------------------
$points = 0;
try {
    $stmt = $pdo->prepare("SELECT points FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row) {
        $points = (int)$row['points'];
    }
} catch (PDOException $e) {
    echo "";
    $points = 0;
}

// -------------------------------------------------------------
// Recent orders (latest 3)
// -------------------------------------------------------------
$recentOrders = [];
try {
    $stmt = $pdo->prepare(
        "SELECT *
           FROM orders
          WHERE user_id = ?
          ORDER BY created_at DESC
          LIMIT 3"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $recentOrders = $stmt->fetchAll();
} catch (PDOException $e) {
    // If orders table doesn't exist or query fails, show nothing
    $recentOrders = [];
}

// Helper to pick the first available key from a record
function pickField(array $row, array $keys) {
    foreach ($keys as $key) {
        if (!empty($row[$key])) {
            return $row[$key];
        }
    }
    return null;
}

?>

<section class="ld-section">
    <div class="container">
         <?php show_flash(); ?>
         
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-4">

            <div class="card w-100" style="max-width: 28rem;">
                <div class="card-body">
                    <h1 class="card-title h4">Welcome back, <?= e($_SESSION['full_name']) ?>!</h1>
                    <p class="text-muted mb-4">Here’s a quick look at your account.</p>

                    <div class="mb-4">
                        <h2 class="h5">Loyalty points</h2>
                        <p class="fs-3 fw-bold mb-0"><?= number_format($points) ?></p>
                        <p class="text-muted small mb-0">Earn <?= POINTS_PER_DOLLAR ?> point<?= POINTS_PER_DOLLAR !== 1 ? 's' : '' ?> per $1 spent. Redeem <?= POINTS_REDEEM_AMOUNT ?> points for $<?= number_format(POINTS_REDEEM_VALUE, 2) ?> off.</p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="<?= APP_URL ?>/customer/order_history.php" class="ld-btn-outline">View all orders</a>
                        <a href="<?= APP_URL ?>/customer/profile_edit.php" class="ld-btn-primary">Edit profile</a>
                    </div>
                </div>
            </div>

            <div class="card w-100">
                <div class="card-body">
                    <h2 class="card-title h5">Recent orders</h2>

                    <?php if (empty($recentOrders)): ?>
                        <p class="text-muted mb-0">No recent orders yet. Start by browsing the <a href="<?= APP_URL ?>/menu.php">menu</a>.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentOrders as $order):
                                $orderDate = pickField($order, ['created_at', 'created', 'order_date', 'date']);
                                $orderDate = $orderDate ? format_date($orderDate) : '—';
                                $total = pickField($order, ['total_amount', 'total_price', 'total', 'amount']);
                                $total = $total !== null ? format_price((float)$total) : '—';
                                $status = pickField($order, ['status', 'order_status', 'status_label']) ?? 'Unknown';
                                $statusSlug = strtolower(str_replace(' ', '_', $status));
                            ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold"><?= e($orderDate) ?></div>
                                        <div class="text-muted small">Total: <?= $total ?></div>
                                    </div>
                                    <span class="ld-chip status-<?= e($statusSlug) ?>"><?= e(ucfirst($status)) ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>