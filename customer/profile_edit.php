<?php
// =============================================================
// customer/profile_edit.php — User profile editing form.
// =============================================================

$page_title   = 'Edit Profile';
$current_page = 'profile';
require_once __DIR__ . '/../includes/header.php';

// Require login
require_login();

$field_errors = $_SESSION['field_errors'] ?? [];
unset($_SESSION['field_errors']);

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Helper to fetch current user data
function fetchUserData(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("SELECT email, phone, full_name FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    return (array)$stmt->fetch();
}

// Initial values for form fields
$userData = fetchUserData($pdo, $_SESSION['user_id']);
$email     = old_input('email', $userData['email'] ?? '');
$phone     = old_input('phone', $userData['phone'] ?? '');
$fullName  = old_input('full_name', $userData['full_name'] ?? '');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $submittedToken)) {
        set_flash('danger', 'Invalid request. Please try again.');
        redirect(APP_URL . '/customer/profile_edit.php');
    }

    $email    = clean_input($_POST['email'] ?? '');
    $phone    = clean_input($_POST['phone'] ?? '');
    $fullName = clean_input($_POST['full_name'] ?? '');

    $_SESSION['field_errors'] = [];

    if ($fullName === '') {
        $_SESSION['field_errors']['full_name'] = 'Please enter your full name.';
    }

    if (mb_strlen($fullName) > 120) {
        $_SESSION['field_errors']['full_name'] = 'Full name must be 120 characters or fewer.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['field_errors']['email'] = 'Please provide a valid email address.';
    }

    if ($phone !== '' && !preg_match('/^\+?[0-9\s\-()]{8,20}$/', $phone)) {
        $_SESSION['field_errors']['phone'] = 'Please enter a valid phone number.';
    }

    if (!empty($_SESSION['field_errors'])) {
        set_old_input([
            'email' => $email,
            'phone' => $phone,
            'full_name' => $fullName,
        ]);
        set_flash('danger', 'Please correct the highlighted fields.');
        redirect(APP_URL . '/customer/profile_edit.php');
    }

    try {
        $stmt = $pdo->prepare(
            "UPDATE users
                SET email = ?, phone = ?, full_name = ?
              WHERE user_id = ?"
        );
        $stmt->execute([$email, $phone, $fullName, $_SESSION['user_id']]);

        // Update session name as well
        $_SESSION['full_name'] = $fullName;

        clear_old_input();
        unset($_SESSION['field_errors']);

        set_flash('success', 'Profile updated!');
        redirect(APP_URL . '/customer/dashboard.php');
    } catch (PDOException $e) {
        set_old_input([
            'email' => $email,
            'phone' => $phone,
            'full_name' => $fullName,
        ]);
        set_flash('danger', 'Failed to update profile. Please try again.');
        redirect(APP_URL . '/customer/profile_edit.php');
    }
}

?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title mb-3">Edit Profile</h1>
        <p class="text-muted mb-4">Update your contact details so we can keep you in the loop.</p>
        <?php show_flash(); ?>

        <div class="row">
            <div class="col-md-8">
                <form method="POST" action="<?= APP_URL ?>/customer/profile_edit.php" class="ld-form needs-validation" data-inline-validate="true" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="full_name">Full name <span class="text-danger" aria-hidden="true">*</span></label>
                        <input
                            id="full_name"
                            name="full_name"
                            type="text"
                            class="form-control <?= !empty($field_errors['full_name']) ? 'is-invalid' : '' ?>"
                            value="<?= e($fullName) ?>"
                            autocomplete="name"
                            aria-describedby="<?= !empty($field_errors['full_name']) ? 'full_name_error' : '' ?>"
                            required
                        >
                        <?php if (!empty($field_errors['full_name'])): ?>
                        <div id="full_name_error" class="invalid-feedback"><?= e($field_errors['full_name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email address <span class="text-danger" aria-hidden="true">*</span></label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            class="form-control <?= !empty($field_errors['email']) ? 'is-invalid' : '' ?>"
                            value="<?= e($email) ?>"
                            autocomplete="email"
                            aria-describedby="<?= !empty($field_errors['email']) ? 'email_error' : '' ?>"
                            required
                        >
                        <?php if (!empty($field_errors['email'])): ?>
                        <div id="email_error" class="invalid-feedback"><?= e($field_errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="phone">Phone number</label>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            class="form-control <?= !empty($field_errors['phone']) ? 'is-invalid' : '' ?>"
                            value="<?= e($phone) ?>"
                            autocomplete="tel"
                            pattern="^\+?[0-9\s\-()]{8,20}$"
                            aria-describedby="<?= !empty($field_errors['phone']) ? 'phone_error' : 'phone_help' ?>"
                        >
                        <div id="phone_help" class="form-text">Use 8-20 characters. Digits, spaces, +, -, and parentheses are allowed.</div>
                        <?php if (!empty($field_errors['phone'])): ?>
                        <div id="phone_error" class="invalid-feedback d-block"><?= e($field_errors['phone']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="ld-btn-primary">Save changes</button>
                    <a href="<?= APP_URL ?>/customer/dashboard.php" class="ld-btn-outline ms-2">Cancel</a>
                </form>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>