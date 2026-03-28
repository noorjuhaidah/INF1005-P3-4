<?php
$page_title = 'Delete Product - Admin';
$current_page = 'admin';

require_once __DIR__ . '/../includes/header.php';

require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id) {
    set_flash('warning', 'Invalid product ID.');
    redirect(APP_URL . '/admin/products.php#flash-container');
}

// Fetch product details for confirmation context.
$productName = '';
try {
    $stmt = $pdo->prepare('SELECT item_name FROM menu_items WHERE item_id = ? LIMIT 1');
    $stmt->execute([$id]);
    $productName = (string)($stmt->fetchColumn() ?: '');
} catch (PDOException $e) {
    error_log('Product delete lookup failed: ' . $e->getMessage());
}

if ($productName === '') {
    set_flash('warning', 'Product not found or already deleted.');
    redirect(APP_URL . '/admin/products.php#flash-container');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(APP_URL . '/admin/product_delete.php?id=' . (int)$id);

    try {
        $stmt = $pdo->prepare('DELETE FROM menu_items WHERE item_id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            set_flash('success', 'Product deleted successfully.');
        } else {
            set_flash('warning', 'Product not found or already deleted.');
        }
    } catch (PDOException $e) {
        error_log('Product delete failed: ' . $e->getMessage());
        set_flash('danger', 'Unable to delete product right now.');
    }

    redirect(APP_URL . '/admin/products.php#flash-container');
}
?>

<section class="ld-section">
    <div class="container" style="max-width: 680px;">
        <h1 class="ld-section-title">Delete Product</h1>
        <p class="ld-section-subtitle">Confirm permanent deletion.</p>

        <div class="card ld-card p-4">
            <p class="mb-2"><strong>Product:</strong> <?= e($productName) ?></p>
            <p class="text-danger mb-4">This action cannot be undone.</p>

            <form method="POST" action="<?= APP_URL ?>/admin/product_delete.php?id=<?= (int)$id ?>" class="d-flex gap-2 flex-wrap">
                <?php csrf_field(); ?>
                <input type="hidden" name="id" value="<?= (int)$id ?>">

                <button type="submit" class="btn btn-danger" aria-label="Confirm delete product <?= e($productName) ?>">
                    Confirm Delete Product
                </button>
                <a href="<?= APP_URL ?>/admin/products.php" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>