<?php
$page_title = 'Register';
$current_page = '';

require_once __DIR__ . '/../includes/header.php';

redirect_if_logged_in();
?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title text-center">Create Account</h1>
        <p class="ld-section-subtitle text-center">
            Join LazyDrip and earn <?= POINTS_SIGNUP_BONUS ?> bonus points!
        </p>

        <?php show_flash(); ?>

        <div class="ld-form-card">
            <form method="post" action="<?= APP_URL ?>/auth/process_register.php"
                  class="needs-validation" novalidate>
                
                <?php csrf_field(); ?>

                <div class="mb-3">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input class="form-control" type="text" id="full_name" name="full_name"
                           required autocomplete="name" placeholder="Jane Tan"
                           value="<?= e(old_input('full_name')) ?>">
                    <div class="invalid-feedback">Please enter your full name.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email"
                           required autocomplete="email" placeholder="you@example.com"
                           value="<?= e(old_input('email')) ?>">
                    <div class="invalid-feedback">Please enter a valid email.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="phone">
                        Phone <span class="text-muted">(optional)</span>
                    </label>
                    <input class="form-control" type="tel" id="phone" name="phone"
                           autocomplete="tel" placeholder="+65 9123 4567"
                           value="<?= e(old_input('phone')) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input class="form-control" type="password" id="password" name="password"
                               required minlength="8" autocomplete="new-password"
                               placeholder="Min. 8 characters">
                        <button class="btn btn-outline-secondary" type="button"
                                id="togglePassword" aria-label="Show or hide password">
                            <i class="bi bi-eye" id="toggleIcon" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">Password must be at least 8 characters.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input class="form-control" type="password" id="confirm_password"
                           name="confirm_password" required autocomplete="new-password"
                           placeholder="Repeat your password">
                    <div class="invalid-feedback">Passwords do not match.</div>
                </div>

                <button type="submit" class="ld-btn-primary">Create Account</button>
            </form>

            <hr class="mt-4">
            <p class="text-center text-muted small mb-0">
                Already have an account?
                <a href="<?= APP_URL ?>/auth/login.php" class="fw-semibold">Log in</a>
            </p>
        </div>
    </div>
</section>

<script>
const toggleBtn = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirm_password');
const toggleIcon = document.getElementById('toggleIcon');

if (toggleBtn) {
    toggleBtn.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        toggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
}

function validatePasswordMatch() {
    if (confirmPasswordInput.value !== passwordInput.value) {
        confirmPasswordInput.setCustomValidity('Passwords do not match');
    } else {
        confirmPasswordInput.setCustomValidity('');
    }
}

if (passwordInput && confirmPasswordInput) {
    passwordInput.addEventListener('input', validatePasswordMatch);
    confirmPasswordInput.addEventListener('input', validatePasswordMatch);
}
</script>

<?php clear_old_input(); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>