<?php
$page_title = 'Manage Reviews';
$current_page = 'admin';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_admin();

$reviewColumns = [];
$reviews = [];
$loadError = '';

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

    $selectParts = ['r.id'];
    $selectParts[] = "r.{$reviewTextColumn} AS review_text";
    $selectParts[] = in_array('created_at', $reviewColumns, true) ? "r.created_at" : "NULL AS created_at";

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
    if (in_array('created_at', $reviewColumns, true)) {
        $sql .= " ORDER BY r.created_at DESC";
    } else {
        $sql .= " ORDER BY r.id DESC";
    }

    $reviewsStmt = $pdo->query($sql);
    $reviews = $reviewsStmt->fetchAll();
} catch (Throwable $e) {
    error_log('Admin review load error: ' . $e->getMessage());
    $loadError = 'Unable to load reviews right now. Please check your reviews table structure.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="ld-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="ld-section-title">Review Management</h1>
                <p class="ld-section-subtitle">View, edit, and remove customer reviews.</p>
            </div>
            <a href="<?= APP_URL ?>/admin/dashboard.php" class="ld-btn-outline">Back to Dashboard</a>
        </div>

        <?php if ($loadError !== ''): ?>
            <div class="alert alert-danger"><?= e($loadError) ?></div>
        <?php endif; ?>

        <div class="card ld-card p-4">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="6">No reviews found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <?php
                            $createdAt = '';
                            if (!empty($review['created_at'])) {
                                $createdAt = format_date($review['created_at']);
                            }
                            ?>
                            <tr>
                                <td><?= e((string) $review['id']) ?></td>
                                <td><?= e((string) ($review['reviewer_name'] ?? 'Anonymous')) ?></td>
                                <td><?= $review['rating'] !== null ? e((string) $review['rating']) . '/5' : '-' ?></td>
                                <td><?= nl2br(e((string) ($review['review_text'] ?? ''))) ?></td>
                                <td><?= e($createdAt) ?></td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="<?= APP_URL ?>/admin/edit_review.php?id=<?= e((string) $review['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="post" action="<?= APP_URL ?>/admin/delete_review.php" onsubmit="return confirm('Delete this review?');">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?= e((string) $review['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
