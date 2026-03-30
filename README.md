# INF1005-P3-4

## Environment Setup

This project now reads database and transport security settings from environment variables.

1. Copy `.env.example` values into your local environment.
2. Set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, and optionally `DB_CHARSET`.
3. Set `APP_URL` to your deployment base URL.
4. Set `FORCE_HTTPS=1` in production behind HTTPS.
5. Optionally set `SESSION_IDLE_TIMEOUT_SECONDS` (default 1800).

## Security Notes

- Passwords are hashed with PHP password hashing APIs.
- Registration enforces password complexity (uppercase, lowercase, number, minimum length 8).
- CSRF protection is enabled on state-changing forms.
- SQL queries use PDO prepared statements.
- Session idle timeout is enforced for authenticated users.

## Technical Requirements Checklist

1. Responsive, device-independent, mobile friendly design using HTML5, Bootstrap, and CSS.
Status: Implemented.
Evidence: shared HTML5 shell, Bootstrap 5 includes, responsive CSS breakpoints, mobile navigation.

2. Custom JavaScript for dynamic client-side functionality.
Status: Implemented.
Evidence: inline validation UX, quantity spinners, search/filter, AJAX cart operations, modal interactions.

3. PHP and MySQL backend with CRUD operations.
Status: Implemented.
Evidence: create/read/update/delete flows for products, reviews, messages, orders, and profiles.

4. Proper form validation and sanitization.
Status: Implemented.
Evidence: server-side validators, sanitization helpers, and client-side feedback for key forms.

5. Security against XSS and SQL injection.
Status: Implemented.
Evidence: output escaping helper, PDO prepared statements, CSRF token checks.

6. Protected user passwords.
Status: Implemented.
Evidence: password hashing and verification using PHP password APIs.

7. W3C validation and WCAG standards.
Status: In progress (code-level controls implemented; validator reports pending).
Next step: run formal validators and archive outputs as submission evidence.

## Standards Validation Workflow

1. Run W3C HTML validator on rendered pages: home, menu, auth, cart, checkout, admin pages.
2. Run W3C CSS validator against assets/css/style.css.
3. Run WCAG checks for keyboard navigation, visible focus, form labels, ARIA announcements, and contrast.
4. Record pass/fail results and corrective actions in this README or a separate audit document.

## Audit Artifact

- Use TECHNICAL_AUDIT_REPORT.md to capture requirement-by-requirement status, validator outputs, and regression checks for submission.