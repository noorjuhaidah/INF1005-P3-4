<?php
// =============================================================
// rewards.php — LazyDrip Loyalty Rewards Programme
// Explains how points are earned, redeemed, and shows the
// current logged-in user's balance.
// =============================================================

$page_title = 'Rewards';
$current_page = 'rewards';
require_once __DIR__ . '/includes/header.php';

// Fetch current user's points fresh from DB (session may be stale)
$userPoints = null;
if (is_logged_in()) {
    try {
        $stmt = $pdo->prepare("SELECT points FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        if ($row !== false) {
            $userPoints = (int) $row['points'];
        }
    } catch (PDOException $e) {
        // Non-critical — just hide the balance widget
        $userPoints = null;
    }
}

// How many full redemptions the user can make right now
$redeemable = ($userPoints !== null && POINTS_REDEEM_AMOUNT > 0)
    ? intdiv($userPoints, POINTS_REDEEM_AMOUNT)
    : 0;

// Points needed for the next redemption
$pointsToNext = ($userPoints !== null)
    ? max(0, POINTS_REDEEM_AMOUNT - ($userPoints % POINTS_REDEEM_AMOUNT))
    : POINTS_REDEEM_AMOUNT;
?>

<!-- Hero -->
<section class="ld-hero">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-7">
                <p class="ld-chip mb-3">LazyDrip Rewards</p>
                <h1 class="ld-hero-title">Sip more, save more.</h1>
                <p class="ld-hero-subtitle mt-3 mx-auto">
                    Every order earns you points. Stack them up and treat yourself
                    to money off your next pick-up. No cards to carry — it is all
                    in your account.
                </p>
                <?php if (!is_logged_in()): ?>
                    <div class="d-flex gap-3 justify-content-center mt-4">
                        <a href="<?= APP_URL ?>/auth/register.php" class="ld-btn-primary">
                            Join &amp; get <?= POINTS_SIGNUP_BONUS ?> free points
                        </a>
                        <a href="<?= APP_URL ?>/auth/login.php" class="ld-btn-outline">Log in</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Points balance card (logged-in users only) -->
<?php if ($userPoints !== null): ?>
    <section class="ld-section-sm">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card ld-card p-4">
                        <div class="row align-items-center gy-3">
                            <div class="col-sm-6">
                                <p class="text-muted small mb-1">Your current balance</p>
                                <p class="mb-0"
                                    style="font-size: 2.8rem; font-weight: 700; font-family: var(--font-head); color: var(--ld-charcoal);">
                                    <?= number_format($userPoints) ?>
                                    <span style="font-size: 1rem; font-weight: 400; color: var(--ld-muted);">pts</span>
                                </p>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <?php if ($redeemable >= 1): ?>
                                    <p class="text-success fw-semibold mb-1">
                                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                                        You can redeem <?= format_price($redeemable * POINTS_REDEEM_VALUE) ?> right now!
                                    </p>
                                    <p class="text-muted small mb-2">
                                        Apply your points discount at checkout.
                                    </p>
                                <?php else: ?>
                                    <p class="text-muted small mb-1">
                                        <?= number_format($pointsToNext) ?> more points until your next
                                        <?= format_price(POINTS_REDEEM_VALUE) ?> reward.
                                    </p>
                                <?php endif; ?>
                                <a href="<?= APP_URL ?>/menu.php" class="ld-btn-primary btn-sm">
                                    Order now
                                </a>
                            </div>
                        </div>

                        <?php if ($userPoints > 0): ?>
                            <!-- Progress bar toward next redemption -->
                            <?php
                            $progressPct = min(100, round(
                                (($userPoints % POINTS_REDEEM_AMOUNT) / POINTS_REDEEM_AMOUNT) * 100
                            ));
                            // If evenly divisible and > 0, bar is full
                            if ($userPoints % POINTS_REDEEM_AMOUNT === 0) {
                                $progressPct = 100;
                            }
                            ?>
                            <div class="mt-4">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Progress to next reward</span>
                                    <span><?= $progressPct ?>%</span>
                                </div>
                                <div class="progress" style="height: 8px;" role="progressbar"
                                    aria-valuenow="<?= $progressPct ?>" aria-valuemin="0" aria-valuemax="100"
                                    aria-label="Points progress toward next reward">
                                    <div class="progress-bar"
                                        style="width: <?= $progressPct ?>%; background-color: var(--ld-blue-dark);"></div>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- How points work -->
<section class="ld-section">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h2 class="ld-section-title">How it works</h2>
                <p class="ld-section-subtitle">Earning and spending points is automatic — no vouchers, no fuss.</p>
            </div>
        </div>

        <div class="row gy-4">

            <!-- Earn -->
            <div class="col-md-6 col-lg-3">
                <div class="card ld-card h-100 p-4 text-center">
                    <div class="fs-1 mb-3" aria-hidden="true">🎁</div>
                    <h3 class="h6 fw-semibold">Sign-up bonus</h3>
                    <p class="text-muted small mb-0">
                        Create a free account and we will credit
                        <strong><?= POINTS_SIGNUP_BONUS ?> points</strong> to your
                        balance straight away.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card ld-card h-100 p-4 text-center">
                    <div class="fs-1 mb-3" aria-hidden="true">☕</div>
                    <h3 class="h6 fw-semibold">Earn on every order</h3>
                    <p class="text-muted small mb-0">
                        Get <strong><?= POINTS_PER_DOLLAR ?> point for every $1</strong>
                        you spend. The more you order, the faster your balance grows.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card ld-card h-100 p-4 text-center">
                    <div class="fs-1 mb-3" aria-hidden="true">💰</div>
                    <h3 class="h6 fw-semibold">Redeem for discounts</h3>
                    <p class="text-muted small mb-0">
                        Once you hit <strong><?= POINTS_REDEEM_AMOUNT ?> points</strong>,
                        apply them at checkout for
                        <strong><?= format_price(POINTS_REDEEM_VALUE) ?> off</strong>
                        your order total.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card ld-card h-100 p-4 text-center">
                    <div class="fs-1 mb-3" aria-hidden="true">🔄</div>
                    <h3 class="h6 fw-semibold">Keep going</h3>
                    <p class="text-muted small mb-0">
                        Points never expire. Accumulate as many as you like and
                        redeem in batches of <?= POINTS_REDEEM_AMOUNT ?>.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Summary table -->
