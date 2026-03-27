<?php
// =============================================================
// reviews.php — Public reviews page
// Allows logged-in users to leave a review, and shows existing reviews.
// Uses the shared CSRF helpers from includes/functions.php.
// =============================================================

$page_title   = 'Reviews';
$current_page = 'reviews';

// Load DB + helpers BEFORE header so POST/redirect logic can run safely.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$reviewColumns = [];
try {
    $colStmt = $pdo->query("SHOW COLUMNS FROM reviews");
    foreach ($colStmt->fetchAll() as $column) {
        if (!empty($column['Field'])) {
            $reviewColumns[] = $column['Field'];
        }
    }
} catch (PDOException $e) {
    $reviewColumns = [];
}

$usesAdminReviewSchema = in_array('comment', $reviewColumns, true)
    && in_array('name', $reviewColumns, true);

// Handle new review submission BEFORE header output (so redirects work)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {

    // Use the shared CSRF helper — falls back to reviews.php on failure
    verify_csrf(APP_URL . '/reviews.php');

    $reviewText = clean_input($_POST['review'] ?? '');
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $reviewerName = trim($_SESSION['full_name'] ?? 'Customer');

    if (empty($reviewText)) {
        set_flash('warning', 'Please enter a review before submitting.');
        redirect(APP_URL . '/reviews.php');
    }

    if (!$rating || $rating < 1 || $rating > 5) {
        set_flash('warning', 'Please choose a rating from 1 to 5.');
        redirect(APP_URL . '/reviews.php');
    }

    try {
        if ($usesAdminReviewSchema) {
            $stmt = $pdo->prepare(
                "INSERT INTO reviews (name, rating, comment, created_at)
                        VALUES (?, ?, ?, NOW())"
            );
            $stmt->execute([$reviewerName, $rating, $reviewText]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO reviews (user_id, review_text, created_at)
                        VALUES (?, ?, NOW())"
            );
            $stmt->execute([$_SESSION['user_id'], $reviewText]);
        }

        set_flash('success', 'Thanks for your review!');
        redirect(APP_URL . '/reviews.php');
    } catch (PDOException $e) {
        error_log('Review submit error: ' . $e->getMessage());
        set_flash('danger', 'Review error: ' . $e->getMessage());
        redirect(APP_URL . '/reviews.php');
    }
}

// Now include header after form processing
require_once __DIR__ . '/includes/header.php';

// Fetch existing reviews
$reviews = [];
try {
    if ($usesAdminReviewSchema) {
        $stmt = $pdo->query(
            "SELECT r.comment AS review_text, r.created_at, r.name AS full_name, r.rating
               FROM reviews r
              ORDER BY r.created_at DESC"
        );
        $reviews = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query(
            "SELECT r.review_text, r.created_at, u.full_name
               FROM reviews r
               LEFT JOIN users u ON u.user_id = r.user_id
              ORDER BY r.created_at DESC"
        );
        $reviews = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $reviews = [];
}

?>

<section class="ld-section">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-8">
                <h1 class="ld-section-title mb-3">Reviews</h1>
                <p class="text-muted mb-4">See what other customers are saying and share your own experience.</p>

                <?php if (is_logged_in()): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Leave a review</h2>
                            <form method="POST" action="<?= APP_URL ?>/reviews.php">
                                <?php csrf_field(); ?>
                                <div class="mb-3">
                                    <label class="form-label" for="rating">Rating</label>
                                    <select id="rating" name="rating" class="form-select" required>
                                        <option value="">Select a rating</option>
                                        <option value="5">5 - Excellent</option>
                                        <option value="4">4 - Good</option>
                                        <option value="3">3 - Okay</option>
                                        <option value="2">2 - Poor</option>
                                        <option value="1">1 - Very poor</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="review">Your review</label>
                                    <textarea id="review" name="review" class="form-control" rows="4" required></textarea>
                                </div>
                                <button type="submit" class="ld-btn-primary">Submit review</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Please <a href="<?= APP_URL ?>/auth/login.php">log in</a> to leave a review.
                    </div>
                <?php endif; ?>

                <?php if (empty($reviews)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-chat-dots fs-1 text-muted"></i>
                        <h2 class="h5 mt-3">No reviews yet</h2>
                        <p class="text-muted">Be the first to share your experience!</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($reviews as $review):
                            $author = $review['full_name'] ?? 'Anonymous';
                            $date   = $review['created_at'] ?? null;
                            $date   = $date ? format_date($date) : '';
                        ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= e($author) ?></strong>
                                        <?php if ($date): ?>
                                            <span class="text-muted small">&middot; <?= e($date) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($review['rating'])): ?>
                                        <span class="ld-chip"><?= (int)$review['rating'] ?>/5</span>
                                    <?php endif; ?>
                                </div>
                                <p class="mt-2 mb-0"><?= nl2br(e($review['review_text'] ?? '')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
