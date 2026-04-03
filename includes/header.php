<?php
// =============================================================
// includes/header.php
// Shared page header: starts session, loads helpers,
// outputs the HTML <head> and the Bootstrap navbar.
//
// Usage in every page:
//   $page_title = 'Menu';        // Set BEFORE the require
//   require_once __DIR__ . '/../includes/header.php';
// =============================================================

require_once __DIR__ . '/db.php';        // Also loads config.php
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");
header(
    "Content-Security-Policy: "
    . "default-src 'self'; "
    . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
    . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
    . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
    . "img-src 'self' data:; "
    . "connect-src 'self'; "
    . "object-src 'none'; "
    . "base-uri 'self'; "
    . "frame-ancestors 'self'"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($page_title) ? e($page_title) . ' — ' . APP_NAME : APP_NAME ?></title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Google Fonts: Poppins + Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@400;500&display=swap"
          rel="stylesheet">

    <!-- LazyDrip custom CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">

    <?php if (!empty($page_styles)): ?>
        <?= $page_styles ?>
    <?php endif; ?>
</head>
<body>

<!-- Skip link: visible only on keyboard focus (styled in style.css .skip-link) -->
<a href="#main-content" class="skip-link">Skip to main content</a>

<header class="ld-navbar-header">
<nav class="navbar navbar-expand-lg navbar-light ld-navbar sticky-top" aria-label="Main navigation">
    <div class="container">

        <!-- Brand logo / name -->
        <a class="navbar-brand ld-brand" href="<?= APP_URL ?>/index.php" aria-label="LazyDrip Home">
            <img src="<?= APP_URL ?>/assets/images/CatLogo_transparent.png" alt="LazyDrip Logo" style="max-height: 40px; margin-right: 8px; width: auto;">
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">

            <!-- Left links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= (($current_page ?? '') === 'home') ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/index.php">
                        <i class="fa-solid fa-house me-1" aria-hidden="true"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (($current_page ?? '') === 'menu') ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/menu.php">
                        <i class="fa-solid fa-mug-hot me-1" aria-hidden="true"></i>Menu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (($current_page ?? '') === 'rewards') ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/rewards.php">
                        <i class="fa-solid fa-gift me-1" aria-hidden="true"></i>Rewards
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (($current_page ?? '') === 'reviews') ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/reviews.php">
                        <i class="fa-solid fa-star me-1" aria-hidden="true"></i>Reviews
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (($current_page ?? '') === 'about') ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/about.php">
                        <i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (($current_page ?? '') === 'contact') ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/contact.php">
                        <i class="fa-solid fa-envelope me-1" aria-hidden="true"></i>Contact
                    </a>
                </li>
            </ul>

            <!-- Right: cart + auth -->
            <ul class="navbar-nav align-items-center gap-2">

                <!-- Cart (only for logged-in customers) -->
                <?php if (is_logged_in() && !is_admin()): ?>
                    <?php $cartCount = cart_count(); ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= APP_URL ?>/cart/cart.php"
                           aria-label="Shopping cart<?= $cartCount > 0 ? ' (' . $cartCount . ' items)' : '' ?>">
                            <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                            <?php if ($cartCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill ld-badge"
                                      aria-hidden="true">
                                    <?= $cartCount ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (is_logged_in()): ?>
                    <!-- Logged-in dropdown -->
                    <li class="nav-item dropdown">
                        <button class="nav-link dropdown-toggle bg-transparent border-0" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false"
                                id="userMenuBtn">
                            <i class="fa-solid fa-user-circle me-1" aria-hidden="true"></i>
                            <?= e($_SESSION['full_name'] ?? 'Account') ?>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuBtn">
                            <?php if (is_admin()): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= APP_URL ?>/admin/dashboard.php">
                                        <i class="fa-solid fa-chart-line me-2" aria-hidden="true"></i>Admin Panel
                                    </a>
                                </li>
                            <?php else: ?>
                                <li>
                                    <a class="dropdown-item" href="<?= APP_URL ?>/customer/dashboard.php">
                                        <i class="fa-solid fa-table-columns me-2" aria-hidden="true"></i>Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= APP_URL ?>/customer/order_history.php">
                                        <i class="fa-solid fa-clock-rotate-left me-2" aria-hidden="true"></i>Order History
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= APP_URL ?>/customer/profile_edit.php">
                                        <i class="fa-solid fa-pen-to-square me-2" aria-hidden="true"></i>Edit Profile
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item text-danger" href="<?= APP_URL ?>/auth/logout.php">
                                    <i class="fa-solid fa-right-from-bracket me-2" aria-hidden="true"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>

                <?php else: ?>
                    <!-- Guest links -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/auth/login.php">
                            <i class="fa-solid fa-right-to-bracket me-1" aria-hidden="true"></i>Log In
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn ld-btn-primary btn-sm px-3"
                           href="<?= APP_URL ?>/auth/register.php">
                            <i class="fa-solid fa-user-plus me-1" aria-hidden="true"></i>Sign Up
                        </a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>
</header>

<!-- Flash messages appear here on every page -->
<div class="container mt-3" id="flash-container" role="status" aria-live="polite" aria-atomic="true">
    <?php show_flash(); ?>
</div>

<script>
(function () {
    if (window.location.hash === '#flash-container') {
        var flash = document.getElementById('flash-container');
        if (flash) {
            flash.setAttribute('tabindex', '-1');
            flash.focus();
        }
    }
})();
</script>

<!-- Page content starts here. id="main-content" is the skip-link target. -->
<main id="main-content">