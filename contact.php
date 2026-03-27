<?php
// =============================================================
// contact.php — Contact page
// Shows a contact form with CSRF protection + server-side
// validation. Submitted messages are stored in
// contact_messages for admin reference.
// =============================================================

$page_title   = 'Contact Us';
$current_page = 'contact';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$contactMessageColumn = 'message';
$contactColumns = [];
try {
    $colStmt = $pdo->query("SHOW COLUMNS FROM contact_messages");
    $contactColumns = array_column($colStmt->fetchAll(), 'Field');
    if (!in_array('message', $contactColumns, true) && in_array('message_text', $contactColumns, true)) {
        $contactMessageColumn = 'message_text';
    }
} catch (PDOException $e) {
    $contactColumns = [];
    $contactMessageColumn = 'message';
}

// ------------------------------------------------------------------
// Handle POST submission
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. CSRF check (uses the helper from functions.php)
    verify_csrf(APP_URL . '/contact.php');

    // 2. Collect + sanitise inputs
    $name    = clean_input($_POST['name']    ?? '');
    $email   = clean_input($_POST['email']   ?? '');
    $subject = clean_input($_POST['subject'] ?? '');
    $message = clean_input($_POST['message'] ?? '');

    // 3. Validate
    $errors = [];

    if ($name === '') {
        $errors[] = 'Your name is required.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if ($subject === '') {
        $errors[] = 'A subject is required.';
    }

    if ($message === '') {
        $errors[] = 'A message is required.';
    } elseif (mb_strlen($message) < 10) {
        $errors[] = 'Your message is a bit short — please give us a little more detail.';
    }

    // 4. If valid, store in DB and redirect with success flash
    if (empty($errors)) {
        try {
            $insertColumns = [];
            $insertValues = [];
            $insertParams = [];

            if (in_array('user_id', $contactColumns, true)) {
                $insertColumns[] = 'user_id';
                $insertValues[] = '?';
                $insertParams[] = is_logged_in() ? (int)$_SESSION['user_id'] : null;
            }

            if (in_array('name', $contactColumns, true)) {
                $insertColumns[] = 'name';
                $insertValues[] = '?';
                $insertParams[] = $name;
            }

            if (in_array('email', $contactColumns, true)) {
                $insertColumns[] = 'email';
                $insertValues[] = '?';
                $insertParams[] = $email;
            }

            if (in_array('subject', $contactColumns, true)) {
                $insertColumns[] = 'subject';
                $insertValues[] = '?';
                $insertParams[] = $subject;
            }

            $insertColumns[] = $contactMessageColumn;
            $insertValues[] = '?';
            $insertParams[] = $message;

            if (in_array('created_at', $contactColumns, true)) {
                $insertColumns[] = 'created_at';
                $insertValues[] = 'NOW()';
            }

            $stmt = $pdo->prepare("
                INSERT INTO contact_messages
                    (" . implode(', ', $insertColumns) . ")
                VALUES (" . implode(', ', $insertValues) . ")
            ");
            $stmt->execute($insertParams);

            set_flash('success', 'Thanks for reaching out, ' . e($name) . '! We will get back to you within 1–2 business days.');
            redirect(APP_URL . '/contact.php');

        } catch (PDOException $e) {
            error_log('Contact form DB error: ' . $e->getMessage());
            set_flash('danger', 'Contact form error: ' . $e->getMessage());
            redirect(APP_URL . '/contact.php');
        }
    }

    // If there are validation errors, fall through and re-render the
    // form with the error list. We keep the user's input via variables
    // already set above.
}

// Prefill fields from logged-in user if this is a fresh GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && is_logged_in()) {
    $name  = $name  ?? e($_SESSION['full_name'] ?? '');
    $email = $email ?? '';  // do not pre-fill email — let user type it
}

// Safe defaults for all form fields (in case of validation re-render)
$name    = $name    ?? '';
$email   = $email   ?? '';
$subject = $subject ?? '';
$message = $message ?? '';
$errors  = $errors  ?? [];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page header -->
<section class="ld-section-sm" style="background: var(--ld-blue-light);">
    <div class="container text-center py-4">
        <h1 class="ld-section-title">Get in touch</h1>
        <p class="text-muted mb-0">
            Got a question, feedback, or just want to say hi? We would love to hear from you.
        </p>
    </div>
</section>

