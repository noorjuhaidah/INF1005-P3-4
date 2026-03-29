<?php
$page_title = 'Login';
$current_page = '';

require_once __DIR__ . '/../includes/header.php';

redirect_if_logged_in();
enforce_https();

$field_errors = $_SESSION['field_errors'] ?? [];
unset($_SESSION['field_errors']);
?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title text-center">Login</h1>
        <p class="ld-section-subtitle text-center">Login to continue.</p>

        <?php show_flash(); ?>

        <div class="ld-form-card">
            <form method="post" action="<?= APP_URL ?>/auth/process_login.php" class="needs-validation" data-inline-validate="true" novalidate>
                <?php csrf_field(); ?>

                <div class="mb-3">
                    <label class="form-label" for="email">Email <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email"
                           value="<?= e(old_input('email')) ?>"
                           autocomplete="email" required
                           aria-describedby="<?= !empty($field_errors['email']) ? 'email_error' : '' ?>"
                           class="form-control <?= !empty($field_errors['email']) ? 'is-invalid' : '' ?>">
                    <div id="email_error" class="invalid-feedback"><?= e($field_errors['email'] ?? 'Please enter a valid email address.') ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password <span class="text-danger" aria-hidden="true">*</span></label>
                    <div class="input-group">
                        <input type="password" id="password" name="password"
                               autocomplete="current-password" required
                               aria-describedby="<?= !empty($field_errors['password']) ? 'password_error' : '' ?>"
                               class="form-control <?= !empty($field_errors['password']) ? 'is-invalid' : '' ?>">
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword"
                                aria-label="Show password" aria-pressed="false">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <?php if (!empty($field_errors['password'])): ?>
                    <div id="password_error" class="invalid-feedback d-block"><?= e($field_errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="ld-btn-primary">Log In</button>
            </form>

            <p class="mt-3 text-center">
                Don’t have an account?
                <a class="ld-auth-link" href="<?= APP_URL ?>/auth/register.php">Register here</a>.
            </p>
        </div>
    </div>
</section>

<script>
const togglePasswordBtn = document.getElementById('togglePassword');
if (togglePasswordBtn) {
    togglePasswordBtn.addEventListener('click', function () {
        const password = document.getElementById('password');
        const icon = this.querySelector('i');
        if (!password || !icon) return;

        const isVisible = password.type === 'text';

        password.type = isVisible ? 'password' : 'text';
        icon.className = isVisible ? 'bi bi-eye' : 'bi bi-eye-slash';
        this.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
        this.setAttribute('aria-pressed', !isVisible);
    });
}
</script>

<?php clear_old_input(); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>