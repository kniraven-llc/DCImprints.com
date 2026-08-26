<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

const DC_QUOTE_ATTACHMENT_MAX_BYTES = 2_999_999;

/**
 * Validate an optional artwork attachment without permanently storing it.
 *
 * @param array<string, mixed> $file
 * @return array{0: array{name:string,tmp_name:string,mime:string,size:int}|null, 1: string|null}
 */
function dc_home_validate_artwork_upload(array $file): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }

    if ($error !== UPLOAD_ERR_OK) {
        return [null, dc_upload_error_message($error)];
    }

    $size = (int) ($file['size'] ?? 0);
    $temporaryPath = (string) ($file['tmp_name'] ?? '');

    if ($size < 1 || $size > DC_QUOTE_ATTACHMENT_MAX_BYTES) {
        return [null, 'Artwork must be smaller than 3 MB.'];
    }

    if (
        $temporaryPath === ''
        || !is_file($temporaryPath)
        || !is_uploaded_file($temporaryPath)
    ) {
        return [null, 'The artwork upload was not valid.'];
    }

    try {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    } catch (Throwable $exception) {
        log_message('Unable to inspect quote artwork upload: ' . $exception->getMessage());
        return [null, 'The artwork file could not be inspected.'];
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    if (!is_string($mime) || !isset($allowedTypes[$mime])) {
        return [null, 'Artwork must be a JPG, PNG, WebP, or PDF file.'];
    }

    $originalName = basename((string) ($file['name'] ?? 'artwork.' . $allowedTypes[$mime]));
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '-', $originalName)
        ?: 'artwork.' . $allowedTypes[$mime];

    return [[
        'name' => mb_substr($safeName, 0, 255),
        'tmp_name' => $temporaryPath,
        'mime' => $mime,
        'size' => $size,
    ], null];
}

function dc_home_public_asset_exists(string $webPath): bool
{
    $path = parse_url($webPath, PHP_URL_PATH);
    return is_string($path) && $path !== ''
        && is_file(PUBLIC_ROOT . '/' . ltrim($path, '/'));
}

function dc_home_initials(string $name): string
{
    $words = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';

    foreach (array_slice($words, 0, 2) as $word) {
        if ($word !== '') {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }
    }

    return $initials !== '' ? $initials : 'DC';
}

function dc_home_phone_href(string $phoneNumber): string
{
    $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

    if (strlen($digits) === 10) {
        return '+1' . $digits;
    }

    if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
        return '+' . $digits;
    }

    return $digits !== '' ? '+' . $digits : '';
}

/**
 * @param array<int, array<string, mixed>> $testimonials
 */
