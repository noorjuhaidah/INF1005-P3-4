<?php
// =============================================================
// customer/profile_edit.php — User profile editing form.
// =============================================================

$page_title   = 'Edit Profile';
$current_page = 'profile';
require_once __DIR__ . '/../includes/header.php';

// Require login
require_login();

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
$email     = $userData['email'] ?? '';
$phone     = $userData['phone'] ?? '';
$fullName  = $userData['full_name'] ?? '';

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

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Please provide a valid email address.');
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

        set_flash('success', 'Profile updated!');
        redirect(APP_URL . '/customer/dashboard.php');
    } catch (PDOException $e) {
        set_flash('danger', 'Failed to update profile. Please try again.');
        redirect(APP_URL . '/customer/profile_edit.php');
    }
}

?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title mb-3">Edit Profile</h1>
        <p class="text-muted mb-4">Update your contact details so we can keep you in the loop.</p>

        <div class="row">
            <div class="col-md-8">
                <form method="POST" action="<?= APP_URL ?>/customer/profile_edit.php" class="ld-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="full_name">Full name</label>
                        <input
                            id="full_name"
                            name="full_name"
                            type="text"
                            class="form-control"
                            value="<?= e($fullName) ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            class="form-control"
                            value="<?= e($email) ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="phone">Phone number</label>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            class="form-control"
                            value="<?= e($phone) ?>"
                        >
                    </div>

                    <button type="submit" class="ld-btn-primary">Save changes</button>
                    <a href="<?= APP_URL ?>/customer/dashboard.php" class="ld-btn-outline ms-2">Cancel</a>
                </form>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>