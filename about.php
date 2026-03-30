<?php
// =============================================================
// about.php — About LazyDrip
// Static informational page describing the brand, pickup model,
// and value propositions.
// =============================================================

$page_title   = 'About Us';
$current_page = 'about';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero banner -->
<section class="ld-hero">
    <div class="container">
        <div class="row align-items-center gy-4">
            <div class="col-lg-7">
                <p class="ld-chip mb-3">Our story</p>
                <h1 class="ld-hero-title">
                    Coffee that fits<br>
                    <span class="ld-underline">your pace.</span>
                </h1>
                <p class="ld-hero-subtitle mt-3">
                    LazyDrip is a minimalist café built around one idea: great
                    coffee without the wait. Order ahead, pick up on Grab, and
                    get on with your day — or slow it right down.
                </p>
                <div class="d-flex gap-3 mt-4 flex-wrap">
                    <a href="<?= APP_URL ?>/menu.php" class="ld-btn-primary">Browse our menu</a>
                    <a href="<?= APP_URL ?>/contact.php" class="ld-btn-outline">Get in touch</a>
                </div>
            </div>
            <div class="col-lg-5 text-center">
                <!-- Decorative blob / illustration placeholder -->
                <div style="
                    width: 100%;
                    max-width: 340px;
                    aspect-ratio: 1;
                    background: var(--ld-blue-light);
                    border-radius: 40% 60% 55% 45% / 45% 45% 55% 55%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto;
                    font-size: 6rem;
                " aria-hidden="true">
                    ☕
                </div>
            </div>
        </div>
    </div>
</section>

<!-- What we are -->
<section class="ld-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="ld-section-title">What is LazyDrip?</h2>
                <p class="ld-section-subtitle mx-auto" style="width: min(100%, 35rem);">
                    We are a small-batch, self-pickup café based in Singapore.
                    No queues at the counter, no table service — just honest
                    coffee and thoughtfully sourced tea, ready when you arrive.
                </p>
            </div>
        </div>

        <!-- 3-column value props -->
        <div class="row gy-4 mt-2">
            <div class="col-md-4">
                <div class="card ld-card h-100 p-4 text-center">
                    <div class="fs-1 mb-3" aria-hidden="true">🛵</div>
                    <h3 class="h5">Grab self-pickup</h3>
                    <p class="text-muted small mb-0">
                        Order on LazyDrip, collect via Grab — no delivery fees,
                        no surprise charges. Just walk in and grab your drink.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card ld-card h-100 p-4 text-center">
                    <div class="fs-1 mb-3" aria-hidden="true">⏱️</div>
                    <h3 class="h5">Order ahead, skip the wait</h3>
                    <p class="text-muted small mb-0">
                        Browse and pay online before you leave home. Your order
                        is ready when you arrive — zero time spent standing in line.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card ld-card h-100 p-4 text-center">
                    <div class="fs-1 mb-3" aria-hidden="true">🌿</div>
                    <h3 class="h5">Simple, quality ingredients</h3>
                    <p class="text-muted small mb-0">
                        Short menus, seasonal specials, and no unnecessary
                        extras. We let the coffee speak for itself.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How pickup works -->
