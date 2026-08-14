<?php

declare(strict_types=1);

require dirname(__DIR__, 2)
    . '/app/bootstrap.php';

require_admin();

$sections = [
    'business' => 'Site Settings',
    'hero' => 'Hero Section',
    'promotions' => 'Announcements & Promotions',
    'services' => 'Services',
    'cta' => 'Call to Action',
    'reviews' => 'Google Reviews',
    'profiles' => 'About & Staff',
    'partners' => 'Catalogs & Partners',
    'quote' => 'Quote Form',
    'location' => 'Location',
    'footer' => 'Footer',
    'theme' => 'Theme',
    'account' => 'Account',
];

$sectionDescriptions = [
    'business' =>
        'Manage business information, the site header, branding, and search-result details.',

    'hero' =>
        'Edit the opening message and the image or video behind it.',


    'promotions' => 'Create, schedule, prioritize, publish, and retire homepage announcements and promotions.',
    'services' =>
        'Edit the Services introduction and manage the service cards.',

    'cta' =>
        'Edit the standalone project prompt shown beneath the service cards.',

    'reviews' =>
        'Edit the Google Reviews section, link, and review cards.',

    'profiles' =>
        'Edit the business overview and add staff cards as needed.',

    'partners' =>
        'Edit the catalogs area and manage partner logos and links.',

    'quote' =>
        'Edit the quote-form heading and submit button.',

    'location' =>
        'Edit the Visit Us section. Contact details come from Site Settings.',

    'footer' =>
        'Edit the footer call to action and business summary.',

    'theme' =>
        'Choose an approved visual theme for the public website.',

    'account' =>
        'Manage the administrator login details and password.',
];

$sectionPartials = [
    'business' =>
        'site-settings.php',

    'hero' =>
        'hero.php',

    'promotions' =>
        'promotions.php',

    'services' =>
        'services.php',

    'cta' =>
        'cta.php',

    'reviews' =>
        'reviews.php',

    'profiles' =>
        'about-staff.php',

    'partners' =>
        'catalogs-partners.php',

    'quote' =>
        'quote-form.php',

    'location' =>
        'location.php',

    'footer' =>
        'footer.php',

    'theme' =>
        'theme.php',

    'account' =>
        'account.php',
];

$section = trim(
    (string) (
        $_GET['section']
        ?? 'business'
    )
);

if (!isset($sections[$section])) {
    $section = 'business';
}

$mustChangePassword =
    admin_must_change_password();

if (
    $mustChangePassword
    && $section !== 'account'
) {
    $section = 'account';
}

$partialFilename =
    $sectionPartials[$section];

$partialPath =
    __DIR__
    . '/partials/'
    . $partialFilename;

if (!is_file($partialPath)) {
    log_message(
        'Missing administration partial: '
        . $partialPath
    );

    throw new RuntimeException(
        'The requested administration section is unavailable.'
    );
}

require_once __DIR__
    . '/partials/_shared.php';

/*
 * Process the selected section before any HTML is sent.
 */
if (is_post()) {
    if (
        !verify_csrf(
            $_POST['csrf_token']
            ?? null
        )
    ) {
        flash(
            'error',
            'Your session expired. Refresh the page and try again.'
        );

        dc_admin_redirect($section);
    }

    $action = trim(
        (string) (
            $_POST['action']
            ?? ''
        )
    );

    if ($action === 'logout') {
        logout_admin();

        redirect('/admin/login/');
    }

    if (
        admin_must_change_password()
        && $action !== 'change_password'
    ) {
        flash(
            'error',
            'Change the temporary administrator password before editing the website.'
        );

        redirect(
            '/admin/?section=account#change-password'
        );
    }

    $adminPartialMode = 'process';

    require $partialPath;

    /*
     * Every recognized action redirects from its partial. Reaching this point
     * means the submitted action was not valid for the selected section.
     */
    flash(
        'error',
        'The requested administration action was not recognized.'
    );

    dc_admin_redirect($section);
}

$success = flash('success');
$warning = flash('warning');
$error = flash('error');

$siteSettings =
    dc_site_settings(true);

$activeTheme =
    dc_active_theme(true);

$currentAdmin =
    current_admin_user(true);

$pageTitle =
    $sections[$section]
    . ' | DC Imprints Admin';

require APP_ROOT
    . '/app/layout/admin-header.php';

?>
<div class="row g-4">
    <aside class="col-lg-3 col-xl-2">
        <div
            class="card border-0 shadow-sm position-sticky"
            style="top: 1rem;"
        >
            <div class="card-body p-3">
                <p class="small text-body-secondary mb-1">
                    Signed in as
                </p>

                <p class="fw-semibold mb-3">
                    <?= e(admin_display_name()) ?>
                </p>

                <nav
                    class="nav nav-pills flex-column gap-1"
                    aria-label="Administration sections"
                >
                    <?php foreach (
                        $sections
                        as $sectionKey => $sectionLabel
                    ): ?>
                        <?php if (
                            $mustChangePassword
                            && $sectionKey !== 'account'
                        ): ?>
                            <?php continue; ?>
                        <?php endif; ?>

                        <a
                            class="nav-link<?= $section === $sectionKey
                                ? ' active'
                                : '' ?>"
                            href="<?= e(
                                dc_admin_section_url(
                                    $sectionKey
                                )
                            ) ?>"
                            <?= $section === $sectionKey
                                ? 'aria-current="page"'
                                : '' ?>
                        >
                            <?= e($sectionLabel) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <hr>

                <a
                    class="btn btn-outline-secondary w-100"
                    href="/"
                    target="_blank"
                    rel="noopener"
                >
                    View Website
                </a>
            </div>
        </div>
    </aside>

    <div class="col-lg-9 col-xl-10">
        <div
            class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4"
        >
            <div>
                <h1 class="h2 mb-1">
                    <?= e($sections[$section]) ?>
                </h1>

                <p class="text-body-secondary mb-0">
                    <?= e(
                        $sectionDescriptions[$section]
                        ?? 'Manage published website content.'
                    ) ?>
                </p>
            </div>
        </div>

        <?php if ($mustChangePassword): ?>
            <div
                class="alert alert-warning"
                role="alert"
            >
                <strong>
                    Change the temporary password now.
                </strong>

                Other administration sections will become available after the
                password is changed.
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div
                class="alert alert-success"
                role="status"
            >
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($warning): ?>
            <div
                class="alert alert-warning"
                role="status"
            >
                <?= e($warning) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div
                class="alert alert-danger"
                role="alert"
            >
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php
        $adminPartialMode = 'render';

        require $partialPath;
        ?>
    </div>
</div>
<?php

require APP_ROOT
    . '/app/layout/admin-footer.php';