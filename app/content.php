<?php

declare(strict_types=1);

/**
 * Shared database-backed content layer for DCImprints.com.
 *
 * Public pages use the read functions in this file. The administration
 * interface uses the write functions. Every function falls back safely when
 * the database is unavailable, so a temporary database failure does not make
 * the public website unusable.
 */

/**
 * Return the request-local content cache by reference.
 *
 * @return array<string, mixed>
 */
function &dc_content_cache(): array
{
    static $cache = [];

    return $cache;
}

function dc_forget_content_cache(?string $key = null): void
{
    $cache =& dc_content_cache();

    if ($key === null) {
        $cache = [];
        return;
    }

    unset($cache[$key]);
}

/**
 * Log a content-layer database failure without exposing it to visitors.
 */
function dc_log_content_error(
    string $operation,
    Throwable $exception
): void {
    log_message(
        sprintf(
            'Content operation "%s" failed: %s',
            $operation,
            $exception->getMessage()
        )
    );
}

/**
 * Determine whether a public web path currently maps to a real file.
 */
function dc_public_path_exists(?string $webPath): bool
{
    if ($webPath === null || trim($webPath) === '') {
        return false;
    }

    $path = parse_url($webPath, PHP_URL_PATH);

    if (!is_string($path) || $path === '') {
        return false;
    }

    return is_file(
        PUBLIC_ROOT
        . '/'
        . ltrim($path, '/')
    );
}

/**
 * Create a telephone URI from a human-readable North American phone number.
 */
function dc_phone_href(string $phoneNumber): string
{
    $phoneNumber = trim($phoneNumber);
    $hasLeadingPlus = str_starts_with(
        $phoneNumber,
        '+'
    );

    $digits = preg_replace(
        '/\D+/',
        '',
        $phoneNumber
    ) ?? '';

    if ($digits === '') {
        return '';
    }

    if ($hasLeadingPlus) {
        return '+' . $digits;
    }

    if (strlen($digits) === 10) {
        return '+1' . $digits;
    }

    return '+' . $digits;
}

/**
 * Convert a title into a stable, URL-safe slug.
 */
function dc_slugify(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return 'item';
    }

    if (
        function_exists(
            'transliterator_transliterate'
        )
    ) {
        $transliterated =
            transliterator_transliterate(
                'Any-Latin; Latin-ASCII; Lower()',
                $value
            );

        if (is_string($transliterated)) {
            $value = $transliterated;
        }
    } else {
        $converted = iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            $value
        );

        if (is_string($converted)) {
            $value = strtolower($converted);
        } else {
            $value = strtolower($value);
        }
    }

    $value = preg_replace(
        '/[^a-z0-9]+/',
        '-',
        $value
    ) ?? '';

    $value = trim($value, '-');

    return $value !== ''
        ? $value
        : 'item';
}

/**
 * Generate compact initials for profile fallbacks.
 */
function dc_generate_initials(string $name): string
{
    $words = preg_split(
        '/\s+/',
        trim($name)
    ) ?: [];

    $initials = '';

    foreach (
        array_slice($words, 0, 2)
        as $word
    ) {
        if ($word === '') {
            continue;
        }

        $initials .= mb_strtoupper(
            mb_substr($word, 0, 1)
        );
    }

    return $initials !== ''
        ? $initials
        : 'DC';
}

/**
 * Current business information used when the database is unavailable.
 *
 * @return array<string, mixed>
 */
function dc_default_site_settings(): array
{
    return [
        'id' => 1,
        'business_name' => 'DC Imprints',
        'tagline' =>
            'Custom apparel & promotional products',
        'phone_number' => '(608) 912-1260',
        'email_address' => 'sales@dcimprints.com',
        'street_address' => '100 Commerce St',
        'city' => 'DeForest',
        'state_code' => 'WI',
        'postal_code' => '53532',
        'country_code' => 'US',
        'location_note' => 'DeForest Town Square',
        'weekday_hours' => '8:00 AMâ€“5:00 PM',
        'weekend_hours' => 'Closed',
        'google_reviews_url' =>
            'https://www.google.com/maps/search/'
            . '?api=1&query='
            . 'DC%20Imprints%20Inc%20DeForest%20WI',
        'page_title' =>
            'DC Imprints | Custom Apparel and '
            . 'Promotional Products',
        'meta_description' =>
            'Screen printing, embroidery, '
            . 'promotional products, corporate apparel, '
            . 'design help, and custom web stores '
            . 'in DeForest, Wisconsin.',
        'active_theme_id' => 1,
    ];
}

/**
 * Add values that are always derived from the shared business settings.
 *
 * @param array<string, mixed> $settings
 *
 * @return array<string, mixed>
 */
function dc_derive_site_settings(
    array $settings
): array {
    $street = trim(
        (string) (
            $settings['street_address']
            ?? ''
        )
    );

    $city = trim(
        (string) (
            $settings['city']
            ?? ''
        )
    );

    $state = trim(
        (string) (
            $settings['state_code']
            ?? ''
        )
    );

    $postalCode = trim(
        (string) (
            $settings['postal_code']
            ?? ''
        )
    );

    $businessName = trim(
        (string) (
            $settings['business_name']
            ?? ''
        )
    );

    $cityAndState = trim(
        implode(
            ', ',
            array_filter(
                [$city, $state],
                'strlen'
            )
        )
    );

    $cityStatePostal = trim(
        implode(
            ' ',
            array_filter(
                [
                    $cityAndState,
                    $postalCode,
                ],
                'strlen'
            )
        )
    );

    $fullAddress = trim(
        implode(
            ', ',
            array_filter(
                [
                    $street,
                    $cityStatePostal,
                ],
                'strlen'
            )
        )
    );

    $mapQuery = trim(
        implode(
            ' ',
            array_filter(
                [
                    $businessName,
                    $fullAddress,
                ],
                'strlen'
            )
        )
    );

    $settings['city_state'] =
        $cityAndState;

    $settings['city_state_postal'] =
        $cityStatePostal;

    $settings['full_address'] =
        $fullAddress;

    $settings['phone_href'] =
        dc_phone_href(
            (string) (
                $settings['phone_number']
                ?? ''
            )
        );

    $settings['google_maps_url'] =
        'https://www.google.com/maps/search/'
        . '?api=1&query='
        . rawurlencode($mapQuery);

    $settings['google_maps_embed_url'] =
        'https://www.google.com/maps?q='
        . rawurlencode($mapQuery)
        . '&output=embed';

    $settings['location_display'] = trim(
        implode(
            ', ',
            array_filter(
                [
                    (string) (
                        $settings['location_note']
                        ?? ''
                    ),
                    $settings['city_state'],
                ],
                static fn (
                    string $value
                ): bool => trim($value) !== ''
            )
        )
    );

    return $settings;
}

/**
 * Read the single shared site-settings row.
 *
 * @return array<string, mixed>
 */
function dc_site_settings(
    bool $refresh = false
): array {
    $cache =& dc_content_cache();

    if (
        !$refresh
        && isset($cache['site_settings'])
    ) {
        return $cache['site_settings'];
    }

    $settings =
        dc_default_site_settings();

    $pdo = database();

    if ($pdo !== null) {
        try {
            $statement = $pdo->query(
                'SELECT *
                 FROM site_settings
                 WHERE id = 1
                 LIMIT 1'
            );

            $row = $statement->fetch();

            if (is_array($row)) {
                $settings = array_replace(
                    $settings,
                    $row
                );
            }
        } catch (Throwable $exception) {
            dc_log_content_error(
                'read site settings',
                $exception
            );
        }
    }

    $settings['id'] =
        (int) (
            $settings['id']
            ?? 1
        );

    $settings['active_theme_id'] =
        (int) (
            $settings['active_theme_id']
            ?? 1
        );

    $settings =
        dc_derive_site_settings(
            $settings
        );

    $cache['site_settings'] =
        $settings;

    return $settings;
}

/**
 * Update the business information that is reused throughout the site.
 *
 * @param array<string, mixed> $values
 */
function dc_update_site_settings(
    array $values
): bool {
    $allowed = [
        'business_name',
        'tagline',
        'phone_number',
        'email_address',
        'street_address',
        'city',
        'state_code',
        'postal_code',
        'country_code',
        'location_note',
        'weekday_hours',
        'weekend_hours',
        'google_reviews_url',
        'page_title',
        'meta_description',
    ];

    $current =
        dc_site_settings();

    $updates = [];

    foreach ($allowed as $key) {
        if (
            array_key_exists(
                $key,
                $values
            )
        ) {
            $updates[$key] = trim(
                (string) $values[$key]
            );
        } else {
            $updates[$key] =
                (string) (
                    $current[$key]
                    ?? ''
                );
        }
    }

    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'UPDATE site_settings SET
                business_name = :business_name,
                tagline = :tagline,
                phone_number = :phone_number,
                email_address = :email_address,
                street_address = :street_address,
                city = :city,
                state_code = :state_code,
                postal_code = :postal_code,
                country_code = :country_code,
                location_note = :location_note,
                weekday_hours = :weekday_hours,
                weekend_hours = :weekend_hours,
                google_reviews_url =
                    :google_reviews_url,
                page_title = :page_title,
                meta_description =
                    :meta_description
             WHERE id = 1'
        );

        $statement->execute($updates);

        dc_forget_content_cache(
            'site_settings'
        );

        return true;
    } catch (Throwable $exception) {
        dc_log_content_error(
            'update site settings',
            $exception
        );

        return false;
    }
}

/**
 * Current fixed content slots used when the database is unavailable.
 *
 * @return array<string, string>
 */
