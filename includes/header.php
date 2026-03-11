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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' — ' . APP_NAME : APP_NAME ?></title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Google Fonts: Poppins + Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@400;500&display=swap"
          rel="stylesheet">
    <!-- LazyDrip custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- ============================================================
     NAVBAR
     Active link detection: set $current_page before including
     header.php, e.g. $current_page = 'menu';
     ============================================================ -->
<nav class="navbar navbar-expand-lg navbar-light ld-navbar sticky-top" aria-label="Main navigation">
    <div class="container">

        <!-- Brand logo / name -->
        <a class="navbar-brand ld-brand" href="<?= APP_URL ?>/index.php">
            <span class="ld-logo-dot"></span>LazyDrip
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
                    <a class="nav-link <?= (($current_page ?? '') === 'home')    ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (($current_page ?? '') === 'menu')    ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/menu.php">Menu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (($current_page ?? '') === 'rewards') ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/rewards.php">Rewards</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (($current_page ?? '') === 'reviews') ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/reviews.php">Reviews</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (($current_page ?? '') === 'about')   ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (($current_page ?? '') === 'contact') ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/contact.php">Contact</a>
                </li>
            </ul>

            <!-- Right: cart + auth -->
            <ul class="navbar-nav align-items-center gap-2">

                <!-- Cart (only for logged-in customers) -->
                <?php if (is_logged_in() && !is_admin()): ?>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?= APP_URL ?>/cart/cart.php"
                       aria-label="Shopping cart">
                        <i class="bi bi-bag" aria-hidden="true"></i>
                        <?php $cartCount = cart_count(); if ($cartCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill ld-badge">
                            <?= $cartCount ?>
                            <span class="visually-hidden">items in cart</span>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (is_logged_in()): ?>
                <!-- Logged-in dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false"
                       id="userMenuBtn">
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                        <?= e($_SESSION['full_name'] ?? 'Account') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuBtn">
                        <?php if (is_admin()): ?>
                        <li>
                            <a class="dropdown-item" href="<?= APP_URL ?>/admin/dashboard.php">
                                <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>Admin Panel
                            </a>
                        </li>
                        <?php else: ?>
                        <li>
                            <a class="dropdown-item" href="<?= APP_URL ?>/customer/dashboard.php">
                                <i class="bi bi-grid me-1" aria-hidden="true"></i>Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= APP_URL ?>/customer/order_history.php">
                                <i class="bi bi-clock-history me-1" aria-hidden="true"></i>Order History
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= APP_URL ?>/customer/profile_edit.php">
                                <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit Profile
                            </a>
                        </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= APP_URL ?>/auth/logout.php">
                                <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>

                <?php else: ?>
                <!-- Guest links -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= APP_URL ?>/auth/login.php">Log In</a>
                </li>
                <li class="nav-item">
                    <a class="btn ld-btn-primary btn-sm px-3"
                       href="<?= APP_URL ?>/auth/register.php">Sign Up</a>
                </li>
                <?php endif; ?>

            </ul>
        </div><!-- /.collapse -->
    </div><!-- /.container -->
</nav>

<!-- Flash messages appear here on every page -->
<div class="container mt-3" id="flash-container">
    <?php show_flash(); ?>
</div>

<!-- Page content starts here -->
<main>
