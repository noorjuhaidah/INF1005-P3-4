<?php
$page_title = 'Manage Orders';
$current_page = 'admin';

require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? '';
    $status = $_POST['status'] ?? '';

    $allowed_status = ['submitted', 'preparing', 'ready_for_pickup', 'completed', 'cancelled'];

    if ($order_id !== '' && is_numeric($order_id) && in_array($status, $allowed_status, true)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$status, $order_id]);
    }

    header('Location: ' . APP_URL . '/admin/orders.php');
    exit;
}

$orders = [];

try {
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}
?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title">Manage Orders</h1>
        <p class="ld-section-subtitle">Update customer order statuses.</p>

        <div class="card ld-card p-4">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User ID</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Update</th>
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
                                <td><?= e((string)$order['order_id']) ?></td>
                                <td><?= e((string)$order['user_id']) ?></td>
                                <td>$<?= number_format((float)$order['total_amount'], 2) ?></td>
                                <td><?= e($order['status']) ?></td>
                                <td><?= e($order['created_at']) ?></td>
                                <td>
                                    <form method="post" action="" class="d-flex gap-2">
                                        <input type="hidden" name="order_id" value="<?= e((string)$order['order_id']) ?>">
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="submitted" <?= $order['status'] === 'submitted' ? 'selected' : '' ?>>submitted</option>
                                            <option value="preparing" <?= $order['status'] === 'preparing' ? 'selected' : '' ?>>preparing</option>
                                            <option value="ready_for_pickup" <?= $order['status'] === 'ready_for_pickup' ? 'selected' : '' ?>>ready_for_pickup</option>
                                            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>completed</option>
                                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>cancelled</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>