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
        object-position: center center;
    }

    .ld-video-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background:
            linear-gradient(90deg, rgba(8, 16, 24, 0.52) 0%, rgba(8, 16, 24, 0.34) 30%, rgba(8, 16, 24, 0.14) 55%, rgba(8, 16, 24, 0.08) 100%),
            linear-gradient(180deg, rgba(9, 17, 24, 0.18) 0%, rgba(9, 17, 24, 0.28) 100%);
    }

    .ld-video-hero .container {
        position: relative;
        z-index: 1;
    }

    .ld-video-panel {
        max-width: 520px;
        padding: 1.85rem 1.9rem;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 28px;
        background: rgba(10, 18, 26, 0.42);
        backdrop-filter: blur(8px);
        box-shadow: 0 28px 60px rgba(0, 0, 0, 0.18);
    }

    .ld-video-hero .ld-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.55rem 1.2rem;
        border-radius: 999px;
        background: rgba(241, 248, 255, 0.92);
        color: #35566b;
        font-weight: 700;
        border: 1px solid rgba(132, 180, 210, 0.45);
        box-shadow: 0 8px 22px rgba(7, 15, 22, 0.08);
    }

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
            align-items: flex-end;
        }

        .ld-video-hero video {
            object-position: 62% center;
        }

        .ld-video-panel {
            max-width: 100%;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
    }
</style>

<section class="ld-video-hero">
    <video autoplay muted loop playsinline poster="<?= APP_URL ?>/assets/images/placeholder.png" aria-hidden="true">
        <source src="<?= APP_URL ?>/assets/videos/lazydrip.mp4" type="video/mp4">
    </video>

    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
