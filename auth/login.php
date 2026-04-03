<?php
$page_title = 'Login';
$current_page = '';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce HTTPS connection for security
enforce_https();

// Redirect user if already logged in using session
redirect_if_logged_in();

require_once __DIR__ . '/../includes/header.php';

// Retrieve field specific validation errors from previous request
$field_errors = $_SESSION['field_errors'] ?? [];
unset($_SESSION['field_errors']); // Clear after retrieving
?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title text-center">Login</h1>
        <p class="ld-section-subtitle text-center">Login to continue.</p>

        <?php 
        // Display flash messages such as success or error feedback
        show_flash(); 
        ?>

        <div class="ld-form-card">
            <!-- Form uses POST method for secure data submission -->
            <form method="post" action="<?= APP_URL ?>/auth/process_login.php" 
                  class="needs-validation" data-inline-validate="true" novalidate>

                <?php 
                // CSRF token for request verification
                csrf_field(); 
                ?>

                <div class="mb-3">
                    <label class="form-label" for="email">
                        Email <span class="text-danger" aria-hidden="true">*</span>
                    </label>

                    <!-- HTML5 validation using email type and required -->
                    <input type="email" id="email" name="email"
                        value="<?= e(old_input('email')) ?>"
                        autocomplete="email" required
                        <?= !empty($field_errors['email']) ? 'aria-describedby="email_error"' : '' ?>
                        class="form-control <?= !empty($field_errors['email']) ? 'is-invalid' : '' ?>">

                    <!-- Bootstrap validation feedback -->
                    <div id="email_error" class="invalid-feedback">
                        <?= e($field_errors['email'] ?? 'Please enter a valid email address.') ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">
                        Password <span class="text-danger" aria-hidden="true">*</span>
                    </label>

                    <div class="input-group">
                        <!-- Password input with required validation -->
                        <input type="password" id="password" name="password"
                            autocomplete="current-password" required
                            <?= !empty($field_errors['password']) ? 'aria-describedby="password_error"' : '' ?>
                            class="form-control <?= !empty($field_errors['password']) ? 'is-invalid' : '' ?>">

                        <!-- Button to toggle password visibility -->
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword"
                            aria-label="Show password" aria-pressed="false">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    <?php if (!empty($field_errors['password'])): ?>
                        <!-- Display password error message -->
                        <div id="password_error" class="invalid-feedback d-block">
                            <?= e($field_errors['password']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Submit button -->
                <button type="submit" class="ld-btn-primary">Log In</button>
            </form>

            <!-- Navigation link for new users -->
            <p class="mt-3 text-center">
                Don’t have an account?
                <a class="ld-auth-link" href="<?= APP_URL ?>/auth/register.php">
                    Register here
                </a>.
            </p>
        </div>
    </div>
</section>

<script>
    // JavaScript to toggle password visibility for better user experience
    const togglePasswordBtn = document.getElementById('togglePassword');

    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function () {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            if (!password || !icon) return;

            // Check current visibility state
            const isVisible = password.type === 'text';

            // Toggle between password and text
            password.type = isVisible ? 'password' : 'text';

            // Update icon and accessibility attributes
            icon.className = isVisible ? 'bi bi-eye' : 'bi bi-eye-slash';
            this.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
            this.setAttribute('aria-pressed', !isVisible);
        });
    }
</script>

<?php 
clear_old_input(); 
?>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>