function dc_home_render_testimonial_carousel(
    array $testimonials,
    int $cardsPerSlide,
    string $carouselId,
    string $visibilityClasses
): void {
    if ($testimonials === []) {
        return;
    }

    $slides = array_chunk($testimonials, $cardsPerSlide);
    $columnClasses = match ($cardsPerSlide) {
        1 => 'col-12',
        2 => 'col-12 col-sm-6',
        3 => 'col-12 col-md-4',
        default => 'col',
    };
    ?>
    <div class="<?= e($visibilityClasses) ?>">
        <div
            id="<?= e($carouselId) ?>"
            class="carousel slide carousel-fade testimonial-carousel"
            aria-label="Customer testimonials"
        >
            <div class="carousel-inner">
                <?php foreach ($slides as $slideIndex => $slide): ?>
                    <?php $themeClass = $slideIndex % 2 === 0
                        ? 'testimonial-slide--cream'
                        : 'testimonial-slide--clay'; ?>
                    <div class="carousel-item <?= e($themeClass) ?> <?= $slideIndex === 0 ? 'active' : '' ?>">
                        <div class="row g-4 justify-content-center">
                            <?php foreach ($slide as $testimonial): ?>
                                <?php
                                $rating = max(1, min(5, (int) ($testimonial['rating'] ?? 5)));
                                $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                                ?>
                                <div class="<?= e($columnClasses) ?>">
                                    <figure class="card testimonial-card h-100 border-0 shadow-sm mb-0">
                                        <div class="card-body d-flex flex-column">
                                            <div
                                                class="testimonial-stars mb-3"
                                                aria-label="<?= e($rating . ' out of 5 stars') ?>"
                                            >
                                                <span aria-hidden="true"><?= e($stars) ?></span>
                                            </div>

                                            <blockquote class="blockquote flex-grow-1 mb-3">
                                                <p class="mb-0">“<?= e((string) $testimonial['quote']) ?>”</p>
                                            </blockquote>

                                            <div class="testimonial-author d-flex align-items-center gap-3">
                                                <span
                                                    class="testimonial-avatar testimonial-avatar--<?= e((string) $testimonial['avatar']) ?>"
                                                    aria-hidden="true"
                                                >
                                                    <span class="testimonial-avatar__head"></span>
                                                    <span class="testimonial-avatar__body"></span>
                                                </span>

                                                <figcaption>
                                                    <strong class="d-block"><?= e((string) $testimonial['name']) ?></strong>
                                                    <span class="testimonial-source small"><?= e((string) $testimonial['source']) ?></span>
                                                </figcaption>
                                            </div>
                                        </div>
                                    </figure>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

$siteSettings = dc_site_settings();
$businessName = trim((string) ($siteSettings['business_name'] ?? 'DC Imprints'));
$businessPhoneDisplay = trim((string) ($siteSettings['phone_number'] ?? ''));
$businessPhoneHref = trim((string) ($siteSettings['phone_href'] ?? ''));
$businessEmail = trim((string) ($siteSettings['email_address'] ?? ''));
$streetAddress = trim((string) ($siteSettings['street_address'] ?? ''));
$businessCity = trim((string) ($siteSettings['city'] ?? ''));
$businessState = strtoupper(trim((string) ($siteSettings['state_code'] ?? '')));
$businessPostalCode = trim((string) ($siteSettings['postal_code'] ?? ''));
$locationNote = trim((string) ($siteSettings['location_note'] ?? ''));
$weekdayHours = trim((string) ($siteSettings['weekday_hours'] ?? ''));
$weekendHours = trim((string) ($siteSettings['weekend_hours'] ?? ''));

if ($businessPhoneHref === '') {
    $businessPhoneHref = dc_home_phone_href($businessPhoneDisplay);
}

$cityState = trim(implode(', ', array_filter(
    [$businessCity, $businessState],
    static fn (string $value): bool => $value !== ''
)));
$cityStatePostal = trim(implode(' ', array_filter(
    [$cityState, $businessPostalCode],
    static fn (string $value): bool => $value !== ''
)));
$businessAddress = trim(implode(', ', array_filter(
    [$streetAddress, $cityStatePostal],
    static fn (string $value): bool => $value !== ''
)));
$locationDisplay = trim(implode(', ', array_filter(
    [$businessCity, $businessState === 'WI' ? 'Wisconsin' : $businessState],
    static fn (string $value): bool => $value !== ''
)));

$pageTitle = trim((string) ($siteSettings['page_title'] ?? $businessName));
$metaDescription = trim((string) ($siteSettings['meta_description'] ?? ''));

$heroImageRecord = dc_site_media('hero_image');
$heroVideoRecord = dc_site_media('hero_video');
$heroImage = trim((string) ($heroImageRecord['file_path'] ?? ''));
$heroVideo = trim((string) ($heroVideoRecord['file_path'] ?? ''));
$heroVideoMime = trim((string) ($heroVideoRecord['mime_type'] ?? 'video/mp4'));
$hasHeroImage = $heroImage !== '' && dc_home_public_asset_exists($heroImage);
$hasHeroVideo = $heroVideo !== '' && dc_home_public_asset_exists($heroVideo);

$mapsQuery = trim($businessName . ' ' . $businessAddress);
$googleMapsUrl = trim((string) ($siteSettings['google_maps_url'] ?? ''));
$googleMapsEmbedUrl = trim((string) ($siteSettings['google_maps_embed_url'] ?? ''));
$googleReviewsUrl = trim((string) ($siteSettings['google_reviews_url'] ?? ''));

if ($googleMapsUrl === '') {
    $googleMapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($mapsQuery);
}

if ($googleMapsEmbedUrl === '') {
    $googleMapsEmbedUrl = 'https://www.google.com/maps?q=' . rawurlencode($mapsQuery) . '&output=embed';
}

$services = [];
foreach (dc_services(true) as $serviceRecord) {
    $imageAssetId = (int) ($serviceRecord['image_asset_id'] ?? 0);
    $imageAsset = $imageAssetId > 0 ? dc_media_asset($imageAssetId) : null;
    $imagePath = trim((string) ($imageAsset['file_path'] ?? ''));

    $services[] = [
        'title' => trim((string) ($serviceRecord['name'] ?? '')),
        'description' => trim((string) ($serviceRecord['description'] ?? '')),
        'anchor' => trim((string) ($serviceRecord['slug'] ?? '')),
        'image' => $imagePath,
        'image_exists' => $imagePath !== '' && dc_home_public_asset_exists($imagePath),
        'image_alt' => trim((string) ($imageAsset['alt_text'] ?? '')),
        'quote_label' => trim((string) (
            $serviceRecord['quote_form_label']
            ?? $serviceRecord['name']
            ?? ''
        )),
    ];
}

$testimonials = [];
foreach (dc_testimonials(true) as $testimonialRecord) {
    $avatarStyle = trim((string) ($testimonialRecord['avatar_style'] ?? 'neutral'));

    if (!in_array($avatarStyle, ['neutral', 'masculine', 'feminine'], true)) {
        $avatarStyle = 'neutral';
    }

    $testimonials[] = [
        'quote' => trim((string) ($testimonialRecord['review_text'] ?? '')),
        'name' => trim((string) ($testimonialRecord['reviewer_name'] ?? '')),
        'source' => trim((string) ($testimonialRecord['source'] ?? 'Google Review')),
        'avatar' => $avatarStyle,
        'rating' => (int) ($testimonialRecord['rating'] ?? 5),
    ];
}

$profileItems = [];
foreach (dc_profiles(true) as $profileRecord) {
    $imageAssetId = (int) ($profileRecord['image_asset_id'] ?? 0);
    $imageAsset = $imageAssetId > 0 ? dc_media_asset($imageAssetId) : null;
    $imagePath = trim((string) ($imageAsset['file_path'] ?? ''));
    $profileName = trim((string) ($profileRecord['name'] ?? ''));

    $profileItems[] = [
        'type' => trim((string) ($profileRecord['profile_type'] ?? 'staff')),
        'name' => $profileName,
        'role' => trim((string) ($profileRecord['role_title'] ?? '')),
        'bio' => trim((string) ($profileRecord['biography'] ?? '')),
        'image' => $imagePath,
        'image_exists' => $imagePath !== '' && dc_home_public_asset_exists($imagePath),
        'image_alt' => trim((string) ($imageAsset['alt_text'] ?? $profileName)),
        'initials' => dc_home_initials($profileName),
    ];
}

if ($profileItems === []) {
    $profileItems[] = [
        'type' => 'organization',
        'name' => $businessName,
        'role' => 'About the Company',
        'bio' => dc_content('about_body'),
        'image' => '',
        'image_exists' => false,
        'image_alt' => $businessName,
        'initials' => dc_home_initials($businessName),
    ];
}

$defaultProfile = $profileItems[0];
$partnerLogoSlots = [];
foreach (dc_partners(true) as $partnerRecord) {
    $logoAssetId = (int) ($partnerRecord['logo_asset_id'] ?? 0);
    $logoAsset = $logoAssetId > 0 ? dc_media_asset($logoAssetId) : null;
    $imagePath = trim((string) ($logoAsset['file_path'] ?? ''));
    $partnerName = trim((string) ($partnerRecord['name'] ?? ''));
    $placeholderLabel = trim((string) ($partnerRecord['placeholder_label'] ?? 'Partner logo'));

    $partnerLogoSlots[] = [
        'name' => $partnerName,
        'url' => trim((string) ($partnerRecord['catalog_url'] ?? '')),
        'image' => $imagePath,
        'image_exists' => $imagePath !== '' && dc_home_public_asset_exists($imagePath),
        'alt' => trim((string) (
            $logoAsset['alt_text']
            ?? ($partnerName !== '' ? $partnerName : $placeholderLabel)
        )),
        'placeholder' => $placeholderLabel,
    ];
}

$serviceOptions = [];
foreach ($services as $service) {
    if ($service['quote_label'] !== '') {
        $serviceOptions[] = $service['quote_label'];
    }
}
$serviceOptions[] = 'Other / Not Sure';
$serviceOptions = array_values(array_unique($serviceOptions));

$errors = [];
$form = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'organization' => '',
    'service' => '',
    'design_help' => false,
    'message' => '',
];