function dc_default_content_values(): array
{
    return [
        'header_call_label' =>
            'Call us',

        'header_quote_button_label' =>
            'Request a Quote',

        'home_heading' =>
            'Custom apparel and promotional '
            . 'products for local organizations.',

        'home_intro' =>
            'DC Imprints provides screen printing, '
            . 'embroidery, promotional products, '
            . 'corporate apparel, and custom web stores '
            . 'from DeForest, Wisconsin.',

        'hero_primary_button_label' =>
            'Request a Quote',

        'hero_secondary_button_label' =>
            'Explore Services',

        'services_eyebrow' =>
            'What We Do',

        'services_heading' =>
            'Services for organizations of every size',

        'services_intro' =>
            'We help businesses, schools, teams, '
            . 'and community organizations create '
            . 'apparel and promotional products '
            . 'that represent them well.',

        'service_card_button_label' =>
            'Request a quote',

        'quote_band_heading' =>
            'Have a project in mind?',

        'quote_band_text' =>
            'Tell DC Imprints what you need, '
            . 'and the team will help identify '
            . 'the right apparel, products, '
            . 'and production approach.',

        'quote_band_button_label' =>
            'Start Your Quote',

        'reviews_eyebrow' =>
            'Customer Feedback',

        'reviews_heading' =>
            'Trusted by local customers',

        'reviews_intro' =>
            'Read what customers have said '
            . 'about their experience working '
            . 'with DC Imprints.',

        'reviews_link_label' =>
            'Read all Google reviews',

        'about_eyebrow' =>
            'About DC Imprints',

        'about_heading' =>
            'The company and people behind the work',

        'about_intro' =>
            'Select DC Imprints to learn about '
            . 'the company, or choose a team member '
            . 'to see their role and experience.',

        'about_body' =>
            'DC Imprints is a locally operated '
            . 'imprinting business serving DeForest, '
            . 'Dane County, and surrounding communities.',

        'about_profile_button_label' =>
            'Start a Conversation',

        'catalogs_eyebrow' =>
            'Catalogs & Brand Partners',

        'catalogs_heading' =>
            'Explore available products',

        'catalogs_intro' =>
            'Browse supplier catalogs and brand partners, '
            . 'then contact DC Imprints for help '
            . 'choosing the right products.',

        'catalog_panel_eyebrow' =>
            'Browse Catalogs',

        'catalog_panel_heading' =>
            'Find the right product for your project',

        'catalog_panel_intro' =>
            'Use the approved supplier links below '
            . 'to explore available apparel, '
            . 'promotional products, and accessories.',

        'supplier_links' =>
            'Supplier catalog links will be added here.',

        'catalog_button_label' =>
            'Ask for Recommendations',

        'quote_eyebrow' =>
            'Contact DC Imprints',

        'quote_heading' =>
            'Request a Quote',

        'contact_intro' =>
            'Tell us what you need, the quantity, '
            . 'and your preferred timeline. '
            . 'We will follow up to discuss the project.',

        'quote_form_intro' =>
            'Choose the closest service, describe '
            . 'the project, and attach existing artwork '
            . 'when helpful. DC Imprints can also assist '
            . 'with design or logo preparation.',

        'quote_submit_label' =>
            'Send Request',

        'location_eyebrow' =>
            'Visit or Contact Us',

        'location_heading' =>
            'Local service in DeForest',

        'location_intro' =>
            'Visit DC Imprints in DeForest Town Square, '
            . 'call during business hours, or email '
            . 'the team about your project.',

        'location_directions_label' =>
            'Get Directions',

        'location_call_label' =>
            'Call DC Imprints',

        'footer_cta_eyebrow' =>
            'Start Your Project',

        'footer_cta_heading' =>
            'Ready to create something '
            . 'people will remember?',

        'footer_cta_text' =>
            'Tell the DC Imprints team what you need, '
            . 'and they will help determine the right '
            . 'products and production approach.',

        'footer_primary_button_label' =>
            'Request a Quote',

        'footer_summary' =>
            'Screen printing, embroidery, '
            . 'promotional products, custom apparel, '
            . 'design support, and managed web stores '
            . 'for businesses, teams, schools, '
            . 'organizations, and events.',
    ];
}

/**
 * Read every fixed content value as a key/value array.
 *
 * @return array<string, string>
 */
function dc_content_values(
    bool $refresh = false
): array {
    $cache =& dc_content_cache();

    if (
        !$refresh
        && isset($cache['content_values'])
    ) {
        return $cache['content_values'];
    }

    $values =
        dc_default_content_values();

    $pdo = database();

    if ($pdo !== null) {
        try {
            $statement = $pdo->query(
                'SELECT
                    content_key,
                    content_value
                 FROM site_content'
            );

            foreach (
                $statement->fetchAll()
                as $row
            ) {
                $key = (string) (
                    $row['content_key']
                    ?? ''
                );

                if ($key !== '') {
                    $values[$key] =
                        (string) (
                            $row['content_value']
                            ?? ''
                        );
                }
            }
        } catch (Throwable $exception) {
            dc_log_content_error(
                'read content values',
                $exception
            );
        }
    }

    $cache['content_values'] =
        $values;

    return $values;
}

function dc_content(
    string $key,
    ?string $fallback = null
): string {
    $values = dc_content_values();

    if (
        array_key_exists(
            $key,
            $values
        )
    ) {
        return $values[$key];
    }

    return $fallback ?? '';
}

/**
 * Read editable content metadata for the administration interface.
 *
 * @return array<int, array<string, mixed>>
 */
function dc_content_records(
    ?string $section = null
): array {
    $pdo = database();

    if ($pdo === null) {
        return [];
    }

    try {
        if ($section === null) {
            $statement = $pdo->query(
                'SELECT *
                 FROM site_content
                 WHERE is_editable = 1
                 ORDER BY
                    section_key,
                    sort_order,
                    content_key'
            );
        } else {
            $statement = $pdo->prepare(
                'SELECT *
                 FROM site_content
                 WHERE is_editable = 1
                   AND section_key = :section_key
                 ORDER BY
                    sort_order,
                    content_key'
            );

            $statement->execute([
                'section_key' => $section,
            ]);
        }

        $records =
            $statement->fetchAll();

        return is_array($records)
            ? $records
            : [];
    } catch (Throwable $exception) {
        dc_log_content_error(
            'read content records',
            $exception
        );

        return [];
    }
}

/**
 * Update only content slots that already exist and are marked editable.
 *
 * @param array<string, mixed> $updates
 */
function dc_update_content_values(
    array $updates
): bool {
    if ($updates === []) {
        return true;
    }

    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    try {
        $keys = array_values(
            array_filter(
                array_map(
                    'strval',
                    array_keys($updates)
                ),
                static fn (
                    string $key
                ): bool => $key !== ''
            )
        );

        if ($keys === []) {
            return true;
        }

        $placeholders = implode(
            ',',
            array_fill(
                0,
                count($keys),
                '?'
            )
        );

        $metadataStatement = $pdo->prepare(
            'SELECT
                content_key,
                max_length
             FROM site_content
             WHERE is_editable = 1
               AND content_key IN ('
            . $placeholders
            . ')'
        );

        $metadataStatement->execute(
            $keys
        );

        $metadata = [];

        foreach (
            $metadataStatement->fetchAll()
            as $row
        ) {
            $metadata[
                (string) $row['content_key']
            ] =
                $row['max_length'] !== null
                    ? (int) $row['max_length']
                    : null;
        }

        if (
            count($metadata)
            !== count(array_unique($keys))
        ) {
            return false;
        }

        $statement = $pdo->prepare(
            'UPDATE site_content
             SET content_value = :content_value
             WHERE content_key = :content_key
               AND is_editable = 1'
        );

        $pdo->beginTransaction();

        foreach (
            $updates
            as $key => $value
        ) {
            $key = (string) $key;
            $value = trim(
                (string) $value
            );

            $maxLength =
                $metadata[$key]
                ?? null;

            if (
                $maxLength !== null
                && mb_strlen($value)
                    > $maxLength
            ) {
                throw new LengthException(
                    sprintf(
                        'Content value "%s" '
                        . 'exceeds its maximum length.',
                        $key
                    )
                );
            }

            $statement->execute([
                'content_key' => $key,
                'content_value' => $value,
            ]);
        }

        $pdo->commit();

        dc_forget_content_cache(
            'content_values'
        );

        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        dc_log_content_error(
            'update content values',
            $exception
        );

        return false;
    }
}

/**
 * Current theme values used when the database is unavailable.
 *
 * @return array<string, mixed>
 */
function dc_default_theme(): array
{
    return [
        'id' => 1,
        'theme_key' => 'dc-brown',
        'name' => 'Sasquatch Sienna',
        'description' =>
            'The signature DC Imprints sienna, '
            . 'cream, charcoal, and white appearance.',
        'primary_color' => '#985f2b',
        'primary_dark_color' => '#7a461f',
        'primary_light_color' => '#e8ded2',
        'primary_soft_color' => '#c6a987',
        'charcoal_color' => '#212121',
        'page_background_color' => '#ffffff',
        'footer_background_color' => '#171411',
        'is_available' => 1,
        'sort_order' => 10,
    ];
}

/**
 * @return array<int, array<string, mixed>>
 */
function dc_available_themes(
    bool $refresh = false
): array {
    $cache =& dc_content_cache();

    if (
        !$refresh
        && isset($cache['available_themes'])
    ) {
        return $cache['available_themes'];
    }

    $themes = [
        dc_default_theme(),
    ];

    $pdo = database();

    if ($pdo !== null) {
        try {
            $statement = $pdo->query(
                'SELECT *
                 FROM themes
                 WHERE is_available = 1
                 ORDER BY
                    sort_order,
                    name'
            );

            $rows = $statement->fetchAll();

            if (
                is_array($rows)
                && $rows !== []
            ) {
                $themes = $rows;
            }
        } catch (Throwable $exception) {
            dc_log_content_error(
                'read available themes',
                $exception
            );
        }
    }

    foreach (
        $themes
        as $index => $theme
    ) {
        $themes[$index]['id'] =
            (int) (
                $theme['id']
                ?? 0
            );

        $themes[$index]['is_available'] =
            (int) (
                $theme['is_available']
                ?? 0
            );

        $themes[$index]['sort_order'] =
            (int) (
                $theme['sort_order']
                ?? 0
            );
    }

    $cache['available_themes'] =
        $themes;

    return $themes;
}

/**
 * @return array<string, mixed>
 */
function dc_active_theme(
    bool $refresh = false
): array {
    $cache =& dc_content_cache();

    if (
        !$refresh
        && isset($cache['active_theme'])
    ) {
        return $cache['active_theme'];
    }

    $settings =
        dc_site_settings($refresh);

    $theme =
        dc_default_theme();

    $pdo = database();

    if ($pdo !== null) {
        try {
            $statement = $pdo->prepare(
                'SELECT *
                 FROM themes
                 WHERE id = :id
                   AND is_available = 1
                 LIMIT 1'
            );

            $statement->execute([
                'id' =>
                    (int) $settings[
                        'active_theme_id'
                    ],
            ]);

            $row =
                $statement->fetch();

            if (is_array($row)) {
                $theme = array_replace(
                    $theme,
                    $row
                );
            }
        } catch (Throwable $exception) {
            dc_log_content_error(
                'read active theme',
                $exception
            );
        }
    }

    $theme['id'] =
        (int) (
            $theme['id']
            ?? 1
        );

    $theme['sort_order'] =
        (int) (
            $theme['sort_order']
            ?? 0
        );

    $theme['is_available'] =
        (int) (
            $theme['is_available']
            ?? 1
        );

    $cache['active_theme'] =
        $theme;

    return $theme;
}

function dc_set_active_theme(
    int $themeId
): bool {
    $pdo = database();

    if (
        $pdo === null
        || $themeId < 1
    ) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'UPDATE site_settings
             SET active_theme_id = :theme_id
             WHERE id = 1
               AND EXISTS (
                   SELECT 1
                   FROM themes
                   WHERE id = :available_theme_id
                     AND is_available = 1
               )'
        );

        $statement->execute([
            'theme_id' => $themeId,
            'available_theme_id' => $themeId,
        ]);

        if ($statement->rowCount() === 0) {
            $check = $pdo->prepare(
                'SELECT 1
                 FROM themes
                 WHERE id = :id
                   AND is_available = 1'
            );

            $check->execute([
                'id' => $themeId,
            ]);

            if (
                $check->fetchColumn()
                === false
            ) {
                return false;
            }
        }

        dc_forget_content_cache(
            'site_settings'
        );

        dc_forget_content_cache(
            'active_theme'
        );

        return true;
    } catch (Throwable $exception) {
        dc_log_content_error(
            'set active theme',
            $exception
        );

        return false;
    }
}

