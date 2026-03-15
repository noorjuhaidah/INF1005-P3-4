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

/* Fetch categories */
$categories = [];

try {
    $stmt = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

/* Fetch product */
$stmt = $pdo->prepare("
SELECT item_name, description, price, category_id, image_path, is_available
FROM menu_items
WHERE item_id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . APP_URL . '/admin/products.php');
    exit;
}

$name = $product['item_name'];
$description = $product['description'];
$price = $product['price'];
$category_id = $product['category_id'];
$image_path = $product['image_path'];
$is_available = $product['is_available'];

$errorMsg = '';
$success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $category_id = $_POST['category_id'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if ($name === '' || $category_id === '' || $price === '' || !is_numeric($price)) {
        $errorMsg = "Please fill all required fields.";
        $success = false;
    }

    /* Handle image upload */
    if (!empty($_FILES['image']['name'])) {

        $uploadDir = __DIR__ . '/../uploads/';
        $filename = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image_path = $filename;
        }
    }

    if ($success) {

        $stmt = $pdo->prepare("
        UPDATE menu_items
        SET item_name=?, description=?, price=?, category_id=?, image_path=?, is_available=?
        WHERE item_id=?
        ");

        $stmt->execute([
            $name,
            $description,
            $price,
            $category_id,
            $image_path,
            $is_available,
            $id
        ]);

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

<form method="post" enctype="multipart/form-data">

<?php if (!empty($image_path)): ?>

<img src="<?= APP_URL ?>/uploads/<?= e($image_path) ?>" style="max-width:120px;margin-bottom:10px;">

<?php endif; ?>

<div class="mb-3">
<label class="form-label">Product Name</label>
<input type="text" class="form-control" name="name" value="<?= e($name) ?>">
</div>

<div class="mb-3">
<label class="form-label">Description</label>
<textarea class="form-control" name="description"><?= e($description) ?></textarea>
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

<div class="mb-3">
<label class="form-label">Change Image</label>
<input type="file" class="form-control" name="image">
</div>

<div class="form-check mb-3">
<input class="form-check-input" type="checkbox" name="is_available" <?= $is_available ? 'checked' : '' ?>>
<label class="form-check-label">Available</label>
</div>

<button type="submit" class="ld-btn-primary">Save Changes</button>
<a href="<?= APP_URL ?>/admin/products.php" class="ld-btn-outline ms-2">Cancel</a>

</form>

</div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>