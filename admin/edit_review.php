<?php
$page_title = 'Edit Review';
$current_page = 'admin';

// Include database and helper functions
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Restrict access to admins only
require_admin();

// Get review ID from query parameter
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Validate review ID, redirect if invalid ID
if (!$id) {
    set_flash('danger', 'Invalid review ID.');
    redirect(APP_URL . '/admin/reviews.php');
}

// Initialise variables
$reviewColumns = [];
$review = null;
$reviewTextColumn = '';
$reviewNameColumn = '';
$reviewRatingColumn = '';
$reviewUserIdColumn = '';
$reviewIdColumn = '';

try {

    // Get columns from reviews table
    $columnsStmt = $pdo->query("SHOW COLUMNS FROM reviews");
    foreach ($columnsStmt->fetchAll() as $column) {
        if (!empty($column['Field'])) {
            $reviewColumns[] = $column['Field'];
        }
    }

    // Helper to pick first matching column name
    $pickColumn = static function (array $candidates, array $columns): string {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }
        return '';
    };

    // Helper to quote SQL column names
    $quoteIdent = static function (string $identifier): string {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new RuntimeException('Unsafe SQL identifier: ' . $identifier);
        }
        return '`' . $identifier . '`';
    };

    // Detect column names
    $reviewIdColumn = $pickColumn(['id', 'review_id'], $reviewColumns);
    $reviewTextColumn = $pickColumn(['comment', 'review_text', 'review', 'feedback'], $reviewColumns);
    $reviewNameColumn = $pickColumn(['name', 'reviewer_name', 'full_name'], $reviewColumns);
    $reviewRatingColumn = $pickColumn(['rating', 'stars'], $reviewColumns);
    $reviewUserIdColumn = $pickColumn(['user_id', 'customer_id'], $reviewColumns);
    $userPrimaryKeyColumn = 'user_id';

    // If name is not directly in reviews table, get from users table
    if ($reviewNameColumn === '' && $reviewUserIdColumn !== '') {
        $userColumns = [];
        $userColumnsStmt = $pdo->query("SHOW COLUMNS FROM users");
        foreach ($userColumnsStmt->fetchAll() as $column) {
            if (!empty($column['Field'])) {
                $userColumns[] = $column['Field'];
            }
        }
        $userPrimaryKeyColumn = $pickColumn(['user_id', 'id'], $userColumns);
        if ($userPrimaryKeyColumn === '') {
            $userPrimaryKeyColumn = 'user_id';
        }
    }

    // Ensures required columns exist
    if ($reviewIdColumn === '' || $reviewTextColumn === '') {
        throw new RuntimeException('No supported review ID/text columns found in reviews table.');
    }

    // Build select query
    $selectParts = ["r." . $quoteIdent($reviewIdColumn) . " AS review_id", "r." . $quoteIdent($reviewTextColumn) . " AS review_text"];

    // Add reviewer name to select if available
    if ($reviewNameColumn !== '') {
        $selectParts[] = "r." . $quoteIdent($reviewNameColumn) . " AS reviewer_name";
    } elseif ($reviewUserIdColumn !== '') {
        $selectParts[] = "u.`full_name` AS reviewer_name";
    } else {
        $selectParts[] = "'Anonymous' AS reviewer_name";
    }

    // Add rating to select if available
    if ($reviewRatingColumn !== '') {
        $selectParts[] = "r." . $quoteIdent($reviewRatingColumn) . " AS rating";
    } else {
        $selectParts[] = "NULL AS rating";
    }

    $sql = "SELECT " . implode(', ', $selectParts) . " FROM reviews r";
    if ($reviewNameColumn === '' && $reviewUserIdColumn !== '') {
        $sql .= " LEFT JOIN users u ON u." . $quoteIdent($userPrimaryKeyColumn) . " = r." . $quoteIdent($reviewUserIdColumn);
    }
    $sql .= " WHERE r." . $quoteIdent($reviewIdColumn) . " = ?";

    // Execute query to fetch review details
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $review = $stmt->fetch();

    // If review not found
    if (!$review) {
        set_flash('danger', 'Review not found.');
        redirect(APP_URL . '/admin/reviews.php');
    }
} catch (Throwable $e) {
    // Log error and redirect
    error_log('Admin edit review load error: ' . $e->getMessage());
    set_flash('danger', 'Unable to load review details.');
    redirect(APP_URL . '/admin/reviews.php');
}

// Handle form submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(APP_URL . '/admin/edit_review.php?id=' . $id);

    // Get form inputs
    $newReviewerName = trim($_POST['name'] ?? '');
    $newRatingRaw = $_POST['rating'] ?? null;
    $newReviewText = clean_input($_POST['review_text'] ?? '');

    // Validate review text
    if ($newReviewText === '') {
        set_flash('warning', 'Review text cannot be empty.');
        redirect(APP_URL . '/admin/edit_review.php?id=' . $id);
    }

    $setParts = [];
    $params = [];

    // Update reviewer name if column exists
    if ($reviewNameColumn !== '') {
        if ($newReviewerName === '') {
            $newReviewerName = 'Anonymous';
        }
        $setParts[] = $quoteIdent($reviewNameColumn) . " = ?";
        $params[] = $newReviewerName;
    }

    // Update rating if column exists
    if ($reviewRatingColumn !== '') {
        $newRating = filter_var($newRatingRaw, FILTER_VALIDATE_INT);
        
        // Validate rating value
        if ($newRating === false || $newRating < 1 || $newRating > 5) {
            set_flash('warning', 'Rating must be between 1 and 5.');
            redirect(APP_URL . '/admin/edit_review.php?id=' . $id);
        }
        $setParts[] = $quoteIdent($reviewRatingColumn) . " = ?";
        $params[] = $newRating;
    }

    // Always update review text
    $setParts[] = $quoteIdent($reviewTextColumn) . " = ?";
    $params[] = $newReviewText;

    // If nothing to update, redirect back
    if (empty($setParts)) {
        set_flash('warning', 'No editable review fields are available in your database schema.');
        redirect(APP_URL . '/admin/reviews.php');
    }

    $params[] = $id;

    try {
        $sql = "UPDATE reviews SET " . implode(', ', $setParts) . " WHERE " . $quoteIdent($reviewIdColumn) . " = ?";
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
                        <input type="text" id="name" name="name" class="form-control"
                            value="<?= e((string) ($review['reviewer_name'] ?? '')) ?>" aria-label="Reviewer name"
                            title="Reviewer name" required>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label" for="reviewer_name_readonly">Name</label>
                        <input id="reviewer_name_readonly" type="text" class="form-control"
                            value="<?= e((string) ($review['reviewer_name'] ?? 'Anonymous')) ?>" aria-label="Reviewer name"
                            title="Reviewer name" readonly aria-readonly="true">
                        <small class="text-muted">Name comes from the linked user account in this schema.</small>
                    </div>
                <?php endif; ?>

                <?php if ($reviewRatingColumn !== ''): ?>
                    <div class="mb-3">
                        <label class="form-label" for="rating">Rating (1-5)</label>
                        <input type="number" id="rating" name="rating" class="form-control" min="1" max="5"
                            value="<?= e((string) ($review['rating'] ?? '')) ?>" required>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label" for="review_text">Review</label>
                    <textarea id="review_text" name="review_text" rows="5" class="form-control"
                        required><?= e((string) ($review['review_text'] ?? '')) ?></textarea>
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
