<?php

/*
 * Site Settings administration partial.
 *
 * This file is included once in process mode and once in render mode.
 */

if (
    ($adminPartialMode ?? '')
    === 'process'
) {
    $action = trim(
        (string) (
            $_POST['action']
            ?? ''
        )
    );

    if (
        dc_admin_process_site_media_action(
            $action,
            'business',
            [
                'brand_mark',
                'favicon',
            ]
        )
    ) {
        return;
    }

    if ($action === 'update_business') {
        $values = [
            'business_name' =>
                trim(
                    (string) (
                        $_POST['business_name']
                        ?? ''
                    )
                ),

            'tagline' =>
                trim(
                    (string) (
                        $_POST['tagline']
                        ?? ''
                    )
                ),

            'phone_number' =>
                trim(
                    (string) (
                        $_POST['phone_number']
                        ?? ''
                    )
                ),

            'email_address' =>
                trim(
                    (string) (
                        $_POST['email_address']
                        ?? ''
                    )
                ),

            'street_address' =>
                trim(
                    (string) (
                        $_POST['street_address']
                        ?? ''
                    )
                ),

            'city' =>
                trim(
                    (string) (
                        $_POST['city']
                        ?? ''
                    )
                ),

            'state_code' =>
                strtoupper(
                    trim(
                        (string) (
                            $_POST['state_code']
                            ?? ''
                        )
                    )
                ),

            'postal_code' =>
                trim(
                    (string) (
                        $_POST['postal_code']
                        ?? ''
                    )
                ),

            'country_code' =>
                strtoupper(
                    trim(
                        (string) (
                            $_POST['country_code']
                            ?? 'US'
                        )
                    )
                ),

            'location_note' =>
                trim(
                    (string) (
                        $_POST['location_note']
                        ?? ''
                    )
                ),

            'weekday_hours' =>
                trim(
                    (string) (
                        $_POST['weekday_hours']
                        ?? ''
                    )
                ),

            'weekend_hours' =>
                trim(
                    (string) (
                        $_POST['weekend_hours']
                        ?? ''
                    )
                ),
        ];

        $validationError =
            dc_admin_validate_text_values(
                $values,
                [
                    'business_name' => [
                        'label' =>
                            'Business name',

                        'limit' =>
                            'business_name',

                        'required' =>
                            true,
                    ],

                    'tagline' => [
                        'label' =>
                            'Short tagline',

                        'limit' =>
                            'tagline',

                        'required' =>
                            true,
                    ],

                    'phone_number' => [
                        'label' =>
                            'Phone number',

                        'limit' =>
                            'phone_number',

                        'required' =>
                            true,
                    ],

                    'email_address' => [
                        'label' =>
                            'Public email address',

                        'limit' =>
                            'email_address',

                        'required' =>
                            true,
                    ],

                    'street_address' => [
                        'label' =>
                            'Street address',

                        'limit' =>
                            'street_address',

                        'required' =>
                            true,
                    ],

                    'city' => [
                        'label' =>
                            'City',

                        'limit' =>
                            'city',

                        'required' =>
                            true,
                    ],

                    'state_code' => [
                        'label' =>
                            'State',

                        'limit' =>
                            'state_code',

                        'required' =>
                            true,
                    ],

                    'postal_code' => [
                        'label' =>
                            'Postal code',

                        'limit' =>
                            'postal_code',

                        'required' =>
                            true,
                    ],

                    'country_code' => [
                        'label' =>
                            'Country code',

                        'limit' =>
                            'country_code',

                        'required' =>
                            true,
                    ],

                    'location_note' => [
                        'label' =>
                            'Location note',

                        'limit' =>
                            'location_note',
                    ],

                    'weekday_hours' => [
                        'label' =>
                            'Monday–Friday hours',

                        'limit' =>
                            'weekday_hours',

                        'required' =>
                            true,
                    ],

                    'weekend_hours' => [
                        'label' =>
                            'Saturday–Sunday hours',

                        'limit' =>
                            'weekend_hours',

                        'required' =>
                            true,
                    ],
                ]
            );

        if ($validationError !== null) {
            flash(
                'error',
                $validationError
            );

            dc_admin_redirect(
                'business',
                'business-information'
            );
        }

        if (
            !filter_var(
                $values['email_address'],
                FILTER_VALIDATE_EMAIL
            )
            || strlen(
                $values['state_code']
            ) !== 2
            || strlen(
                $values['country_code']
            ) !== 2
        ) {
            flash(
                'error',
                'Check the public email address and use two-letter state and country codes.'
            );

            dc_admin_redirect(
                'business',
                'business-information'
            );
        }

        $updated =
            dc_update_site_settings(
                $values
            );

        if ($updated) {
            foreach (
                dc_profiles(false, true)
                as $profile
            ) {
                if (
                    (
                        $profile[
                            'profile_type'
                        ]
                        ?? $profile['type']
                        ?? ''
                    ) !== 'organization'
                ) {
                    continue;
                }

                dc_update_profile(
                    (int) $profile['id'],
                    [
                        'name' =>
                            $values[
                                'business_name'
                            ],

                        'role_title' =>
                            'About the Company',

                        'biography' =>
                            (string) (
                                $profile[
                                    'biography'
                                ]
                                ?? $profile['bio']
                                ?? ''
                            ),

                        'image_asset_id' =>
                            $profile[
                                'image_asset_id'
                            ]
                            ?? null,

                        'is_active' =>
                            true,
                    ]
                );

                break;
            }

            dc_update_content_values([
                'location_call_label' =>
                    'Call '
                    . $values[
                        'business_name'
                    ],
            ]);
        }

        dc_admin_finish(
            $updated,
            'Business information published.',
            'Business information could not be published.',
            'business',
            'business-information'
        );
    }

    if (
        $action
        === 'update_header_settings'
    ) {
        $callLabel = trim(
            (string) (
                $_POST[
                    'header_call_label'
                ]
                ?? ''
            )
        );

        $quoteLabel = trim(
            (string) (
                $_POST[
                    'header_quote_button_label'
                ]
                ?? ''
            )
        );

        $validationError =
            dc_admin_validate_text_values(
                [
                    'header_call_label' =>
                        $callLabel,

                    'header_quote_button_label' =>
                        $quoteLabel,
                ],
                [
                    'header_call_label' => [
                        'label' =>
                            'Phone-number label',

                        'limit' =>
                            'header_call_label',

                        'required' =>
                            true,
                    ],

                    'header_quote_button_label' => [
                        'label' =>
                            'Quote-button text',

                        'limit' =>
                            'header_quote_button_label',

                        'required' =>
                            true,
                    ],
                ]
            );

        if ($validationError !== null) {
            flash(
                'error',
                $validationError
            );

            dc_admin_redirect(
                'business',
                'header-settings'
            );
        }

        $updates = [
            'header_call_label' =>
                $callLabel,

            'header_quote_button_label' =>
                $quoteLabel,
        ];

        foreach (
            [
                'hero_primary_button_label',
                'service_card_button_label',
                'quote_band_button_label',
                'footer_primary_button_label',
                'about_profile_button_label',
                'catalog_button_label',
            ]
            as $sharedQuoteKey
        ) {
            $updates[$sharedQuoteKey] =
                $quoteLabel;
        }

        dc_admin_finish(
            dc_update_content_values(
                $updates
            ),
            'Header and quote-button labels published.',
            'The header labels could not be published.',
            'business',
            'header-settings'
        );
    }

    if (
        $action
        === 'update_search_settings'
    ) {
        $pageTitleValue = trim(
            (string) (
                $_POST['page_title']
                ?? ''
            )
        );

        $metaDescriptionValue = trim(
            (string) (
                $_POST[
                    'meta_description'
                ]
                ?? ''
            )
        );

        $validationError =
            dc_admin_validate_text_values(
                [
                    'page_title' =>
                        $pageTitleValue,

                    'meta_description' =>
                        $metaDescriptionValue,
                ],
                [
                    'page_title' => [
                        'label' =>
                            'Browser and search title',

                        'limit' =>
                            'page_title',

                        'required' =>
                            true,
                    ],

                    'meta_description' => [
                        'label' =>
                            'Search description',

                        'limit' =>
                            'meta_description',

                        'required' =>
                            true,
                    ],
                ]
            );

        if ($validationError !== null) {
            flash(
                'error',
                $validationError
            );

            dc_admin_redirect(
                'business',
                'search-settings'
            );
        }

        dc_admin_finish(
            dc_update_site_settings([
                'page_title' =>
                    $pageTitleValue,

                'meta_description' =>
                    $metaDescriptionValue,
            ]),
            'Search and browser information published.',
            'Search and browser information could not be published.',
            'business',
            'search-settings'
        );
    }

    return;
}