if (is_post()) {
    $form = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'organization' => trim((string) ($_POST['organization'] ?? '')),
        'service' => trim((string) ($_POST['service'] ?? '')),
        'design_help' => isset($_POST['design_help']),
        'message' => trim((string) ($_POST['message'] ?? '')),
    ];

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Refresh the page and try again.';
    }

    if ($form['name'] === '' || mb_strlen($form['name']) > 100) {
        $errors['name'] = 'Enter your name.';
    }

    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 254) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (mb_strlen($form['phone']) > 40) {
        $errors['phone'] = 'Enter a valid phone number.';
    }

    if (mb_strlen($form['organization']) > 150) {
        $errors['organization'] = 'The organization name is too long.';
    }

    if (!in_array($form['service'], $serviceOptions, true)) {
        $errors['service'] = 'Select the service you are interested in.';
    }

    if ($form['message'] === '' || mb_strlen($form['message']) > 5000) {
        $errors['message'] = 'Describe your project in 5,000 characters or fewer.';
    }

    [$artwork, $artworkError] = dc_home_validate_artwork_upload($_FILES['artwork'] ?? []);

    if ($artworkError !== null) {
        $errors['artwork'] = $artworkError;
    }

    if ($errors === []) {
        if (send_quote_email($form, $artwork, $businessName)) {
            flash('success', 'Thank you. Your request has been sent.');
            redirect('/#contact');
        }

        $errors['form'] = 'The message could not be sent. Please contact '
            . $businessName
            . ' directly.';
    }
}

