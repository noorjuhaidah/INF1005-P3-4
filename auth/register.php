<?php
$page_title = 'Register';
$current_page = '';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce secure HTTPS connection
enforce_https();

// Redirect user if already logged in
redirect_if_logged_in();

require_once __DIR__ . '/../includes/header.php';

// Retrieve field level validation errors from previous request
$field_errors = $_SESSION['field_errors'] ?? [];
unset($_SESSION['field_errors']); // Clear errors after use
?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title text-center">Create Account</h1>
        <p class="ld-section-subtitle text-center">
            Join LazyDrip and earn <?= POINTS_SIGNUP_BONUS ?> bonus points!
        </p>

        <?php 
        // Display feedback messages
        show_flash(); 
        ?>

        <div class="ld-form-card">
            <!-- Form uses POST method for secure data submission -->
            <form method="post" action="<?= APP_URL ?>/auth/process_register.php" 
                  class="needs-validation" novalidate>

                <?php 
                // Include CSRF token for request verification
                csrf_field(); 
                ?>

                <div class="mb-3">
                    <!-- Full name input with validation -->
                    <label class="form-label" for="full_name">
                        Full Name <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="text" id="full_name" name="full_name" required autocomplete="name"
                        placeholder="Jane Tan"
                        value="<?= e(old_input('full_name')) ?>"
                        <?= !empty($field_errors['full_name']) ? 'aria-describedby="full_name_error"' : '' ?>
                        class="form-control <?= !empty($field_errors['full_name']) ? 'is-invalid' : '' ?>">

                    <!-- Display validation feedback -->
                    <div id="full_name_error" class="invalid-feedback">
                        <?= e($field_errors['full_name'] ?? 'Please enter your full name.') ?>
                    </div>
                </div>

                <div class="mb-3">
                    <!-- Email input with HTML5 validation -->
                    <label class="form-label" for="email">
                        Email <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                        placeholder="you@example.com"
                        value="<?= e(old_input('email')) ?>"
                        <?= !empty($field_errors['email']) ? 'aria-describedby="email_error"' : '' ?>
                        class="form-control <?= !empty($field_errors['email']) ? 'is-invalid' : '' ?>">

                    <!-- Display validation feedback -->
                    <div id="email_error" class="invalid-feedback">
                        <?= e($field_errors['email'] ?? 'Please enter a valid email.') ?>
                    </div>
                </div>

                <div class="mb-3">
                    <!-- Optional phone number input -->
                    <label class="form-label" for="phone">
                        Phone <span class="text-muted">optional</span>
                    </label>
                    <input class="form-control <?= !empty($field_errors['phone']) ? 'is-invalid' : '' ?>" 
                        type="tel" id="phone" name="phone"
                        autocomplete="tel" placeholder="+65 9123 4567"
                        pattern="^\+?[0-9\s\-()]{8,20}$"
                        aria-describedby="<?= !empty($field_errors['phone']) ? 'phone_error' : 'phone_help' ?>"
                        value="<?= e(old_input('phone')) ?>">

                    <!-- Helper text for user guidance -->
                    <div id="phone_help" class="form-text">
                        Use 8 to 20 characters digits spaces plus minus and parentheses allowed
                    </div>

                    <?php if (!empty($field_errors['phone'])): ?>
                        <!-- Display phone validation error -->
                        <div id="phone_error" class="invalid-feedback d-block">
                            <?= e($field_errors['phone']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <!-- Password input -->
                    <label class="form-label" for="password">
                        Password <span class="text-danger" aria-hidden="true">*</span>
                    </label>

                    <div class="input-group">
                        <input type="password" id="password" name="password"
                            required minlength="8"
                            autocomplete="new-password"
                            placeholder="Minimum 8 characters"
                            <?= !empty($field_errors['password']) ? 'aria-describedby="password_error"' : '' ?>
                            class="form-control <?= !empty($field_errors['password']) ? 'is-invalid' : '' ?>">

                        <!-- Toggle password visibility -->
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                            aria-label="Show password" aria-pressed="false">
                            <i class="bi bi-eye" id="toggleIcon" aria-hidden="true"></i>
                        </button>
                    </div>

                    <!-- Display password validation error -->
                    <div id="password_error" class="invalid-feedback d-block">
                        <?= e($field_errors['password'] ?? '') ?>
                    </div>
                </div>

                <div class="mb-4">
                    <!-- Confirm password input -->
                    <label class="form-label" for="confirm_password">
                        Confirm Password <span class="text-danger" aria-hidden="true">*</span>
                    </label>

                    <div class="input-group">
                        <input type="password" id="confirm_password" name="confirm_password"
                            required minlength="8" autocomplete="new-password"
                            placeholder="Repeat your password"
                            <?= !empty($field_errors['confirm_password']) ? 'aria-describedby="confirm_password_error"' : '' ?>
                            class="form-control <?= !empty($field_errors['confirm_password']) ? 'is-invalid' : '' ?>">

                        <!-- Toggle confirm password visibility -->
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword"
                            aria-label="Show password" aria-pressed="false">
                            <i class="bi bi-eye" id="toggleConfirmIcon" aria-hidden="true"></i>
                        </button>
                    </div>

                    <!-- Display confirm password error -->
                    <div id="confirm_password_error" class="invalid-feedback d-block">
                        <?= e($field_errors['confirm_password'] ?? '') ?>
                    </div>
                </div>

                <!-- Submit button -->
                <button type="submit" class="ld-btn-primary">Create Account</button>
            </form>

            <hr class="mt-4">

            <!-- Link to login page -->
            <p class="text-center text-muted small mb-0">
                Already have an account?
                <a href="<?= APP_URL ?>/auth/login.php" class="fw-semibold ld-auth-link">
                    Log in
                </a>
            </p>
        </div>
    </div>
</section>

<?php 
clear_old_input(); 
?>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>