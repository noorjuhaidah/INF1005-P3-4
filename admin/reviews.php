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

    $pickColumn = static function (array $candidates, array $columns): string {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }
        return '';
    };

    $reviewIdColumn = $pickColumn(['id', 'review_id'], $reviewColumns);
    $reviewTextColumn = $pickColumn(['comment', 'review_text', 'review', 'feedback'], $reviewColumns);
    $reviewNameColumn = $pickColumn(['name', 'reviewer_name', 'full_name'], $reviewColumns);
    $reviewRatingColumn = $pickColumn(['rating', 'stars'], $reviewColumns);
    $reviewUserIdColumn = $pickColumn(['user_id', 'customer_id'], $reviewColumns);
    $reviewCreatedAtColumn = $pickColumn(['created_at', 'created_on', 'review_date'], $reviewColumns);
    $userPrimaryKeyColumn = 'user_id';

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

    if ($reviewIdColumn === '' || $reviewTextColumn === '') {
        throw new RuntimeException('No supported review ID/text columns found in reviews table.');
    }

    $selectParts = ["r.{$reviewIdColumn} AS review_id"];
    $selectParts[] = "r.{$reviewTextColumn} AS review_text";
    $selectParts[] = $reviewCreatedAtColumn !== '' ? "r.{$reviewCreatedAtColumn} AS created_at" : "NULL AS created_at";

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
        $sql .= " LEFT JOIN users u ON u.{$userPrimaryKeyColumn} = r.{$reviewUserIdColumn}";
    }
    if ($reviewCreatedAtColumn !== '') {
        $sql .= " ORDER BY r.{$reviewCreatedAtColumn} DESC";
    } else {
        $sql .= " ORDER BY r.{$reviewIdColumn} DESC";
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
                    <caption class="visually-hidden">Reviews table listing reviewer details, rating, comment, created date, and row actions.</caption>
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Rating</th>
                            <th scope="col">Comment</th>
                            <th scope="col">Created</th>
                            <th scope="col">Actions</th>
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
                                <th scope="row"><?= e((string) $review['review_id']) ?></th>
                                <td><?= e((string) ($review['reviewer_name'] ?? 'Anonymous')) ?></td>
                                <td><?= $review['rating'] !== null ? e((string) $review['rating']) . '/5' : '-' ?></td>
                                <td><?= nl2br(e((string) ($review['review_text'] ?? ''))) ?></td>
                                <td><?= e($createdAt) ?></td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="<?= APP_URL ?>/admin/edit_review.php?id=<?= e((string) $review['review_id']) ?>" class="btn btn-sm btn-outline-primary" aria-label="Edit review <?= e((string) $review['review_id']) ?>">Edit</a>
                                        <a href="<?= APP_URL ?>/admin/delete_review.php?id=<?= e((string) $review['review_id']) ?>" class="btn btn-sm btn-outline-danger" aria-label="Delete review <?= e((string) $review['review_id']) ?>">Delete</a>
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
