<?php
$page_title = 'Admin Dashboard';
$current_page = 'admin';

require_once __DIR__ . '/../includes/header.php';

// Ensure the user is an admin before proceeding
require_admin();

// Initialise dashboard metrics
$totalProducts = 0;
$totalOrders = 0;
$totalRevenue = 0;
$totalMessages = 0;
$unreadMessages = 0;
$orderStatusLabels = [];
$orderStatusCounts = [];

try {
    // Fetch total products, orders, and revenue
    $stmt = $pdo->query("SELECT COUNT(*) FROM menu_items");
    $totalProducts = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $totalOrders = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders");
    $totalRevenue = (float) $stmt->fetchColumn();

    try {
        // Fetch total and unread messages
        $stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages");
        $totalMessages = (int) $stmt->fetchColumn();

        $contactColumns = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM contact_messages");
        foreach ($stmt->fetchAll() as $column) {
            if (!empty($column['Field'])) {
                $contactColumns[] = $column['Field'];
            }
        }

        if (in_array('is_read', $contactColumns, true)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
            $unreadMessages = (int) $stmt->fetchColumn();
        }
    } catch (PDOException $e) {
        $totalMessages = 0;
        $unreadMessages = 0;
    }

    // Fetch order counts by status for the chart
    $stmt = $pdo->query("
        SELECT status, COUNT(*) AS total
        FROM orders
        GROUP BY status
        ORDER BY total DESC
    ");
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $orderStatusLabels[] = ucfirst(str_replace('_', ' ', $row['status']));
        $orderStatusCounts[] = (int) $row['total'];
    }
} catch (PDOException $e) {
    $totalProducts = 0;
    $totalOrders = 0;
    $totalRevenue = 0;
    $totalMessages = 0;
    $unreadMessages = 0;
    $orderStatusLabels = [];
    $orderStatusCounts = [];
}
?>

<section class="ld-section">
    <div class="container">
        // Admin dashboard header
        <h1 class="ld-section-title">
            <i class="fa-solid fa-chart-line me-2"></i>Admin Dashboard
        </h1>
        <p class="ld-section-subtitle">Manage products and orders.</p>

        // Display any flash messages (success/error) from previous actions
        <?php show_flash(); ?>

        // Summary cards
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card ld-card p-4 text-center">
                    <h2 class="h5"><i class="fa-solid fa-box-open me-2"></i>Total Products</h2>
                    <p class="h2 mb-0"><?= e((string) $totalProducts) ?></p>
                </div>
            </div>

            // Total orders and revenue cards
            <div class="col-md-4">
                <div class="card ld-card p-4 text-center">
                    <h2 class="h5"><i class="fa-solid fa-receipt me-2"></i>Total Orders</h2>
                    <p class="h2 mb-0"><?= e((string) $totalOrders) ?></p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card ld-card p-4 text-center">
                    <h2 class="h5"><i class="fa-solid fa-dollar-sign me-2"></i>Total Revenue</h2>
                    <p class="h2 mb-0">$<?= number_format($totalRevenue, 2) ?></p>
                </div>
            </div>
        </div>

        // Order status chart
        <div class="card ld-card p-4 mb-4">
            <h2 class="h4 mb-3">
                <i class="fa-solid fa-chart-pie me-2"></i>Orders by Status
            </h2>
            <p class="text-muted small mb-3">
                Overview of current order distribution.
            </p>
            <div style="height: 320px;">
                <canvas id="ordersStatusChart"></canvas>
            </div>
        </div>

        // Management sections
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card ld-card p-4 h-100">
                    <h2 class="h4 mb-3">
                        <i class="fa-solid fa-mug-hot me-2"></i>Product Management
                    </h2>
                    <p class="text-muted">Add, edit, or delete menu items.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= APP_URL ?>/admin/products.php" class="ld-btn-primary">
                            <i class="fa-solid fa-boxes-stacked me-1"></i>Manage Products
                        </a>
                        <a href="<?= APP_URL ?>/admin/product_create.php" class="ld-btn-outline">
                            <i class="fa-solid fa-plus me-1"></i>Add Product
                        </a>
                    </div>
                </div>
            </div>

            // Order management section
            <div class="col-md-6">
                <div class="card ld-card p-4 h-100">
                    <h2 class="h4 mb-3">
                        <i class="fa-solid fa-bag-shopping me-2"></i>Order Management
                    </h2>
                    <p class="text-muted">View and update customer orders.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= APP_URL ?>/admin/orders.php" class="ld-btn-primary">
                            <i class="fa-solid fa-list-check me-1"></i>Manage Orders
                        </a>
                    </div>
                </div>
            </div>

            // Review management section
            <div class="col-md-6">
                <div class="card ld-card p-4 h-100">
                    <h2 class="h4 mb-3">
                        <i class="fa-solid fa-star me-2"></i>Review Management
                    </h2>
                    <p class="text-muted">View, edit, or delete customer reviews.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= APP_URL ?>/admin/reviews.php" class="ld-btn-primary">
                            <i class="fa-solid fa-comments me-1"></i>Manage Reviews
                        </a>
                    </div>
                </div>
            </div>

            // Customer inquiries section
            <div class="col-md-6">
                <div class="card ld-card p-4 h-100">
                    <h2 class="h4 mb-3">
                        <i class="fa-solid fa-envelope-open-text me-2"></i>Customer Inquiries
                    </h2>
                    <p class="text-muted mb-2">Access messages submitted from Contact Us.</p>
                    <p class="small text-muted mb-3">
                        Total: <?= e((string) $totalMessages) ?>
                        <?php if ($unreadMessages > 0): ?>
                            &middot; Unread: <?= e((string) $unreadMessages) ?>
                        <?php endif; ?>
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= APP_URL ?>/admin/messages.php" class="ld-btn-primary">
                            <i class="fa-solid fa-inbox me-1"></i>Check Messages
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

// Load Chart.js library
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const statusChartCtx = document.getElementById('ordersStatusChart');

if (statusChartCtx) {
    new Chart(statusChartCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($orderStatusLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            datasets: [{
                label: 'Orders by Status',
                data: <?= json_encode($orderStatusCounts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