/**
 * @return array<string, mixed>|null
 */
function dc_media_asset(
    int $assetId
): ?array {
    if ($assetId < 1) {
        return null;
    }

    $pdo = database();

    if ($pdo === null) {
        return null;
    }

    try {
        $statement = $pdo->prepare(
            'SELECT *
             FROM media_assets
             WHERE id = :id
             LIMIT 1'
        );

        $statement->execute([
            'id' => $assetId,
        ]);

        $row =
            $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        $row['id'] =
            (int) $row['id'];

        $row['width'] =
            $row['width'] !== null
                ? (int) $row['width']
                : null;

        $row['height'] =
            $row['height'] !== null
                ? (int) $row['height']
                : null;

        $row['file_size_bytes'] =
            $row['file_size_bytes'] !== null
                ? (int) $row['file_size_bytes']
                : null;

        $row['is_managed_upload'] =
            (int) $row[
                'is_managed_upload'
            ];

        $row['file_exists'] =
            dc_public_path_exists(
                (string) $row['file_path']
            );

        return $row;
    } catch (Throwable $exception) {
        dc_log_content_error(
            'read media asset',
            $exception
        );

        return null;
    }
}

/**
 * Read fixed global media slots such as the brand mark and hero media.
 *
 * @return array<string, array<string, mixed>>
 */
function dc_site_media_slots(
    bool $refresh = false
): array {
    $cache =& dc_content_cache();

    if (
        !$refresh
        && isset($cache['site_media_slots'])
    ) {
        return $cache['site_media_slots'];
    }

    $defaults = [
        'brand_mark' => [
            'slot_key' => 'brand_mark',
            'admin_label' => 'Brand mark',
            'media_asset_id' => 1,
            'file_path' =>
                '/assets/images/brand/'
                . 'sasquatch-mark.svg',
            'media_kind' => 'svg',
            'alt_text' =>
                'DC Imprints Sasquatch brand mark',
        ],
        'favicon' => [
            'slot_key' => 'favicon',
            'admin_label' => 'Website icon',
            'media_asset_id' => 2,
            'file_path' => '/favicon.ico',
            'media_kind' => 'icon',
            'alt_text' => null,
        ],
        'hero_image' => [
            'slot_key' => 'hero_image',
            'admin_label' => 'Hero image',
            'media_asset_id' => 3,
            'file_path' =>
                '/assets/images/content/hero.jpg',
            'media_kind' => 'image',
            'alt_text' => null,
        ],
        'hero_video' => [
            'slot_key' => 'hero_video',
            'admin_label' => 'Hero video',
            'media_asset_id' => 4,
            'file_path' =>
                '/assets/video/hero.mp4',
            'media_kind' => 'video',
            'alt_text' => null,
        ],
    ];

    $slots = $defaults;
    $pdo = database();

    if ($pdo !== null) {
        try {
            $statement = $pdo->query(
                'SELECT
                    slot.slot_key,
                    slot.admin_label,
                    slot.media_asset_id,
                    slot.sort_order,
                    media.display_name,
                    media.file_path,
                    media.original_filename,
                    media.media_kind,
                    media.mime_type,
                    media.width,
                    media.height,
                    media.file_size_bytes,
                    media.alt_text,
                    media.is_managed_upload
                 FROM site_media_slots AS slot
                 LEFT JOIN media_assets AS media
                   ON media.id =
                      slot.media_asset_id
                 ORDER BY
                    slot.sort_order,
                    slot.slot_key'
            );

            foreach (
                $statement->fetchAll()
                as $row
            ) {
                $key = (string) (
                    $row['slot_key']
                    ?? ''
                );

                if ($key === '') {
                    continue;
                }

                $slots[$key] =
                    array_replace(
                        $slots[$key] ?? [],
                        $row
                    );
            }
        } catch (Throwable $exception) {
            dc_log_content_error(
                'read site media slots',
                $exception
            );
        }
    }

    foreach (
        $slots
        as $key => $slot
    ) {
        $slots[$key]['media_asset_id'] =
            $slot['media_asset_id'] !== null
                ? (int) $slot[
                    'media_asset_id'
                ]
                : null;

        $slots[$key]['sort_order'] =
            (int) (
                $slot['sort_order']
                ?? 0
            );

        $slots[$key]['file_exists'] =
            dc_public_path_exists(
                isset($slot['file_path'])
                    ? (string) $slot['file_path']
                    : null
            );
    }

    $cache['site_media_slots'] =
        $slots;

    return $slots;
}

/**
 * @return array<string, mixed>|null
 */
function dc_site_media(
    string $slotKey
): ?array {
    $slots =
        dc_site_media_slots();

    return $slots[$slotKey]
        ?? null;
}

/**
 * Register a media record after app/uploads.php has safely stored the file.
 *
 * @param array<string, mixed> $asset
 */
function dc_create_media_asset(
    array $asset
): ?int {
    $pdo = database();

    if ($pdo === null) {
        return null;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO media_assets (
                display_name,
                file_path,
                original_filename,
                media_kind,
                mime_type,
                width,
                height,
                file_size_bytes,
                alt_text,
                is_managed_upload
             ) VALUES (
                :display_name,
                :file_path,
                :original_filename,
                :media_kind,
                :mime_type,
                :width,
                :height,
                :file_size_bytes,
                :alt_text,
                :is_managed_upload
             )'
        );

        $statement->execute([
            'display_name' => trim(
                (string) (
                    $asset['display_name']
                    ?? 'Media'
                )
            ),
            'file_path' => trim(
                (string) (
                    $asset['file_path']
                    ?? ''
                )
            ),
            'original_filename' =>
                isset(
                    $asset[
                        'original_filename'
                    ]
                )
                    ? trim(
                        (string) $asset[
                            'original_filename'
                        ]
                    )
                    : null,
            'media_kind' =>
                (string) (
                    $asset['media_kind']
                    ?? 'image'
                ),
            'mime_type' =>
                isset($asset['mime_type'])
                    ? trim(
                        (string) $asset[
                            'mime_type'
                        ]
                    )
                    : null,
            'width' =>
                isset($asset['width'])
                && $asset['width'] !== ''
                    ? (int) $asset['width']
                    : null,
            'height' =>
                isset($asset['height'])
                && $asset['height'] !== ''
                    ? (int) $asset['height']
                    : null,
            'file_size_bytes' =>
                isset(
                    $asset[
                        'file_size_bytes'
                    ]
                )
                    ? (int) $asset[
                        'file_size_bytes'
                    ]
                    : null,
            'alt_text' =>
                isset($asset['alt_text'])
                    ? trim(
                        (string) $asset[
                            'alt_text'
                        ]
                    )
                    : null,
            'is_managed_upload' =>
                !empty(
                    $asset[
                        'is_managed_upload'
                    ]
                )
                    ? 1
                    : 0,
        ]);

        return (int) $pdo->lastInsertId();
    } catch (Throwable $exception) {
        dc_log_content_error(
            'create media asset',
            $exception
        );

        return null;
    }
}

