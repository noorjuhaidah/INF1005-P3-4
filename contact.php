<?php
// Contact page.
// Shows a contact form with CSRF protection and server side validation.
// Submitted messages are stored in contact_messages for admin review.

$page_title = 'Contact Us';
$current_page = 'contact';

// Load dependencies before rendering the header so form handling can run first.
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Handle form submission before header output so redirects still work.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // First, verify the CSRF token.
    verify_csrf(APP_URL . '/contact.php');

<<<<<<< HEAD
    // Collect and sanitize form inputs.
    $name    = clean_input($_POST['name']    ?? '');
    $email   = clean_input($_POST['email']   ?? '');
=======
    // 2. Collect + sanitise inputs
    $name = clean_input($_POST['name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
>>>>>>> 2f2abd23e06769bd9b9f415b2aebeedb574d9701
    $subject = clean_input($_POST['subject'] ?? '');
    $message = clean_input($_POST['message'] ?? '');

    // Validate required fields and basic formatting.
    $errors = [];

    if ($name === '') {
<<<<<<< HEAD
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
    } elseif (strlen($message) < 10) {
        $errors[] = 'Your message is a bit short — please give us a little more detail.';
=======
        $name_error = 'Your name is required.';
        $errors[] = $name_error;
    } elseif (mb_strlen($name) > 120) {
        $name_error = 'Your name must be 120 characters or fewer.';
        $errors[] = $name_error;
    }

    if ($email === '') {
        $email_error = 'Email address is required.';
        $errors[] = $email_error;
    } elseif (mb_strlen($email) > 254) {
        $email_error = 'Email address is too long.';
        $errors[] = $email_error;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_error = 'Please enter a valid email address.';
        $errors[] = $email_error;
    }

    if ($subject === '') {
        $subject_error = 'A subject is required.';
        $errors[] = $subject_error;
    } elseif (mb_strlen($subject) > 150) {
        $subject_error = 'Subject must be 150 characters or fewer.';
        $errors[] = $subject_error;
    }

    if ($message === '') {
        $message_error = 'A message is required.';
        $errors[] = $message_error;
    } elseif (mb_strlen($message) < 10) {
        $message_error = 'Your message is a bit short — please give us a little more detail.';
        $errors[] = $message_error;
    } elseif (mb_strlen($message) > 2000) {
        $message_error = 'Message must be 2000 characters or fewer.';
        $errors[] = $message_error;
>>>>>>> 2f2abd23e06769bd9b9f415b2aebeedb574d9701
    }

    // If everything is valid, save the message and redirect with a success notice.
    if (empty($errors)) {
        try {
<<<<<<< HEAD
            $userId = is_logged_in() ? (int)$_SESSION['user_id'] : null;
=======
            $insertColumns = [];
            $insertValues = [];
            $insertParams = [];

            if (in_array('user_id', $contactColumns, true)) {
                $insertColumns[] = 'user_id';
                $insertValues[] = '?';
                $insertParams[] = is_logged_in() ? (int) $_SESSION['user_id'] : null;
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
>>>>>>> 2f2abd23e06769bd9b9f415b2aebeedb574d9701

            $stmt = $pdo->prepare("
                INSERT INTO contact_messages
                    (user_id, name, email, subject, message, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $name, $email, $subject, $message]);

            set_flash('success', 'Thanks for reaching out, ' . e($name) . '! We will get back to you within 1–2 business days.');
            redirect(APP_URL . '/contact.php');

        } catch (PDOException $e) {
            error_log('Contact form DB error: ' . $e->getMessage());
<<<<<<< HEAD
            set_flash('danger', 'Something went wrong saving your message. Please try again later.');
=======
            set_flash('danger', 'Unable to send your message right now. Please try again in a moment.');
>>>>>>> 2f2abd23e06769bd9b9f415b2aebeedb574d9701
            redirect(APP_URL . '/contact.php');
        }
    }

    // If validation fails, fall through and re-render with errors.
    // The values already assigned above are reused to refill the form.
}

// Prefill name and email for signed in users on a fresh GET request.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && is_logged_in()) {
<<<<<<< HEAD
    $name  = $name  ?? e($_SESSION['full_name'] ?? '');
    $email = $email ?? '';  // Leave email blank for privacy.
}

// Safe defaults for all fields when the form is shown for the first time.
$name    = $name    ?? '';
$email   = $email   ?? '';
=======
    $name = $name ?? e($_SESSION['full_name'] ?? '');
    $email = $email ?? '';  // do not pre-fill email — let user type it
}