<section class="ld-section" style="background: var(--ld-blue-light);">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h2 class="ld-section-title">How it works</h2>
                <p class="ld-section-subtitle">Three steps from sofa to sip.</p>
            </div>
        </div>

        <ol class="row gy-4 justify-content-center list-unstyled mb-0" aria-label="How LazyDrip pickup works in four steps">
            <!-- Step 1 -->
            <li class="col-sm-6 col-lg-3">
                <div class="d-flex flex-column align-items-center text-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3"
                         style="
                             width: 56px; height: 56px;
                             background: var(--ld-blue-dark);
                             color: #fff;
                             font-family: var(--font-head);
                             font-weight: 700;
                             font-size: 1.2rem;
                         "
                         aria-hidden="true">1</div>
                    <h3 class="h6 fw-semibold">Create an account</h3>
                    <p class="text-muted small mb-0">
                        Sign up in under a minute and get
                        <?= POINTS_SIGNUP_BONUS ?> bonus loyalty points instantly.
                    </p>
                </div>
            </li>
            <!-- Step 2 -->
            <li class="col-sm-6 col-lg-3">
                <div class="d-flex flex-column align-items-center text-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3"
                         style="
                             width: 56px; height: 56px;
                             background: var(--ld-blue-dark);
                             color: #fff;
                             font-family: var(--font-head);
                             font-weight: 700;
                             font-size: 1.2rem;
                         "
                         aria-hidden="true">2</div>
                    <h3 class="h6 fw-semibold">Pick your drinks</h3>
                    <p class="text-muted small mb-0">
                        Browse our menu, add to cart, and place your order.
                        Pay securely online.
                    </p>
                </div>
            </li>
            <!-- Step 3 -->
            <li class="col-sm-6 col-lg-3">
                <div class="d-flex flex-column align-items-center text-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3"
                         style="
                             width: 56px; height: 56px;
                             background: var(--ld-blue-dark);
                             color: #fff;
                             font-family: var(--font-head);
                             font-weight: 700;
                             font-size: 1.2rem;
                         "
                         aria-hidden="true">3</div>
                    <h3 class="h6 fw-semibold">Collect via Grab</h3>
                    <p class="text-muted small mb-0">
                        We prepare your order; just drop by and collect.
                        No queue, no fuss.
                    </p>
                </div>
            </li>
            <!-- Step 4 -->
            <li class="col-sm-6 col-lg-3">
                <div class="d-flex flex-column align-items-center text-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3"
                         style="
                             width: 56px; height: 56px;
                             background: var(--ld-blue-dark);
                             color: #fff;
                             font-family: var(--font-head);
                             font-weight: 700;
                             font-size: 1.2rem;
                         "
                         aria-hidden="true">4</div>
                    <h3 class="h6 fw-semibold">Earn &amp; redeem</h3>
                    <p class="text-muted small mb-0">
                        Every dollar you spend earns <?= POINTS_PER_DOLLAR ?> point.
                        Stack them up and enjoy <?= format_price(POINTS_REDEEM_VALUE) ?> off your next order.
                    </p>
                </div>
            </li>
        </ol>
    </div>
</section>

<!-- Values / philosophy -->
<section class="ld-section">
    <div class="container">
        <div class="row align-items-center gy-4">
            <div class="col-lg-5">
                <h2 class="ld-section-title">Minimalism is the point</h2>
                <p class="text-muted">
                    We deliberately keep the menu short. Fewer choices means
                    more attention on every item we do offer — fresher
                    ingredients, better execution, and a simpler experience
                    for you.
                </p>
                <p class="text-muted">
                    The LazyDrip name is a nod to slow mornings, unhurried
                    rituals, and the idea that good coffee should never feel
                    stressful. The pickup model is there so that <em>getting</em>
                    your coffee is stress-free too.
                </p>
                <a href="<?= APP_URL ?>/rewards.php" class="ld-btn-outline mt-2">
                    See our rewards programme
                </a>
            </div>
            <div class="col-lg-6 offset-lg-1">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="card ld-card p-3 text-center h-100">
                            <div class="fs-2 mb-2" aria-hidden="true">🏆</div>
                            <p class="fw-semibold mb-0 small">Quality over quantity</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card ld-card p-3 text-center h-100">
                            <div class="fs-2 mb-2" aria-hidden="true">♻️</div>
                            <p class="fw-semibold mb-0 small">Sustainability-minded</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card ld-card p-3 text-center h-100">
                            <div class="fs-2 mb-2" aria-hidden="true">📱</div>
                            <p class="fw-semibold mb-0 small">Digital-first ordering</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card ld-card p-3 text-center h-100">
                            <div class="fs-2 mb-2" aria-hidden="true">🤝</div>
                            <p class="fw-semibold mb-0 small">Community driven</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="ld-section-sm" style="background: var(--ld-blue-light);">
    <div class="container text-center py-3">
        <h2 class="ld-section-title">Ready to give it a try?</h2>
        <p class="text-muted mb-4">
            Join LazyDrip and get <?= POINTS_SIGNUP_BONUS ?> points on us — no strings attached.
        </p>
        <?php if (!is_logged_in()): ?>
            <a href="<?= APP_URL ?>/auth/register.php" class="ld-btn-primary me-3">Create an account</a>
            <a href="<?= APP_URL ?>/menu.php" class="ld-btn-outline">Browse the menu</a>
        <?php else: ?>
            <a href="<?= APP_URL ?>/menu.php" class="ld-btn-primary me-3">Browse the menu</a>
            <a href="<?= APP_URL ?>/rewards.php" class="ld-btn-outline">View my rewards</a>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