function dc_assign_site_media(
    string $slotKey,
    ?int $assetId
): bool {
    $pdo = database();

    if (
        $pdo === null
        || trim($slotKey) === ''
    ) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'UPDATE site_media_slots
             SET media_asset_id =
                    :media_asset_id
             WHERE slot_key = :slot_key'
        );

        $statement->bindValue(
            ':media_asset_id',
            $assetId,
            $assetId === null
                ? PDO::PARAM_NULL
                : PDO::PARAM_INT
        );

        $statement->bindValue(
            ':slot_key',
            $slotKey,
            PDO::PARAM_STR
        );

        $statement->execute();

        if ($statement->rowCount() === 0) {
            $check = $pdo->prepare(
                'SELECT 1
                 FROM site_media_slots
                 WHERE slot_key = :slot_key'
            );

            $check->execute([
                'slot_key' => $slotKey,
            ]);

            if (
                $check->fetchColumn()
                === false
            ) {
                return false;
            }
        }

        dc_forget_content_cache(
            'site_media_slots'
        );

        return true;
    } catch (Throwable $exception) {
        dc_log_content_error(
            'assign site media',
            $exception
        );

        return false;
    }
}

/**
 * Current service records used when the database is unavailable.
 *
 * @return array<int, array<string, mixed>>
 */
function dc_default_services(): array
{
    return [
        [
            'id' => 1,
            'slug' => 'screen-printing',
            'name' => 'Screen Printing',
            'description' =>
                'Custom printed apparel for businesses, '
                . 'schools, teams, events, fundraisers, '
                . 'and community organizations.',
            'navigation_label' => null,
            'navigation_summary' =>
                'Custom apparel for teams, events, '
                . 'and organizations',
            'quote_form_label' => null,
            'footer_label' => null,
            'image_asset_id' => 5,
            'image' =>
                '/assets/images/content/'
                . 'screen-printing.jpg',
            'sort_order' => 10,
            'is_active' => 1,
        ],
        [
            'id' => 2,
            'slug' => 'embroidery',
            'name' => 'Embroidery',
            'description' =>
                'Professional stitched logos and designs '
                . 'for polos, jackets, hats, uniforms, '
                . 'workwear, and more.',
            'navigation_label' => null,
            'navigation_summary' =>
                'Professional stitched logos and designs',
            'quote_form_label' => null,
            'footer_label' => null,
            'image_asset_id' => 6,
            'image' =>
                '/assets/images/content/'
                . 'embroidery.jpg',
            'sort_order' => 20,
            'is_active' => 1,
        ],
        [
            'id' => 3,
            'slug' => 'promotional-products',
            'name' => 'Promotional Products',
            'description' =>
                'Branded products for employee programs, '
                . 'customer outreach, events, campaigns, '
                . 'and organizational use.',
            'navigation_label' => null,
            'navigation_summary' =>
                'Branded products for outreach and events',
            'quote_form_label' => null,
            'footer_label' => null,
            'image_asset_id' => 7,
            'image' =>
                '/assets/images/content/'
                . 'promotional-products.jpg',
            'sort_order' => 30,
            'is_active' => 1,
        ],
        [
            'id' => 4,
            'slug' => 'corporate-apparel',
            'name' =>
                'Corporate & Office Apparel',
            'description' =>
                'Coordinated apparel that helps employees '
                . 'represent an organization consistently '
                . 'and professionally.',
            'navigation_label' =>
                'Corporate Apparel',
            'navigation_summary' =>
                'Coordinated apparel '
                . 'for professional teams',
            'quote_form_label' =>
                'Corporate / Office Apparel',
            'footer_label' =>
                'Corporate Apparel',
            'image_asset_id' => 8,
            'image' =>
                '/assets/images/content/'
                . 'corporate-apparel.jpg',
            'sort_order' => 40,
            'is_active' => 1,
        ],
        [
            'id' => 5,
            'slug' => 'custom-web-stores',
            'name' => 'Custom Web Stores',
            'description' =>
                'Managed ordering pages for approved '
                . 'apparel programs, groups, teams, '
                . 'and organizations.',
            'navigation_label' => null,
            'navigation_summary' =>
                'Managed ordering for approved programs',
            'quote_form_label' =>
                'Custom Web Store',
            'footer_label' => null,
            'image_asset_id' => 9,
            'image' =>
                '/assets/images/content/'
                . 'custom-web-stores.jpg',
            'sort_order' => 50,
            'is_active' => 1,
        ],
        [
            'id' => 6,
            'slug' => 'design-help',
            'name' => 'Design & Logo Help',
            'description' =>
                'Assistance preparing, improving, '
                . 'or creating artwork that is ready '
                . 'for apparel and promotional products.',
            'navigation_label' => null,
            'navigation_summary' =>
                'Artwork preparation '
                . 'and design assistance',
            'quote_form_label' =>
                'Design / Logo Assistance',
            'footer_label' => null,
            'image_asset_id' => 10,
            'image' =>
                '/assets/images/content/'
                . 'design-help.jpg',
            'sort_order' => 60,
            'is_active' => 1,
        ],
    ];
}

/**
 * Normalize a service row and add aliases used by the current template.
 *
 * @param array<string, mixed> $service
 *
 * @return array<string, mixed>
 */
function dc_normalize_service(
    array $service
): array {
    $name = trim(
        (string) (
            $service['name']
            ?? ''
        )
    );

    $slug = trim(
        (string) (
            $service['slug']
            ?? ''
        )
    );

    $image = isset(
        $service['file_path']
    )
        ? (string) $service['file_path']
        : (string) (
            $service['image']
            ?? ''
        );

    $imageAssetId =
        $service['image_asset_id']
        ?? null;

    $service['id'] =
        (int) (
            $service['id']
            ?? 0
        );

    $service['image_asset_id'] =
        $imageAssetId !== null
            ? (int) $imageAssetId
            : null;

    $service['sort_order'] =
        (int) (
            $service['sort_order']
            ?? 0
        );

    $service['is_active'] =
        (int) (
            $service['is_active']
            ?? 0
        );

    $service['slug'] =
        $slug !== ''
            ? $slug
            : dc_slugify($name);

    $service['name'] = $name;
    $service['title'] = $name;
    $service['anchor'] =
        $service['slug'];

    $service['navigation_label'] = trim(
        (string) (
            $service['navigation_label']
            ?? ''
        )
    ) ?: $name;

    $service['navigation_summary'] = trim(
        (string) (
            $service['navigation_summary']
            ?? ''
        )
    ) ?: (string) (
        $service['description']
        ?? ''
    );

    $service['quote_form_label'] = trim(
        (string) (
            $service['quote_form_label']
            ?? ''
        )
    ) ?: $name;

    $service['footer_label'] = trim(
        (string) (
            $service['footer_label']
            ?? ''
        )
    ) ?: $service['navigation_label'];

    $service['image'] = $image;

    $service['image_alt'] = trim(
        (string) (
            $service['alt_text']
            ?? ''
        )
    ) ?: $name;

    $service['image_exists'] =
        dc_public_path_exists($image);

    return $service;
}

/**
 * @return array<int, array<string, mixed>>
 */
function dc_services(
    bool $activeOnly = true,
    bool $refresh = false
): array {
    $cacheKey = $activeOnly
        ? 'services_active'
        : 'services_all';

    $cache =& dc_content_cache();

    if (
        !$refresh
        && isset($cache[$cacheKey])
    ) {
        return $cache[$cacheKey];
    }

    $services =
        dc_default_services();

    $pdo = database();

    if ($pdo !== null) {
        try {
            $sql =
                'SELECT
                    service.*,
                    media.file_path,
                    media.alt_text,
                    media.media_kind,
                    media.mime_type
                 FROM services AS service
                 LEFT JOIN media_assets AS media
                   ON media.id =
                      service.image_asset_id';

            if ($activeOnly) {
                $sql .=
                    ' WHERE service.is_active = 1';
            }

            $sql .=
                ' ORDER BY
                    service.sort_order,
                    service.id';

            $rows =
                $pdo->query($sql)->fetchAll();

            if (is_array($rows)) {
                $services = $rows;
            }
        } catch (Throwable $exception) {
            dc_log_content_error(
                'read services',
                $exception
            );
        }
    }

    if ($activeOnly) {
        $services = array_values(
            array_filter(
                $services,
                static fn (
                    array $service
                ): bool =>
                    (int) (
                        $service['is_active']
                        ?? 0
                    ) === 1
            )
        );
    }

    $services = array_map(
        'dc_normalize_service',
        $services
    );

    usort(
        $services,
        static fn (
            array $first,
            array $second
        ): int =>
            [
                $first['sort_order'],
                $first['id'],
            ]
            <=>
            [
                $second['sort_order'],
                $second['id'],
            ]
    );

    $cache[$cacheKey] =
        $services;

    return $services;
}

/**
 * @return array<int, string>
 */
function dc_service_options(
    bool $includeOther = true
): array {
    $options = array_map(
        static fn (
            array $service
        ): string =>
            (string) $service[
                'quote_form_label'
            ],
        dc_services(true)
    );

    if ($includeOther) {
        $options[] =
            'Other / Not Sure';
    }

    return $options;
}

function dc_unique_service_slug(
    string $name,
    ?int $excludeServiceId = null
): string {
    $base = dc_slugify($name);
    $candidate = $base;
    $suffix = 2;

    $pdo = database();

    if ($pdo === null) {
        return $candidate;
    }

    try {
        while (true) {
            $sql =
                'SELECT id
                 FROM services
                 WHERE slug = :slug';

            $parameters = [
                'slug' => $candidate,
            ];

            if (
                $excludeServiceId
                !== null
            ) {
                $sql .=
                    ' AND id <> :excluded_id';

                $parameters[
                    'excluded_id'
                ] = $excludeServiceId;
            }

            $sql .= ' LIMIT 1';

            $statement =
                $pdo->prepare($sql);

            $statement->execute(
                $parameters
            );

            if (
                $statement->fetchColumn()
                === false
            ) {
                return $candidate;
            }

            $candidate =
                $base . '-' . $suffix;

            $suffix++;
        }
    } catch (Throwable $exception) {
        dc_log_content_error(
            'generate unique service slug',
            $exception
        );

        return $candidate;
    }
}

