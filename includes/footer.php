<?php
// =============================================================
// includes/footer.php
// Closes the <main> tag, renders the site footer,
// and loads Bootstrap JS + custom scripts.
//
// Usage: require_once __DIR__ . '/../includes/footer.php';
//        (at the very bottom of every page)
// =============================================================
?>
</main><!-- /#main closed -->

<footer class="ld-footer mt-auto py-5">
    <div class="container">
        <div class="row gy-4">

            <!-- Brand blurb -->
            <div class="col-md-4">
                <h2 class="ld-footer-brand">LazyDrip</h2>
                <p class="text-muted small">
                    Minimalist coffee &amp; tea, brewed for slow mornings.
                    Order ahead through our website and skip the wait.
                </p>
            </div>

            <!-- Quick links -->
            <div class="col-md-2">
                <h2 class="ld-footer-heading">Explore</h2>
                <ul class="list-unstyled small">
                    <li><a href="<?= APP_URL ?>/menu.php">Menu</a></li>
                    <li><a href="<?= APP_URL ?>/rewards.php">Rewards</a></li>
                    <li><a href="<?= APP_URL ?>/reviews.php">Reviews</a></li>
                    <li><a href="<?= APP_URL ?>/about.php">About Us</a></li>
                </ul>
            </div>

            <!-- Account links -->
            <div class="col-md-2">
                <h2 class="ld-footer-heading">Account</h2>
                <ul class="list-unstyled small">
                    <?php if (is_logged_in()): ?>
                        <li><a href="<?= APP_URL ?>/customer/dashboard.php">Dashboard</a></li>
                        <li><a href="<?= APP_URL ?>/customer/order_history.php">Orders</a></li>
                        <li><a href="<?= APP_URL ?>/auth/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= APP_URL ?>/auth/login.php">Log In</a></li>
                        <li><a href="<?= APP_URL ?>/auth/register.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Contact snippet -->
            <div class="col-md-4">
                <h2 class="ld-footer-heading">Get in Touch</h2>
                <p class="text-muted small mb-1">
                    <i class="bi bi-envelope me-1" aria-hidden="true"></i>
                    hello@lazydrip.sg
                </p>
                <p class="text-muted small">
                    <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>
                    1 Punggol Coast Road,
                    Singapore 828608
                </p>
                <a href="<?= APP_URL ?>/contact.php" class="btn ld-btn-outline btn-sm mt-2">
                    Contact Us
                </a>
            </div>

        </div><!-- /.row -->

        <hr class="mt-4 mb-3">

        <p class="text-center text-muted small mb-0">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>. Built for INF1005 — SIT.
        </p>
    </div><!-- /.container -->
</footer>

<!-- Bootstrap 5 JS (bundle includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- LazyDrip custom scripts -->
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>

</html>