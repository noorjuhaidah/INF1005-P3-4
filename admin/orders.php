<?php
$page_title = 'Manage Orders';
$current_page = 'admin';

require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

/* Handle status update*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(APP_URL . '/admin/orders.php');

    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $statusRaw = filter_input(INPUT_POST, 'status', FILTER_UNSAFE_RAW);
    $status = is_string($statusRaw) ? trim($statusRaw) : '';

    $allowed_status = [
        'submitted',
        'preparing',
        'ready_for_pickup',
        'completed',
        'cancelled'
    ];

    if ($order_id !== false && $order_id > 0 && in_array($status, $allowed_status, true)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE orders
                SET status = ?
                WHERE order_id = ?
            ");

            $stmt->execute([$status, (int) $order_id]);

            if ($stmt->rowCount() > 0) {
                set_flash('success', 'Order status updated successfully.');
            } else {
                set_flash('warning', 'Order not found or status unchanged.');
            }
        } catch (PDOException $e) {
            error_log('Admin orders status update error: ' . $e->getMessage());
            set_flash('danger', 'Unable to update order status right now.');
        }
    } else {
        set_flash('warning', 'Invalid order update request.');
    }

    header('Location: ' . APP_URL . '/admin/orders.php');
    exit;
}

/* Fetch orders with pagination*/
$orders = [];
$totalOrders = 0;
$perPage = 10;
$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
if (!$currentPage || $currentPage < 1) {
    $currentPage = 1;
}
$totalPages = 1;

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders");
    $countStmt->execute();
    $totalOrders = (int) $countStmt->fetchColumn();

    $totalPages = max(1, (int) ceil($totalOrders / $perPage));
    if ($currentPage > $totalPages) {
        $currentPage = $totalPages;
    }

    $offset = ($currentPage - 1) * $perPage;

    $stmt = $pdo->prepare("
        SELECT
            o.order_id,
            o.total_amount,
            o.status,
            o.created_at,
            u.full_name
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        ORDER BY o.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Admin orders load error: ' . $e->getMessage());
    $orders = [];
    $totalOrders = 0;
    $totalPages = 1;
    $currentPage = 1;
}
?>

<section class="ld-section">
    <div class="container">

        <h1 class="ld-section-title">Manage Orders</h1>
        <p class="ld-section-subtitle">Update customer order statuses.</p>

                <?php show_flash(); ?>

        <div class="card ld-card p-4">

            <div class="table-responsive">

                <table class="table align-middle">

                    <caption class="visually-hidden">Orders table listing order ID, customer, total, status, created
                        date, and status update action.</caption>

                    <thead>
                        <tr>
                            <th scope="col">Order ID</th>
                            <th scope="col">Customer</th>
                            <th scope="col">Total</th>
                            <th scope="col">Status</th>
                            <th scope="col">Created At</th>
                            <th scope="col">Update status</th>
                        </tr>
                    </thead>
                    <tbody>

                                                <?php if (empty($orders)): ?>

                            <tr>
                                <td colspan="6">No orders found.</td>
                            </tr>

                                                <?php else: ?>

                                                        <?php foreach ($orders as $order): ?>

                                <tr>

                                    <th scope="row"><?= e((string) $order['order_id']) ?></th>

                                    <td><?= e($order['full_name']) ?></td>

                                    <td>$<?= number_format((float) $order['total_amount'], 2) ?></td>

                                    <td><?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></td>

                                    <td><?= e($order['created_at']) ?></td>

                                    <td>

                                        <form method="post" class="d-flex gap-2"
                                            aria-label="Update status for order <?= e((string) $order['order_id']) ?>">

                                                                                        <?php csrf_field(); ?>

                                            <input type="hidden" name="order_id" value="<?= e((string) $order['order_id']) ?>">

                                            <label class="visually-hidden"
                                                for="status-<?= e((string) $order['order_id']) ?>">Order status for order
                                                <?= e((string) $order['order_id']) ?></label>

                                            <select id="status-<?= e((string) $order['order_id']) ?>" name="status"
                                                class="form-select form-select-sm"
                                                aria-label="Order status for order <?= e((string) $order['order_id']) ?>">

                                                <option value="submitted" <?= $order['status'] === 'submitted' ? 'selected' : '' ?>>Submitted</option>

                                                <option value="preparing" <?= $order['status'] === 'preparing' ? 'selected' : '' ?>>Preparing</option>

                                                <option value="ready_for_pickup" <?= $order['status'] === 'ready_for_pickup' ? 'selected' : '' ?>>Ready For Pickup</option>

                                                <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>

                                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>

                                            </select>

                                            <button type="submit" class="btn btn-sm btn-primary"
                                                aria-label="Save status update for order <?= e((string) $order['order_id']) ?>">Save</button>

                                        </form>

                                    </td>

                                </tr>

                                                        <?php endforeach; ?>

                                                <?php endif; ?>

                    </tbody>

                </table>

            </div>

                        <?php if ($totalPages > 1): ?>
                <nav aria-label="Orders pagination" class="mt-4">
                    <ul class="pagination mb-0">
                        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= APP_URL ?>/admin/orders.php?page=<?= max(1, $currentPage - 1) ?>"
                                aria-label="Previous page">Previous</a>
                        </li>

                                        <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                            <li class="page-item <?= $page === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="<?= APP_URL ?>/admin/orders.php?page=<?= $page ?>"
                                    aria-label="Go to page <?= $page ?>"><?= $page ?></a>
                            </li>
                                        <?php endfor; ?>

                        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link"
                                href="<?= APP_URL ?>/admin/orders.php?page=<?= min($totalPages, $currentPage + 1) ?>"
                                aria-label="Next page">Next</a>
                        </li>
                    </ul>
                </nav>
                        <?php endif; ?>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>