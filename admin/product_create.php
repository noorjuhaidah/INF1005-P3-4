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
$fieldErrors = [];

/* Fetch categories */
$categories = [];

try {
    $stmt = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if ($name === '') {
        $fieldErrors['name'] = 'Product name is required.';
    }

    if ($category_id === '') {
        $fieldErrors['category_id'] = 'Category is required.';
    }

    if ($price === '' || !is_numeric($price) || (float)$price < 0) {
        $fieldErrors['price'] = 'Valid price is required.';
    }

    /* Image upload */
    $image_path = '';

    if (!empty($_FILES['image']['name'])) {

        $uploadDir = __DIR__ . '/../uploads/';
        $filename = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image_path = $filename;
        } else {
            $fieldErrors['image'] = 'Image upload failed. Please try a different file.';
        }
    }

    if (empty($fieldErrors)) {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO menu_items
                (item_name, description, price, category_id, image_path, is_available)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $description,
                $price,
                $category_id,
                $image_path,
                $is_available
            ]);

            header('Location: ' . APP_URL . '/admin/products.php');
            exit;

        } catch (PDOException $e) {
            $fieldErrors['form'] = 'Unable to add product.';
        }
    }
}
?>

<section class="ld-section">
<div class="container">

<h1 class="ld-section-title">Add Product</h1>

<div class="ld-form-card">

<?php if (!empty($fieldErrors)): ?>
<div class="alert alert-danger" role="status" aria-live="polite" aria-atomic="true">
    <strong>Please fix the following:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($fieldErrors as $msg): ?>
            <li><?= e($msg) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">

<div class="mb-3">
<label class="form-label" for="name">Product Name <span class="text-danger" aria-hidden="true">*</span></label>
<input id="name" type="text" class="form-control <?= !empty($fieldErrors['name']) ? 'is-invalid' : '' ?>" name="name" value="<?= e($name) ?>" aria-describedby="<?= !empty($fieldErrors['name']) ? 'name_error' : '' ?>" required>
<?php if (!empty($fieldErrors['name'])): ?><div id="name_error" class="invalid-feedback"><?= e($fieldErrors['name']) ?></div><?php endif; ?>
</div>

<div class="mb-3">
<label class="form-label" for="description">Description</label>
<textarea id="description" class="form-control" name="description"><?= e($description) ?></textarea>
</div>

<div class="mb-3">
<label class="form-label" for="price">Price <span class="text-danger" aria-hidden="true">*</span></label>
<input id="price" type="number" step="0.01" min="0" class="form-control <?= !empty($fieldErrors['price']) ? 'is-invalid' : '' ?>" name="price" value="<?= e($price) ?>" aria-describedby="price_hint<?= !empty($fieldErrors['price']) ? ' price_error' : '' ?>" required>
<div id="price_hint" class="form-text">Use numbers only, for example 4.50.</div>
<?php if (!empty($fieldErrors['price'])): ?><div id="price_error" class="invalid-feedback"><?= e($fieldErrors['price']) ?></div><?php endif; ?>
</div>

<div class="mb-3">
<label class="form-label" for="category_id">Category <span class="text-danger" aria-hidden="true">*</span></label>
<select id="category_id" class="form-control <?= !empty($fieldErrors['category_id']) ? 'is-invalid' : '' ?>" name="category_id" aria-describedby="<?= !empty($fieldErrors['category_id']) ? 'category_error' : '' ?>" required>

<option value="">Select category</option>

<?php foreach ($categories as $cat): ?>

<option value="<?= $cat['category_id'] ?>" <?= $category_id == $cat['category_id'] ? 'selected' : '' ?>>
<?= e($cat['category_name']) ?>
</option>

<?php endforeach; ?>

</select>
<?php if (!empty($fieldErrors['category_id'])): ?><div id="category_error" class="invalid-feedback"><?= e($fieldErrors['category_id']) ?></div><?php endif; ?>
</div>

<div class="mb-3">
<label class="form-label" for="image">Product Image</label>
<input id="image" type="file" class="form-control <?= !empty($fieldErrors['image']) ? 'is-invalid' : '' ?>" name="image" accept="image/*" aria-describedby="image_help<?= !empty($fieldErrors['image']) ? ' image_error' : '' ?>">
<div id="image_help" class="form-text">Upload a clear product image. Ensure customer-facing pages include descriptive alt text or mark decorative images with empty alt text.</div>
<?php if (!empty($fieldErrors['image'])): ?><div id="image_error" class="invalid-feedback"><?= e($fieldErrors['image']) ?></div><?php endif; ?>
</div>

<div class="form-check mb-3">
<input id="is_available" class="form-check-input" type="checkbox" name="is_available" <?= $is_available ? 'checked' : '' ?>>
<label class="form-check-label" for="is_available">Available</label>
</div>

<button type="submit" class="ld-btn-primary">Add Product</button>
<a href="<?= APP_URL ?>/admin/products.php" class="ld-btn-outline ms-2">Cancel</a>

</form>

</div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>