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
$category_id = '';
$is_available = 1;
$errorMsg = '';
$success = true;

/* Fetch categories for dropdown */
$categories = [];

try {
    $stmt = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if ($name === '') {
        $errorMsg .= "Product name is required.<br>";
        $success = false;
    }

    if ($category_id === '') {
        $errorMsg .= "Category is required.<br>";
        $success = false;
    }

    if ($price === '' || !is_numeric($price)) {
        $errorMsg .= "Valid price is required.<br>";
        $success = false;
    }

    if ($success) {
        try {

            $stmt = $pdo->prepare("
                INSERT INTO menu_items
                (item_name, description, price, category_id, is_available)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $description,
                $price,
                $category_id,
                $is_available
            ]);

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

<form method="post">

<div class="mb-3">
<label class="form-label">Product Name</label>
<input type="text" class="form-control" name="name" value="<?= e($name) ?>">
</div>

<div class="mb-3">
<label class="form-label">Description</label>
<textarea class="form-control" name="description" rows="3"><?= e($description) ?></textarea>
</div>

<div class="mb-3">
<label class="form-label">Price</label>
<input type="number" step="0.01" class="form-control" name="price" value="<?= e($price) ?>">
</div>

<div class="mb-3">
<label class="form-label">Category</label>
<select class="form-control" name="category_id">

<option value="">Select category</option>

<?php foreach ($categories as $cat): ?>

<option value="<?= $cat['category_id'] ?>" <?= $category_id == $cat['category_id'] ? 'selected' : '' ?>>
<?= e($cat['category_name']) ?>
</option>

<?php endforeach; ?>

</select>
</div>

<div class="form-check mb-3">
<input class="form-check-input" type="checkbox" name="is_available" <?= $is_available ? 'checked' : '' ?>>
<label class="form-check-label">Available</label>
</div>

<button type="submit" class="ld-btn-primary">Add Product</button>
<a href="<?= APP_URL ?>/admin/products.php" class="ld-btn-outline ms-2">Cancel</a>

</form>

</div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>