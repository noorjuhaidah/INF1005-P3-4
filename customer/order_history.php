<?php
// =============================================================
// customer/order_history.php — Past orders list for logged-in users.
// =============================================================

$page_title   = 'Order History';
$current_page = 'orders';
require_once __DIR__ . '/../includes/header.php';

// Require login
require_login();

// Fetch orders for this user
$orders = [];
try {
    $stmt = $pdo->prepare(
        "SELECT *
           FROM orders
          WHERE user_id = ?
          ORDER BY created_at DESC"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    // If the table / columns don't exist, we'll just show an empty list
    $orders = [];
}

function pickField(array $row, array $keys) {
    foreach ($keys as $key) {
        if (!empty($row[$key]) || (isset($row[$key]) && $row[$key] === '0')) {
            return $row[$key];
        }
    }
    return null;
}

?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title mb-3">Order History</h1>
        <p class="text-muted mb-4">A list of your previous orders, starting with the most recent.</p>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="bi bi-clock-history fs-1 text-muted"></i>
                <h2 class="h5 mt-3">No orders found</h2>
                <p class="text-muted">Once you place an order, it will appear here.</p>
                <a href="<?= APP_URL ?>/menu.php" class="ld-btn-primary mt-2">Browse menu</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order):
                            $dateRaw = pickField($order, ['created_at', 'created', 'order_date', 'date']);
                            $date = $dateRaw ? format_date($dateRaw) : '—';
                            $totalRaw = pickField($order, ['total_amount', 'total_price', 'total', 'amount']);
                            $total = $totalRaw !== null ? format_price((float)$totalRaw) : '—';
                            $status = pickField($order, ['status', 'order_status', 'status_label']) ?? 'Unknown';
                            $statusSlug = strtolower(str_replace(' ', '_', $status));
                        ?>
                        <tr>
                            <td><?= e($date) ?></td>
                            <td><?= e($total) ?></td>
                            <td><span class="ld-chip status-<?= e($statusSlug) ?>"><?= e(ucfirst($status)) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <a href="<?= APP_URL ?>/customer/dashboard.php" class="ld-back-link mt-3 d-inline-block">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to dashboard
        </a>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
