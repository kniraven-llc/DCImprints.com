<?php

declare(strict_types=1);

/*
 * Shared data used throughout the header.
 *
 * Business information is loaded once from site_settings, while the service
 * menu is generated from the same service records used by the homepage,
 * quote form, and footer.
 */
$siteSettings = dc_site_settings();
$activeTheme = dc_active_theme();
$headerServices = dc_services(true);

$businessName = trim(
    (string) (
        $siteSettings['business_name']
        ?? 'DC Imprints'
    )
);

$businessTagline = trim(
    (string) (
        $siteSettings['tagline']
        ?? ''
    )
);

$phoneNumber = trim(
    (string) (
        $siteSettings['phone_number']
        ?? ''
    )
);

$phoneHref = trim(
    (string) (
        $siteSettings['phone_href']
        ?? ''
    )
);

$streetAddress = trim(
    (string) (
        $siteSettings['street_address']
        ?? ''
    )
);

$city = trim(
    (string) (
        $siteSettings['city']
        ?? ''
    )
);

$weekdayHours = trim(
    (string) (
        $siteSettings['weekday_hours']
        ?? ''
    )
);

/*
 * The compact mobile-header display removes unnecessary :00 characters while
 * leaving the full business-hours setting unchanged everywhere else.
 */
$compactWeekdayHours = preg_replace(
    '/(?<=\d):00(?=\s*(?:AM|PM))/i',
    '',
    $weekdayHours
);

if (!is_string($compactWeekdayHours)) {
    $compactWeekdayHours = $weekdayHours;
}

$mobileHours = trim(
    implode(
        ', ',
        array_filter(
            [
                'Mon–Fri',
                $compactWeekdayHours,
            ],
            static fn (
                string $value
            ): bool => trim($value) !== ''
        )
    )
);

$mobileAddress = trim(
    implode(
        ', ',
        array_filter(
            [
                $streetAddress,
                $city,
            ],
            static fn (
                string $value
            ): bool => trim($value) !== ''
        )
    )
);

$headerCallLabel = dc_content(
    'header_call_label',
    'Call us'
);

$headerQuoteButtonLabel = dc_content(
    'header_quote_button_label',
    'Request a Quote'
);

/*
 * Fixed global media slots.
 */
$brandMark = dc_site_media(
    'brand_mark'
);

$brandMarkPath = trim(
    (string) (
        $brandMark['file_path']
        ?? ''
    )
);

$favicon = dc_site_media(
    'favicon'
);

$faviconPath = trim(
    (string) (
        $favicon['file_path']
        ?? '/favicon.ico'
    )
);

$faviconMime = trim(
    (string) (
        $favicon['mime_type']
        ?? ''
    )
);

/*
 * Page metadata can still be overridden by an individual page controller.
 */
$pageTitle = trim(
    (string) (
        $pageTitle
        ?? $siteSettings['page_title']
        ?? $businessName
    )
);

if ($pageTitle === '') {
    $pageTitle = $businessName;
}

$metaDescription = trim(
    (string) (
        $metaDescription
        ?? $siteSettings['meta_description']
        ?? ''
    )
);

$currentPath = parse_url(
    $_SERVER['REQUEST_URI']
    ?? '/',
    PHP_URL_PATH
);

if (
    !is_string($currentPath)
    || $currentPath === ''
) {
    $currentPath = '/';
}

$normalizedPath = rtrim(
    $currentPath,
    '/'
);

$isHomePage =
    $normalizedPath === ''
    || $normalizedPath === '/index.php';

$sectionHref = static fn (
    string $section
): string =>
    ($isHomePage ? '#' : '/#')
    . $section;

$headerClasses = $isHomePage
    ? 'site-header site-header--overlay'
    : 'site-header site-header--solid';

$bodyPageClass = $isHomePage
    ? 'site-home'
    : 'site-interior';

$isServicesPage = str_starts_with(
    $currentPath,
    '/services'
);

$isAboutPage = str_starts_with(
    $currentPath,
    '/about'
);

$isContactPage = str_starts_with(
    $currentPath,
    '/contact'
);

/*
 * Theme values are restricted to six-digit hexadecimal colors before being
 * inserted into CSS.
 */
$validThemeColor = static function (
    mixed $value,
    string $fallback
): string {
    $color = trim(
        (string) $value
    );

    return preg_match(
        '/^#[0-9a-fA-F]{6}$/',
        $color
    ) === 1
        ? strtolower($color)
        : $fallback;
};

$hexToRgb = static function (
    string $hex
): string {
    $hex = ltrim($hex, '#');

    return implode(
        ', ',
        [
            (string) hexdec(
                substr($hex, 0, 2)
            ),
            (string) hexdec(
                substr($hex, 2, 2)
            ),
            (string) hexdec(
                substr($hex, 4, 2)
            ),
        ]
    );
};

$themePrimary = $validThemeColor(
    $activeTheme['primary_color']
    ?? null,
    '#985f2b'
);

