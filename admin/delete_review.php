<?php
require_once __DIR__ . '/../includes/admin_bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id) {
    set_flash('danger', 'Invalid review ID.');
    redirect(APP_URL . '/admin/reviews.php#flash-container');
}

try {
    // Get columns from reviews table
    $reviewColumns = [];
    $columnsStmt = $pdo->query("SHOW COLUMNS FROM reviews");
    foreach ($columnsStmt->fetchAll() as $column) {
        if (!empty($column['Field'])) {
            $reviewColumns[] = $column['Field'];
        }
    }

    // Helper to safely quote identifiers
    $quoteIdent = static function (string $identifier): string {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new RuntimeException('Unsafe SQL identifier: ' . $identifier);
        }
        return '`' . $identifier . '`';
    };

    // Determine review ID column
    $reviewIdColumn = '';
    if (in_array('id', $reviewColumns, true)) {
        $reviewIdColumn = 'id';
    } elseif (in_array('review_id', $reviewColumns, true)) {
        $reviewIdColumn = 'review_id';
    }

    if ($reviewIdColumn === '') {
        throw new RuntimeException('No supported review ID column found in reviews table.');
    }

    // Detect review text column
    $reviewTextColumn = '';
    if (in_array('comment', $reviewColumns, true)) {
        $reviewTextColumn = 'comment';
    } elseif (in_array('review_text', $reviewColumns, true)) {
        $reviewTextColumn = 'review_text';
    } elseif (in_array('review', $reviewColumns, true)) {
        $reviewTextColumn = 'review';
    } elseif (in_array('feedback', $reviewColumns, true)) {
        $reviewTextColumn = 'feedback';
    }

    // Detect reviewer name column
    $reviewerNameColumn = '';
    if (in_array('name', $reviewColumns, true)) {
        $reviewerNameColumn = 'name';
    } elseif (in_array('reviewer_name', $reviewColumns, true)) {
        $reviewerNameColumn = 'reviewer_name';
    } elseif (in_array('full_name', $reviewColumns, true)) {
        $reviewerNameColumn = 'full_name';
    }

    // Show confirmation page
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $selectParts = [$quoteIdent($reviewIdColumn) . " AS review_id"];
        $selectParts[] = $reviewTextColumn !== '' ? $quoteIdent($reviewTextColumn) . " AS review_text" : "'' AS review_text";
        $selectParts[] = $reviewerNameColumn !== '' ? $quoteIdent($reviewerNameColumn) . " AS reviewer_name" : "'' AS reviewer_name";

        // Fetch review details
        $lookupStmt = $pdo->prepare(
            "SELECT " . implode(', ', $selectParts) . " FROM reviews WHERE " . $quoteIdent($reviewIdColumn) . " = ? LIMIT 1"
        );
        $lookupStmt->execute([$id]);
        $review = $lookupStmt->fetch();

        // If not found, redirect
        if (!$review) {
            set_flash('warning', 'Review not found or already deleted.');
            redirect(APP_URL . '/admin/reviews.php#flash-container');
        }

        // Load page layout
        $page_title = 'Delete Review - Admin';
        $current_page = 'admin';
        require_once __DIR__ . '/../includes/header.php';
?>

        <!-- Delete confirmation UI -->
        <section class="ld-section">
            <div class="container" style="max-width: 680px;">
                <h1 class="ld-section-title">Delete Review</h1>
                <p class="ld-section-subtitle">Confirm permanent deletion.</p>

                <div class="card ld-card p-4">
                    <p class="mb-2"><strong>Review ID:</strong> <?= e((string) ($review['review_id'] ?? $id)) ?></p>
                    <?php if (!empty($review['reviewer_name'])): ?>
                        <p class="mb-2"><strong>Reviewer:</strong> <?= e((string) $review['reviewer_name']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($review['review_text'])): ?>
                        <p class="mb-2"><strong>Comment:</strong>
                            <?= e(mb_strimwidth((string) $review['review_text'], 0, 140, '...')) ?></p>
                    <?php endif; ?>

                    <!-- Warning message -->
                    <p class="text-danger mb-4">This action cannot be undone.</p>

                    <!-- Delete form -->
                    <form method="POST" action="<?= APP_URL ?>/admin/delete_review.php?id=<?= (int) $id ?>"
                        class="d-flex gap-2 flex-wrap">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= (int) $id ?>">

                        <!-- Confirm delete button -->
                        <button type="submit" class="btn btn-danger"
                            aria-label="Confirm delete review <?= e((string) ($review['review_id'] ?? $id)) ?>">
                            Confirm Delete Review
                        </button>
                        <a href="<?= APP_URL ?>/admin/reviews.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </section>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
        exit;
    }

    // Handle deletion on POST request
    verify_csrf(APP_URL . '/admin/delete_review.php?id=' . (int) $id);

    // Execute delete query
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE " . $quoteIdent($reviewIdColumn) . " = ?");
    $stmt->execute([$id]);

    // Check if any row was deleted
    if ($stmt->rowCount() > 0) {
        set_flash('success', 'Review deleted successfully.');
    } else {
        set_flash('warning', 'Review not found or already deleted.');
    }
} catch (Throwable $e) {
    // Log error for debugging, but show generic message to user
    error_log('Admin review delete error: ' . $e->getMessage());
    set_flash('danger', 'Failed to delete review.');
}

// Redirect to reviews page
redirect(APP_URL . '/admin/reviews.php#flash-container');
