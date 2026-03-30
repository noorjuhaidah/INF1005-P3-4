<?php
$page_title = 'Delete Product - Admin';
$current_page = 'admin';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Restrict access to admins only
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id) {
    set_flash('warning', 'Invalid product ID.');
    redirect(APP_URL . '/admin/products.php#flash-container');
}

// Fetch product name for confirmation message
$productName = '';
try {
    $stmt = $pdo->prepare('SELECT item_name FROM menu_items WHERE item_id = ? LIMIT 1');
    $stmt->execute([$id]);
    $productName = (string) ($stmt->fetchColumn() ?: '');
} catch (PDOException $e) {
    error_log('Product delete lookup failed: ' . $e->getMessage());
}

if ($productName === '') {
    set_flash('warning', 'Product not found or already deleted.');
    redirect(APP_URL . '/admin/products.php#flash-container');
}

// Handle deletion on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(APP_URL . '/admin/product_delete.php?id=' . (int) $id);

    try {
        $stmt = $pdo->prepare('DELETE FROM menu_items WHERE item_id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            set_flash('success', 'Product deleted successfully.');
        } else {
            set_flash('warning', 'Product not found or already deleted.');
        }
    } catch (PDOException $e) {
        // If historical order rows reference this item, fall back to hiding it.
        if ($e->getCode() === '23000') {
            try {
                $archive = $pdo->prepare('UPDATE menu_items SET is_available = 0 WHERE item_id = ?');
                $archive->execute([$id]);

                if ($archive->rowCount() > 0) {
                    set_flash('warning', 'Product has order history, so it was hidden from the menu instead of being permanently deleted.');
                } else {
                    set_flash('warning', 'Product could not be permanently deleted because it has order history, and it was already hidden.');
                }
            } catch (PDOException $archiveError) {
                error_log('Product archive fallback failed: ' . $archiveError->getMessage());
                set_flash('danger', 'Unable to delete product right now.');
            }
        } else {
            error_log('Product delete failed: ' . $e->getMessage());
            set_flash('danger', 'Unable to delete product right now.');
        }
    }

    redirect(APP_URL . '/admin/products.php#flash-container');
}

require_once __DIR__ . '/../includes/header.php';
?>

// Delete confirmation UI
<section class="ld-section">
    <div class="container" style="max-width: 680px;">
        <h1 class="ld-section-title">Delete Product</h1>
        <p class="ld-section-subtitle">Confirm permanent deletion.</p>

        // Warning message
        <div class="card ld-card p-4">
            <p class="mb-2"><strong>Product:</strong> <?= e($productName) ?></p>
            <p class="text-danger mb-4">This action cannot be undone.</p>

            // Delete form
            <form method="POST" action="<?= APP_URL ?>/admin/product_delete.php?id=<?= (int) $id ?>"
                class="d-flex gap-2 flex-wrap">
                <?php csrf_field(); ?>
                <input type="hidden" name="id" value="<?= (int) $id ?>">

                // Confirm delete button
                <button type="submit" class="btn btn-danger" aria-label="Confirm delete product <?= e($productName) ?>">
                    Confirm Delete Product
                </button>
                <a href="<?= APP_URL ?>/admin/products.php" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>