$themePrimaryDark = $validThemeColor(
    $activeTheme['primary_dark_color']
    ?? null,
    '#7a461f'
);

$themePrimaryLight = $validThemeColor(
    $activeTheme['primary_light_color']
    ?? null,
    '#e8ded2'
);

$themePrimarySoft = $validThemeColor(
    $activeTheme['primary_soft_color']
    ?? null,
    '#c6a987'
);

$themeCharcoal = $validThemeColor(
    $activeTheme['charcoal_color']
    ?? null,
    '#212121'
);

$themePageBackground = $validThemeColor(
    $activeTheme['page_background_color']
    ?? null,
    '#ffffff'
);

$themeFooterBackground = $validThemeColor(
    $activeTheme['footer_background_color']
    ?? null,
    '#171411'
);

$themePrimaryRgb = $hexToRgb(
    $themePrimary
);

$themePrimaryDarkRgb = $hexToRgb(
    $themePrimaryDark
);

$themeKey = preg_replace(
    '/[^a-zA-Z0-9_-]/',
    '',
    (string) (
        $activeTheme['theme_key']
        ?? 'dc-brown'
    )
);

if (
    !is_string($themeKey)
    || $themeKey === ''
) {
    $themeKey = 'dc-brown';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title><?= e($pageTitle) ?></title>

    <meta
        name="description"
        content="<?= e($metaDescription) ?>"
    >

    <link
        rel="canonical"
        href="<?= e(url(ltrim($currentPath, '/'))) ?>"
    >

    <?php if ($faviconPath !== ''): ?>
        <link
            rel="icon"
            href="<?= e($faviconPath) ?>"
            <?= $faviconMime !== ''
                ? 'type="' . e($faviconMime) . '"'
                : '' ?>
        >
    <?php endif; ?>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="/assets/css/site.css"
        rel="stylesheet"
    >

    <link
        href="/assets/css/themes.css"
        rel="stylesheet"
    >

    <link
        href="/assets/css/promotions.css"
        rel="stylesheet"
    >

    <link
        href="/assets/css/seasonal-effects.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --dc-brown: <?= e($themePrimary) ?>;
            --dc-brown-dark: <?= e($themePrimaryDark) ?>;
            --dc-brown-light: <?= e($themePrimaryLight) ?>;
            --dc-brown-soft: <?= e($themePrimarySoft) ?>;
            --dc-charcoal: <?= e($themeCharcoal) ?>;
            --dc-page-background: <?= e($themePageBackground) ?>;
            --dc-footer-background: <?= e($themeFooterBackground) ?>;

            --bs-primary: <?= e($themePrimary) ?>;
            --bs-primary-rgb: <?= e($themePrimaryRgb) ?>;

            --bs-link-color: <?= e($themePrimary) ?>;
            --bs-link-color-rgb: <?= e($themePrimaryRgb) ?>;

            --bs-link-hover-color: <?= e($themePrimaryDark) ?>;
            --bs-link-hover-color-rgb: <?= e($themePrimaryDarkRgb) ?>;

            --bs-focus-ring-color:
                rgba(
                    <?= e($themePrimaryRgb) ?>,
                    0.25
                );
        }
    </style>
</head>

<body
    class="<?= e($bodyPageClass) ?> d-flex flex-column min-vh-100"
    data-theme="<?= e($themeKey) ?>"
>
<a
    class="skip-link visually-hidden-focusable"
    href="#main-content"
>
    Skip to content
</a>

<header
    id="site-header"
    class="<?= e($headerClasses) ?>"
