<?php
$page_title = 'Edit Product';
$current_page = 'admin';

require_once __DIR__ . '/../includes/header.php';

// Restrict access to admins only
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Validate product ID, redirect if invalid ID
if ($id === false || $id < 1) {
    header('Location: ' . APP_URL . '/admin/products.php');
    exit;
}

// Fetch categories for dropdown
$categories = [];

try {
    $stmt = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Fetch product details for editing
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

// Initialise form variables with current product data
$name = $product['item_name'];
$description = $product['description'];
$price = $product['price'];
$category_id = $product['category_id'];
$image_path = $product['image_path'];
$is_available = $product['is_available'];

$fieldErrors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf(APP_URL . '/admin/product_edit.php?id=' . (int) $id);

    // Get and validate form inputs
    $name = clean_input((string) ($_POST['name'] ?? ''));
    $description = clean_input((string) ($_POST['description'] ?? ''));
    $priceRaw = filter_input(INPUT_POST, 'price', FILTER_UNSAFE_RAW);
    $price = is_string($priceRaw) ? filter_var(trim($priceRaw), FILTER_VALIDATE_FLOAT) : false;
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    // Validate inputs and collect errors
    if ($name === '' || mb_strlen($name) > 120) {
        $fieldErrors['name'] = 'Product name is required and must be 120 characters or fewer.';
    }

    // Description is optional
    if ($description !== '' && mb_strlen($description) > 2000) {
        $fieldErrors['description'] = 'Description must be 2000 characters or fewer.';
    }

    // Validate category ID against existing categories
    if ($category_id === false || $category_id < 1) {
        $fieldErrors['category_id'] = 'Valid category is required.';
    }

    // Validate price
    if ($price === false || $price < 0 || $price > 9999.99) {
        $fieldErrors['price'] = 'Valid price is required.';
    }

    // Handle image upload if a new file is provided
    if (!empty($_FILES['image']['name'])) {

        if (!isset($_FILES['image']['error']) || is_array($_FILES['image']['error'])) {
            $fieldErrors['image'] = 'Invalid upload payload.';
        } elseif ((int) $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $fieldErrors['image'] = 'Image upload failed. Please try a different file.';
        } else {
            $maxBytes = 2 * 1024 * 1024;
            if ((int) $_FILES['image']['size'] > $maxBytes) {
                $fieldErrors['image'] = 'Image must be 2MB or smaller.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = (string) $finfo->file($_FILES['image']['tmp_name']);
                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];

                // Validate file types
                if (!isset($allowedTypes[$mimeType])) {
                    $fieldErrors['image'] = 'Only JPG, PNG, or WEBP files are allowed.';
                } else {
                    $uploadDir = __DIR__ . '/../uploads/';
                    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                        $fieldErrors['image'] = 'Unable to prepare upload directory.';
                    } else {
                        $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
                        $targetFile = $uploadDir . $filename;

                        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                            $image_path = $filename;
                        } else {
                            $fieldErrors['image'] = 'Image upload failed. Please try a different file.';
                        }
                    }
                }
            }
        }
    }

    // If no validation errors, proceed to update the product
    if (empty($fieldErrors)) {

        try {
            // Update product in database
            $stmt = $pdo->prepare(" 
            UPDATE menu_items
            SET item_name=?, description=?, price=?, category_id=?, image_path=?, is_available=?
            WHERE item_id=?
            ");

            // Bind parameters and execute
            $stmt->execute([
                $name,
                $description,
                (float) $price,
                (int) $category_id,
                $image_path,
                $is_available,
                $id
            ]);

            // Success message and redirect
            header('Location: ' . APP_URL . '/admin/products.php');
            exit;
        } catch (PDOException $e) {
            $fieldErrors['form'] = 'Unable to save product changes.';
        }
    }
}
?>

