<?php
// reviews.php — for public reviews page
// Allows logged-in users to leave a review, and shows existing reviews.
// Uses the shared CSRF helpers from includes/functions.php

$page_title = 'Reviews';
$current_page = 'reviews';

// load DB + helpers before header so POST/redirect logic can run safely.
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

$reviewTextColumn = in_array('comment', $reviewColumns, true) ? 'comment' : (in_array('review_text', $reviewColumns, true) ? 'review_text' : '');
$reviewNameColumn = in_array('name', $reviewColumns, true) ? 'name' : '';
$reviewRatingColumn = in_array('rating', $reviewColumns, true) ? 'rating' : '';
$reviewUserIdColumn = in_array('user_id', $reviewColumns, true) ? 'user_id' : '';

$quoteIdent = static function (string $identifier): string {
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
        throw new RuntimeException('Unsafe SQL identifier: ' . $identifier);
    }
    return '`' . $identifier . '`';
};

// handle new review submission before header output so redirects work
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {

    // uses shared CSRF helper — falls back to reviews.php if failed, so safe to call before header output
    verify_csrf(APP_URL . '/reviews.php');

    $reviewText = clean_input($_POST['review'] ?? '');
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $reviewerName = clean_input((string) ($_SESSION['full_name'] ?? 'Customer'));

    if (empty($reviewText)) {
        set_flash('warning', 'Please enter a review before submitting.');
        redirect(APP_URL . '/reviews.php');
    }

    if (mb_strlen($reviewText) > 1000) {
        set_flash('warning', 'Review must be 1000 characters or fewer.');
        redirect(APP_URL . '/reviews.php');
    }

    if (mb_strlen($reviewerName) > 120) {
        $reviewerName = mb_substr($reviewerName, 0, 120);
    }

    if (!$rating || $rating < 1 || $rating > 5) {
        set_flash('warning', 'Please choose a rating from 1 to 5.');
        redirect(APP_URL . '/reviews.php');
    }

    try {
        $insertColumns = [];
        $insertValues = [];
        $insertParams = [];

        if ($reviewNameColumn !== '') {
            $insertColumns[] = $reviewNameColumn;
            $insertValues[] = '?';
            $insertParams[] = $reviewerName;
        }

        if ($reviewRatingColumn !== '') {
            $insertColumns[] = $reviewRatingColumn;
            $insertValues[] = '?';
            $insertParams[] = $rating;
        }

        if ($reviewUserIdColumn !== '') {
            $insertColumns[] = $reviewUserIdColumn;
            $insertValues[] = '?';
            $insertParams[] = (int) $_SESSION['user_id'];
        }

        if ($reviewTextColumn === '') {
            throw new PDOException('No supported review text column found.');
        }

        $insertColumns[] = $reviewTextColumn;
        $insertValues[] = '?';
        $insertParams[] = $reviewText;

        if (in_array('created_at', $reviewColumns, true)) {
            $insertColumns[] = 'created_at';
            $insertValues[] = 'NOW()';
        }

        $safeInsertColumns = array_map($quoteIdent, $insertColumns);
        $stmt = $pdo->prepare(
            "INSERT INTO reviews (" . implode(', ', $safeInsertColumns) . ")
                VALUES (" . implode(', ', $insertValues) . ")"
        );
        $stmt->execute($insertParams);

        set_flash('success', 'Thanks for your review!');
        redirect(APP_URL . '/reviews.php');
    } catch (PDOException $e) {
        error_log('Review submit error: ' . $e->getMessage());
        set_flash('danger', 'Unable to submit your review right now. Please try again.');
        redirect(APP_URL . '/reviews.php');
    }
}

// include header after form processing
require_once __DIR__ . '/includes/header.php';

