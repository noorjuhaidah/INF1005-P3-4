<?php
$page_title = 'Admin Dashboard';
$current_page = 'admin';

require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

$totalProducts = 0;
$totalOrders = 0;
$totalRevenue = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $totalProducts = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $totalOrders = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders");
    $totalRevenue = (float)$stmt->fetchColumn();
} catch (PDOException $e) {
    $totalProducts = 0;
    $totalOrders = 0;
    $totalRevenue = 0;
}
?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title">Admin Dashboard</h1>
        <p class="ld-section-subtitle">Manage products and orders.</p>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card ld-card p-4 text-center">
                    <h5>Total Products</h5>
                    <h2><?= e((string)$totalProducts) ?></h2>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card ld-card p-4 text-center">
                    <h5>Total Orders</h5>
                    <h2><?= e((string)$totalOrders) ?></h2>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card ld-card p-4 text-center">
                    <h5>Total Revenue</h5>
                    <h2>$<?= number_format($totalRevenue, 2) ?></h2>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card ld-card p-4 h-100">
                    <h4 class="mb-3">Product Management</h4>
                    <p class="text-muted">Add, edit, or delete menu items.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= APP_URL ?>/admin/products.php" class="ld-btn-primary">Manage Products</a>
                        <a href="<?= APP_URL ?>/admin/product_create.php" class="ld-btn-outline">Add Product</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card ld-card p-4 h-100">
                    <h4 class="mb-3">Order Management</h4>
                    <p class="text-muted">View and update customer orders.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= APP_URL ?>/admin/orders.php" class="ld-btn-primary">Manage Orders</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>