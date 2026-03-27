<?php
$page_title = 'Admin Dashboard';
$current_page = 'admin';

require_once __DIR__ . '/../includes/header.php';

require_admin();

$totalProducts = 0;
$totalOrders = 0;
$totalRevenue = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM menu_items");
    $totalProducts = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $totalOrders = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders");
    $totalRevenue = (float) $stmt->fetchColumn();
} catch (PDOException $e) {
    $totalProducts = 0;
    $totalOrders = 0;
    $totalRevenue = 0;
}
?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title">
            <i class="fa-solid fa-chart-line me-2"></i>Admin Dashboard
        </h1>
        <p class="ld-section-subtitle">Manage products and orders.</p>

        <?php show_flash(); ?>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card ld-card p-4 text-center">
                    <h5><i class="fa-solid fa-box-open me-2"></i>Total Products</h5>
                    <h2><?= e((string) $totalProducts) ?></h2>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card ld-card p-4 text-center">
                    <h5><i class="fa-solid fa-receipt me-2"></i>Total Orders</h5>
                    <h2><?= e((string) $totalOrders) ?></h2>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card ld-card p-4 text-center">
                    <h5><i class="fa-solid fa-dollar-sign me-2"></i>Total Revenue</h5>
                    <h2>$<?= number_format($totalRevenue, 2) ?></h2>
                </div>
            </div>
        </div>

        <div class="card ld-card p-4 mb-4">
            <h4 class="mb-3">
                <i class="fa-solid fa-chart-column me-2"></i>Business Overview
            </h4>
            <div style="height: 320px;">
                <canvas id="adminOverviewChart"></canvas>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card ld-card p-4 h-100">
                    <h4 class="mb-3">
                        <i class="fa-solid fa-mug-hot me-2"></i>Product Management
                    </h4>
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

            <div class="col-md-6">
                <div class="card ld-card p-4 h-100">
                    <h4 class="mb-3">
                        <i class="fa-solid fa-bag-shopping me-2"></i>Order Management
                    </h4>
                    <p class="text-muted">View and update customer orders.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= APP_URL ?>/admin/orders.php" class="ld-btn-primary">
                            <i class="fa-solid fa-list-check me-1"></i>Manage Orders
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card ld-card p-4 h-100">
                    <h4 class="mb-3">
                        <i class="fa-solid fa-star me-2"></i>Review Management
                    </h4>
                    <p class="text-muted">View, edit, or delete customer reviews.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= APP_URL ?>/admin/reviews.php" class="ld-btn-primary">
                            <i class="fa-solid fa-comments me-1"></i>Manage Reviews
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const adminChartCtx = document.getElementById('adminOverviewChart');

if (adminChartCtx) {
    new Chart(adminChartCtx, {
        type: 'bar',
        data: {
            labels: ['Products', 'Orders', 'Revenue'],
            datasets: [{
                label: 'LazyDrip Overview',
                data: [
                    <?= (int) $totalProducts ?>,
                    <?= (int) $totalOrders ?>,
                    <?= (float) $totalRevenue ?>
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>