// fetches existing reviews
$reviews = [];
try {
    $selectParts = [];
    $selectParts[] = $reviewTextColumn !== '' ? "r." . $quoteIdent($reviewTextColumn) . " AS review_text" : "'' AS review_text";
    $selectParts[] = in_array('created_at', $reviewColumns, true) ? "r.`created_at`" : "NULL AS created_at";

    if ($reviewNameColumn !== '') {
        $selectParts[] = "r." . $quoteIdent($reviewNameColumn) . " AS full_name";
    } elseif ($reviewUserIdColumn !== '') {
        $selectParts[] = "u.`full_name` AS full_name";
    } else {
        $selectParts[] = "'Anonymous' AS full_name";
    }

    if ($reviewRatingColumn !== '') {
        $selectParts[] = "r." . $quoteIdent($reviewRatingColumn) . " AS rating";
    } else {
        $selectParts[] = "NULL AS rating";
    }

    $sql = "SELECT " . implode(', ', $selectParts) . " FROM reviews r";
    if ($reviewNameColumn === '' && $reviewUserIdColumn !== '') {
        $sql .= " LEFT JOIN users u ON u.`user_id` = r." . $quoteIdent($reviewUserIdColumn);
    }
    if (in_array('created_at', $reviewColumns, true)) {
        $sql .= " ORDER BY r.`created_at` DESC";
    }

    $stmt = $pdo->query($sql);
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    $reviews = [];
}

?>

<section class="ld-section">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-8">
                <h1 class="ld-section-title mb-3">
                    <i class="fa-solid fa-star me-2" aria-hidden="true"></i>Reviews
                </h1>
                <p class="text-muted mb-4">See what other customers are saying and share your own experience.</p>

                <?php if (is_logged_in()): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="h5 mb-3">
                                <i class="fa-solid fa-pen-to-square me-2" aria-hidden="true"></i>Leave a review
                            </h2>
                            <form method="POST" action="<?= APP_URL ?>/reviews.php" class="needs-validation"
                                data-inline-validate="true" novalidate>
                                <?php csrf_field(); ?>
                                <div class="mb-3">
                                    <label class="form-label" for="rating">
                                        <i class="fa-solid fa-star me-1" aria-hidden="true"></i>Rating
                                    </label>
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
                                    <label class="form-label" for="review">
                                        <i class="fa-solid fa-comment me-1" aria-hidden="true"></i>Your review
                                    </label>
                                    <textarea id="review" name="review" class="form-control" rows="4" maxlength="1000"
                                        required></textarea>
                                </div>
                                <button type="submit" class="ld-btn-primary">
                                    <i class="fa-solid fa-paper-plane me-1" aria-hidden="true"></i>Submit review
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>
                        Please <a href="<?= APP_URL ?>/auth/login.php">log in</a> to leave a review.
                    </div>
                <?php endif; ?>

                <?php if (empty($reviews)): ?>
                    <div class="text-center py-5">
                        <i class="fa-regular fa-comment-dots fs-1 text-muted" aria-hidden="true"></i>
                        <h2 class="h5 mt-3">No reviews yet</h2>
                        <p class="text-muted">Be the first to share your experience!</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($reviews as $review):
                            $author = $review['full_name'] ?? 'Anonymous';
                            $date = $review['created_at'] ?? null;
                            $date = $date ? format_date($date) : '';
                            ?>
                            <article class="list-group-item" aria-label="Customer review by <?= e($author) ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>
                                            <i class="fa-solid fa-user me-1" aria-hidden="true"></i><?= e($author) ?>
                                        </strong>
                                        <?php if ($date): ?>
                                            <span class="text-muted small">&middot; <i class="fa-regular fa-calendar me-1" aria-hidden="true"></i><?= e($date) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($review['rating'])): ?>
                                        <span class="ld-chip">
                                            <i class="fa-solid fa-star me-1" aria-hidden="true"></i><?= (int) $review['rating'] ?>/5
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="mt-2 mb-0"><?= nl2br(e($review['review_text'] ?? '')) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>