/**
 * @param array<string, mixed> $service
 */
function dc_create_service(
    array $service
): ?int {
    $pdo = database();

    if ($pdo === null) {
        return null;
    }

    $name = trim(
        (string) (
            $service['name']
            ?? ''
        )
    );

    $description = trim(
        (string) (
            $service['description']
            ?? ''
        )
    );

    if (
        $name === ''
        || $description === ''
    ) {
        return null;
    }

    try {
        $orderStatement = $pdo->query(
            'SELECT
                COALESCE(MAX(sort_order), 0) + 10
             FROM services'
        );

        $nextOrder =
            (int) $orderStatement->fetchColumn();

        $slug =
            dc_unique_service_slug($name);

        $statement = $pdo->prepare(
            'INSERT INTO services (
                slug,
                name,
                description,
                navigation_label,
                navigation_summary,
                quote_form_label,
                footer_label,
                image_asset_id,
                sort_order,
                is_active
             ) VALUES (
                :slug,
                :name,
                :description,
                :navigation_label,
                :navigation_summary,
                :quote_form_label,
                :footer_label,
                :image_asset_id,
                :sort_order,
                :is_active
             )'
        );

        $statement->execute([
            'slug' => $slug,
            'name' => $name,
            'description' => $description,

            'navigation_label' =>
                trim(
                    (string) (
                        $service[
                            'navigation_label'
                        ]
                        ?? ''
                    )
                ) ?: null,

            'navigation_summary' =>
                trim(
                    (string) (
                        $service[
                            'navigation_summary'
                        ]
                        ?? ''
                    )
                ) ?: null,

            'quote_form_label' =>
                trim(
                    (string) (
                        $service[
                            'quote_form_label'
                        ]
                        ?? ''
                    )
                ) ?: null,

            'footer_label' =>
                trim(
                    (string) (
                        $service[
                            'footer_label'
                        ]
                        ?? ''
                    )
                ) ?: null,

            'image_asset_id' =>
                !empty(
                    $service[
                        'image_asset_id'
                    ]
                )
                    ? (int) $service[
                        'image_asset_id'
                    ]
                    : null,

            'sort_order' =>
                isset(
                    $service['sort_order']
                )
                    ? (int) $service[
                        'sort_order'
                    ]
                    : $nextOrder,

            'is_active' =>
                array_key_exists(
                    'is_active',
                    $service
                )
                    ? (
                        !empty(
                            $service[
                                'is_active'
                            ]
                        )
                            ? 1
                            : 0
                    )
                    : 1,
        ]);

        dc_forget_content_cache(
            'services_active'
        );

        dc_forget_content_cache(
            'services_all'
        );

        return (int) $pdo->lastInsertId();
    } catch (Throwable $exception) {
        dc_log_content_error(
            'create service',
            $exception
        );

        return null;
    }
}

/**
 * @param array<string, mixed> $service
 */
function dc_update_service(
    int $serviceId,
    array $service
): bool {
    $pdo = database();

    if (
        $pdo === null
        || $serviceId < 1
    ) {
        return false;
    }

    $name = trim(
        (string) (
            $service['name']
            ?? ''
        )
    );

    $description = trim(
        (string) (
            $service['description']
            ?? ''
        )
    );

    if (
        $name === ''
        || $description === ''
    ) {
        return false;
    }

    try {
        $slug = dc_unique_service_slug(
            $name,
            $serviceId
        );

        $statement = $pdo->prepare(
            'UPDATE services SET
                slug = :slug,
                name = :name,
                description = :description,
                navigation_label =
                    :navigation_label,
                navigation_summary =
                    :navigation_summary,
                quote_form_label =
                    :quote_form_label,
                footer_label =
                    :footer_label,
                image_asset_id =
                    :image_asset_id,
                is_active = :is_active
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $serviceId,
            'slug' => $slug,
            'name' => $name,
            'description' => $description,

            'navigation_label' =>
                trim(
                    (string) (
                        $service[
                            'navigation_label'
                        ]
                        ?? ''
                    )
                ) ?: null,

            'navigation_summary' =>
                trim(
                    (string) (
                        $service[
                            'navigation_summary'
                        ]
                        ?? ''
                    )
                ) ?: null,

            'quote_form_label' =>
                trim(
                    (string) (
                        $service[
                            'quote_form_label'
                        ]
                        ?? ''
                    )
                ) ?: null,

            'footer_label' =>
                trim(
                    (string) (
                        $service[
                            'footer_label'
                        ]
                        ?? ''
                    )
                ) ?: null,

            'image_asset_id' =>
                !empty(
                    $service[
                        'image_asset_id'
                    ]
                )
                    ? (int) $service[
                        'image_asset_id'
                    ]
                    : null,

            'is_active' =>
                !empty(
                    $service[
                        'is_active'
                    ]
                )
                    ? 1
                    : 0,
        ]);

        dc_forget_content_cache(
            'services_active'
        );

        dc_forget_content_cache(
            'services_all'
        );

        return true;
    } catch (Throwable $exception) {
        dc_log_content_error(
            'update service',
            $exception
        );

        return false;
    }
}

function dc_set_service_active(
    int $serviceId,
    bool $isActive
): bool {
    return dc_set_record_active(
        'services',
        $serviceId,
        $isActive
    );
}

function dc_delete_service(
    int $serviceId
): bool {
    return dc_delete_record(
        'services',
        $serviceId
    );
}

/**
 * @param array<int, int|string> $orderedIds
 */
function dc_reorder_services(
    array $orderedIds
): bool {
    return dc_reorder_records(
        'services',
        $orderedIds
    );
}

/**
 * Current review records used when the database is unavailable.
 *
 * @return array<int, array<string, mixed>>
 */
function dc_default_testimonials(): array
{
    $reviews = [
        [
            'Jackson Vanderwerken',
            'They were very accommodating of my '
            . 'unique requests and advised me well '
            . 'on what would be best for my business.',
            'masculine',
        ],
        [
            'Stephen Ketelsen',
            'We ordered custom golf shirts for the '
            . 'Stoughton golf team through DC Imprint, '
            . 'and they turned out fantastic!',
            'masculine',
        ],
        [
            'Mike Frasier',
            'Youâ€™re not just another number at '
            . 'DC Imprints. They genuinely care '
            . 'about your experience, and Danâ€™s '
            . 'communication was excellent.',
            'masculine',
        ],
        [
            'Steve Gottschalk',
            'They embroidered several baseball '
            . 'and winter hats with my business name '
            . 'and logo. They look amazing, and the '
            . 'work was completed quickly.',
            'masculine',
        ],
        [
            'Cassie Schmidt',
            'They accommodated our tight turnaround '
            . 'during the holiday season. Communication '
            . 'was fast, pricing was clear, and weâ€™ll '
            . 'definitely work with them again.',
            'feminine',
        ],
        [
            'Icon Motors',
            'They gave great attention to detail '
            . 'and provided expert advice when we had '
            . 'questions. We will 100% be using '
            . 'them again.',
            'neutral',
        ],
        [
            'Meagan Buchko',
            'The team always goes above and beyond '
            . 'for our business. We always feel like '
            . 'they are 100% dedicated to our success.',
            'feminine',
        ],
        [
            'Kieran Bretz',
            'DC Imprints was attentive to important '
            . 'design details and timely in both '
            . 'their responses and delivery.',
            'neutral',
        ],
        [
            'Deaven Wiedmer',
            'I purchased T-shirts, pullovers, '
            . 'and zip-up sweatshirts. I absolutely '
            . 'love everything I received.',
            'neutral',
        ],
        [
            'Taylor',
            'The owner and staff were helpful '
            . 'and accommodating. The embroidery '
            . 'is high quality and exactly what '
            . 'we were looking for.',
            'neutral',
        ],
        [
            'Helga Heady',
            'Really great prices, phenomenal work, '
            . 'fast delivery, and the best customer '
            . 'service. Highly recommend!',
            'feminine',
        ],
        [
            'Kyle Warner',
            'I had a wonderful experience working '
            . 'with Chris and Brandi. I highly recommend '
            . 'DC Imprints for custom logo wear.',
            'masculine',
        ],
    ];

    $result = [];

    foreach (
        $reviews
        as $index => $review
    ) {
        [
            $name,
            $text,
            $avatar,
        ] = $review;

        $result[] = [
            'id' => $index + 1,
            'reviewer_name' => $name,
            'review_text' => $text,
            'rating' => 5,
            'source' => 'Google Review',
            'avatar_style' => $avatar,
            'sort_order' =>
                ($index + 1) * 10,
            'is_active' => 1,
        ];
    }

    return $result;
}

/**
 * @param array<string, mixed> $testimonial
 *
 * @return array<string, mixed>
 */
function dc_normalize_testimonial(
    array $testimonial
): array {
    $testimonial['id'] =
        (int) (
            $testimonial['id']
            ?? 0
        );

    $testimonial['rating'] = max(
        1,
        min(
            5,
            (int) (
                $testimonial['rating']
                ?? 5
            )
        )
    );

    $testimonial['sort_order'] =
        (int) (
            $testimonial['sort_order']
            ?? 0
        );

    $testimonial['is_active'] =
        (int) (
            $testimonial['is_active']
            ?? 0
        );

    $testimonial['name'] =
        (string) (
            $testimonial[
                'reviewer_name'
            ]
            ?? ''
        );

    $testimonial['quote'] =
        (string) (
            $testimonial[
                'review_text'
            ]
            ?? ''
        );

    $testimonial['avatar'] =
        (string) (
            $testimonial[
                'avatar_style'
            ]
            ?? 'neutral'
        );

    return $testimonial;
}