<section class="ld-section">
    <div class="container">
        <div class="row gy-5 justify-content-between">

            <!-- ---- Contact form ---- -->
            <div class="col-lg-7">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <strong>Please fix the following:</strong>
                        <ul class="mb-0 mt-1">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_URL ?>/contact.php" novalidate>
                    <?php csrf_field(); ?>

                    <div class="row g-3">

                        <div class="col-sm-6">
                            <label class="form-label" for="name">Your name <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   class="form-control <?= !empty($errors) && $name === '' ? 'is-invalid' : '' ?>"
                                   value="<?= e($name) ?>"
                                   autocomplete="name"
                                   required>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label" for="email">Email address <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-control <?= !empty($errors) && !filter_var($email, FILTER_VALIDATE_EMAIL) ? 'is-invalid' : '' ?>"
                                   value="<?= e($email) ?>"
                                   autocomplete="email"
                                   required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="subject">Subject <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text"
                                   id="subject"
                                   name="subject"
                                   class="form-control <?= !empty($errors) && $subject === '' ? 'is-invalid' : '' ?>"
                                   value="<?= e($subject) ?>"
                                   placeholder="e.g. Question about my order"
                                   required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="message">Message <span class="text-danger" aria-hidden="true">*</span></label>
                            <textarea id="message"
                                      name="message"
                                      class="form-control <?= !empty($errors) && $message === '' ? 'is-invalid' : '' ?>"
                                      rows="6"
                                      placeholder="Tell us what is on your mind…"
                                      required><?= e($message) ?></textarea>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="ld-btn-primary">
                                <i class="bi bi-send me-1" aria-hidden="true"></i>
                                Send message
                            </button>
                        </div>

                    </div><!-- /.row -->
                </form>
            </div><!-- /.col -->

            <!-- ---- Info sidebar ---- -->
            <div class="col-lg-4">

                <div class="card ld-card p-4 mb-4">
                    <h2 class="h5 mb-3">Contact info</h2>
                    <ul class="list-unstyled text-muted small mb-0">
                        <li class="mb-2">
                            <i class="bi bi-envelope me-2" aria-hidden="true"></i>
                            <a href="mailto:hello@lazydrip.sg">hello@lazydrip.sg</a>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-geo-alt me-2" aria-hidden="true"></i>
                            Somewhere cosy in Singapore
                        </li>
                        <li>
                            <i class="bi bi-clock me-2" aria-hidden="true"></i>
                            Mon–Fri, 8 am – 6 pm
                        </li>
                    </ul>
                </div>

                <div class="card ld-card p-4">
                    <h2 class="h5 mb-3">Frequently asked</h2>
                    <div class="accordion accordion-flush" id="faqAccordion">

                        <div class="accordion-item border-0">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed p-0 bg-transparent shadow-none fw-semibold small"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#faq1"
                                        aria-expanded="false"
                                        aria-controls="faq1">
                                    How do I track my order?
                                </button>
                            </h3>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <p class="accordion-body px-0 text-muted small">
                                    Log in and visit <a href="<?= APP_URL ?>/customer/order_history.php">Order History</a>.
                                    Your order status updates as we prepare it.
                                </p>
                            </div>
                        </div>

                        <hr class="my-2">

                        <div class="accordion-item border-0">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed p-0 bg-transparent shadow-none fw-semibold small"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#faq2"
                                        aria-expanded="false"
                                        aria-controls="faq2">
                                    How does self-pickup work?
                                </button>
                            </h3>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <p class="accordion-body px-0 text-muted small">
                                    Order on LazyDrip, then collect via Grab when the status shows
                                    "Ready for pickup". No delivery charges apply.
                                </p>
                            </div>
                        </div>

                        <hr class="my-2">

                        <div class="accordion-item border-0">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed p-0 bg-transparent shadow-none fw-semibold small"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#faq3"
                                        aria-expanded="false"
                                        aria-controls="faq3">
                                    How do I earn &amp; redeem points?
                                </button>
                            </h3>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <p class="accordion-body px-0 text-muted small">
                                    Earn <?= POINTS_PER_DOLLAR ?> point per $1 spent.
                                    Redeem <?= POINTS_REDEEM_AMOUNT ?> points for
                                    <?= format_price(POINTS_REDEEM_VALUE) ?> off at checkout.
                                    See the <a href="<?= APP_URL ?>/rewards.php">Rewards</a> page for full details.
                                </p>
                            </div>
                        </div>

                    </div><!-- /#faqAccordion -->
                </div>

            </div><!-- /.col sidebar -->

        </div><!-- /.row -->
    </div><!-- /.container -->
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