<!-- Load page layout -->
<section class="ld-section">
    <div class="container">

    <!-- Admin dashboard header -->
        <h1 class="ld-section-title">Edit Product</h1>

        <div class="ld-form-card">

            <!-- Display form-level errors if any -->
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

            <!-- Product edit form -->
            <form method="post" enctype="multipart/form-data" class="needs-validation" data-inline-validate="true"
                novalidate>
                <?php csrf_field(); ?>

                <?php if (!empty($image_path)): ?>

                    <img src="<?= APP_URL ?>/uploads/<?= e($image_path) ?>" alt="Current image for <?= e($name) ?>"
                        title="Current image for <?= e($name) ?>" style="max-width:120px;margin-bottom:10px;">

                <?php endif; ?>

                <!-- Product name field -->
                <div class="mb-3">
                    <label class="form-label" for="name">Product Name <span class="text-danger"
                            aria-hidden="true">*</span></label>
                    <input id="name" type="text"
                        class="form-control <?= !empty($fieldErrors['name']) ? 'is-invalid' : '' ?>" name="name"
                        value="<?= e($name) ?>" aria-label="Product name"
                        <?= !empty($fieldErrors['name']) ? 'aria-describedby="name_error"' : '' ?> required>
                    <?php if (!empty($fieldErrors['name'])): ?>
                        <div id="name_error" class="invalid-feedback"><?= e($fieldErrors['name']) ?></div><?php endif; ?>
                </div>

                <!-- Product description field -->
                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" class="form-control" name="description"
                        aria-label="Product description"><?= e($description) ?></textarea>
                    <?php if (!empty($fieldErrors['description'])): ?>
                        <div class="invalid-feedback d-block"><?= e($fieldErrors['description']) ?></div><?php endif; ?>
                </div>

                <!-- Product price field -->
                <div class="mb-3">
                    <label class="form-label" for="price">Price <span class="text-danger"
                            aria-hidden="true">*</span></label>
                    <input id="price" type="number" step="0.01" min="0"
                        class="form-control <?= !empty($fieldErrors['price']) ? 'is-invalid' : '' ?>" name="price"
                        value="<?= e($price) ?>" aria-label="Price"
                        aria-describedby="price_hint<?= !empty($fieldErrors['price']) ? ' price_error' : '' ?>"
                        required>
                    <div id="price_hint" class="form-text">Use numbers only, for example 4.50.</div>
                    <?php if (!empty($fieldErrors['price'])): ?>
                        <div id="price_error" class="invalid-feedback"><?= e($fieldErrors['price']) ?></div><?php endif; ?>
                </div>

                <!-- Product category dropdown -->
                <div class="mb-3">
                    <label class="form-label" for="category_id">Category <span class="text-danger"
                            aria-hidden="true">*</span></label>
                    <select id="category_id"
                        class="form-control <?= !empty($fieldErrors['category_id']) ? 'is-invalid' : '' ?>"
                        name="category_id" aria-label="Category"
                        <?= !empty($fieldErrors['category_id']) ? 'aria-describedby="category_error"' : '' ?> required>

                        <option value="">Select category</option>

                        <?php foreach ($categories as $cat): ?>

                            <option value="<?= $cat['category_id'] ?>" <?= $category_id == $cat['category_id'] ? 'selected' : '' ?>>
                                <?= e($cat['category_name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                    <?php if (!empty($fieldErrors['category_id'])): ?>
                        <div id="category_error" class="invalid-feedback"><?= e($fieldErrors['category_id']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Product image upload field -->
                <div class="mb-3">
                    <label class="form-label" for="image">Change Image</label>
                    <input id="image" type="file"
                        class="form-control <?= !empty($fieldErrors['image']) ? 'is-invalid' : '' ?>" name="image"
                        accept="image/*" aria-label="Change product image"
                        aria-describedby="image_help<?= !empty($fieldErrors['image']) ? ' image_error' : '' ?>">
                    <div id="image_help" class="form-text">Upload a clear replacement image. Ensure customer-facing
                        pages include descriptive alt text or mark decorative images with empty alt text.</div>
                    <?php if (!empty($fieldErrors['image'])): ?>
                        <div id="image_error" class="invalid-feedback"><?= e($fieldErrors['image']) ?></div><?php endif; ?>
                </div>

                <!-- Product availability checkbox -->
                <div class="form-check mb-3">
                    <input id="is_available" class="form-check-input" type="checkbox" name="is_available"
                        aria-label="Available" <?= $is_available ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_available">Available</label>
                </div>

                <!-- Form action buttons -->
                <button type="submit" class="ld-btn-primary">Save Changes</button>
                <a href="<?= APP_URL ?>/admin/products.php" class="ld-btn-outline ms-2">Cancel</a>

            </form>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