$success = flash('success');

$heroEyebrow = $locationDisplay;
$heroPrimaryButtonLabel = dc_content('hero_primary_button_label', 'Request a Quote');
$heroSecondaryButtonLabel = dc_content('hero_secondary_button_label', 'Explore Services');
$servicesEyebrow = dc_content('services_eyebrow', 'What We Do');
$servicesHeading = dc_content('services_heading', 'Services for organizations of every size');
$serviceCardButtonLabel = dc_content('service_card_button_label', 'Request a quote');
$quoteBandHeading = dc_content('quote_band_heading', 'Have a project in mind?');
$quoteBandText = dc_content(
    'quote_band_text',
    'Tell DC Imprints what you need, and the team will help identify the right apparel, products, and production approach.'
);
$quoteBandButtonLabel = dc_content('quote_band_button_label', 'Start Your Quote');
$reviewsEyebrow = dc_content('reviews_eyebrow', 'Customer Feedback');
$reviewsHeading = dc_content('reviews_heading', 'Trusted by local customers');
$reviewsIntro = dc_content(
    'reviews_intro',
    'Read what customers have said about their experience working with DC Imprints.'
);
$reviewsLinkLabel = dc_content('reviews_link_label', 'Read all Google reviews');
$aboutEyebrow = dc_content('about_eyebrow', 'About DC Imprints');
$aboutHeading = dc_content('about_heading', 'The company and people behind the work');
$aboutIntro = dc_content(
    'about_intro',
    'Select DC Imprints to learn about the company, or choose a team member to see their role and experience.'
);
$aboutProfileButtonLabel = dc_content('about_profile_button_label', 'Start a Conversation');
$catalogsEyebrow = dc_content('catalogs_eyebrow', 'Catalogs & Brand Partners');
$catalogsHeading = dc_content('catalogs_heading', 'Explore available products');
$catalogsIntro = dc_content(
    'catalogs_intro',
    'Browse supplier catalogs and brand partners, then contact DC Imprints for help choosing the right products.'
);
$catalogPanelEyebrow = dc_content('catalog_panel_eyebrow', 'Browse Catalogs');
$catalogPanelHeading = dc_content('catalog_panel_heading', 'Find the right product for your project');
$catalogPanelIntro = dc_content(
    'catalog_panel_intro',
    'Use the approved supplier links below to explore available apparel, promotional products, and accessories.'
);
$catalogButtonLabel = dc_content('catalog_button_label', 'Ask for Recommendations');
$quoteEyebrow = dc_content('quote_eyebrow', 'Contact DC Imprints');
$quoteHeading = dc_content('quote_heading', 'Request a Quote');
$quoteFormIntro = dc_content(
    'quote_form_intro',
    'Choose the closest service, describe the project, and attach existing artwork when helpful. DC Imprints can also assist with design or logo preparation.'
);
$quoteSubmitLabel = dc_content('quote_submit_label', 'Send Request');
$locationEyebrow = dc_content('location_eyebrow', 'Visit or Contact Us');
$locationHeading = dc_content('location_heading', 'Local service in DeForest');
$locationIntro = dc_content(
    'location_intro',
    'Visit DC Imprints in DeForest Town Square, call during business hours, or email the team about your project.'
);
$locationDirectionsLabel = dc_content('location_directions_label', 'Get Directions');
$locationCallLabel = dc_content('location_call_label', 'Call DC Imprints');