/**
 * @return array<int, array<string, mixed>>
 */
function dc_testimonials(
    bool $activeOnly = true,
    bool $refresh = false
): array {
    $cacheKey = $activeOnly
        ? 'testimonials_active'
        : 'testimonials_all';

    $cache =& dc_content_cache();

    if (
        !$refresh
        && isset($cache[$cacheKey])
    ) {
        return $cache[$cacheKey];
    }

    $testimonials =
        dc_default_testimonials();

    $pdo = database();

    if ($pdo !== null) {
        try {
            $sql =
                'SELECT *
                 FROM testimonials';

            if ($activeOnly) {
                $sql .=
                    ' WHERE is_active = 1';
            }

            $sql .=
                ' ORDER BY sort_order, id';

            $rows =
                $pdo->query($sql)->fetchAll();

            if (is_array($rows)) {
                $testimonials = $rows;
            }
        } catch (Throwable $exception) {
            dc_log_content_error(
                'read testimonials',
                $exception
            );
        }
    }

    if ($activeOnly) {
        $testimonials = array_values(
            array_filter(
                $testimonials,
                static fn (
                    array $testimonial
                ): bool =>
                    (int) (
                        $testimonial[
                            'is_active'
                        ]
                        ?? 0
                    ) === 1
            )
        );
    }

    $testimonials = array_map(
        'dc_normalize_testimonial',
        $testimonials
    );

    $cache[$cacheKey] =
        $testimonials;

    return $testimonials;
}

/**
 * @param array<string, mixed> $testimonial
 */
function dc_create_testimonial(
    array $testimonial
): ?int {
    $pdo = database();

    if ($pdo === null) {
        return null;
    }

    $name = trim(
        (string) (
            $testimonial[
                'reviewer_name'
            ]
            ?? ''
        )
    );

    $text = trim(
        (string) (
            $testimonial[
                'review_text'
            ]
            ?? ''
        )
    );

    if (
        $name === ''
        || $text === ''
    ) {
        return null;
    }

    try {
        $nextOrder = (int) $pdo->query(
            'SELECT
                COALESCE(MAX(sort_order), 0) + 10
             FROM testimonials'
        )->fetchColumn();

        $avatarStyle =
            (string) (
                $testimonial[
                    'avatar_style'
                ]
                ?? 'neutral'
            );

        if (
            !in_array(
                $avatarStyle,
                [
                    'neutral',
                    'masculine',
                    'feminine',
                ],
                true
            )
        ) {
            $avatarStyle = 'neutral';
        }

        $statement = $pdo->prepare(
            'INSERT INTO testimonials (
                reviewer_name,
                review_text,
                rating,
                source,
                avatar_style,
                sort_order,
                is_active
             ) VALUES (
                :reviewer_name,
                :review_text,
                :rating,
                :source,
                :avatar_style,
                :sort_order,
                :is_active
             )'
        );

        $statement->execute([
            'reviewer_name' => $name,
            'review_text' => $text,

            'rating' => max(
                1,
                min(
                    5,
                    (int) (
                        $testimonial[
                            'rating'
                        ]
                        ?? 5
                    )
                )
            ),

            'source' =>
                trim(
                    (string) (
                        $testimonial[
                            'source'
                        ]
                        ?? ''
                    )
                ) ?: 'Google Review',

            'avatar_style' =>
                $avatarStyle,

            'sort_order' =>
                isset(
                    $testimonial[
                        'sort_order'
                    ]
                )
                    ? (int) $testimonial[
                        'sort_order'
                    ]
                    : $nextOrder,

            'is_active' =>
                array_key_exists(
                    'is_active',
                    $testimonial
                )
                    ? (
                        !empty(
                            $testimonial[
                                'is_active'
                            ]
                        )
                            ? 1
                            : 0
                    )
                    : 1,
        ]);

        dc_forget_content_cache(
            'testimonials_active'
        );

        dc_forget_content_cache(
            'testimonials_all'
        );

        return (int) $pdo->lastInsertId();
    } catch (Throwable $exception) {
        dc_log_content_error(
            'create testimonial',
            $exception
        );

        return null;
    }
}

/**
 * @param array<string, mixed> $testimonial
 */
function dc_update_testimonial(
    int $testimonialId,
    array $testimonial
): bool {
    $pdo = database();

    if (
        $pdo === null
        || $testimonialId < 1
    ) {
        return false;
    }

    $name = trim(
        (string) (
            $testimonial[
                'reviewer_name'
            ]
            ?? ''
        )
    );

    $text = trim(
        (string) (
            $testimonial[
                'review_text'
            ]
            ?? ''
        )
    );

    if (
        $name === ''
        || $text === ''
    ) {
        return false;
    }

    $avatarStyle =
        (string) (
            $testimonial[
                'avatar_style'
            ]
            ?? 'neutral'
        );

    if (
        !in_array(
            $avatarStyle,
            [
                'neutral',
                'masculine',
                'feminine',
            ],
            true
        )
    ) {
        $avatarStyle = 'neutral';
    }

    try {
        $statement = $pdo->prepare(
            'UPDATE testimonials SET
                reviewer_name =
                    :reviewer_name,
                review_text =
                    :review_text,
                rating = :rating,
                source = :source,
                avatar_style =
                    :avatar_style,
                is_active = :is_active
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $testimonialId,
            'reviewer_name' => $name,
            'review_text' => $text,

            'rating' => max(
                1,
                min(
                    5,
                    (int) (
                        $testimonial[
                            'rating'
                        ]
                        ?? 5
                    )
                )
            ),

            'source' =>
                trim(
                    (string) (
                        $testimonial[
                            'source'
                        ]
                        ?? ''
                    )
                ) ?: 'Google Review',

            'avatar_style' =>
                $avatarStyle,

            'is_active' =>
                !empty(
                    $testimonial[
                        'is_active'
                    ]
                )
                    ? 1
                    : 0,
        ]);

        dc_forget_content_cache(
            'testimonials_active'
        );

        dc_forget_content_cache(
            'testimonials_all'
        );

        return true;
    } catch (Throwable $exception) {
        dc_log_content_error(
            'update testimonial',
            $exception
        );

        return false;
    }
}

function dc_set_testimonial_active(
    int $testimonialId,
    bool $isActive
): bool {
    return dc_set_record_active(
        'testimonials',
        $testimonialId,
        $isActive
    );
}

function dc_delete_testimonial(
    int $testimonialId
): bool {
    return dc_delete_record(
        'testimonials',
        $testimonialId
    );
}

/**
 * @param array<int, int|string> $orderedIds
 */
function dc_reorder_testimonials(
    array $orderedIds
): bool {
    return dc_reorder_records(
        'testimonials',
        $orderedIds
    );
}

/**
 * Current company and staff profiles used when the database is unavailable.
 *
 * @return array<int, array<string, mixed>>
 */
function dc_default_profiles(): array
{
    return [
        [
            'id' => 1,
            'profile_type' =>
                'organization',
            'name' => 'DC Imprints',
            'role_title' =>
                'About the Company',
            'biography' =>
                dc_content('about_body')
                . "\n\n"
                . 'Use this profile to explain the '
                . 'companyâ€™s history, local relationships, '
                . 'production capabilities, customer '
                . 'service, and commitment to helping '
                . 'clients complete their projects '
                . 'successfully.',
            'image_asset_id' => 11,
            'image' =>
                '/assets/images/content/about.jpg',
            'sort_order' => 10,
            'is_active' => 1,
            'is_protected' => 1,
        ],
        [
            'id' => 2,
            'profile_type' => 'staff',
            'name' => 'Staff Member Name',
            'role_title' => 'Role / Specialty',
            'biography' =>
                'Add a short approved introduction '
                . 'explaining this personâ€™s role '
                . 'and how they help customers.',
            'image_asset_id' => 12,
            'image' =>
                '/assets/images/content/staff-1.jpg',
            'sort_order' => 20,
            'is_active' => 1,
            'is_protected' => 0,
        ],
        [
            'id' => 3,
            'profile_type' => 'staff',
            'name' => 'Staff Member Name',
            'role_title' => 'Role / Specialty',
            'biography' =>
                'Add a short approved introduction '
                . 'explaining this personâ€™s experience '
                . 'or production specialty.',
            'image_asset_id' => 13,
            'image' =>
                '/assets/images/content/staff-2.jpg',
            'sort_order' => 30,
            'is_active' => 1,
            'is_protected' => 0,
        ],
        [
            'id' => 4,
            'profile_type' => 'staff',
            'name' => 'Staff Member Name',
            'role_title' => 'Role / Specialty',
            'biography' =>
                'Add a short approved introduction '
                . 'explaining how this person supports '
                . 'projects and customers.',
            'image_asset_id' => 14,
            'image' =>
                '/assets/images/content/staff-3.jpg',
            'sort_order' => 40,
            'is_active' => 1,
            'is_protected' => 0,
        ],
    ];
}

/**
 * @param array<string, mixed> $profile
 *
 * @return array<string, mixed>
 */