>
    <nav
        class="navbar navbar-expand-xl site-navbar"
        aria-label="Primary navigation"
    >
        <div class="container">
            <a
                class="navbar-brand site-brand"
                href="/"
                aria-label="<?= e($businessName) ?> home"
            >
                <?php if ($brandMarkPath !== ''): ?>
                    <img
                        class="site-brand__mark"
                        src="<?= e($brandMarkPath) ?>"
                        alt=""
                        aria-hidden="true"
                    >
                <?php endif; ?>

                <span class="site-brand__text">
                    <span class="site-brand__name">
                        <?= e($businessName) ?>
                    </span>

                    <?php if ($businessTagline !== ''): ?>
                        <span class="site-brand__tagline">
                            <?= e($businessTagline) ?>
                        </span>
                    <?php endif; ?>
                </span>
            </a>

            <button
                class="navbar-toggler site-navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavigation"
                aria-controls="mainNavigation"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="site-navbar-toggler__line"></span>
                <span class="site-navbar-toggler__line"></span>
                <span class="site-navbar-toggler__line"></span>
            </button>

            <div
                class="collapse navbar-collapse"
                id="mainNavigation"
            >
                <ul
                    class="navbar-nav ms-auto align-items-xl-center"
                >
                    <li class="nav-item dropdown">
                        <a
                            class="nav-link dropdown-toggle site-nav-link<?= $isServicesPage ? ' active' : '' ?>"
                            href="<?= e($sectionHref('services')) ?>"
                            id="servicesDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            data-nav-section="services"
                            <?= $isServicesPage
                                ? 'aria-current="page"'
                                : '' ?>
                        >
                            Services
                        </a>

                        <ul
                            class="dropdown-menu services-menu"
                            aria-labelledby="servicesDropdown"
                        >
                            <?php foreach ($headerServices as $service): ?>
                                <?php
                                $serviceSlug = trim(
                                    (string) (
                                        $service['slug']
                                        ?? ''
                                    )
                                );

                                $serviceLabel = trim(
                                    (string) (
                                        $service['navigation_label']
                                        ?? $service['name']
                                        ?? ''
                                    )
                                );

                                $serviceSummary = trim(
                                    (string) (
                                        $service['navigation_summary']
                                        ?? $service['description']
                                        ?? ''
                                    )
                                );
                                ?>

                                <li>
                                    <a
                                        class="dropdown-item services-menu__link"
                                        href="<?= e($sectionHref($serviceSlug)) ?>"
                                        data-site-nav-link
                                    >
                                        <span class="services-menu__title">
                                            <?= e($serviceLabel) ?>
                                        </span>

                                        <?php if ($serviceSummary !== ''): ?>
                                            <span class="services-menu__description">
                                                <?= e($serviceSummary) ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link site-nav-link"
                            href="<?= e($sectionHref('reviews')) ?>"
                            data-nav-section="reviews"
                            data-site-nav-link
                        >
                            Reviews
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link site-nav-link<?= $isAboutPage ? ' active' : '' ?>"
                            href="<?= e($sectionHref('about')) ?>"
                            data-nav-section="about"
                            <?= $isAboutPage
                                ? 'aria-current="page"'
                                : '' ?>
                            data-site-nav-link
                        >
                            About
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link site-nav-link"
                            href="<?= e($sectionHref('catalogs')) ?>"
                            data-nav-section="catalogs"
                            data-site-nav-link
                        >
                            Catalogs
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link site-nav-link"
                            href="<?= e($sectionHref('location')) ?>"
                            data-nav-section="location"
                            data-site-nav-link
                        >
                            Visit Us
                        </a>
                    </li>
                </ul>

                <?php if ($phoneNumber !== '' && $phoneHref !== ''): ?>
                    <a
                        class="site-nav-phone d-none d-xl-inline-flex"
                        href="tel:<?= e($phoneHref) ?>"
                        aria-label="<?= e(
                            'Call '
                            . $businessName
                            . ' at '
                            . $phoneNumber
                        ) ?>"
                    >
                        <svg
                            class="site-nav-phone__icon"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                d="M6.62 10.79a15.46 15.46 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24 11.36 11.36 0 0 0 3.57.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.61 21 3 13.39 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.36 11.36 0 0 0 .57 3.57 1 1 0 0 1-.25 1.02l-2.2 2.2Z"
                            ></path>
                        </svg>

                        <span>
                            <span class="site-nav-phone__label">
                                <?= e($headerCallLabel) ?>
                            </span>

                            <strong>
                                <?= e($phoneNumber) ?>
                            </strong>
                        </span>
                    </a>
                <?php endif; ?>

                <a
                    class="btn site-nav-quote<?= $isContactPage ? ' active is-active' : '' ?>"
                    href="<?= e($sectionHref('contact')) ?>"
                    data-nav-section="contact"
                    <?= $isContactPage
                        ? 'aria-current="page"'
                        : '' ?>
                    data-site-nav-link
                >
                    <?= e($headerQuoteButtonLabel) ?>
                </a>

                <div class="site-nav-mobile-panel d-xl-none">
                    <?php if ($phoneNumber !== '' && $phoneHref !== ''): ?>
                        <a
                            class="site-nav-mobile-panel__item"
                            href="tel:<?= e($phoneHref) ?>"
                        >
                            <span class="site-nav-mobile-panel__label">
                                Phone
                            </span>

                            <strong>
                                <?= e($phoneNumber) ?>
                            </strong>
                        </a>
                    <?php endif; ?>

                    <?php if ($mobileHours !== ''): ?>
                        <div class="site-nav-mobile-panel__item">
                            <span class="site-nav-mobile-panel__label">
                                Hours
                            </span>

                            <strong>
                                <?= e($mobileHours) ?>
                            </strong>
                        </div>
                    <?php endif; ?>

                    <?php if ($mobileAddress !== ''): ?>
                        <a
                            class="site-nav-mobile-panel__item"
                            href="<?= e($sectionHref('location')) ?>"
                            data-site-nav-link
                        >
                            <span class="site-nav-mobile-panel__label">
                                Visit
                            </span>

                            <strong>
                                <?= e($mobileAddress) ?>
                            </strong>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>

<main
    id="main-content"
    class="flex-grow-1"
>