<section class="ld-section-sm" style="background: var(--ld-blue-light);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <h2 class="h5 text-center fw-bold mb-4">At a glance</h2>
                <table class="table table-borderless bg-white rounded"
                    style="border-radius: var(--ld-radius); overflow: hidden;">
                    <caption class="visually-hidden">Rewards program summary values at a glance.</caption>
                    <tbody>
                        <tr>
                            <th scope="row" class="fw-semibold ps-4 py-3">Sign-up bonus</th>
                            <td class="py-3 pe-4 text-end text-muted"><?= POINTS_SIGNUP_BONUS ?> points</td>
                        </tr>
                        <tr class="table-light">
                            <th scope="row" class="fw-semibold ps-4 py-3">Earn rate</th>
                            <td class="py-3 pe-4 text-end text-muted"><?= POINTS_PER_DOLLAR ?> pt per $1 spent</td>
                        </tr>
                        <tr>
                            <th scope="row" class="fw-semibold ps-4 py-3">Points to redeem</th>
                            <td class="py-3 pe-4 text-end text-muted"><?= POINTS_REDEEM_AMOUNT ?> points</td>
                        </tr>
                        <tr class="table-light">
                            <th scope="row" class="fw-semibold ps-4 py-3">Redemption value</th>
                            <td class="py-3 pe-4 text-end text-muted"><?= format_price(POINTS_REDEEM_VALUE) ?> off order
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" class="fw-semibold ps-4 py-3">Expiry</th>
                            <td class="py-3 pe-4 text-end text-muted">Never</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Redemption explainer -->
<section class="ld-section">
    <div class="container">
        <div class="row align-items-start gy-4">
            <div class="col-lg-6">
                <h2 class="ld-section-title">How to redeem at checkout</h2>
                <ol class="list-group list-group-numbered list-group-flush">
                    <li class="list-group-item border-0 ps-0">
                        <strong>Add items to your cart</strong> and proceed to checkout
                        as usual.
                    </li>
                    <li class="list-group-item border-0 ps-0">
                        If your balance is <?= POINTS_REDEEM_AMOUNT ?> points or more,
                        a <strong>"Use my points"</strong> option will appear on the
                        checkout page.
                    </li>
                    <li class="list-group-item border-0 ps-0">
                        Tick the box to apply
                        <strong><?= format_price(POINTS_REDEEM_VALUE) ?> off</strong>
                        your total. Your points are deducted automatically when
                        the order is placed.
                    </li>
                    <li class="list-group-item border-0 ps-0">
                        You continue to earn points on the
                        <strong>post-discount amount</strong> paid.
                    </li>
                </ol>
            </div>
            <div class="col-lg-5 offset-lg-1">
                <div class="card ld-card p-4" style="background: var(--ld-blue-light); border: none; box-shadow: none;">
                    <h3 class="h6 fw-semibold mb-3">
                        <i class="bi bi-lightbulb me-1" aria-hidden="true"></i>
                        Example
                    </h3>
                    <p class="text-muted small mb-2">
                        You order $20 worth of drinks with <?= POINTS_REDEEM_AMOUNT ?> points saved up.
                    </p>
                    <ul class="list-unstyled text-muted small mb-0">
                        <li class="mb-1">
                            <i class="bi bi-check2 me-1 text-success" aria-hidden="true"></i>
                            Apply <?= POINTS_REDEEM_AMOUNT ?> points → <strong><?= format_price(POINTS_REDEEM_VALUE) ?>
                                off</strong>
                        </li>
                        <li class="mb-1">
                            <i class="bi bi-check2 me-1 text-success" aria-hidden="true"></i>
                            You pay <strong><?= format_price(20 - POINTS_REDEEM_VALUE) ?></strong>
                        </li>
                        <li>
                            <i class="bi bi-check2 me-1 text-success" aria-hidden="true"></i>
                            You earn <strong><?= (int) ((20 - POINTS_REDEEM_VALUE) * POINTS_PER_DOLLAR) ?> new
                                points</strong> on that order
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<?php if (!is_logged_in()): ?>
    <section class="ld-section-sm" style="background: var(--ld-blue-light);">
        <div class="container text-center py-3">
            <h2 class="h4 fw-bold mb-2">Start earning today</h2>
            <p class="text-muted mb-4">
                Sign up for free and collect your <?= POINTS_SIGNUP_BONUS ?> welcome points immediately.
            </p>
            <a href="<?= APP_URL ?>/auth/register.php" class="ld-btn-primary me-3">Create an account</a>
            <a href="<?= APP_URL ?>/menu.php" class="ld-btn-outline">Browse the menu</a>
        </div>
    </section>
<?php else: ?>
    <section class="ld-section-sm" style="background: var(--ld-blue-light);">
        <div class="container text-center py-3">
            <h2 class="h4 fw-bold mb-2">Ready to order?</h2>
            <p class="text-muted mb-4">Browse the menu and keep those points rolling in.</p>
            <a href="<?= APP_URL ?>/menu.php" class="ld-btn-primary">Browse the menu</a>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>