// Safe defaults for all form fields (in case of validation re-render)
$name = $name ?? '';
$email = $email ?? '';
>>>>>>> 2f2abd23e06769bd9b9f415b2aebeedb574d9701
$subject = $subject ?? '';
$message = $message ?? '';
$errors = $errors ?? [];

// Render the header after form handling is complete.
require_once __DIR__ . '/includes/header.php';
?>

<!-- Contact page header -->
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

            <!-- Contact form area -->
            <div class="col-lg-7">

<<<<<<< HEAD
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
=======
                <form method="POST" action="<?= APP_URL ?>/contact.php" class="needs-validation"
                    data-inline-validate="true" novalidate>
>>>>>>> 2f2abd23e06769bd9b9f415b2aebeedb574d9701
                    <?php csrf_field(); ?>

                    <div class="row g-3">

                        <div class="col-sm-6">
<<<<<<< HEAD
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
=======
                            <label class="form-label" for="name">Your name <span class="text-danger"
                                    aria-hidden="true">*</span></label>
                            <input type="text" id="name" name="name"
                                class="form-control <?= $name_error ? 'is-invalid' : '' ?>" value="<?= e($name) ?>"
                                autocomplete="name" maxlength="120" required
                                aria-describedby="<?= $name_error ? 'name-error' : '' ?>">
                            <?php if ($name_error): ?>
                                <div id="name-error" class="invalid-feedback"><?= e($name_error) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label" for="email">Email address <span class="text-danger"
                                    aria-hidden="true">*</span></label>
                            <input type="email" id="email" name="email"
                                class="form-control <?= $email_error ? 'is-invalid' : '' ?>" value="<?= e($email) ?>"
                                autocomplete="email" maxlength="254" required
                                aria-describedby="<?= $email_error ? 'email-error' : '' ?>">
                            <?php if ($email_error): ?>
                                <div id="email-error" class="invalid-feedback"><?= e($email_error) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="subject">Subject <span class="text-danger"
                                    aria-hidden="true">*</span></label>
                            <input type="text" id="subject" name="subject"
                                class="form-control <?= $subject_error ? 'is-invalid' : '' ?>"
                                value="<?= e($subject) ?>" placeholder="e.g. Question about my order" maxlength="150"
                                required aria-describedby="<?= $subject_error ? 'subject-error' : '' ?>">
                            <?php if ($subject_error): ?>
                                <div id="subject-error" class="invalid-feedback"><?= e($subject_error) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="message">Message <span class="text-danger"
                                    aria-hidden="true">*</span></label>
                            <textarea id="message" name="message"
                                class="form-control <?= $message_error ? 'is-invalid' : '' ?>" rows="6"
                                placeholder="Tell us what is on your mind…" maxlength="2000" required
                                aria-describedby="<?= $message_error ? 'message-error' : '' ?>"><?= e($message) ?></textarea>
                            <?php if ($message_error): ?>
                                <div id="message-error" class="invalid-feedback"><?= e($message_error) ?></div>
                            <?php endif; ?>
>>>>>>> 2f2abd23e06769bd9b9f415b2aebeedb574d9701
                        </div>

                        <div class="col-12">
                            <button type="submit" class="ld-btn-primary">
                                <i class="bi bi-send me-1" aria-hidden="true"></i>
                                Send message
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            <!-- Contact info and FAQ sidebar -->
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
                            1 Punggol Coast Road, Singapore 828608
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
                                <button
                                    class="accordion-button collapsed py-2 px-0 bg-transparent shadow-none fw-semibold small"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="false"
                                    aria-controls="faq1">
                                    How do I track my order?
                                </button>
                            </h3>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <p class="accordion-body px-0 text-muted small">
                                    Log in and visit <a href="<?= APP_URL ?>/customer/order_history.php">Order
                                        History</a>.
                                    Your order status updates as we prepare it.
                                </p>
                            </div>
                        </div>

                        <hr class="my-2">

                        <div class="accordion-item border-0">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed py-2 px-0 bg-transparent shadow-none fw-semibold small"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false"
                                    aria-controls="faq2">
                                    How does self-pickup work?
                                </button>
                            </h3>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <p class="accordion-body px-0 text-muted small">
                                    Place your order on LazyDrip and track its status in your order history.
                                    Once it shows "Ready for pickup", you can collect it. No delivery fees required.
                                </p>
                            </div>
                        </div>

                        <hr class="my-2">

                        <div class="accordion-item border-0">
                            <h3 class="accordion-header">
                                <button
                                    class="accordion-button collapsed py-2 px-0 bg-transparent shadow-none fw-semibold small"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false"
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

                    </div>
                </div>

            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>