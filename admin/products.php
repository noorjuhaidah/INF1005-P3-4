<?php
$page_title = 'Manage Products';
$current_page = 'admin';

require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

$products = [];

try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY product_id DESC");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}
?>

<section class="ld-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="ld-section-title">Manage Products</h1>
                <p class="ld-section-subtitle">View all menu items.</p>
            </div>
            <a href="<?= APP_URL ?>/admin/product_create.php" class="ld-btn-primary">Add Product</a>
        </div>

        <div class="card ld-card p-4">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Available</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6">No products found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= e((string)$product['product_id']) ?></td>
                                <td><?= e($product['name']) ?></td>
                                <td><?= e($product['category']) ?></td>
                                <td>$<?= number_format((float)$product['price'], 2) ?></td>
                                <td><?= $product['is_available'] ? 'Yes' : 'No' ?></td>
                                <td>
                                    <a href="<?= APP_URL ?>/admin/product_edit.php?id=<?= e((string)$product['product_id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <a href="<?= APP_URL ?>/admin/product_delete.php?id=<?= e((string)$product['product_id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this product?');">Delete</a>
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