function dc_normalize_profile(
    array $profile
): array {
    $image = isset(
        $profile['file_path']
    )
        ? (string) $profile['file_path']
        : (string) (
            $profile['image']
            ?? ''
        );

    $imageAssetId =
        $profile['image_asset_id']
        ?? null;

    $profile['id'] =
        (int) (
            $profile['id']
            ?? 0
        );

    $profile['image_asset_id'] =
        $imageAssetId !== null
            ? (int) $imageAssetId
            : null;

    $profile['sort_order'] =
        (int) (
            $profile['sort_order']
            ?? 0
        );

    $profile['is_active'] =
        (int) (
            $profile['is_active']
            ?? 0
        );

    $profile['is_protected'] =
        (int) (
            $profile['is_protected']
            ?? 0
        );

    $profile['type'] =
        (string) (
            $profile['profile_type']
            ?? 'staff'
        );

    $profile['role'] =
        (string) (
            $profile['role_title']
            ?? ''
        );

    $profile['bio'] =
        (string) (
            $profile['biography']
            ?? ''
        );

    $profile['image'] = $image;

    $profile['image_alt'] = trim(
        (string) (
            $profile['alt_text']
            ?? ''
        )
    ) ?: (string) (
        $profile['name']
        ?? ''
    );

    $profile['image_exists'] =
        dc_public_path_exists($image);

    $profile['initials'] =
        dc_generate_initials(
            (string) (
                $profile['name']
                ?? ''
            )
        );

    return $profile;
}

/**
 * @return array<int, array<string, mixed>>
 */
function dc_profiles(
    bool $activeOnly = true,
    bool $refresh = false
): array {
    $cacheKey = $activeOnly
        ? 'profiles_active'
        : 'profiles_all';

    $cache =& dc_content_cache();

    if (
        !$refresh
        && isset($cache[$cacheKey])
    ) {
        return $cache[$cacheKey];
    }

    $profiles =
        dc_default_profiles();

    $pdo = database();

    if ($pdo !== null) {
        try {
            $sql =
                'SELECT
                    profile.*,
                    media.file_path,
                    media.alt_text,
                    media.media_kind,
                    media.mime_type
                 FROM profiles AS profile
                 LEFT JOIN media_assets AS media
                   ON media.id =
                      profile.image_asset_id';

            if ($activeOnly) {
                $sql .=
                    ' WHERE profile.is_active = 1';
            }

            $sql .=
                ' ORDER BY
                    CASE
                        WHEN profile.profile_type =
                            \'organization\'
                        THEN 0
                        ELSE 1
                    END,
                    profile.sort_order,
                    profile.id';

            $rows =
                $pdo->query($sql)->fetchAll();

            if (is_array($rows)) {
                $profiles = $rows;
            }
        } catch (Throwable $exception) {
            dc_log_content_error(
                'read profiles',
                $exception
            );
        }
    }

    if ($activeOnly) {
        $profiles = array_values(
            array_filter(
                $profiles,
                static fn (
                    array $profile
                ): bool =>
                    (int) (
                        $profile['is_active']
                        ?? 0
                    ) === 1
            )
        );
    }

    $profiles = array_map(
        'dc_normalize_profile',
        $profiles
    );

    $cache[$cacheKey] =
        $profiles;

    return $profiles;
}

/**
 * @param array<string, mixed> $profile
 */
function dc_create_profile(
    array $profile
): ?int {
    $pdo = database();

    if ($pdo === null) {
        return null;
    }

    $name = trim(
        (string) (
            $profile['name']
            ?? ''
        )
    );

    $role = trim(
        (string) (
            $profile['role_title']
            ?? ''
        )
    );

    $biography = trim(
        (string) (
            $profile['biography']
            ?? ''
        )
    );

    if (
        $name === ''
        || $role === ''
        || $biography === ''
    ) {
        return null;
    }

    try {
        $nextOrder = (int) $pdo->query(
            'SELECT
                COALESCE(MAX(sort_order), 0) + 10
             FROM profiles'
        )->fetchColumn();

        $profileType =
            ($profile['profile_type'] ?? 'staff')
            === 'organization'
                ? 'organization'
                : 'staff';

        $statement = $pdo->prepare(
            'INSERT INTO profiles (
                profile_type,
                name,
                role_title,
                biography,
                image_asset_id,
                sort_order,
                is_active,
                is_protected
             ) VALUES (
                :profile_type,
                :name,
                :role_title,
                :biography,
                :image_asset_id,
                :sort_order,
                :is_active,
                0
             )'
        );

        $statement->execute([
            'profile_type' => $profileType,
            'name' => $name,
            'role_title' => $role,
            'biography' => $biography,

            'image_asset_id' =>
                !empty(
                    $profile[
                        'image_asset_id'
                    ]
                )
                    ? (int) $profile[
                        'image_asset_id'
                    ]
                    : null,

            'sort_order' =>
                isset(
                    $profile['sort_order']
                )
                    ? (int) $profile[
                        'sort_order'
                    ]
                    : $nextOrder,

            'is_active' =>
                array_key_exists(
                    'is_active',
                    $profile
                )
                    ? (
                        !empty(
                            $profile[
                                'is_active'
                            ]
                        )
                            ? 1
                            : 0
                    )
                    : 1,
        ]);

        dc_forget_content_cache(
            'profiles_active'
        );

        dc_forget_content_cache(
            'profiles_all'
        );

        return (int) $pdo->lastInsertId();
    } catch (Throwable $exception) {
        dc_log_content_error(
            'create profile',
            $exception
        );

        return null;
    }
}

/**
 * @param array<string, mixed> $profile
 */
function dc_update_profile(
    int $profileId,
    array $profile
): bool {
    $pdo = database();

    if (
        $pdo === null
        || $profileId < 1
    ) {
        return false;
    }

    $name = trim(
        (string) (
            $profile['name']
            ?? ''
        )
    );

    $role = trim(
        (string) (
            $profile['role_title']
            ?? ''
        )
    );

    $biography = trim(
        (string) (
            $profile['biography']
            ?? ''
        )
    );

    if (
        $name === ''
        || $role === ''
        || $biography === ''
    ) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'UPDATE profiles SET
                name = :name,
                role_title = :role_title,
                biography = :biography,
                image_asset_id =
                    :image_asset_id,
                is_active = :is_active
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $profileId,
            'name' => $name,
            'role_title' => $role,
            'biography' => $biography,

            'image_asset_id' =>
                !empty(
                    $profile[
                        'image_asset_id'
                    ]
                )
                    ? (int) $profile[
                        'image_asset_id'
                    ]
                    : null,

            'is_active' =>
                !empty(
                    $profile[
                        'is_active'
                    ]
                )
                    ? 1
                    : 0,
        ]);

        dc_forget_content_cache(
            'profiles_active'
        );

        dc_forget_content_cache(
            'profiles_all'
        );

        return true;
    } catch (Throwable $exception) {
        dc_log_content_error(
            'update profile',
            $exception
        );

        return false;
    }
}

function dc_set_profile_active(
    int $profileId,
    bool $isActive
): bool {
    return dc_set_record_active(
        'profiles',
        $profileId,
        $isActive
    );
}

function dc_delete_profile(
    int $profileId
): bool {
    $pdo = database();

    if (
        $pdo === null
        || $profileId < 1
    ) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'DELETE FROM profiles
             WHERE id = :id
               AND is_protected = 0'
        );

        $statement->execute([
            'id' => $profileId,
        ]);

        if (
            $statement->rowCount()
            < 1
        ) {
            return false;
        }

        dc_forget_content_cache(
            'profiles_active'
        );

        dc_forget_content_cache(
            'profiles_all'
        );

        return true;
    } catch (Throwable $exception) {
        dc_log_content_error(
            'delete profile',
            $exception
        );

        return false;
    }
}

/**
 * @param array<int, int|string> $orderedIds
 */
function dc_reorder_profiles(
    array $orderedIds
): bool {
    return dc_reorder_records(
        'profiles',
        $orderedIds
    );
}

/**
 * Current partner placeholders used when the database is unavailable.
 *
 * @return array<int, array<string, mixed>>
 */
function dc_default_partners(): array
{
    $partners = [];

    for (
        $index = 1;
        $index <= 6;
        $index++
    ) {
        $partners[] = [
            'id' => $index,
            'name' => null,
            'catalog_url' => null,
            'logo_asset_id' =>
                14 + $index,
            'placeholder_label' =>
                'Partner logo',
            'image' =>
                '/assets/images/partners/'
                . 'partner-'
                . $index
                . '.svg',
            'sort_order' =>
                $index * 10,
            'is_active' => 1,
        ];
    }

    return $partners;
}

/**
 * @param array<string, mixed> $partner
 *
 * @return array<string, mixed>
 */
function dc_normalize_partner(
    array $partner
): array {
    $image = isset(
        $partner['file_path']
    )
        ? (string) $partner['file_path']
        : (string) (
            $partner['image']
            ?? ''
        );

    $name = trim(
        (string) (
            $partner['name']
            ?? ''
        )
    );

    $placeholder = trim(
        (string) (
            $partner[
                'placeholder_label'
            ]
            ?? 'Partner logo'
        )
    );

    $logoAssetId =
        $partner['logo_asset_id']
        ?? null;

    $partner['id'] =
        (int) (
            $partner['id']
            ?? 0
        );

    $partner['logo_asset_id'] =
        $logoAssetId !== null
            ? (int) $logoAssetId
            : null;

    $partner['sort_order'] =
        (int) (
            $partner['sort_order']
            ?? 0
        );

    $partner['is_active'] =
        (int) (
            $partner['is_active']
            ?? 0
        );

    $partner['name'] =
        $name !== ''
            ? $name
            : null;

    $partner['image'] = $image;
    $partner['logo'] = $image;

    $partner['alt_text'] = trim(
        (string) (
            $partner['alt_text']
            ?? ''
        )
    ) ?: (
        $name !== ''
            ? $name . ' logo'
            : $placeholder
    );

    $partner['image_exists'] =
        dc_public_path_exists($image);

    return $partner;
}

/**
 * @return array<int, array<string, mixed>>
 */
