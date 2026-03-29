<?php
$page_title = 'Manage Products';
$current_page = 'admin';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_admin();

$products = [];
$totalProducts = 0;
$perPage = 10;
$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
if (!$currentPage || $currentPage < 1) {
    $currentPage = 1;
}
$totalPages = 1;

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items");
    $countStmt->execute();
    $totalProducts = (int)$countStmt->fetchColumn();

    $totalPages = max(1, (int)ceil($totalProducts / $perPage));
    if ($currentPage > $totalPages) {
        $currentPage = $totalPages;
    }

    $offset = ($currentPage - 1) * $perPage;

    $stmt = $pdo->prepare("
        SELECT m.item_id,
               m.item_name,
               m.price,
               m.is_available,
               c.category_name
        FROM menu_items m
        JOIN categories c ON m.category_id = c.category_id
        ORDER BY m.item_id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Admin products load error: ' . $e->getMessage());
    $products = [];
    $totalProducts = 0;
    $totalPages = 1;
    $currentPage = 1;
}

require_once __DIR__ . '/../includes/header.php';
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
                    <caption class="visually-hidden">Products table listing product details and row actions for edit and delete.</caption>
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Category</th>
                            <th scope="col">Price</th>
                            <th scope="col">Available</th>
                            <th scope="col">Actions</th>
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
                                <th scope="row"><?= e((string)$product['item_id']) ?></th>
                                <td><?= e($product['item_name']) ?></td>
                                <td><?= e($product['category_name']) ?></td>
                                <td>$<?= number_format((float)$product['price'], 2) ?></td>
                                <td><?= $product['is_available'] ? 'Yes' : 'No' ?></td>
                                <td>
                                    <a href="<?= APP_URL ?>/admin/product_edit.php?id=<?= e((string)$product['item_id']) ?>" class="btn btn-sm btn-outline-primary" aria-label="Edit product <?= e($product['item_name']) ?>">Edit</a>
                                    <a href="<?= APP_URL ?>/admin/product_delete.php?id=<?= e((string)$product['item_id']) ?>" class="btn btn-sm btn-outline-danger" aria-label="Delete product <?= e($product['item_name']) ?>">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Products pagination" class="mt-4">
                    <ul class="pagination mb-0">
                        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= APP_URL ?>/admin/products.php?page=<?= max(1, $currentPage - 1) ?>" aria-label="Previous page">Previous</a>
                        </li>

                        <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                            <li class="page-item <?= $page === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="<?= APP_URL ?>/admin/products.php?page=<?= $page ?>" aria-label="Go to page <?= $page ?>"><?= $page ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= APP_URL ?>/admin/products.php?page=<?= min($totalPages, $currentPage + 1) ?>" aria-label="Next page">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>