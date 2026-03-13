<?php
$page_title = 'Edit Product';
$current_page = 'admin';

require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

$id = $_GET['id'] ?? '';

if ($id === '' || !is_numeric($id)) {
    header('Location: ' . APP_URL . '/admin/products.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . APP_URL . '/admin/products.php');
    exit;
}

$name = $product['name'];
$description = $product['description'];
$price = $product['price'];
$category = $product['category'];
$image_url = $product['image_url'];
$is_available = $product['is_available'];
$errorMsg = '';
$success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if ($name === '' || $category === '' || $price === '' || !is_numeric($price)) {
        $errorMsg = "Please fill in all required fields properly.";
        $success = false;
    }

    if ($success) {
        $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, category=?, image_url=?, is_available=? WHERE product_id=?");
        $stmt->execute([$name, $description, $price, $category, $image_url, $is_available, $id]);

        header('Location: ' . APP_URL . '/admin/products.php');
        exit;
    }
}
?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title">Edit Product</h1>

        <div class="ld-form-card">
            <?php if (!$success): ?>
                <div class="alert alert-danger"><?= $errorMsg ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="mb-3">
                    <label class="form-label" for="name">Product Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= e($description) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="price">Price</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?= e((string)$price) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="category">Category</label>
                    <input type="text" class="form-control" id="category" name="category" value="<?= e($category) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="image_url">Image URL</label>
                    <input type="text" class="form-control" id="image_url" name="image_url" value="<?= e($image_url) ?>">
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="is_available" name="is_available" <?= $is_available ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_available">Available</label>
                </div>

                <button type="submit" class="ld-btn-primary">Save Changes</button>
                <a href="<?= APP_URL ?>/admin/products.php" class="ld-btn-outline ms-2">Cancel</a>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>