?>
<section
    class="card border-0 shadow-sm mb-4 admin-content-type-card"
    id="business-information"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            Business Information
        </h2>

        <p class="small text-body-secondary mb-0">
            Contact details, address, and hours reused throughout the website.
        </p>
    </div>

    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="update_business"
            >

            <div class="row g-3">
                <?php
                $businessFields = [
                    [
                        'name' => 'business_name',
                        'label' => 'Business name',
                        'column' => 'col-md-6',
                        'autocomplete' => 'organization',
                    ],
                    [
                        'name' => 'tagline',
                        'label' => 'Short tagline',
                        'column' => 'col-md-6',
                    ],
                    [
                        'name' => 'phone_number',
                        'label' => 'Phone number',
                        'column' => 'col-md-6',
                        'autocomplete' => 'tel',
                    ],
                    [
                        'name' => 'email_address',
                        'label' => 'Public email address',
                        'column' => 'col-md-6',
                        'type' => 'email',
                        'autocomplete' => 'email',
                    ],
                    [
                        'name' => 'street_address',
                        'label' => 'Street address',
                        'column' => 'col-md-6',
                        'autocomplete' => 'street-address',
                    ],
                    [
                        'name' => 'city',
                        'label' => 'City',
                        'column' => 'col-md-3',
                        'autocomplete' => 'address-level2',
                    ],
                    [
                        'name' => 'state_code',
                        'label' => 'State',
                        'column' => 'col-md-3',
                        'autocomplete' => 'address-level1',
                        'class' => ' text-uppercase',
                    ],
                    [
                        'name' => 'postal_code',
                        'label' => 'Postal code',
                        'column' => 'col-md-4',
                        'autocomplete' => 'postal-code',
                    ],
                    [
                        'name' => 'country_code',
                        'label' => 'Country',
                        'column' => 'col-md-2',
                        'autocomplete' => 'country',
                        'class' => ' text-uppercase',
                    ],
                    [
                        'name' => 'location_note',
                        'label' => 'Location note',
                        'column' => 'col-md-6',
                        'required' => false,
                        'help' => 'Example: DeForest Town Square',
                    ],
                    [
                        'name' => 'weekday_hours',
                        'label' => 'Monday–Friday hours',
                        'column' => 'col-md-6',
                    ],
                    [
                        'name' => 'weekend_hours',
                        'label' => 'Saturday–Sunday hours',
                        'column' => 'col-md-6',
                    ],
                ];
                ?>

                <?php foreach (
                    $businessFields
                    as $field
                ): ?>
                    <?php
                    $fieldName =
                        (string) $field['name'];

                    $required =
                        $field['required']
                        ?? true;
                    ?>

                    <div class="<?= e(
                        (string) $field['column']
                    ) ?>">
                        <label
                            class="form-label"
                            for="<?= e($fieldName) ?>"
                        >
                            <?= e(
                                (string) $field[
                                    'label'
                                ]
                            ) ?>
                        </label>

                        <input
                            class="form-control<?= e(
                                (string) (
                                    $field['class']
                                    ?? ''
                                )
                            ) ?>"
                            id="<?= e($fieldName) ?>"
                            name="<?= e($fieldName) ?>"
                            type="<?= e(
                                (string) (
                                    $field['type']
                                    ?? 'text'
                                )
                            ) ?>"
                            maxlength="<?= dc_admin_limit(
                                $fieldName
                            ) ?>"
                            data-character-count
                            value="<?= e(
                                (string) (
                                    $siteSettings[
                                        $fieldName
                                    ]
                                    ?? ''
                                )
                            ) ?>"
                            <?= isset(
                                $field[
                                    'autocomplete'
                                ]
                            )
                                ? 'autocomplete="'
                                    . e(
                                        (string) $field[
                                            'autocomplete'
                                        ]
                                    )
                                    . '"'
                                : '' ?>
                            <?= $required
                                ? 'required'
                                : '' ?>
                        >

                        <?php if (
                            isset($field['help'])
                        ): ?>
                            <div class="form-text">
                                <?= e(
                                    (string) $field[
                                        'help'
                                    ]
                                ) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <button
                class="btn btn-primary mt-4"
                type="submit"
            >
                Publish Business Information
            </button>
        </form>
    </div>