require APP_ROOT . '/app/layout/header.php';
?>
<?php
$promotionDisplayArea = 'announcement';
require APP_ROOT . '/app/layout/promotions.php';
?>


<section class="dc-hero">
    <div class="dc-hero__media" aria-hidden="true">
        <?php if ($hasHeroVideo): ?>
            <video
                autoplay
                muted
                loop
                playsinline
                preload="metadata"
                <?= $hasHeroImage ? 'poster="' . e($heroImage) . '"' : '' ?>
            >
                <source src="<?= e($heroVideo) ?>" type="<?= e($heroVideoMime) ?>">
            </video>
        <?php elseif ($hasHeroImage): ?>
            <img src="<?= e($heroImage) ?>" alt="" fetchpriority="high">
        <?php else: ?>
            <div class="dc-hero__fallback"></div>
        <?php endif; ?>
    </div>

    <div class="dc-hero__overlay" aria-hidden="true"></div>

    <div class="container py-5">
        <div class="dc-hero__content py-5">
            <p class="dc-hero__eyebrow text-uppercase fw-semibold mb-3">
                <?= e($heroEyebrow) ?>
            </p>

            <h1 class="display-3 fw-bold mb-4"><?= e(content('home_heading')) ?></h1>
            <p class="lead fs-4 mb-4"><?= e(content('home_intro')) ?></p>

            <div class="d-flex flex-column flex-sm-row gap-3">
                <a class="btn btn-primary btn-lg px-4" href="#contact">
                    <?= e($heroPrimaryButtonLabel) ?>
                </a>
                <a class="btn btn-outline-light btn-lg px-4" href="#services">
                    <?= e($heroSecondaryButtonLabel) ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?php
$promotionDisplayArea = 'seasonal';
require APP_ROOT . '/app/layout/promotions.php';
?>

<section id="services" class="py-5 bg-body-tertiary">
    <div class="container py-lg-4">
        <div class="row mb-4 mb-lg-5">
            <div class="col-lg-9">
                <p class="text-uppercase fw-semibold text-primary mb-2"><?= e($servicesEyebrow) ?></p>
                <h2 class="display-6 fw-bold"><?= e($servicesHeading) ?></h2>
                <p class="lead mb-0"><?= e(content('services_intro')) ?></p>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($services as $service): ?>
                <div class="col-md-6 col-xl-4" data-reveal>
                    <article
                        id="<?= e($service['anchor']) ?>"
                        class="card service-card h-100 border-0 shadow-sm"
                    >
                        <div class="service-card__media" aria-hidden="true">
                            <?php if ($service['image_exists']): ?>
                                <img
                                    src="<?= e($service['image']) ?>"
                                    alt="<?= e($service['image_alt']) ?>"
                                    loading="lazy"
                                >
                            <?php else: ?>
                                <div
                                    class="service-card__placeholder service-card__placeholder--<?= e($service['anchor']) ?>"
                                ></div>
                            <?php endif; ?>
                        </div>

                        <div class="card-body p-4 d-flex flex-column">
                            <h3 class="h4"><?= e($service['title']) ?></h3>
                            <p class="flex-grow-1 mb-4"><?= e($service['description']) ?></p>
                            <a class="fw-semibold text-decoration-none" href="#contact">
                                <?= e($serviceCardButtonLabel) ?> <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="quote-band py-5" aria-label="Quote call to action">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h2 class="h1 mb-2"><?= e($quoteBandHeading) ?></h2>
                <p class="lead mb-0"><?= e($quoteBandText) ?></p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a class="btn btn-light btn-lg px-4" href="#contact">
                    <?= e($quoteBandButtonLabel) ?>
                </a>
            </div>
        </div>
    </div>
</section>

