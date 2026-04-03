<?php
// includes/footer.php
// Closes the <main> tag, renders the site footer,
// and loads Bootstrap JS + custom scripts.
//
// Usage: require_once __DIR__ . '/../includes/footer.php';
//        (at the very bottom of every page)
?>
</main><!-- /#main closed -->

<footer class="ld-footer mt-auto py-5">
    <div class="container">
        <div class="row gy-4">

            <!-- Brand blurb -->
            <div class="col-md-4">
                <h2 class="ld-footer-brand">
                    <i class="fa-solid fa-mug-hot me-2" aria-hidden="true"></i>LazyDrip
                </h2>
                <p class="text-muted small">
                    Minimalist coffee &amp; tea, brewed for slow mornings.
                    Order ahead through our website and skip the wait.
                </p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-muted" aria-label="Instagram">
                        <i class="fa-brands fa-instagram fa-lg" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="text-muted" aria-label="Facebook">
                        <i class="fa-brands fa-facebook fa-lg" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="text-muted" aria-label="Twitter">
                        <i class="fa-brands fa-x-twitter fa-lg" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <!-- Quick links -->
            <div class="col-md-2">
                <h2 class="ld-footer-heading">
                    <i class="fa-solid fa-compass me-2" aria-hidden="true"></i>Explore
                </h2>
                <ul class="list-unstyled small">
                    <li><a href="<?= APP_URL ?>/menu.php"><i class="fa-solid fa-mug-hot me-1" aria-hidden="true"></i>Menu</a></li>
                    <li><a href="<?= APP_URL ?>/rewards.php"><i class="fa-solid fa-gift me-1" aria-hidden="true"></i>Rewards</a></li>
                    <li><a href="<?= APP_URL ?>/reviews.php"><i class="fa-solid fa-star me-1" aria-hidden="true"></i>Reviews</a></li>
                    <li><a href="<?= APP_URL ?>/about.php"><i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>About Us</a></li>
                </ul>
            </div>

            <!-- Account links -->
            <div class="col-md-2">
                <h2 class="ld-footer-heading">
                    <i class="fa-solid fa-user me-2" aria-hidden="true"></i>Account
                </h2>
                <ul class="list-unstyled small">
                    <?php if (is_logged_in()): ?>
                        <li><a href="<?= APP_URL ?>/customer/dashboard.php"><i class="fa-solid fa-table-columns me-1" aria-hidden="true"></i>Dashboard</a></li>
                        <li><a href="<?= APP_URL ?>/customer/order_history.php"><i class="fa-solid fa-clock-rotate-left me-1" aria-hidden="true"></i>Orders</a></li>
                        <li><a href="<?= APP_URL ?>/auth/logout.php"><i class="fa-solid fa-right-from-bracket me-1" aria-hidden="true"></i>Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= APP_URL ?>/auth/login.php"><i class="fa-solid fa-right-to-bracket me-1" aria-hidden="true"></i>Log In</a></li>
                        <li><a href="<?= APP_URL ?>/auth/register.php"><i class="fa-solid fa-user-plus me-1" aria-hidden="true"></i>Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Contact snippet -->
            <div class="col-md-4">
                <h2 class="ld-footer-heading">
                    <i class="fa-solid fa-envelope me-2" aria-hidden="true"></i>Get in Touch
                </h2>
                <p class="text-muted small mb-1">
                    <i class="fa-solid fa-envelope me-1" aria-hidden="true"></i>
                    hello@lazydrip.sg
                </p>
                <p class="text-muted small">
                    <i class="fa-solid fa-location-dot me-1" aria-hidden="true"></i>
                    1 Punggol Coast Road,
                    Singapore 828608
                </p>
                <a href="<?= APP_URL ?>/contact.php" class="btn ld-btn-outline btn-sm mt-2">
                    <i class="fa-solid fa-paper-plane me-1" aria-hidden="true"></i>Contact Us
                </a>
            </div>

        </div><!-- /.row -->

        <hr class="mt-4 mb-3">

        <p class="text-center text-muted small mb-0">
            <i class="fa-regular fa-copyright me-1" aria-hidden="true"></i><?= date('Y') ?> <?= APP_NAME ?>. Built for INF1005 — SIT.
        </p>
    </div><!-- /.container -->
</footer>

<!-- Bootstrap 5 JS (bundle includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- LazyDrip custom scripts -->
<script src="<?= APP_URL ?>/assets/js/main.js?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
</body>

</html>