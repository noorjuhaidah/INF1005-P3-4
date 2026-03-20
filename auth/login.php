<?php
$page_title = 'Login';
$current_page = '';

require_once __DIR__ . '/../includes/header.php';

redirect_if_logged_in();
?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title text-center">Login</h1>
        <p class="ld-section-subtitle text-center">Login to continue.</p>

        <?php show_flash(); ?>

        <div class="ld-form-card">
            <form method="post" action="<?= APP_URL ?>/auth/process_login.php">
                <?php csrf_field(); ?>

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email"
                           value="<?= e(old_input('email')) ?>"
                           autocomplete="email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password"
                           autocomplete="current-password" required>
                </div>

                <button type="submit" class="ld-btn-primary">Log In</button>
            </form>

            <p class="mt-3 text-center">
                Don’t have an account?
                <a href="<?= APP_URL ?>/auth/register.php">Register here</a>.
            </p>
        </div>
    </div>
</section>

<?php clear_old_input(); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>