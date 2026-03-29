<?php
$page_title = 'Manage Orders';
$current_page = 'admin';

require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

/* -------------------------------
   Handle status update
--------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $order_id = $_POST['order_id'] ?? '';
    $status = $_POST['status'] ?? '';

    $allowed_status = [
        'submitted',
        'preparing',
        'ready_for_pickup',
        'completed',
        'cancelled'
    ];

    if ($order_id !== '' && is_numeric($order_id) && in_array($status, $allowed_status, true)) {

        $stmt = $pdo->prepare("
            UPDATE orders
            SET status = ?
            WHERE order_id = ?
        ");

        $stmt->execute([$status, $order_id]);
    }

    header('Location: ' . APP_URL . '/admin/orders.php');
    exit;
}

/* -------------------------------
   Fetch orders
--------------------------------*/
$orders = [];

try {

    $stmt = $pdo->query("
        SELECT 
            o.order_id,
            o.total_amount,
            o.status,
            o.created_at,
            u.full_name
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        ORDER BY o.created_at DESC
    ");

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

<caption class="visually-hidden">Orders table listing order ID, customer, total, status, created date, and status update action.</caption>

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

<th scope="row"><?= e((string)$order['order_id']) ?></th>

<td><?= e($order['full_name']) ?></td>

<td>$<?= number_format((float)$order['total_amount'], 2) ?></td>

<td><?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></td>

<td><?= e($order['created_at']) ?></td>

<td>

<form method="post" class="d-flex gap-2" aria-label="Update status for order <?= e((string)$order['order_id']) ?>">

<input type="hidden" name="order_id" value="<?= e((string)$order['order_id']) ?>">

<label class="visually-hidden" for="status-<?= e((string)$order['order_id']) ?>">Order status for order <?= e((string)$order['order_id']) ?></label>

<select id="status-<?= e((string)$order['order_id']) ?>" name="status" class="form-select form-select-sm" aria-label="Order status for order <?= e((string)$order['order_id']) ?>">

    <option value="submitted" <?= $order['status'] === 'submitted' ? 'selected' : '' ?>>Submitted</option>

    <option value="preparing" <?= $order['status'] === 'preparing' ? 'selected' : '' ?>>Preparing</option>

    <option value="ready_for_pickup" <?= $order['status'] === 'ready_for_pickup' ? 'selected' : '' ?>>Ready For Pickup</option>

    <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>

    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>

</select>

<button type="submit" class="btn btn-sm btn-primary" aria-label="Save status update for order <?= e((string)$order['order_id']) ?>">Save</button>

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