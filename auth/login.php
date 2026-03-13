<?php
$page_title = 'Login';
$current_page = '';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="ld-section">
    <div class="container">
        <h1 class="ld-section-title text-center">Login</h1>
        <p class="ld-section-subtitle text-center">Login to continue.</p>

        <div class="ld-form-card">
            <form method="post" action="<?= APP_URL ?>/auth/process_login.php">
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="ld-btn-primary">Log In</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>