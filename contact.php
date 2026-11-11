<?php
require_once _DIR_ . '/includes/init.php';

$pageTitle = 'Contact Us';

// Handle POST inline (PRG)
if (is_post()) {
    csrf_verify();

    $v = new Validator($_POST);
    $v->required('name', 'Name')->max('name', 120, 'Name');
    $v->required('email', 'Email')->email('email');
    $v->required('subject', 'Subject')->max('subject', 190, 'Subject');
    $v->required('message', 'Message');
    $errors = $v->errors();

    if (!$errors) {
        if (!rate_limit('contact:' . client_ip(), 3, 600)) {
            flash('warning', 'Too many submissions. Please wait a few minutes.');
            redirect('contact.php');
        }

        $userId = current_user_id();
        db_insert(
            'INSERT INTO contact_messages (user_id, name, email, subject, message) VALUES (?,?,?,?,?)',
            [$userId, clean_text(input('name')), strtolower(input('email')), clean_text(input('subject')), clean_text(input('message'))]
        );

        // Notify support
        send_mail(SUPPORT_EMAIL, '[' . APP_NAME . '] Contact: ' . input('subject'),
            '<p>From: ' . e(input('name')) . ' &lt;' . e(input('email')) . '&gt;</p>'
            . '<p>' . nl2br(e(input('message'))) . '</p>'
        );

        flash('success', 'Your message has been sent! We\'ll get back to you within 24 hours.');
        redirect('contact.php');
    }

    set_old($_POST, $errors);
}

include _DIR_ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:900px">
    <h1 class="h3 mb-1">Contact Us</h1>
    <p class="text-muted mb-4">Have a question or need help? We're here for you.</p>

    <div class="row g-4">
        <!-- Contact info -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">Get in touch</h5>

                    <div class="d-flex gap-3 mb-3">
                        <div class="flex-shrink-0 text-primary fs-4"><i class="bi bi-envelope"></i></div>
                        <div>
                            <div class="fw-semibold small">Email</div>
                            <a href="mailto:<?= e(SUPPORT_EMAIL) ?>" class="text-muted small"><?= e(SUPPORT_EMAIL) ?></a>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mb-3">
                        <div class="flex-shrink-0 text-primary fs-4"><i class="bi bi-telephone"></i></div>
                        <div>
                            <div class="fw-semibold small">Phone</div>
                            <span class="text-muted small"><?= e(SUPPORT_PHONE) ?></span>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mb-3">
                        <div class="flex-shrink-0 text-primary fs-4"><i class="bi bi-person-heart"></i></div>
                        <div>
                            <div class="fw-semibold small">Expert consultation</div>
                            <a href="<?= e(url('consultations.php')) ?>" class="text-muted small">Book a session →</a>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="flex-shrink-0 text-primary fs-4"><i class="bi bi-chat-heart"></i></div>
                        <div>
                            <div class="fw-semibold small">AI Assistant</div>
                            <a href="<?= e(url('ai-chatbot.php')) ?>" class="text-muted small">Chat now →</a>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="small text-muted fw-semibold">Support hours</div>
                        <div class="small text-muted">Mon – Fri, 9 AM – 6 PM</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact form -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form method="post" action="" novalidate class="needs-validation">
                        <?= csrf_field() ?>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="name" class="form-label">Your name</label>
                                <input type="text" id="name" name="name" required maxlength="120"
                                       class="form-control<?= invalid_class('name') ?>"
                                       value="<?= e(old('name', is_logged_in() ? current_user()['full_name'] : '')) ?>">
                                <?= invalid_feedback('name') ?>
                            </div>
                            <div class="col-sm-6">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" id="email" name="email" required maxlength="190"
                                       class="form-control<?= invalid_class('email') ?>"
                                       value="<?= e(old('email', is_logged_in() ? current_user()['email'] : '')) ?>">
                                <?= invalid_feedback('email') ?>
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" id="subject" name="subject" required maxlength="190"
                                       class="form-control<?= invalid_class('subject') ?>"
                                       value="<?= e(old('subject')) ?>">
                                <?= invalid_feedback('subject') ?>
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label">Message</label>
                                <textarea id="message" name="message" rows="5" required
                                          class="form-control<?= invalid_class('message') ?>"
                                          placeholder="Describe your question or issue…"><?= e(old('message')) ?></textarea>
                                <?= invalid_feedback('message') ?>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary px-4">Send message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include _DIR_ . '/includes/footer.php'; ?>