<section id="reviews" class="reviews-section py-4">
    <div class="container">
        <div class="reviews-heading mb-4">
            <p class="text-uppercase fw-semibold text-primary mb-2"><?= e($reviewsEyebrow) ?></p>
            <h2 class="display-6 fw-bold mb-3"><?= e($reviewsHeading) ?></h2>

            <div class="reviews-intro d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2 gap-lg-4">
                <p class="lead mb-0"><?= e($reviewsIntro) ?></p>

                <?php if ($googleReviewsUrl !== ''): ?>
                    <a
                        class="reviews-google-link fw-semibold text-decoration-none flex-shrink-0"
                        href="<?= e($googleReviewsUrl) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <?= e($reviewsLinkLabel) ?> <span aria-hidden="true">&nearr;</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php
        dc_home_render_testimonial_carousel($testimonials, 1, 'testimonialCarouselMobile', 'd-md-none');
        dc_home_render_testimonial_carousel($testimonials, 2, 'testimonialCarouselTablet', 'd-none d-md-block d-xl-none');
        dc_home_render_testimonial_carousel($testimonials, 3, 'testimonialCarouselDesktop', 'd-none d-xl-block');
        ?>
    </div>
</section>

<section id="about" class="py-5 bg-body-tertiary">
    <div class="container py-lg-4">
        <div class="row mb-4">
            <div class="col-lg-9">
                <p class="text-uppercase fw-semibold text-primary mb-2"><?= e($aboutEyebrow) ?></p>
                <h2 class="display-6 fw-bold"><?= e($aboutHeading) ?></h2>
                <p class="lead mb-0"><?= e($aboutIntro) ?></p>
            </div>
        </div>

        <div class="profile-browser">
            <div
                class="profile-selector-strip"
                role="group"
                aria-label="<?= e('Choose ' . $businessName . ' or a staff member') ?>"
            >
                <?php foreach ($profileItems as $index => $profile): ?>
                    <button
                        class="profile-selector<?= $profile['type'] === 'organization' ? ' profile-selector--organization' : '' ?>"
                        type="button"
                        data-profile-index="<?= $index ?>"
                        aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"
                        aria-controls="profile-detail"
                        title="<?= e($profile['name']) ?>"
                    >
                        <?php if ($profile['image_exists']): ?>
                            <img
                                src="<?= e($profile['image']) ?>"
                                alt="<?= e($profile['image_alt']) ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <span class="profile-selector__placeholder" aria-hidden="true">
                                <?= e($profile['initials']) ?>
                            </span>
                        <?php endif; ?>
                        <span class="profile-selector__label"><?= e($profile['name']) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <article id="profile-detail" class="profile-detail" aria-live="polite" aria-atomic="true">
                <div class="profile-detail__grid">
                    <div class="profile-detail__media">
                        <img
                            id="profile-detail-image"
                            src="<?= e(
                                $defaultProfile['image_exists']
                                    ? $defaultProfile['image']
                                    : 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='
                            ) ?>"
                            alt="<?= e($defaultProfile['image_alt']) ?>"
                            <?= $defaultProfile['image_exists'] ? '' : 'hidden' ?>
                        >
                        <div
                            id="profile-detail-placeholder"
                            class="profile-detail__placeholder"
                            <?= $defaultProfile['image_exists'] ? 'hidden' : '' ?>
                            aria-hidden="true"
                        >
                            <?= e($defaultProfile['initials']) ?>
                        </div>
                    </div>

                    <div class="profile-detail__content">
                        <p id="profile-detail-role" class="profile-detail__role text-uppercase mb-2">
                            <?= e($defaultProfile['role']) ?>
                        </p>
                        <h3 id="profile-detail-name" class="display-6 fw-bold mb-3">
                            <?= e($defaultProfile['name']) ?>
                        </h3>
                        <p id="profile-detail-bio" class="profile-detail__bio lead mb-4">
                            <?= e($defaultProfile['bio']) ?>
                        </p>
                        <a class="btn btn-primary" href="#contact"><?= e($aboutProfileButtonLabel) ?></a>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>

