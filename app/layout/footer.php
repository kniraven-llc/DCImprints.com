<?php

declare(strict_types=1);

/*
 * Load shared footer data independently so the footer remains reliable even
 * when a page does not explicitly define the variables used by header.php.
 */
$siteSettings = $siteSettings
    ?? dc_site_settings();

$footerServices = dc_services(true);

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

$emailAddress = trim(
    (string) (
        $siteSettings['email_address']
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

$stateCode = strtoupper(
    trim(
        (string) (
            $siteSettings['state_code']
            ?? ''
        )
    )
);

$postalCode = trim(
    (string) (
        $siteSettings['postal_code']
        ?? ''
    )
);

$countryCode = strtoupper(
    trim(
        (string) (
            $siteSettings['country_code']
            ?? 'US'
        )
    )
);

$locationNote = trim(
    (string) (
        $siteSettings['location_note']
        ?? ''
    )
);

$weekdayHours = trim(
    (string) (
        $siteSettings['weekday_hours']
        ?? ''
    )
);

$weekendHours = trim(
    (string) (
        $siteSettings['weekend_hours']
        ?? ''
    )
);

$footerMapsUrl = trim(
    (string) (
        $siteSettings['google_maps_url']
        ?? ''
    )
);

$currentPath = $currentPath
    ?? (
        parse_url(
            $_SERVER['REQUEST_URI']
            ?? '/',
            PHP_URL_PATH
        )
        ?: '/'
    );

$normalizedPath = $normalizedPath
    ?? rtrim(
        $currentPath,
        '/'
    );

$isHomePage = $isHomePage
    ?? (
        $normalizedPath === ''
        || $normalizedPath === '/index.php'
    );

$footerSectionHref = static fn (
    string $section
): string =>
    ($isHomePage ? '#' : '/#')
    . $section;

/*
 * Footer copy comes from the same editable content records used by the admin
 * interface.
 */
$footerCtaEyebrow = dc_content(
    'footer_cta_eyebrow',
    'Start Your Project'
);

$footerCtaHeading = dc_content(
    'footer_cta_heading',
    'Ready to create something people will remember?'
);

$footerCtaText = dc_content(
    'footer_cta_text',
    'Tell the DC Imprints team what you need, and they will '
    . 'help determine the right products and production approach.'
);

$footerPrimaryButtonLabel = dc_content(
    'footer_primary_button_label',
    'Request a Quote'
);

$footerSummary = dc_content(
    'footer_summary',
    'Screen printing, embroidery, promotional products, custom '
    . 'apparel, design support, and managed web stores for businesses, '
    . 'teams, schools, organizations, and events.'
);

/*
 * Use the same managed brand-mark slot as the site header.
 */
$footerBrandMark = dc_site_media(
    'brand_mark'
);

$footerBrandMarkPath = trim(
    (string) (
        $footerBrandMark['file_path']
        ?? ''
    )
);

/*
 * Preserve the current "DeForest, Wisconsin" presentation while deriving it
 * from the centralized city and state settings.
 */
$stateNames = [
    'WI' => 'Wisconsin',
];

$stateDisplay = $stateNames[$stateCode]
    ?? $stateCode;

$footerLocationDisplay = trim(
    implode(
        ', ',
        array_filter(
            [
                $city,
                $stateDisplay,
            ],
            static fn (
                string $value
            ): bool => trim($value) !== ''
        )
    )
);

/*
 * Convert the editable weekday-hours value into Schema.org's compact
 * openingHours format when it follows the site's expected time-range format.
 */
$openingHoursSchema = null;

if (
    preg_match(
        '/^\s*'
        . '(\d{1,2}):(\d{2})\s*(AM|PM)'
        . '\s*[–—-]\s*'
        . '(\d{1,2}):(\d{2})\s*(AM|PM)'
        . '\s*$/iu',
        $weekdayHours,
        $hourMatches
    ) === 1
) {
    $openingTime = DateTimeImmutable::createFromFormat(
        '!g:i A',
        $hourMatches[1]
        . ':'
        . $hourMatches[2]
        . ' '
        . strtoupper($hourMatches[3])
    );

    $closingTime = DateTimeImmutable::createFromFormat(
        '!g:i A',
        $hourMatches[4]
        . ':'
        . $hourMatches[5]
        . ' '
        . strtoupper($hourMatches[6])
    );

    if (
        $openingTime instanceof DateTimeImmutable
        && $closingTime instanceof DateTimeImmutable
    ) {
        $openingHoursSchema =
            'Mo-Fr '
            . $openingTime->format('H:i')
            . '-'
            . $closingTime->format('H:i');
    }
}
?>
</main>

<footer
    class="site-footer mt-auto"
    itemscope
    itemtype="https://schema.org/LocalBusiness"
>
    <meta
        itemprop="name"
        content="<?= e($businessName) ?>"
    >

    <?php if ($footerMapsUrl !== ''): ?>
        <link
            itemprop="hasMap"
            href="<?= e($footerMapsUrl) ?>"
        >
    <?php endif; ?>

    <?php if ($openingHoursSchema !== null): ?>
        <meta
            itemprop="openingHours"
            content="<?= e($openingHoursSchema) ?>"
        >
    <?php endif; ?>

    <div class="site-footer__cta">
        <div class="container">
            <div
                class="
                    site-footer__cta-panel
                    d-flex
                    flex-column
                    flex-lg-row
                    align-items-lg-center
                    justify-content-between
                    gap-4
                "
            >
                <div>
                    <?php if ($footerCtaEyebrow !== ''): ?>
                        <p
                            class="
                                site-footer__eyebrow
                                text-uppercase
                                fw-semibold
                                mb-2
                            "
                        >
                            <?= e($footerCtaEyebrow) ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($footerCtaHeading !== ''): ?>
                        <h2 class="h2 mb-2">
                            <?= e($footerCtaHeading) ?>
                        </h2>
                    <?php endif; ?>

                    <?php if ($footerCtaText !== ''): ?>
                        <p class="mb-0">
                            <?= e($footerCtaText) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div
                    class="
                        d-flex
                        flex-column
                        flex-sm-row
                        gap-2
                        flex-shrink-0
                    "
                >
                    <a
                        class="
                            btn
                            btn-light
                            btn-lg
                            px-4
                        "
                        href="<?= e(
                            $footerSectionHref(
                                'contact'
                            )
                        ) ?>"
                    >
                        <?= e($footerPrimaryButtonLabel) ?>
                    </a>

                    <?php if ($phoneNumber !== '' && $phoneHref !== ''): ?>
                        <a
                            class="
                                btn
                                btn-outline-light
                                btn-lg
                                px-4
                            "
                            href="tel:<?= e($phoneHref) ?>"
                        >
                            Call <?= e($phoneNumber) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="site-footer__main">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <a
                        class="site-footer__brand"
                        href="/"
                        aria-label="<?= e($businessName) ?> home"
                    >
                        <?php if ($footerBrandMarkPath !== ''): ?>
                            <img
                                class="site-footer__brand-mark"
                                src="<?= e($footerBrandMarkPath) ?>"
                                alt=""
                                aria-hidden="true"
                            >
                        <?php endif; ?>

                        <span>
                            <span
                                class="site-footer__brand-name"
                            >
                                <?= e($businessName) ?>
                            </span>

                            <?php if ($businessTagline !== ''): ?>
                                <span
                                    class="site-footer__brand-tagline"
                                >
                                    <?= e($businessTagline) ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    </a>

                    <?php if ($footerSummary !== ''): ?>
                        <p class="site-footer__summary mb-0">
                            <?= e($footerSummary) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="col-6 col-lg-2">
                    <h2 class="site-footer__heading h6">
                        Services
                    </h2>

                    <ul class="site-footer__links">
                        <?php foreach ($footerServices as $service): ?>
                            <?php
                            $serviceSlug = trim(
                                (string) (
                                    $service['slug']
                                    ?? ''
                                )
                            );

                            $serviceLabel = trim(
                                (string) (
                                    $service['footer_label']
                                    ?? $service['name']
                                    ?? ''
                                )
                            );
                            ?>

                            <?php if ($serviceSlug !== '' && $serviceLabel !== ''): ?>
                                <li>
                                    <a
                                        href="<?= e(
                                            $footerSectionHref(
                                                $serviceSlug
                                            )
                                        ) ?>"
                                    >
                                        <?= e($serviceLabel) ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="col-6 col-lg-2">
                    <h2 class="site-footer__heading h6">
                        Explore
                    </h2>

                    <ul class="site-footer__links">
                        <li>
                            <a
                                href="<?= e(
                                    $footerSectionHref(
                                        'services'
                                    )
                                ) ?>"
                            >
                                All Services
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?= e(
                                    $footerSectionHref(
                                        'reviews'
                                    )
                                ) ?>"
                            >
                                Customer Reviews
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?= e(
                                    $footerSectionHref(
                                        'about'
                                    )
                                ) ?>"
                            >
                                About <?= e($businessName) ?>
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?= e(
                                    $footerSectionHref(
                                        'catalogs'
                                    )
                                ) ?>"
                            >
                                Catalogs &amp; Partners
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?= e(
                                    $footerSectionHref(
                                        'location'
                                    )
                                ) ?>"
                            >
                                Visit Us
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?= e(
                                    $footerSectionHref(
                                        'contact'
                                    )
                                ) ?>"
                            >
                                <?= e($footerPrimaryButtonLabel) ?>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="col-lg-4">
                    <h2 class="site-footer__heading h6">
                        Contact <?= e($businessName) ?>
                    </h2>

                    <div class="site-footer__contact mb-0">
                        <?php if (
                            $streetAddress !== ''
                            || $city !== ''
                            || $stateCode !== ''
                            || $postalCode !== ''
                        ): ?>
                            <div class="site-footer__contact-row">
                                <span
                                    class="site-footer__contact-label"
                                >
                                    Address
                                </span>

                                <address
                                    class="mb-0"
                                    itemprop="address"
                                    itemscope
                                    itemtype="https://schema.org/PostalAddress"
                                >
                                    <?php if ($footerMapsUrl !== ''): ?>
                                        <a
                                            href="<?= e($footerMapsUrl) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                    <?php endif; ?>

                                    <?php if ($streetAddress !== ''): ?>
                                        <span
                                            itemprop="streetAddress"
                                        >
                                            <?= e($streetAddress) ?>
                                        </span>

                                        <?php if (
                                            $city !== ''
                                            || $stateCode !== ''
                                            || $postalCode !== ''
                                        ): ?>
                                            <br>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($city !== ''): ?>
                                        <span
                                            itemprop="addressLocality"
                                        >
                                            <?= e($city) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($city !== '' && $stateCode !== ''): ?>
                                        ,
                                    <?php endif; ?>

                                    <?php if ($stateCode !== ''): ?>
                                        <span
                                            itemprop="addressRegion"
                                        >
                                            <?= e($stateCode) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($postalCode !== ''): ?>
                                        <span
                                            itemprop="postalCode"
                                        >
                                            <?= e($postalCode) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($footerMapsUrl !== ''): ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($countryCode !== ''): ?>
                                        <meta
                                            itemprop="addressCountry"
                                            content="<?= e($countryCode) ?>"
                                        >
                                    <?php endif; ?>

                                    <?php if ($locationNote !== ''): ?>
                                        <span
                                            class="
                                                site-footer__contact-note
                                                d-block
                                            "
                                        >
                                            <?= e($locationNote) ?>
                                        </span>
                                    <?php endif; ?>
                                </address>
                            </div>
                        <?php endif; ?>

                        <?php if ($phoneNumber !== '' && $phoneHref !== ''): ?>
                            <div class="site-footer__contact-row">
                                <span
                                    class="site-footer__contact-label"
                                >
                                    Phone
                                </span>

                                <a
                                    href="tel:<?= e($phoneHref) ?>"
                                    itemprop="telephone"
                                >
                                    <?= e($phoneNumber) ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($emailAddress !== ''): ?>
                            <div class="site-footer__contact-row">
                                <span
                                    class="site-footer__contact-label"
                                >
                                    Email
                                </span>

                                <a
                                    href="mailto:<?= e($emailAddress) ?>"
                                    itemprop="email"
                                >
                                    <?= e($emailAddress) ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (
                            $weekdayHours !== ''
                            || $weekendHours !== ''
                        ): ?>
                            <div class="site-footer__contact-row">
                                <span
                                    class="site-footer__contact-label"
                                >
                                    Hours
                                </span>

                                <div>
                                    <?php if ($weekdayHours !== ''): ?>
                                        <span>
                                            Monday–Friday,
                                            <?= e($weekdayHours) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($weekendHours !== ''): ?>
                                        <span
                                            class="
                                                site-footer__contact-note
                                                d-block
                                            "
                                        >
                                            Saturday–Sunday:
                                            <?= e($weekendHours) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="site-footer__bottom">
        <div
            class="
                container
                d-flex
                flex-column
                flex-md-row
                align-items-md-center
                justify-content-between
                gap-2
            "
        >
            <p class="mb-0">
                &copy;
                <?= date('Y') ?>
                <?= e($businessName) ?>.
                All rights reserved.
            </p>

            <div
                class="
                    d-flex
                    flex-wrap
                    align-items-center
                    gap-3
                "
            >
                <?php if ($footerLocationDisplay !== ''): ?>
                    <span>
                        <?= e($footerLocationDisplay) ?>
                    </span>
                <?php endif; ?>

                <a
                    class="site-footer__back-to-top"
                    href="#main-content"
                >
                    Back to top

                    <span aria-hidden="true">
                        &uarr;
                    </span>
                </a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/promotions.js"></script>
<script src="/assets/js/seasonal-effects.js"></script>
<script src="/assets/js/site.js"></script>
</body>
</html>