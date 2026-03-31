<?php
// index.php - LazyDrip Home / Landing Page

$page_title = 'Home';
$current_page = 'home';
require_once __DIR__ . '/includes/header.php';

$heroVideoFile = __DIR__ . '/assets/videos/lazydrip.mp4';
$heroVideoVersion = time() . '-' . (file_exists($heroVideoFile) ? substr(hash_file('sha1', $heroVideoFile), 0, 12) : 'missing');
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
        width: min(100%, 32.5rem);
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

    .ld-video-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
        min-height: 44px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.45);
        background: rgba(10, 18, 26, 0.55);
        color: #fff;
        font-size: 0.9rem;
        font-weight: 600;
        padding: 0.45rem 0.9rem;
        transition: background-color 0.2s, border-color 0.2s;
    }

    .ld-video-toggle:hover,
    .ld-video-toggle:focus-visible {
        background: rgba(10, 18, 26, 0.78);
        border-color: rgba(255, 255, 255, 0.7);
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
    <video id="heroVideo" autoplay muted loop playsinline aria-hidden="true">
        <source src="<?= APP_URL ?>/assets/videos/lazydrip.mp4?v=<?= $heroVideoVersion ?>" type="video/mp4">
        <!-- Fallback for when video doesn't load -->
        <img src="<?= APP_URL ?>/assets/images/placeholder.png" alt="Coffee shop interior with warm lighting and minimalist decor">
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
                        <a href="<?= APP_URL ?>/auth/register.php" class="ld-btn-primary">
                            Join for Free
                        </a>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="heroVideoToggle" class="ld-video-toggle mt-3"
                            aria-controls="heroVideo" aria-pressed="false">
                        Pause background video
                    </button>
                    <p class="ld-video-note">Slow pours, soft light, and your next cup already waiting.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>

    
(function () {
    const video = document.getElementById('heroVideo');
    const toggle = document.getElementById('heroVideoToggle');
    if (!video || !toggle) return;

    function autoplayVideo() {
        const playPromise = video.play();
        if (playPromise && typeof playPromise.then === 'function') {
            playPromise.then(finishAutoplayAttempt).catch(finishAutoplayAttempt);
        } else {
            finishAutoplayAttempt();
        }
    }

    function setButtonState() {
        const isPaused = video.paused;
        toggle.setAttribute('aria-pressed', String(isPaused));
        toggle.textContent = isPaused ? 'Play background video' : 'Pause background video';
    }

    function finishAutoplayAttempt() {
        setButtonState();
    }

    // Wait for video metadata to load before attempting autoplay
    if (video.readyState >= 1) {
        // Metadata is already loaded
        autoplayVideo();
    } else {
        // Wait for metadata to load
        video.addEventListener('loadedmetadata', autoplayVideo, { once: true });
    }

    toggle.addEventListener('click', function () {
        if (video.paused) {
            const playPromise = video.play();
            if (playPromise && typeof playPromise.then === 'function') {
                playPromise.catch(setButtonState);
            } else {
                setButtonState();
            }
        } else {
            video.pause();
            setButtonState();
        }
    });

    video.addEventListener('play', setButtonState);
    video.addEventListener('pause', setButtonState);
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