<section id="catalogs" class="py-5">
    <div class="container py-lg-4">
        <div class="row mb-4 mb-lg-5">
            <div class="col-lg-9">
                <p class="text-uppercase fw-semibold text-primary mb-2"><?= e($catalogsEyebrow) ?></p>
                <h2 class="display-6 fw-bold"><?= e($catalogsHeading) ?></h2>
                <p class="lead mb-0"><?= e($catalogsIntro) ?></p>
            </div>
        </div>

        <div class="row g-4 align-items-stretch">
            <div class="col-lg-7">
                <div class="row g-3">
                    <?php foreach ($partnerLogoSlots as $partner): ?>
                        <div class="col-6 col-md-4" data-reveal>
                            <?php if ($partner['url'] !== ''): ?>
                                <a
                                    class="catalog-logo-tile h-100"
                                    href="<?= e($partner['url']) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="<?= e(
                                        'Open '
                                        . ($partner['name'] !== '' ? $partner['name'] : $partner['placeholder'])
                                        . ' catalog'
                                    ) ?>"
                                >
                            <?php else: ?>
                                <div class="catalog-logo-tile h-100">
                            <?php endif; ?>

                            <?php if ($partner['image_exists']): ?>
                                <img src="<?= e($partner['image']) ?>" alt="<?= e($partner['alt']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="catalog-logo-placeholder" aria-hidden="true">
                                    <span><?= e($partner['placeholder']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($partner['url'] !== ''): ?>
                                </a>
                            <?php else: ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="catalog-link-panel h-100" data-reveal>
                    <p class="text-uppercase fw-semibold text-primary small mb-2"><?= e($catalogPanelEyebrow) ?></p>
                    <h3 class="h4"><?= e($catalogPanelHeading) ?></h3>
                    <p><?= e($catalogPanelIntro) ?></p>
                    <div class="catalog-link-list"><?= nl2br(e(content('supplier_links'))) ?></div>
                    <a class="btn btn-primary mt-4" href="#contact"><?= e($catalogButtonLabel) ?></a>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="contact" class="py-5 bg-body-tertiary">
    <div class="container py-lg-4">
        <div class="row mb-4 mb-lg-5">
            <div class="col-lg-8">
                <p class="text-uppercase fw-semibold text-primary mb-2"><?= e($quoteEyebrow) ?></p>
                <h2 class="display-6 fw-bold"><?= e($quoteHeading) ?></h2>
                <p class="lead mb-0"><?= e(content('contact_intro')) ?></p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <p><?= e($quoteFormIntro) ?></p>

                        <?php if ($success): ?>
                            <div class="alert alert-success" role="status"><?= e($success) ?></div>
                        <?php endif; ?>

                        <?php if (isset($errors['form'])): ?>
                            <div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div>
                        <?php endif; ?>

                        <form method="post" enctype="multipart/form-data" novalidate>
                            <?= csrf_field() ?>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="name">Name <span aria-hidden="true">*</span></label>
                                    <input
                                        class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                                        id="name"
                                        name="name"
                                        value="<?= e($form['name']) ?>"
                                        required
                                        autocomplete="name"
                                    >
                                    <?php if (isset($errors['name'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email <span aria-hidden="true">*</span></label>
                                    <input
                                        class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                                        id="email"
                                        name="email"
                                        type="email"
                                        value="<?= e($form['email']) ?>"
                                        required
                                        autocomplete="email"
                                    >
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="phone">Phone</label>
                                    <input
                                        class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                                        id="phone"
                                        name="phone"
                                        value="<?= e($form['phone']) ?>"
                                        autocomplete="tel"
                                    >
                                    <?php if (isset($errors['phone'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['phone']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="organization">Organization</label>
                                    <input
                                        class="form-control<?= isset($errors['organization']) ? ' is-invalid' : '' ?>"
                                        id="organization"
                                        name="organization"
                                        value="<?= e($form['organization']) ?>"
                                        autocomplete="organization"
                                    >
                                    <?php if (isset($errors['organization'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['organization']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="service">Service <span aria-hidden="true">*</span></label>
                                    <select
                                        class="form-select<?= isset($errors['service']) ? ' is-invalid' : '' ?>"
                                        id="service"
                                        name="service"
                                        required
                                    >
                                        <option value="">Select a service</option>
                                        <?php foreach ($serviceOptions as $option): ?>
                                            <option value="<?= e($option) ?>" <?= $form['service'] === $option ? 'selected' : '' ?>>
                                                <?= e($option) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['service'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['service']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-12">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            id="design_help"
                                            name="design_help"
                                            type="checkbox"
                                            value="1"
                                            <?= $form['design_help'] ? 'checked' : '' ?>
                                        >
                                        <label class="form-check-label" for="design_help">
                                            I would like help with a design or logo.
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="message">
                                        Project details <span aria-hidden="true">*</span>
                                    </label>
                                    <textarea
                                        class="form-control<?= isset($errors['message']) ? ' is-invalid' : '' ?>"
                                        id="message"
                                        name="message"
                                        rows="7"
                                        required
                                    ><?= e($form['message']) ?></textarea>
                                    <?php if (isset($errors['message'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['message']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="artwork">
                                        Artwork attachment <span class="text-body-secondary">(optional)</span>
                                    </label>
                                    <input
                                        class="form-control<?= isset($errors['artwork']) ? ' is-invalid' : '' ?>"
                                        id="artwork"
                                        name="artwork"
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                                    >
                                    <div class="form-text">JPG, PNG, WebP, or PDF; maximum 3 MB.</div>
                                    <?php if (isset($errors['artwork'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['artwork']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-12">
                                    <button class="btn btn-primary btn-lg" type="submit">
                                        <?= e($quoteSubmitLabel) ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="location" class="py-5">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-stretch">
            <div class="col-lg-5">
                <p class="text-uppercase fw-semibold text-primary mb-2"><?= e($locationEyebrow) ?></p>
                <h2 class="display-6 fw-bold"><?= e($locationHeading) ?></h2>
                <p class="lead"><?= e($locationIntro) ?></p>

                <dl class="location-details row mb-4">
                    <dt class="col-sm-4">Address</dt>
                    <dd class="col-sm-8">
                        <address class="mb-0">
                            <a href="<?= e($googleMapsUrl) ?>" target="_blank" rel="noopener noreferrer">
                                <?php if ($streetAddress !== ''): ?>
                                    <?= e($streetAddress) ?><br>
                                <?php endif; ?>
                                <?= e($cityStatePostal) ?>
                            </a>
                            <?php if ($locationNote !== ''): ?>
                                <span class="location-detail-note d-block mt-1">
                                    Located in <?= e($locationNote) ?>
                                </span>
                            <?php endif; ?>
                        </address>
                    </dd>

                    <?php if ($businessPhoneDisplay !== '' && $businessPhoneHref !== ''): ?>
                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">
                            <a href="tel:<?= e($businessPhoneHref) ?>"><?= e($businessPhoneDisplay) ?></a>
                        </dd>
                    <?php endif; ?>

                    <?php if ($businessEmail !== ''): ?>
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">
                            <a href="mailto:<?= e($businessEmail) ?>"><?= e($businessEmail) ?></a>
                        </dd>
                    <?php endif; ?>

                    <?php if ($weekdayHours !== '' || $weekendHours !== ''): ?>
                        <dt class="col-sm-4">Hours</dt>
                        <dd class="col-sm-8">
                            <?php if ($weekdayHours !== ''): ?>
                                <span class="d-block">Monday–Friday</span>
                                <span class="d-block"><?= e($weekdayHours) ?></span>
                            <?php endif; ?>
                            <?php if ($weekendHours !== ''): ?>
                                <span class="location-detail-note d-block mt-1">
                                    Saturday–Sunday: <?= e($weekendHours) ?>
                                </span>
                            <?php endif; ?>
                        </dd>
                    <?php endif; ?>
                </dl>

                <div class="d-flex flex-column flex-sm-row gap-2">
                    <a
                        class="btn btn-primary"
                        href="<?= e($googleMapsUrl) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <?= e($locationDirectionsLabel) ?>
                    </a>
                    <?php if ($businessPhoneHref !== ''): ?>
                        <a class="btn btn-outline-primary" href="tel:<?= e($businessPhoneHref) ?>">
                            <?= e($locationCallLabel) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="location-map rounded shadow-sm overflow-hidden">
                    <iframe
                        src="<?= e($googleMapsEmbedUrl) ?>"
                        title="<?= e('Map showing ' . $businessName . ' at ' . $businessAddress) ?>"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                    ></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<script id="profile-data" type="application/json">
<?= json_encode(
    $profileItems,
    JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_UNESCAPED_SLASHES
) ?>
</script>

<?php
$promotionDisplayArea = 'global';
require APP_ROOT . '/app/layout/promotions.php';
?>

<?php require APP_ROOT . '/app/layout/footer.php'; ?>