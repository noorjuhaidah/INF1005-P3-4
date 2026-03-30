<?php
$page_title = 'Register';
$current_page = '';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

enforce_https();
redirect_if_logged_in();

require_once __DIR__ . '/../includes/header.php';

$field_errors = $_SESSION['field_errors'] ?? [];
unset($_SESSION['field_errors']);
?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title text-center">Create Account</h1>
        <p class="ld-section-subtitle text-center">
            Join LazyDrip and earn <?= POINTS_SIGNUP_BONUS ?> bonus points!
        </p>

        <?php show_flash(); ?>

        <div class="ld-form-card">
            <form method="post" action="<?= APP_URL ?>/auth/process_register.php" class="needs-validation" novalidate>

                <?php csrf_field(); ?>

                <div class="mb-3">
                    <label class="form-label" for="full_name">Full Name <span class="text-danger"
                            aria-hidden="true">*</span></label>
                    <input type="text" id="full_name" name="full_name" required autocomplete="name"
                        placeholder="Jane Tan" value="<?= e(old_input('full_name')) ?>"
                        aria-describedby="<?= !empty($field_errors['full_name']) ? 'full_name_error' : '' ?>"
                        class="form-control <?= !empty($field_errors['full_name']) ? 'is-invalid' : '' ?>">
                    <div id="full_name_error" class="invalid-feedback">
                        <?= e($field_errors['full_name'] ?? 'Please enter your full name.') ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">Email <span class="text-danger"
                            aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                        placeholder="you@example.com" value="<?= e(old_input('email')) ?>"
                        aria-describedby="<?= !empty($field_errors['email']) ? 'email_error' : '' ?>"
                        class="form-control <?= !empty($field_errors['email']) ? 'is-invalid' : '' ?>">
                    <div id="email_error" class="invalid-feedback">
                        <?= e($field_errors['email'] ?? 'Please enter a valid email.') ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="phone">
                        Phone <span class="text-muted">(optional)</span>
                    </label>
                    <input class="form-control <?= !empty($field_errors['phone']) ? 'is-invalid' : '' ?>" type="tel"
                        id="phone" name="phone" autocomplete="tel" placeholder="+65 9123 4567"
                        pattern="^\+?[0-9\s\-()]{8,20}$"
                        aria-describedby="<?= !empty($field_errors['phone']) ? 'phone_error' : 'phone_help' ?>"
                        value="<?= e(old_input('phone')) ?>">
                    <div id="phone_help" class="form-text">Use 8-20 characters. Digits, spaces, +, -, and parentheses
                        are allowed.</div>
                    <?php if (!empty($field_errors['phone'])): ?>
                        <div id="phone_error" class="invalid-feedback d-block"><?= e($field_errors['phone']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password <span class="text-danger"
                            aria-hidden="true">*</span></label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" required minlength="8"
                            autocomplete="new-password" placeholder="Min. 8 characters"
                            aria-describedby="<?= !empty($field_errors['password']) ? 'password_error' : '' ?>"
                            class="form-control <?= !empty($field_errors['password']) ? 'is-invalid' : '' ?>">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                            aria-label="Show password" aria-pressed="false">
                            <i class="bi bi-eye" id="toggleIcon" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div id="password_error" class="invalid-feedback d-block"><?= e($field_errors['password'] ?? '') ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="confirm_password">Confirm Password <span class="text-danger"
                            aria-hidden="true">*</span></label>
                    <div class="input-group">
                        <input type="password" id="confirm_password" name="confirm_password" required
                            autocomplete="new-password" placeholder="Repeat your password"
                            aria-describedby="<?= !empty($field_errors['confirm_password']) ? 'confirm_password_error' : '' ?>"
                            class="form-control <?= !empty($field_errors['confirm_password']) ? 'is-invalid' : '' ?>">
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword"
                            aria-label="Show password" aria-pressed="false">
                            <i class="bi bi-eye" id="toggleConfirmIcon" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div id="confirm_password_error" class="invalid-feedback d-block">
                        <?= e($field_errors['confirm_password'] ?? '') ?></div>
                </div>

                <button type="submit" class="ld-btn-primary">Create Account</button>
            </form>

            <hr class="mt-4">
            <p class="text-center text-muted small mb-0">
                Already have an account?
                <a href="<?= APP_URL ?>/auth/login.php" class="fw-semibold ld-auth-link">Log in</a>
            </p>
        </div>
    </div>
</section>

<script>
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const toggleIcon = document.getElementById('toggleIcon');

    const toggleConfirmBtn = document.getElementById('toggleConfirmPassword');
    const toggleConfirmIcon = document.getElementById('toggleConfirmIcon');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            toggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            this.setAttribute('aria-pressed', !isPassword);
        });
    }

    if (toggleConfirmBtn) {
        toggleConfirmBtn.addEventListener('click', function () {
            const isPassword = confirmPasswordInput.type === 'password';
            confirmPasswordInput.type = isPassword ? 'text' : 'password';
            toggleConfirmIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            this.setAttribute('aria-pressed', !isPassword);
        });
    }
</script>

<?php clear_old_input(); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>