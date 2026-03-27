<?php
// =============================================================
// index.php - LazyDrip Home / Landing Page
// =============================================================

$page_title = 'Home';
$current_page = 'home';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .ld-video-hero {
        position: relative;
        min-height: calc(100vh - 96px);
        display: flex;
        align-items: center;
        overflow: hidden;
        background: #0e1a22;
    }

    .ld-video-hero video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ld-video-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background:
            linear-gradient(90deg, rgba(8, 16, 24, 0.82) 0%, rgba(8, 16, 24, 0.58) 42%, rgba(8, 16, 24, 0.28) 100%),
            linear-gradient(180deg, rgba(9, 17, 24, 0.28) 0%, rgba(9, 17, 24, 0.52) 100%);
    }

    .ld-video-hero .container {
        position: relative;
        z-index: 1;
    }

    .ld-video-panel {
        max-width: 640px;
        padding: 2rem 2.2rem;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 28px;
        background: rgba(7, 15, 22, 0.36);
        backdrop-filter: blur(10px);
        box-shadow: 0 28px 60px rgba(0, 0, 0, 0.22);
    }

    .ld-video-hero .ld-chip,
    .ld-video-hero .ld-hero-title,
    .ld-video-hero .ld-hero-subtitle {
        color: #fff;
    }

    .ld-video-note {
        margin-top: 1rem;
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.95rem;
        letter-spacing: 0.01em;
    }

    @media (max-width: 991.98px) {
        .ld-video-hero {
            min-height: 78vh;
        }

        .ld-video-panel {
            max-width: 100%;
            padding: 1.5rem;
        }
    }
</style>

<section class="ld-video-hero">
    <video autoplay muted loop playsinline poster="<?= APP_URL ?>/assets/images/placeholder.png" aria-hidden="true">
        <source src="<?= APP_URL ?>/assets/videos/lazydrip.mp4" type="video/mp4">
    </video>

    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="ld-video-panel">
                    <p class="ld-chip mb-3">Now open for pre-orders</p>
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
                    <p class="ld-video-note">Slow pours, soft light, and your next cup already waiting.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ld-section">
    <div class="container text-center">
        <p class="text-muted">
            Foundation loaded successfully - database, session, navbar, and footer all connected.
        </p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
