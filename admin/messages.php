<?php
$page_title = 'Customer Inquiries';
$current_page = 'admin';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Restrict access to admins only
require_admin();

// Initialise variables for messages
$messageColumns = [];
$messages = [];
$loadError = '';

// Column name placeholders
$messageIdColumn = '';
$messageNameColumn = '';
$messageEmailColumn = '';
$messageSubjectColumn = '';
$messageBodyColumn = '';
$messageCreatedAtColumn = '';
$messageReadColumn = '';
$messageUserIdColumn = '';

// Helper to pick first matching column name
$pickColumn = static function (array $candidates, array $columns): string {
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }
    return '';
};

// Helper to quote column names
$quoteIdent = static function (string $identifier): string {
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
        throw new RuntimeException('Unsafe SQL identifier: ' . $identifier);
    }
    return '`' . $identifier . '`';
};

try {
    // Get column names from contact_messages table
    $columnsStmt = $pdo->query("SHOW COLUMNS FROM contact_messages");
    foreach ($columnsStmt->fetchAll() as $column) {
        if (!empty($column['Field'])) {
            $messageColumns[] = $column['Field'];
        }
    }

    // Detect column names dynamically
    $messageIdColumn = $pickColumn(['message_id', 'id'], $messageColumns);
    $messageNameColumn = $pickColumn(['name', 'full_name'], $messageColumns);
    $messageEmailColumn = $pickColumn(['email'], $messageColumns);
    $messageSubjectColumn = $pickColumn(['subject', 'title'], $messageColumns);
    $messageBodyColumn = $pickColumn(['message', 'message_text', 'content'], $messageColumns);
    $messageCreatedAtColumn = $pickColumn(['created_at', 'submitted_at', 'created_on'], $messageColumns);
    $messageReadColumn = $pickColumn(['is_read', 'read_status'], $messageColumns);
    $messageUserIdColumn = $pickColumn(['user_id', 'customer_id'], $messageColumns);

    // Ensure essential columns exist
    if ($messageIdColumn === '' || $messageBodyColumn === '') {
        throw new RuntimeException('No supported message ID/body columns found in contact_messages table.');
    }

    // Handle read/unread action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $messageReadColumn !== '') {
        verify_csrf(APP_URL . '/admin/messages.php');

        // Get message ID and action
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $actionRaw = filter_input(INPUT_POST, 'mark_action', FILTER_UNSAFE_RAW);
        $action = is_string($actionRaw) ? trim($actionRaw) : '';
        
        // Only proceed if valid
        if ($id && in_array($action, ['read', 'unread'], true)) {
            $newValue = $action === 'read' ? 1 : 0;
            // Update read status
            $updateStmt = $pdo->prepare("
                UPDATE contact_messages
                SET " . $quoteIdent($messageReadColumn) . " = ?
                WHERE " . $quoteIdent($messageIdColumn) . " = ?
            ");
            $updateStmt->execute([$newValue, $id]);
        }

        // Redirect to refresh page
        redirect(APP_URL . '/admin/messages.php');
    }

    // Build select query
    $selectParts = ["m." . $quoteIdent($messageIdColumn) . " AS message_id"];
    $selectParts[] = $messageBodyColumn !== '' ? "m." . $quoteIdent($messageBodyColumn) . " AS message_body" : "'' AS message_body";
    $selectParts[] = $messageSubjectColumn !== '' ? "m." . $quoteIdent($messageSubjectColumn) . " AS message_subject" : "'' AS message_subject";
    $selectParts[] = $messageCreatedAtColumn !== '' ? "m." . $quoteIdent($messageCreatedAtColumn) . " AS created_at" : "NULL AS created_at";
    $selectParts[] = $messageReadColumn !== '' ? "m." . $quoteIdent($messageReadColumn) . " AS is_read" : "NULL AS is_read";

    // Determine if user table join is needed
    $joinUsers = false;
    if ($messageNameColumn !== '') {
        $selectParts[] = "m." . $quoteIdent($messageNameColumn) . " AS sender_name";
    } else {
        $selectParts[] = "'Anonymous' AS sender_name";
        if ($messageUserIdColumn !== '') {
            $joinUsers = true;
        }
    }

    if ($messageEmailColumn !== '') {
        $selectParts[] = "m." . $quoteIdent($messageEmailColumn) . " AS sender_email";
    } else {
        $selectParts[] = "'' AS sender_email";
        if ($messageUserIdColumn !== '') {
            $joinUsers = true;
        }
    }

    $sql = "SELECT " . implode(', ', $selectParts) . " FROM contact_messages m";
    if ($joinUsers) {
        $userColumns = [];
        $userColumnsStmt = $pdo->query("SHOW COLUMNS FROM users");
        foreach ($userColumnsStmt->fetchAll() as $column) {
            if (!empty($column['Field'])) {
                $userColumns[] = $column['Field'];
            }
        }

        $userPrimaryKeyColumn = $pickColumn(['user_id', 'id'], $userColumns);
        $userNameColumn = $pickColumn(['full_name', 'name'], $userColumns);
        $userEmailColumn = $pickColumn(['email'], $userColumns);

        if ($userPrimaryKeyColumn !== '') {
            $sql = "SELECT " . implode(', ', $selectParts) . ",
                           " . ($userNameColumn !== '' ? "u." . $quoteIdent($userNameColumn) : "'Anonymous'") . " AS user_name_fallback,
                           " . ($userEmailColumn !== '' ? "u." . $quoteIdent($userEmailColumn) : "''") . " AS user_email_fallback
                FROM contact_messages m
                LEFT JOIN users u ON u." . $quoteIdent($userPrimaryKeyColumn) . " = m." . $quoteIdent($messageUserIdColumn);
        }
    }

    // Sort messages (latest first)
    if ($messageCreatedAtColumn !== '') {
        $sql .= " ORDER BY m." . $quoteIdent($messageCreatedAtColumn) . " DESC";
    } else {
        $sql .= " ORDER BY m." . $quoteIdent($messageIdColumn) . " DESC";
    }

    // Execute query
    $messagesStmt = $pdo->query($sql);
    $messages = $messagesStmt->fetchAll();

    foreach ($messages as &$message) {
        if (($message['sender_name'] ?? '') === 'Anonymous' && !empty($message['user_name_fallback'])) {
            $message['sender_name'] = $message['user_name_fallback'];
        }
        if (($message['sender_email'] ?? '') === '' && !empty($message['user_email_fallback'])) {
            $message['sender_email'] = $message['user_email_fallback'];
        }
    }
    unset($message);
} catch (Throwable $e) {
    error_log('Admin messages load error: ' . $e->getMessage());
    $loadError = 'Unable to load contact inquiries right now.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="ld-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="ld-section-title">Customer Inquiries</h1>
                <p class="ld-section-subtitle">Messages submitted via Contact Us.</p>
            </div>
            <a href="<?= APP_URL ?>/admin/dashboard.php" class="ld-btn-outline">Back to Dashboard</a>
        </div>

        <?php if ($loadError !== ''): ?>
            <div class="alert alert-danger"><?= e($loadError) ?></div>
        <?php endif; ?>

        <div class="card ld-card p-4">
            <div class="table-responsive">
                <table class="table align-middle">
                    <caption class="visually-hidden">Customer inquiries table listing sender details, message content,
                        submitted date, status, and row action.</caption>
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Subject</th>
                            <th scope="col">Message</th>
                            <th scope="col">Submitted</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="7">No inquiries found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <?php
                                $createdAt = '';
                                if (!empty($message['created_at'])) {
                                    $createdAt = format_date($message['created_at']);
                                }
                                $messageId = (string) ($message['message_id'] ?? '');
                                $isRead = isset($message['is_read']) ? (int) $message['is_read'] === 1 : null;
                                ?>
                                <tr>
                                    <th scope="row"><?= e($messageId) ?></th>
                                    <td><?= e((string) ($message['sender_name'] ?? 'Anonymous')) ?></td>
                                    <td><?= e((string) ($message['sender_email'] ?? '')) ?></td>
                                    <td><?= e((string) ($message['message_subject'] ?? '')) ?></td>
                                    <td style="min-width: 260px; white-space: normal;">
                                        <?= nl2br(e((string) ($message['message_body'] ?? ''))) ?></td>
                                    <td><?= e($createdAt) ?></td>
                                    <td>
                                        <?php if ($isRead === null): ?>
                                            <span class="text-muted">N/A</span>
                                        <?php elseif ($isRead): ?>
                                            <span class="badge bg-success-subtle text-success-emphasis"
                                                aria-label="Message status: Read">Read</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis"
                                                aria-label="Message status: Unread">Unread</span>
                                        <?php endif; ?>

                                        <?php if ($messageReadColumn !== ''): ?>
                                            <form method="post" action="<?= APP_URL ?>/admin/messages.php" class="mt-2"
                                                aria-label="Update read status for message <?= e($messageId) ?>">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="id" value="<?= e($messageId) ?>">
                                                <input type="hidden" name="mark_action" value="<?= $isRead ? 'unread' : 'read' ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                    aria-label="Mark message <?= e($messageId) ?> as <?= $isRead ? 'Unread' : 'Read' ?>">
                                                    Mark as <?= $isRead ? 'Unread' : 'Read' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
