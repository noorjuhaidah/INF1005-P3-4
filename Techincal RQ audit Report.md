# Technical Audit Report

Date: 2026-03-30
Project: LazyDrip (INF1005-P3-4)

## Verified Checks Completed

- PHP syntax lint: PASS (all PHP files lint clean).
- Security baseline hardening: completed (env-driven DB config, APP_URL override, CSRF helper normalization, removal of legacy admin mysqli connector).
- Accessibility hardening: completed for cart remove modal focus trap, focus restore, and background region isolation.
- Session hardening: completed (idle timeout enforcement via SESSION_IDLE_TIMEOUT_SECONDS + activity timestamp refresh on login/register).
- Transport and deployment hardening: completed (HSTS header on HTTPS requests, APP_URL-based stylesheet path).
- Password policy hardening: completed (server and form-level complexity enforcement).
- TR2 robustness: completed (safer numeric parsing and alert auto-dismiss guard in assets/js/main.js).
- TR3 data integrity: completed (profile email uniqueness check in customer/profile_edit.php).
- TR4 validation consistency: completed (payment form uses inline validation, server-side cardholder length check, and no card number repopulation on errors).
- TR5 session lifecycle hardening: completed (session bootstrap helper for session-dependent utility functions and logout cookie invalidation with SameSite support).
- TR3 race-condition handling: completed (duplicate email SQL constraint conflict now returns field-level error in profile update flow).

## Requirement Matrix

1. Responsive, device-independent, mobile-friendly design using HTML5, Bootstrap, CSS.
Status: Implemented
Evidence files:
- includes/header.php
- assets/css/style.css
- menu.php
- includes/footer.php
Validation notes:
- Confirm responsive behavior at <=576px, 768px, 992px, 1200px.
Automated evidence:
- DOCTYPE occurrences in PHP templates: 1
- Viewport meta occurrences: 1
- Bootstrap CSS include occurrences: 1
- Bootstrap JS include occurrences: 1
- CSS media query blocks detected: 1+

2. Custom JavaScript for dynamic client-side functionality.
Status: Implemented
Evidence files:
- assets/js/main.js
- menu.php
- cart/cart.php
Validation notes:
- Verify live search/filter, cart AJAX updates, inline validation, modal behavior.
Automated evidence:
- JS files in project: 1
- fetch() occurrences across JS/PHP: 15
- Form validation pattern matches (needs-validation/checkValidity): 12

3. PHP + MySQL backend with comprehensive CRUD.
Status: Implemented
Evidence files:
- admin/product_create.php
- admin/product_edit.php
- admin/product_delete.php
- admin/products.php
- reviews.php
- admin/messages.php
- lazydrip.sql
Validation notes:
- Run CRUD smoke tests for products, reviews, contact messages, and order flow.
Automated evidence:
- PDO prepared statement occurrences: 42+
- PDO execute call occurrences: 42

4. Proper form validation and sanitization.
Status: Implemented
Evidence files:
- includes/functions.php
- auth/process_register.php
- auth/process_login.php
- admin/product_create.php
- admin/product_edit.php
- cart/payment.php
Validation notes:
- Ensure server-side validation blocks malformed input even with JS disabled.
Automated evidence:
- clean_input usage matches: 20
- Input validation pattern matches (filter_var/FILTER_VALIDATE/preg_match): 35

5. Security against XSS and SQL injection.
Status: Implemented
Evidence files:
- includes/functions.php
- includes/db.php
- cart/add_to_cart.php
- cart/update_cart.php
- auth/process_login.php
- auth/process_register.php
Validation notes:
- Confirm escaping and prepared statements are consistently used.
Automated evidence:
- Escaping helper usage matches (e(...)): 406
- CSRF helper usage matches (csrf_field/verify_csrf): 31

6. User passwords protected appropriately.
Status: Implemented
Evidence files:
- auth/process_register.php
- auth/process_login.php
- lazydrip.sql
Validation notes:
- Confirm password_hash/password_verify flow and no plaintext password storage.
Automated evidence:
- password_hash occurrences: 2
- password_verify occurrences: 1

7. W3C validation and WCAG standards.
Status: In progress (implementation-level controls present)
Evidence files:
- includes/header.php
- assets/css/style.css
- cart/cart.php
- admin/messages.php
Validation notes:
- Complete formal validator runs and record outcomes below.
Automated evidence:
- skip-link pattern matches: 5
- role attribute matches: 10
- aria-* attribute matches: 200+ (search cap reached)
- focus-visible rule matches in CSS: 1

## Standards Evidence Log

### W3C HTML Validator
- Page(s) tested:
- Result summary:
- Errors:
- Warnings:
- Action taken:
Notes:
- External validator run pending. Static checks indicate HTML5 shell and responsive meta are present.

### W3C CSS Validator
- File tested: assets/css/style.css
- Result summary:
- Errors:
- Warnings:
- Action taken:
Notes:
- External validator run pending.

### WCAG Accessibility Checks
Keyboard navigation:
- Result:
- Action taken:

Focus visibility:
- Result:
- Action taken:

Form labels and error association:
- Result:
- Action taken:

Color contrast:
- Result:
- Action taken:

ARIA/live regions:
- Result:
- Action taken:

## Quick Regression Checklist

- [ ] Login/register still works
- [ ] Cart add/update/remove works with and without JS fallback
- [ ] Checkout and payment flow completes
- [ ] Admin product create/edit/delete works
- [ ] Reviews submission and listing work
- [ ] Contact message admin status toggle works
- [ ] Responsive layout intact on mobile/tablet/desktop

## Remaining Work To Close Audit

1. Execute W3C HTML validation on representative rendered pages and log exact outputs.
2. Execute W3C CSS validation and log exact outputs.
3. Run manual WCAG checks (keyboard-only, focus flow, contrast screenshots) and record outcomes.
