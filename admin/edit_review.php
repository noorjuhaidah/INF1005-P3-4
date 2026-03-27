<?php
$page_title = 'Edit Review';
$current_page = 'admin';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    set_flash('danger', 'Invalid review ID.');
    redirect(APP_URL . '/admin/reviews.php');
}

$reviewColumns = [];
$review = null;
$reviewTextColumn = '';
$reviewNameColumn = '';
$reviewRatingColumn = '';
$reviewUserIdColumn = '';

try {
    $columnsStmt = $pdo->query("SHOW COLUMNS FROM reviews");
    foreach ($columnsStmt->fetchAll() as $column) {
        if (!empty($column['Field'])) {
            $reviewColumns[] = $column['Field'];
        }
    }

    $reviewTextColumn = in_array('comment', $reviewColumns, true)
        ? 'comment'
        : (in_array('review_text', $reviewColumns, true) ? 'review_text' : '');
    $reviewNameColumn = in_array('name', $reviewColumns, true) ? 'name' : '';
    $reviewRatingColumn = in_array('rating', $reviewColumns, true) ? 'rating' : '';
    $reviewUserIdColumn = in_array('user_id', $reviewColumns, true) ? 'user_id' : '';

    if ($reviewTextColumn === '') {
        throw new RuntimeException('No supported review text column found in reviews table.');
    }

    $selectParts = ['r.id', "r.{$reviewTextColumn} AS review_text"];

    if ($reviewNameColumn !== '') {
        $selectParts[] = "r.{$reviewNameColumn} AS reviewer_name";
    } elseif ($reviewUserIdColumn !== '') {
        $selectParts[] = "u.full_name AS reviewer_name";
    } else {
        $selectParts[] = "'Anonymous' AS reviewer_name";
    }

    if ($reviewRatingColumn !== '') {
        $selectParts[] = "r.{$reviewRatingColumn} AS rating";
    } else {
        $selectParts[] = "NULL AS rating";
    }

    $sql = "SELECT " . implode(', ', $selectParts) . " FROM reviews r";
    if ($reviewNameColumn === '' && $reviewUserIdColumn !== '') {
        $sql .= " LEFT JOIN users u ON u.user_id = r.{$reviewUserIdColumn}";
    }
    $sql .= " WHERE r.id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $review = $stmt->fetch();

    if (!$review) {
        set_flash('danger', 'Review not found.');
        redirect(APP_URL . '/admin/reviews.php');
    }
} catch (Throwable $e) {
    error_log('Admin edit review load error: ' . $e->getMessage());
    set_flash('danger', 'Unable to load review details.');
    redirect(APP_URL . '/admin/reviews.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(APP_URL . '/admin/edit_review.php?id=' . $id);

    $newReviewerName = trim($_POST['name'] ?? '');
    $newRatingRaw = $_POST['rating'] ?? null;
    $newReviewText = clean_input($_POST['review_text'] ?? '');

    if ($newReviewText === '') {
        set_flash('warning', 'Review text cannot be empty.');
        redirect(APP_URL . '/admin/edit_review.php?id=' . $id);
    }

    $setParts = [];
    $params = [];

    if ($reviewNameColumn !== '') {
        if ($newReviewerName === '') {
            $newReviewerName = 'Anonymous';
        }
        $setParts[] = "{$reviewNameColumn} = ?";
        $params[] = $newReviewerName;
    }

    if ($reviewRatingColumn !== '') {
        $newRating = filter_var($newRatingRaw, FILTER_VALIDATE_INT);
        if ($newRating === false || $newRating < 1 || $newRating > 5) {
            set_flash('warning', 'Rating must be between 1 and 5.');
            redirect(APP_URL . '/admin/edit_review.php?id=' . $id);
        }
        $setParts[] = "{$reviewRatingColumn} = ?";
        $params[] = $newRating;
    }

    $setParts[] = "{$reviewTextColumn} = ?";
    $params[] = $newReviewText;

    if (empty($setParts)) {
        set_flash('warning', 'No editable review fields are available in your database schema.');
        redirect(APP_URL . '/admin/reviews.php');
    }

    $params[] = $id;

    try {
        $sql = "UPDATE reviews SET " . implode(', ', $setParts) . " WHERE id = ?";
        $updateStmt = $pdo->prepare($sql);
        $updateStmt->execute($params);

        set_flash('success', 'Review updated successfully.');
        redirect(APP_URL . '/admin/reviews.php');
    } catch (PDOException $e) {
        error_log('Admin edit review update error: ' . $e->getMessage());
        set_flash('danger', 'Failed to update review.');
        redirect(APP_URL . '/admin/edit_review.php?id=' . $id);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title">Edit Review</h1>
        <p class="ld-section-subtitle">Update review content and metadata.</p>

        <div class="card ld-card p-4">
            <form method="post" action="<?= APP_URL ?>/admin/edit_review.php?id=<?= e((string) $id) ?>">
                <?php csrf_field(); ?>

                <?php if ($reviewNameColumn !== ''): ?>
                    <div class="mb-3">
                        <label class="form-label" for="name">Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?= e((string) ($review['reviewer_name'] ?? '')) ?>" required>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" value="<?= e((string) ($review['reviewer_name'] ?? 'Anonymous')) ?>" disabled>
                        <small class="text-muted">Name comes from the linked user account in this schema.</small>
                    </div>
                <?php endif; ?>

                <?php if ($reviewRatingColumn !== ''): ?>
                    <div class="mb-3">
                        <label class="form-label" for="rating">Rating (1-5)</label>
                        <input type="number" id="rating" name="rating" class="form-control" min="1" max="5" value="<?= e((string) ($review['rating'] ?? '')) ?>" required>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label" for="review_text">Review</label>
                    <textarea id="review_text" name="review_text" rows="5" class="form-control" required><?= e((string) ($review['review_text'] ?? '')) ?></textarea>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="ld-btn-primary">Save Changes</button>
                    <a href="<?= APP_URL ?>/admin/reviews.php" class="ld-btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