</section>

<section
    class="card border-0 shadow-sm mb-4 admin-content-type-card"
    id="header-settings"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            Site Header &amp; Quote Buttons
        </h2>

        <p class="small text-body-secondary mb-0">
            Text shown in the navigation bar and on links that open the quote
            form.
        </p>
    </div>

    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="update_header_settings"
            >

            <div class="row g-4">
                <div class="col-lg-6">
                    <label
                        class="form-label"
                        for="header_call_label"
                    >
                        Label above the header phone number
                    </label>

                    <input
                        class="form-control"
                        id="header_call_label"
                        name="header_call_label"
                        maxlength="<?= dc_admin_limit(
                            'header_call_label'
                        ) ?>"
                        data-character-count
                        value="<?= e(
                            dc_content(
                                'header_call_label'
                            )
                        ) ?>"
                        required
                    >

                    <div class="form-text">
                        Example: Call us
                    </div>
                </div>

                <div class="col-lg-6">
                    <label
                        class="form-label"
                        for="header_quote_button_label"
                    >
                        Text on buttons that open the quote form
                    </label>

                    <input
                        class="form-control"
                        id="header_quote_button_label"
                        name="header_quote_button_label"
                        maxlength="<?= dc_admin_limit(
                            'header_quote_button_label'
                        ) ?>"
                        data-character-count
                        value="<?= e(
                            dc_content(
                                'header_quote_button_label'
                            )
                        ) ?>"
                        required
                    >

                    <div class="form-text">
                        Reused everywhere the website sends a visitor to the
                        quote form.
                    </div>
                </div>
            </div>

            <button
                class="btn btn-primary mt-4"
                type="submit"
            >
                Publish Header Settings
            </button>
        </form>
    </div>
