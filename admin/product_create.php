<?php
$page_title = 'Add Product';
$current_page = 'admin';

require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

$name = '';
$description = '';
$price = '';
$category = '';
$image_url = '';
$is_available = 1;
$errorMsg = '';
$success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if ($name === '') {
        $errorMsg .= "Product name is required.<br>";
        $success = false;
    }

    if ($category === '') {
        $errorMsg .= "Category is required.<br>";
        $success = false;
    }

    if ($price === '' || !is_numeric($price)) {
        $errorMsg .= "Valid price is required.<br>";
        $success = false;
    }

    if ($success) {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, image_url, is_available) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $category, $image_url, $is_available]);

            header('Location: ' . APP_URL . '/admin/products.php');
            exit;
        } catch (PDOException $e) {
            $errorMsg = "Unable to add product.";
            $success = false;
        }
    }
}
?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title">Add Product</h1>

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
                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?= e($price) ?>">
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

                <button type="submit" class="ld-btn-primary">Add Product</button>
                <a href="<?= APP_URL ?>/admin/products.php" class="ld-btn-outline ms-2">Cancel</a>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>