function dc_partners(
    bool $activeOnly = true,
    bool $refresh = false
): array {
    $cacheKey = $activeOnly
        ? 'partners_active'
        : 'partners_all';

    $cache =& dc_content_cache();

    if (
        !$refresh
        && isset($cache[$cacheKey])
    ) {
        return $cache[$cacheKey];
    }

    $partners =
        dc_default_partners();

    $pdo = database();

    if ($pdo !== null) {
        try {
            $sql =
                'SELECT
                    partner.*,
                    media.file_path,
                    media.alt_text,
                    media.media_kind,
                    media.mime_type
                 FROM partners AS partner
                 LEFT JOIN media_assets AS media
                   ON media.id =
                      partner.logo_asset_id';

            if ($activeOnly) {
                $sql .=
                    ' WHERE partner.is_active = 1';
            }

            $sql .=
                ' ORDER BY
                    partner.sort_order,
                    partner.id';

            $rows =
                $pdo->query($sql)->fetchAll();

            if (is_array($rows)) {
                $partners = $rows;
            }
        } catch (Throwable $exception) {
            dc_log_content_error(
                'read partners',
                $exception
            );
        }
    }

    if ($activeOnly) {
        $partners = array_values(
            array_filter(
                $partners,
                static fn (
                    array $partner
                ): bool =>
                    (int) (
                        $partner['is_active']
                        ?? 0
                    ) === 1
            )
        );
    }

    $partners = array_map(
        'dc_normalize_partner',
        $partners
    );

    $cache[$cacheKey] =
        $partners;

    return $partners;
}

/**
 * @param array<string, mixed> $partner
 */
function dc_create_partner(
    array $partner
): ?int {
    $pdo = database();

    if ($pdo === null) {
        return null;
    }

    $name = trim(
        (string) (
            $partner['name']
            ?? ''
        )
    );

    if ($name === '') {
        return null;
    }

    try {
        $nextOrder = (int) $pdo->query(
            'SELECT
                COALESCE(MAX(sort_order), 0) + 10
             FROM partners'
        )->fetchColumn();

        $statement = $pdo->prepare(
            'INSERT INTO partners (
                name,
                catalog_url,
                logo_asset_id,
                placeholder_label,
                sort_order,
                is_active
             ) VALUES (
                :name,
                :catalog_url,
                :logo_asset_id,
                :placeholder_label,
                :sort_order,
                :is_active
             )'
        );

        $statement->execute([
            'name' => $name,

            'catalog_url' =>
                trim(
                    (string) (
                        $partner[
                            'catalog_url'
                        ]
                        ?? ''
                    )
                ) ?: null,

            'logo_asset_id' =>
                !empty(
                    $partner[
                        'logo_asset_id'
                    ]
                )
                    ? (int) $partner[
                        'logo_asset_id'
                    ]
                    : null,

            'placeholder_label' =>
                trim(
                    (string) (
                        $partner[
                            'placeholder_label'
                        ]
                        ?? ''
                    )
                ) ?: 'Partner logo',

            'sort_order' =>
                isset(
                    $partner['sort_order']
                )
                    ? (int) $partner[
                        'sort_order'
                    ]
                    : $nextOrder,

            'is_active' =>
                array_key_exists(
                    'is_active',
                    $partner
                )
                    ? (
                        !empty(
                            $partner[
                                'is_active'
                            ]
                        )
                            ? 1
                            : 0
                    )
                    : 1,
        ]);

        dc_forget_content_cache(
            'partners_active'
        );

        dc_forget_content_cache(
            'partners_all'
        );

        return (int) $pdo->lastInsertId();
    } catch (Throwable $exception) {
        dc_log_content_error(
            'create partner',
            $exception
        );

        return null;
    }
}

/**
 * @param array<string, mixed> $partner
 */
function dc_update_partner(
    int $partnerId,
    array $partner
): bool {
    $pdo = database();

    if (
        $pdo === null
        || $partnerId < 1
    ) {
        return false;
    }

    $name = trim(
        (string) (
            $partner['name']
            ?? ''
        )
    );

    if ($name === '') {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'UPDATE partners SET
                name = :name,
                catalog_url = :catalog_url,
                logo_asset_id = :logo_asset_id,
                placeholder_label =
                    :placeholder_label,
                is_active = :is_active
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $partnerId,
            'name' => $name,

            'catalog_url' =>
                trim(
                    (string) (
                        $partner[
                            'catalog_url'
                        ]
                        ?? ''
                    )
                ) ?: null,

            'logo_asset_id' =>
                !empty(
                    $partner[
                        'logo_asset_id'
                    ]
                )
                    ? (int) $partner[
                        'logo_asset_id'
                    ]
                    : null,

            'placeholder_label' =>
                trim(
                    (string) (
                        $partner[
                            'placeholder_label'
                        ]
                        ?? ''
                    )
                ) ?: 'Partner logo',

            'is_active' =>
                !empty(
                    $partner[
                        'is_active'
                    ]
                )
                    ? 1
                    : 0,
        ]);

        dc_forget_content_cache(
            'partners_active'
        );

        dc_forget_content_cache(
            'partners_all'
        );

        return true;
    } catch (Throwable $exception) {
        dc_log_content_error(
            'update partner',
            $exception
        );

        return false;
    }
}

function dc_set_partner_active(
    int $partnerId,
    bool $isActive
): bool {
    return dc_set_record_active(
        'partners',
        $partnerId,
        $isActive
    );
}

function dc_delete_partner(
    int $partnerId
): bool {
    return dc_delete_record(
        'partners',
        $partnerId
    );
}

/**
 * @param array<int, int|string> $orderedIds
 */
function dc_reorder_partners(
    array $orderedIds
): bool {
    return dc_reorder_records(
        'partners',
        $orderedIds
    );
}

/**
 * Shared helper for simple visibility toggles.
 */
function dc_set_record_active(
    string $table,
    int $recordId,
    bool $isActive
): bool {
    $allowedTables = [
        'services',
        'testimonials',
        'profiles',
        'partners',
    ];

    if (
        !in_array(
            $table,
            $allowedTables,
            true
        )
        || $recordId < 1
    ) {
        return false;
    }

    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'UPDATE '
            . $table
            . '
             SET is_active = :is_active
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $recordId,
            'is_active' =>
                $isActive ? 1 : 0,
        ]);

        dc_forget_content_cache(
            $table . '_active'
        );

        dc_forget_content_cache(
            $table . '_all'
        );

        return true;
    } catch (Throwable $exception) {
        dc_log_content_error(
            'set '
            . $table
            . ' visibility',
            $exception
        );

        return false;
    }
}

/**
 * Shared helper for records that can be permanently removed.
 */
function dc_delete_record(
    string $table,
    int $recordId
): bool {
    $allowedTables = [
        'services',
        'testimonials',
        'partners',
    ];

    if (
        !in_array(
            $table,
            $allowedTables,
            true
        )
        || $recordId < 1
    ) {
        return false;
    }

    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'DELETE FROM '
            . $table
            . '
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $recordId,
        ]);

        if (
            $statement->rowCount()
            < 1
        ) {
            return false;
        }

        dc_forget_content_cache(
            $table . '_active'
        );

        dc_forget_content_cache(
            $table . '_all'
        );

        return true;
    } catch (Throwable $exception) {
        dc_log_content_error(
            'delete '
            . $table
            . ' record',
            $exception
        );

        return false;
    }
}

/**
 * Store display order without asking the administrator to type order numbers.
 *
 * @param array<int, int|string> $orderedIds
 */
function dc_reorder_records(
    string $table,
    array $orderedIds
): bool {
    $allowedTables = [
        'services',
        'testimonials',
        'profiles',
        'partners',
    ];

    if (
        !in_array(
            $table,
            $allowedTables,
            true
        )
    ) {
        return false;
    }

    $ids = array_values(
        array_unique(
            array_filter(
                array_map(
                    'intval',
                    $orderedIds
                ),
                static fn (
                    int $id
                ): bool => $id > 0
            )
        )
    );

    if ($ids === []) {
        return false;
    }

    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'UPDATE '
            . $table
            . '
             SET sort_order = :sort_order
             WHERE id = :id'
        );

        $pdo->beginTransaction();

        foreach (
            $ids
            as $index => $id
        ) {
            $statement->execute([
                'id' => $id,
                'sort_order' =>
                    ($index + 1) * 10,
            ]);
        }

        $pdo->commit();

        dc_forget_content_cache(
            $table . '_active'
        );

        dc_forget_content_cache(
            $table . '_all'
        );

        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        dc_log_content_error(
            'reorder ' . $table,
            $exception
        );

        return false;
    }
}

/**
 * Compact dashboard totals for the administration landing page.
 *
 * @return array<string, int>
 */
function dc_admin_content_counts(): array
{
    $counts = [
        'services' =>
            count(dc_services(true)),
        'testimonials' =>
            count(dc_testimonials(true)),
        'profiles' =>
            count(dc_profiles(true)),
        'partners' =>
            count(dc_partners(true)),
    ];

    $pdo = database();

    if ($pdo === null) {
        return $counts;
    }

    try {
        $statement = $pdo->query(
            'SELECT
                (
                    SELECT COUNT(*)
                    FROM services
                    WHERE is_active = 1
                ) AS services,
                (
                    SELECT COUNT(*)
                    FROM testimonials
                    WHERE is_active = 1
                ) AS testimonials,
                (
                    SELECT COUNT(*)
                    FROM profiles
                    WHERE is_active = 1
                ) AS profiles,
                (
                    SELECT COUNT(*)
                    FROM partners
                    WHERE is_active = 1
                ) AS partners'
        );

        $row =
            $statement->fetch();

        if (is_array($row)) {
            foreach (
                $counts
                as $key => $value
            ) {
                $counts[$key] =
                    (int) (
                        $row[$key]
                        ?? $value
                    );
            }
        }
    } catch (Throwable $exception) {
        dc_log_content_error(
            'read admin content counts',
            $exception
        );
    }

    return $counts;
}