</section>

<?php
dc_admin_render_site_media_cards(
    [
        'brand_mark',
        'favicon',
    ],
    'business',
    'Branding',
    'Replace the logo mark or browser-tab icon.'
);
?>

<section
    class="card border-0 shadow-sm mb-4 admin-content-type-card"
    id="search-settings"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            Search &amp; Browser Information
        </h2>

        <p class="small text-body-secondary mb-0">
            These fields are used by browsers and search engines rather than
            as normal homepage text.
        </p>
    </div>

    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="update_search_settings"
            >

            <div class="row g-4 align-items-start">
                <div class="col-xl-7">
                    <div class="mb-3">
                        <label
                            class="form-label"
                            for="page_title"
                        >
                            Browser-tab and possible Google-result title
                        </label>

                        <input
                            class="form-control"
                            id="page_title"
                            name="page_title"
                            maxlength="<?= dc_admin_limit(
                                'page_title'
                            ) ?>"
                            data-character-count
                            value="<?= e(
                                (string) $siteSettings[
                                    'page_title'
                                ]
                            ) ?>"
                            required
                            data-search-preview-title-input
                        >

                        <div class="form-text">
                            Appears in the browser tab. Google may use it as
                            the blue search-result link.
                        </div>
                    </div>

                    <div>
                        <label
                            class="form-label"
                            for="meta_description"
                        >
                            Possible Google-result description
                        </label>

                        <textarea
                            class="form-control"
                            id="meta_description"
                            name="meta_description"
                            rows="4"
                            maxlength="<?= dc_admin_limit(
                                'meta_description'
                            ) ?>"
                            data-character-count
                            required
                            data-search-preview-description-input
                        ><?= e(
                            (string) $siteSettings[
                                'meta_description'
                            ]
                        ) ?></textarea>

                        <div class="form-text">
                            Google may show this beneath the result title and
                            may shorten or rewrite it.
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div
                        class="admin-search-preview"
                        aria-label="Example search-result preview"
                    >
                        <p class="admin-search-preview__label">
                            Possible Google result
                        </p>

                        <div class="admin-search-preview__site">
                            <?= e(
                                (string) $siteSettings[
                                    'business_name'
                                ]
                            ) ?>
                        </div>

                        <div class="admin-search-preview__url">
                            dcimprints.com
                        </div>

                        <div
                            class="admin-search-preview__title"
                            data-search-preview-title
                        >
                            <?= e(
                                (string) $siteSettings[
                                    'page_title'
                                ]
                            ) ?>
                        </div>

                        <div
                            class="admin-search-preview__description"
                            data-search-preview-description
                        >
                            <?= e(
                                (string) $siteSettings[
                                    'meta_description'
                                ]
                            ) ?>
                        </div>
                    </div>
                </div>
            </div>

            <button
                class="btn btn-primary mt-4"
                type="submit"
            >
                Publish Search Information
            </button>
        </form>
    </div>
</section>