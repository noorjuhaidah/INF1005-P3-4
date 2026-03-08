<?php
// =============================================================
// index.php — LazyDrip Home / Landing Page (stub)
// Full content will be built in the Landing Page module.
// This stub confirms the foundation (db, session, nav) works.
// =============================================================

$page_title  = 'Home';
$current_page = 'home';
require_once __DIR__ . '/includes/header.php';
?>

<section class="ld-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <p class="ld-chip mb-3">Now open for pre-orders ☕</p>
                <h1 class="ld-hero-title mb-3">
                    Coffee that<br>doesn't rush you.
                </h1>
                <p class="ld-hero-subtitle mb-4">
                    Minimalist brews for slow mornings. Order ahead, pick up with Grab, 
                    and earn rewards with every sip.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="<?= APP_URL ?>/menu.php" class="ld-btn-primary">
                        Browse Menu
                    </a>
                    <?php if (!is_logged_in()): ?>
                    <a href="<?= APP_URL ?>/auth/register.php" class="ld-btn-outline">
                        Join for Free
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ld-section">
    <div class="container text-center">
        <p class="text-muted">
            ✅ Foundation loaded successfully — database, session, navbar, footer all connected.
        </p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
