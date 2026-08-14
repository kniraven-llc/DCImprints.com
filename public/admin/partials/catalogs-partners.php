<?php

declare(strict_types=1);

/*
 * Catalogs & Partners administration partial.
 *
 * Handles:
 * - Catalogs section heading and introduction
 * - Catalog help-panel text
 * - Creating partners
 * - Editing partner names and catalog URLs
 * - Partner logos
 * - Visibility
 * - Display order
 * - Deletion
 */

function dc_admin_partners_find(
    int $partnerId
): ?array {
    foreach (
        dc_partners(false, true)
        as $partner
    ) {
        if (
            (int) (
                $partner['id']
                ?? 0
            ) === $partnerId
        ) {
            return $partner;
        }
    }

    return null;
}

/**
 * @return array<int, int>
 */
function dc_admin_partner_ids(): array
{
    return array_values(
        array_map(
            static fn (
                array $partner
            ): int =>
                (int) (
                    $partner['id']
                    ?? 0
                ),
            dc_partners(false, true)
        )
    );
}

function dc_admin_reorder_partner(
    int $partnerId,
    string $direction
): bool {
    $ids =
        dc_admin_partner_ids();

    $index =
        array_search(
            $partnerId,
            $ids,
            true
        );

    if ($index === false) {
        return false;
    }

    $targetIndex =
        $direction === 'up'
            ? $index - 1
            : $index + 1;

    if (!isset($ids[$targetIndex])) {
        return true;
    }

    [
        $ids[$index],
        $ids[$targetIndex],
    ] = [
        $ids[$targetIndex],
        $ids[$index],
    ];

    return dc_reorder_partners(
        $ids
    );
}

/**
 * @return array<string, mixed>
 */
function dc_admin_partner_logo_spec(): array
{
    $configuredMaximum =
        4 * 1024 * 1024;

    $serverLimits =
        array_values(
            array_filter(
                [
                    dc_admin_ini_size_to_bytes(
                        (string) ini_get(
                            'upload_max_filesize'
                        )
                    ),

                    dc_admin_ini_size_to_bytes(
                        (string) ini_get(
                            'post_max_size'
                        )
                    ),
                ],

                static fn (
                    int $bytes
                ): bool =>
                    $bytes > 0
            )
        );

    $effectiveMaximum =
        $configuredMaximum;

    if ($serverLimits !== []) {
        $effectiveMaximum =
            min(
                $configuredMaximum,
                ...$serverLimits
            );
    }

    return [
        'accept' =>
            'image/jpeg,image/png,image/webp',

        'mime_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
        ],

        'types' =>
            'PNG, WebP, or JPG',

        'max_bytes' =>
            $effectiveMaximum,

        'configured_max_bytes' =>
            $configuredMaximum,

        'server_limited' =>
            $effectiveMaximum
            < $configuredMaximum,

        'min_width' => 300,
        'min_height' => 100,
        'max_width' => 4000,
        'max_height' => 2000,
        'min_ratio' => 0.75,
        'max_ratio' => 6.00,

        'recommended' =>
            'Transparent PNG or WebP at 1200 × 600 px, ideally 500 KB or smaller.',
    ];
}

function dc_admin_partner_logo_attributes(): string
{
    $spec =
        dc_admin_partner_logo_spec();

    $attributes = [
        'data-media-purpose' =>
            'partner_logo',

        'data-max-bytes' =>
            (string) $spec[
                'max_bytes'
            ],

        'data-min-width' =>
            (string) $spec[
                'min_width'
            ],

        'data-min-height' =>
            (string) $spec[
                'min_height'
            ],

        'data-max-width' =>
            (string) $spec[
                'max_width'
            ],

        'data-max-height' =>
            (string) $spec[
                'max_height'
            ],

        'data-min-ratio' =>
            (string) $spec[
                'min_ratio'
            ],

        'data-max-ratio' =>
            (string) $spec[
                'max_ratio'
            ],
    ];

    $parts = [];

    foreach (
        $attributes
        as $name => $value
    ) {
        $parts[] =
            $name
            . '="'
            . e($value)
            . '"';
    }

    return implode(
        ' ',
        $parts
    );
}

function dc_admin_render_partner_logo_requirements(): void
{
    $spec =
        dc_admin_partner_logo_spec();
    ?>
    <div
        class="admin-media-requirements alert alert-light border small mt-2 mb-0"
    >
        <strong>Recommended:</strong>

        <?= e(
            (string) $spec[
                'recommended'
            ]
        ) ?>

        <br>

        <strong>Accepted and enforced:</strong>

        <?= e(
            (string) $spec[
                'types'
            ]
        ) ?>,

        maximum

        <?= e(
            dc_admin_format_bytes(
                (int) $spec[
                    'max_bytes'
                ]
            )
        ) ?>.

        Images must be

        <?= e(
            (string) $spec[
                'min_width'
            ]
        ) ?>–<?= e(
            (string) $spec[
                'max_width'
            ]
        ) ?> px wide and

        <?= e(
            (string) $spec[
                'min_height'
            ]
        ) ?>–<?= e(
            (string) $spec[
                'max_height'
            ]
        ) ?> px tall.

        <?php if (
            !empty(
                $spec[
                    'server_limited'
                ]
            )
        ): ?>
            <br>

            <strong>Server limit:</strong>

            PHP currently limits this upload to

            <?= e(
                dc_admin_format_bytes(
                    (int) $spec[
                        'max_bytes'
                    ]
                )
            ) ?>.

            The normal field limit is

            <?= e(
                dc_admin_format_bytes(
                    (int) $spec[
                        'configured_max_bytes'
                    ]
                )
            ) ?>.
        <?php endif; ?>
    </div>

    <div
        class="form-text text-danger d-none"
        data-media-validation-message
        role="alert"
    ></div>
    <?php
}

/**
 * @param array<string, mixed> $file
 *
 * @return array{0: bool, 1: string}
 */
function dc_admin_validate_partner_logo(
    array $file
): array {
    $spec =
        dc_admin_partner_logo_spec();

    $error =
        (int) (
            $file['error']
            ?? UPLOAD_ERR_NO_FILE
        );

    if ($error !== UPLOAD_ERR_OK) {
        return [
            false,
            dc_upload_error_message(
                $error
            ),
        ];
    }

    $temporaryPath =
        (string) (
            $file['tmp_name']
            ?? ''
        );

    $size =
        (int) (
            $file['size']
            ?? 0
        );

    if (
        $temporaryPath === ''
        || !is_file($temporaryPath)
    ) {
        return [
            false,
            'The uploaded partner logo could not be read.',
        ];
    }

    if (
        $size < 1
        || $size
            > (int) $spec[
                'max_bytes'
            ]
    ) {
        return [
            false,
            'The partner logo must be no larger than '
            . dc_admin_format_bytes(
                (int) $spec[
                    'max_bytes'
                ]
            )
            . '.',
        ];
    }

    try {
        $mime =
            (new finfo(
                FILEINFO_MIME_TYPE
            ))->file(
                $temporaryPath
            );
    } catch (Throwable $exception) {
        log_message(
            'Unable to inspect partner logo: '
            . $exception->getMessage()
        );

        return [
            false,
            'The server could not inspect the selected logo.',
        ];
    }

    if (
        !is_string($mime)
        || !in_array(
            $mime,
            $spec[
                'mime_types'
            ],
            true
        )
    ) {
        return [
            false,
            'Use a PNG, WebP, or JPG partner logo.',
        ];
    }

    $dimensions =
        @getimagesize(
            $temporaryPath
        );

    if ($dimensions === false) {
        return [
            false,
            'The selected file is not a valid image.',
        ];
    }

    $width =
        (int) (
            $dimensions[0]
            ?? 0
        );

    $height =
        (int) (
            $dimensions[1]
            ?? 0
        );

    if (
        $width
            < (int) $spec[
                'min_width'
            ]
        || $width
            > (int) $spec[
                'max_width'
            ]
        || $height
            < (int) $spec[
                'min_height'
            ]
        || $height
            > (int) $spec[
                'max_height'
            ]
    ) {
        return [
            false,
            sprintf(
                'Partner logos must be %d–%d px wide and %d–%d px tall.',
                (int) $spec[
                    'min_width'
                ],
                (int) $spec[
                    'max_width'
                ],
                (int) $spec[
                    'min_height'
                ],
                (int) $spec[
                    'max_height'
                ]
            ),
        ];
    }

    $ratio =
        $height > 0
            ? $width / $height
            : 0.0;

    if (
        $ratio
            < (float) $spec[
                'min_ratio'
            ]
        || $ratio
            > (float) $spec[
                'max_ratio'
            ]
    ) {
        return [
            false,
            'The logo is too narrow or too wide. '
            . (string) $spec[
                'recommended'
            ],
        ];
    }

    return [
        true,
        '',
    ];
}

function dc_admin_valid_partner_url(
    string $catalogUrl
): bool {
    return $catalogUrl === ''
        || filter_var(
            $catalogUrl,
            FILTER_VALIDATE_URL
        ) !== false;
}

function dc_admin_validate_partner(
    string $name,
    string $catalogUrl
): ?string {
    if ($name === '') {
        return 'Partner or supplier name is required.';
    }

    if (
        mb_strlen($name)
        > 100
    ) {
        return 'Partner or supplier name must be 100 characters or fewer.';
    }

    if (
        mb_strlen($catalogUrl)
        > 2048
    ) {
        return 'Catalog page link must be 2,048 characters or fewer.';
    }

    if (
        !dc_admin_valid_partner_url(
            $catalogUrl
        )
    ) {
        return 'Enter a complete catalog URL beginning with http:// or https://.';
    }

    return null;
}

function dc_admin_delete_partner_record(
    int $partnerId
): bool {
    $partner =
        dc_admin_partners_find(
            $partnerId
        );

    if ($partner === null) {
        return false;
    }

    $assetId =
        isset(
            $partner[
                'logo_asset_id'
            ]
        )
            ? (int) $partner[
                'logo_asset_id'
            ]
            : null;

    $deleted =
        dc_delete_partner(
            $partnerId
        );

    if (
        $deleted
        && $assetId !== null
        && $assetId > 0
    ) {
        dc_delete_unused_managed_media_asset(
            $assetId
        );
    }

    return $deleted;
}

/**
 * @param array<int, string> $keys
 * @param array<string, array<string, mixed>> $records
 * @param array<string, array<string, mixed>> $configuration
 */
function dc_admin_render_catalog_content_form(
    string $action,
    string $heading,
    string $description,
    string $buttonLabel,
    array $keys,
    array $records,
    array $configuration
): void {
    ?>
    <section
        class="card border-0 shadow-sm mb-4 admin-section-copy"
    >
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-1">
                <?= e($heading) ?>
            </h2>

            <p class="small text-body-secondary mb-0">
                <?= e($description) ?>
            </p>
        </div>

        <div class="card-body p-4">
            <form method="post">
                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="action"
                    value="<?= e($action) ?>"
                >

                <div class="row g-4">
                    <?php foreach (
                        $keys
                        as $key
                    ): ?>
                        <?php
                        $record =
                            $records[$key]
                            ?? [];

                        $fieldConfiguration =
                            $configuration[$key]
                            ?? [];

                        $maximum =
                            isset(
                                $record[
                                    'max_length'
                                ]
                            )
                            && $record[
                                'max_length'
                            ] !== null
                                ? max(
                                    1,
                                    (int) $record[
                                        'max_length'
                                    ]
                                )
                                : 2000;

                        $value =
                            (string) (
                                $record[
                                    'content_value'
                                ]
                                ?? dc_content($key)
                            );

                        $fieldId =
                            'catalogs_'
                            . $action
                            . '_'
                            . $key;

                        $isTextarea =
                            !empty(
                                $fieldConfiguration[
                                    'textarea'
                                ]
                            );
                        ?>

                        <div class="col-12">
                            <label
                                class="form-label"
                                for="<?= e(
                                    $fieldId
                                ) ?>"
                            >
                                <?= e(
                                    (string) (
                                        $fieldConfiguration[
                                            'label'
                                        ]
                                        ?? $key
                                    )
                                ) ?>
                            </label>

                            <?php if ($isTextarea): ?>
                                <textarea
                                    class="form-control"
                                    id="<?= e(
                                        $fieldId
                                    ) ?>"
                                    name="content[<?= e(
                                        $key
                                    ) ?>]"
                                    rows="<?= e(
                                        (string) (
                                            $fieldConfiguration[
                                                'rows'
                                            ]
                                            ?? 4
                                        )
                                    ) ?>"
                                    maxlength="<?= e(
                                        (string) $maximum
                                    ) ?>"
                                    data-character-count
                                    required
                                ><?= e($value) ?></textarea>
                            <?php else: ?>
                                <input
                                    class="form-control"
                                    id="<?= e(
                                        $fieldId
                                    ) ?>"
                                    name="content[<?= e(
                                        $key
                                    ) ?>]"
                                    value="<?= e($value) ?>"
                                    maxlength="<?= e(
                                        (string) $maximum
                                    ) ?>"
                                    data-character-count
                                    required
                                >
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button
                    class="btn btn-primary mt-4"
                    type="submit"
                >
                    <?= e($buttonLabel) ?>
                </button>
            </form>
        </div>
    </section>
    <?php
}

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

    /*
     * Update the main Catalogs section heading and introduction.
     */
    if (
        $action
        === 'update_catalogs_intro'
    ) {
        $postedContent =
            $_POST['content']
            ?? [];

        if (!is_array($postedContent)) {
            $postedContent = [];
        }

        dc_admin_update_content_fields(
            $postedContent,
            [
                'catalogs_eyebrow',
                'catalogs_heading',
                'catalogs_intro',
            ],
            'partners'
        );
    }

    /*
     * Update the separate help panel beside the partner logos.
     */
    if (
        $action
        === 'update_catalog_help'
    ) {
        $postedContent =
            $_POST['content']
            ?? [];

        if (!is_array($postedContent)) {
            $postedContent = [];
        }

        dc_admin_update_content_fields(
            $postedContent,
            [
                'catalog_panel_eyebrow',
                'catalog_panel_heading',
                'catalog_panel_intro',
            ],
            'partners'
        );
    }

    /*
     * Create a partner.
     */
    if (
        $action
        === 'create_partner'
    ) {
        $partnerName = trim(
            (string) (
                $_POST['name']
                ?? ''
            )
        );

        $catalogUrl = trim(
            (string) (
                $_POST['catalog_url']
                ?? ''
            )
        );

        $validationError =
            dc_admin_validate_partner(
                $partnerName,
                $catalogUrl
            );

        if ($validationError !== null) {
            flash(
                'error',
                $validationError
            );

            dc_admin_redirect(
                'partners',
                'add-partner'
            );
        }

        $logoFile =
            $_FILES['partner_logo']
            ?? [];

        if (
            is_array($logoFile)
            && dc_admin_has_upload(
                $logoFile
            )
        ) {
            [
                $validLogo,
                $logoMessage,
            ] =
                dc_admin_validate_partner_logo(
                    $logoFile
                );

            if (!$validLogo) {
                flash(
                    'error',
                    $logoMessage
                );

                dc_admin_redirect(
                    'partners',
                    'add-partner'
                );
            }
        }

        $partnerId =
            dc_create_partner([
                'name' =>
                    $partnerName,

                'catalog_url' =>
                    $catalogUrl,

                'placeholder_label' =>
                    mb_substr(
                        $partnerName,
                        0,
                        100
                    ),

                'is_active' =>
                    isset(
                        $_POST[
                            'is_active'
                        ]
                    ),
            ]);

        if ($partnerId === null) {
            flash(
                'error',
                'The partner could not be created.'
            );

            dc_admin_redirect(
                'partners',
                'add-partner'
            );
        }

        if (
            is_array($logoFile)
            && dc_admin_has_upload(
                $logoFile
            )
        ) {
            [
                $uploaded,
                $message,
            ] =
                dc_replace_partner_logo(
                    $partnerId,
                    $logoFile,
                    $partnerName
                    . ' logo'
                );

            if (!$uploaded) {
                flash(
                    'warning',
                    'Partner created, but its logo was not uploaded: '
                    . $message
                );

                dc_admin_redirect(
                    'partners',
                    'partner-' . $partnerId
                );
            }
        }

        flash(
            'success',
            'Partner created.'
        );

        dc_admin_redirect(
            'partners',
            'partner-' . $partnerId
        );
    }

    /*
     * Update an existing partner.
     */
    if (
        $action
        === 'update_partner'
    ) {
        $partnerId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        $partner =
            dc_admin_partners_find(
                $partnerId
            );

        if ($partner === null) {
            flash(
                'error',
                'The selected partner could not be found.'
            );

            dc_admin_redirect(
                'partners'
            );
        }

        $partnerName = trim(
            (string) (
                $_POST['name']
                ?? ''
            )
        );

        $catalogUrl = trim(
            (string) (
                $_POST['catalog_url']
                ?? ''
            )
        );

        $validationError =
            dc_admin_validate_partner(
                $partnerName,
                $catalogUrl
            );

        if ($validationError !== null) {
            flash(
                'error',
                $validationError
            );

            dc_admin_redirect(
                'partners',
                'partner-' . $partnerId
            );
        }

        $logoFile =
            $_FILES['partner_logo']
            ?? [];

        if (
            is_array($logoFile)
            && dc_admin_has_upload(
                $logoFile
            )
        ) {
            [
                $validLogo,
                $logoMessage,
            ] =
                dc_admin_validate_partner_logo(
                    $logoFile
                );

            if (!$validLogo) {
                flash(
                    'error',
                    $logoMessage
                );

                dc_admin_redirect(
                    'partners',
                    'partner-' . $partnerId
                );
            }
        }

        $updated =
            dc_update_partner(
                $partnerId,
                [
                    'name' =>
                        $partnerName,

                    'catalog_url' =>
                        $catalogUrl,

                    'placeholder_label' =>
                        mb_substr(
                            $partnerName,
                            0,
                            100
                        ),

                    'logo_asset_id' =>
                        $partner[
                            'logo_asset_id'
                        ]
                        ?? null,

                    'is_active' =>
                        isset(
                            $_POST[
                                'is_active'
                            ]
                        ),
                ]
            );

        if (!$updated) {
            flash(
                'error',
                'The partner could not be updated.'
            );

            dc_admin_redirect(
                'partners',
                'partner-' . $partnerId
            );
        }

        if (
            is_array($logoFile)
            && dc_admin_has_upload(
                $logoFile
            )
        ) {
            [
                $uploaded,
                $message,
            ] =
                dc_replace_partner_logo(
                    $partnerId,
                    $logoFile,
                    $partnerName
                    . ' logo'
                );

            if (!$uploaded) {
                flash(
                    'warning',
                    'Partner details were saved, but the logo was not replaced: '
                    . $message
                );

                dc_admin_redirect(
                    'partners',
                    'partner-' . $partnerId
                );
            }
        } elseif (
            isset(
                $_POST[
                    'remove_image'
                ]
            )
        ) {
            dc_remove_partner_logo(
                $partnerId
            );
        }

        flash(
            'success',
            'Partner updated.'
        );

        dc_admin_redirect(
            'partners',
            'partner-' . $partnerId
        );
    }

    /*
     * Show or hide a partner.
     */
    if (
        $action
        === 'toggle_record'
        && (
            $_POST['record_type']
            ?? ''
        ) === 'partner'
    ) {
        $partnerId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        $isActive =
            (int) (
                $_POST['is_active']
                ?? 0
            ) === 1;

        dc_admin_finish(
            dc_set_partner_active(
                $partnerId,
                $isActive
            ),
            $isActive
                ? 'Partner shown on the website.'
                : 'Partner hidden from the website.',
            'The partner visibility could not be changed.',
            'partners',
            'partner-' . $partnerId
        );
    }

    /*
     * Move a partner up or down.
     */
    if (
        $action
        === 'move_record'
        && (
            $_POST['record_type']
            ?? ''
        ) === 'partner'
    ) {
        $partnerId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        $direction =
            (string) (
                $_POST['direction']
                ?? 'down'
            );

        dc_admin_finish(
            dc_admin_reorder_partner(
                $partnerId,
                $direction
            ),
            'Partner order updated.',
            'The partner order could not be updated.',
            'partners',
            'partner-' . $partnerId
        );
    }

    /*
     * Permanently delete a partner.
     */
    if (
        $action
        === 'delete_record'
        && (
            $_POST['record_type']
            ?? ''
        ) === 'partner'
    ) {
        $partnerId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        dc_admin_finish(
            dc_admin_delete_partner_record(
                $partnerId
            ),
            'Partner permanently deleted.',
            'The partner could not be deleted.',
            'partners'
        );
    }

    return;
}

/*
 * Render mode begins here.
 */

$catalogContentRecords = [];

foreach (
    dc_content_records('catalogs')
    as $record
) {
    $key =
        (string) (
            $record['content_key']
            ?? ''
        );

    if ($key !== '') {
        $catalogContentRecords[$key] =
            $record;
    }
}

$catalogFieldConfiguration = [
    'catalogs_eyebrow' => [
        'label' =>
            'Small label above the Catalogs heading',

        'textarea' =>
            false,
    ],

    'catalogs_heading' => [
        'label' =>
            'Catalogs heading',

        'textarea' =>
            false,
    ],

    'catalogs_intro' => [
        'label' =>
            'Catalogs introduction',

        'textarea' =>
            true,

        'rows' =>
            4,
    ],

    'catalog_panel_eyebrow' => [
        'label' =>
            'Small label inside the catalog help panel',

        'textarea' =>
            false,
    ],

    'catalog_panel_heading' => [
        'label' =>
            'Catalog help-panel heading',

        'textarea' =>
            false,
    ],

    'catalog_panel_intro' => [
        'label' =>
            'Catalog help-panel paragraph',

        'textarea' =>
            true,

        'rows' =>
            5,
    ],
];

dc_admin_render_catalog_content_form(
    'update_catalogs_intro',
    'Catalogs Introduction',
    'Edit the heading and introduction above the partner logos.',
    'Publish Catalogs Introduction',
    [
        'catalogs_eyebrow',
        'catalogs_heading',
        'catalogs_intro',
    ],
    $catalogContentRecords,
    $catalogFieldConfiguration
);

dc_admin_render_catalog_content_form(
    'update_catalog_help',
    'Catalog Help Panel',
    'Edit the separate help panel beside the partner logos.',
    'Publish Catalog Help Panel',
    [
        'catalog_panel_eyebrow',
        'catalog_panel_heading',
        'catalog_panel_intro',
    ],
    $catalogContentRecords,
    $catalogFieldConfiguration
);

$partners =
    dc_partners(false, true);

?>
<div
    class="admin-records-heading d-flex align-items-center justify-content-between gap-3 mb-3"
>
    <div>
        <p
            class="text-uppercase text-primary fw-semibold small mb-1"
        >
            Partner Cards
        </p>

        <h2 class="h4 mb-0">
            Catalog Partners
        </h2>
    </div>

    <span class="badge text-bg-secondary">
        <?= count($partners) ?> total
    </span>
</div>

<section
    class="card border-0 shadow-sm mb-4"
    id="add-partner"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            Add a Partner
        </h2>

        <p class="small text-body-secondary mb-0">
            Add a supplier or brand logo and its approved catalog link.
        </p>
    </div>

    <div class="card-body p-4">
        <form
            method="post"
            enctype="multipart/form-data"
        >
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="create_partner"
            >

            <div class="row g-4">
                <div class="col-lg-6">
                    <label
                        class="form-label"
                        for="new_partner_name"
                    >
                        Partner or supplier name
                    </label>

                    <input
                        class="form-control"
                        id="new_partner_name"
                        name="name"
                        maxlength="100"
                        data-character-count
                        required
                    >

                    <div class="form-text">
                        This name is also displayed when no logo has been
                        uploaded.
                    </div>
                </div>

                <div class="col-lg-6">
                    <label
                        class="form-label"
                        for="new_partner_url"
                    >
                        Catalog page link
                    </label>

                    <input
                        class="form-control"
                        id="new_partner_url"
                        name="catalog_url"
                        type="url"
                        maxlength="2048"
                        data-character-count
                        placeholder="https://"
                    >

                    <div class="form-text">
                        Optional. Use the complete public catalog URL.
                    </div>
                </div>

                <div class="col-lg-6">
                    <label
                        class="form-label"
                        for="new_partner_logo"
                    >
                        Partner logo
                    </label>

                    <input
                        class="form-control"
                        id="new_partner_logo"
                        name="partner_logo"
                        type="file"
                        accept="<?= e(
                            (string) dc_admin_partner_logo_spec()[
                                'accept'
                            ]
                        ) ?>"
                        <?= dc_admin_partner_logo_attributes() ?>
                    >

                    <?php
                    dc_admin_render_partner_logo_requirements();
                    ?>
                </div>

                <div
                    class="col-lg-6 d-flex align-items-end"
                >
                    <div class="form-check mb-2">
                        <input
                            class="form-check-input"
                            id="new_partner_active"
                            name="is_active"
                            type="checkbox"
                            value="1"
                            checked
                        >

                        <label
                            class="form-check-label"
                            for="new_partner_active"
                        >
                            Show this partner on the website immediately
                        </label>
                    </div>
                </div>
            </div>

            <button
                class="btn btn-primary mt-4"
                type="submit"
            >
                Create Partner
            </button>
        </form>
    </div>
</section>

<?php if ($partners === []): ?>
    <div class="alert alert-light border">
        No catalog partners have been added.
    </div>
<?php else: ?>
    <div class="vstack gap-4">
        <?php foreach (
            $partners
            as $index => $partner
        ): ?>
            <?php
            $partnerId =
                (int) $partner['id'];

            $isActive =
                (int) (
                    $partner[
                        'is_active'
                    ]
                    ?? 0
                ) === 1;

            $partnerName =
                (string) (
                    $partner['name']
                    ?? 'Unnamed Partner'
                );
            ?>

            <article
                class="card border-0 shadow-sm"
                id="partner-<?= $partnerId ?>"
            >
                <div
                    class="card-header bg-white d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 py-3"
                >
                    <div>
                        <h3 class="h5 mb-1">
                            <?= e($partnerName) ?>
                        </h3>

                        <span
                            class="badge <?= $isActive
                                ? 'text-bg-success'
                                : 'text-bg-secondary' ?>"
                        >
                            <?= $isActive
                                ? 'Published'
                                : 'Hidden' ?>
                        </span>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <form method="post">
                            <?= csrf_field() ?>

                            <input
                                type="hidden"
                                name="action"
                                value="move_record"
                            >

                            <input
                                type="hidden"
                                name="record_type"
                                value="partner"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $partnerId ?>"
                            >

                            <input
                                type="hidden"
                                name="direction"
                                value="up"
                            >

                            <button
                                class="btn btn-outline-secondary btn-sm"
                                type="submit"
                                <?= $index === 0
                                    ? 'disabled'
                                    : '' ?>
                            >
                                Move Up
                            </button>
                        </form>

                        <form method="post">
                            <?= csrf_field() ?>

                            <input
                                type="hidden"
                                name="action"
                                value="move_record"
                            >

                            <input
                                type="hidden"
                                name="record_type"
                                value="partner"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $partnerId ?>"
                            >

                            <input
                                type="hidden"
                                name="direction"
                                value="down"
                            >

                            <button
                                class="btn btn-outline-secondary btn-sm"
                                type="submit"
                                <?= $index
                                    === count($partners) - 1
                                        ? 'disabled'
                                        : '' ?>
                            >
                                Move Down
                            </button>
                        </form>

                        <form method="post">
                            <?= csrf_field() ?>

                            <input
                                type="hidden"
                                name="action"
                                value="toggle_record"
                            >

                            <input
                                type="hidden"
                                name="record_type"
                                value="partner"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $partnerId ?>"
                            >

                            <input
                                type="hidden"
                                name="is_active"
                                value="<?= $isActive
                                    ? '0'
                                    : '1' ?>"
                            >

                            <button
                                class="btn btn-outline-secondary btn-sm"
                                type="submit"
                            >
                                <?= $isActive
                                    ? 'Hide'
                                    : 'Publish' ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form
                        method="post"
                        enctype="multipart/form-data"
                    >
                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="action"
                            value="update_partner"
                        >

                        <input
                            type="hidden"
                            name="record_id"
                            value="<?= $partnerId ?>"
                        >

                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label
                                        class="form-label"
                                        for="partner_name_<?= $partnerId ?>"
                                    >
                                        Partner or supplier name
                                    </label>

                                    <input
                                        class="form-control"
                                        id="partner_name_<?= $partnerId ?>"
                                        name="name"
                                        maxlength="100"
                                        data-character-count
                                        value="<?= e(
                                            (string) (
                                                $partner[
                                                    'name'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label
                                        class="form-label"
                                        for="partner_url_<?= $partnerId ?>"
                                    >
                                        Catalog page link
                                    </label>

                                    <input
                                        class="form-control"
                                        id="partner_url_<?= $partnerId ?>"
                                        name="catalog_url"
                                        type="url"
                                        maxlength="2048"
                                        data-character-count
                                        value="<?= e(
                                            (string) (
                                                $partner[
                                                    'catalog_url'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>"
                                        placeholder="https://"
                                    >
                                </div>

                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        id="partner_active_<?= $partnerId ?>"
                                        name="is_active"
                                        type="checkbox"
                                        value="1"
                                        <?= $isActive
                                            ? 'checked'
                                            : '' ?>
                                    >

                                    <label
                                        class="form-check-label"
                                        for="partner_active_<?= $partnerId ?>"
                                    >
                                        Show this partner on the website
                                    </label>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <p class="form-label">
                                    Current logo
                                </p>

                                <?php if (
                                    !empty(
                                        $partner[
                                            'image_exists'
                                        ]
                                    )
                                ): ?>
                                    <div
                                        class="border rounded bg-white p-3 mb-3"
                                    >
                                        <img
                                            class="img-fluid d-block mx-auto"
                                            style="max-height: 10rem;"
                                            src="<?= e(
                                                (string) $partner[
                                                    'image'
                                                ]
                                            ) ?>"
                                            alt="<?= e(
                                                (string) (
                                                    $partner[
                                                        'alt_text'
                                                    ]
                                                    ?? $partnerName
                                                    . ' logo'
                                                )
                                            ) ?>"
                                        >
                                    </div>
                                <?php else: ?>
                                    <div
                                        class="border rounded bg-body-tertiary p-4 text-center text-body-secondary mb-3"
                                    >
                                        <?= e(
                                            (string) (
                                                $partner[
                                                    'placeholder_label'
                                                ]
                                                ?? $partnerName
                                            )
                                        ) ?>
                                    </div>
                                <?php endif; ?>

                                <label
                                    class="form-label"
                                    for="partner_logo_<?= $partnerId ?>"
                                >
                                    Replace logo
                                </label>

                                <input
                                    class="form-control"
                                    id="partner_logo_<?= $partnerId ?>"
                                    name="partner_logo"
                                    type="file"
                                    accept="<?= e(
                                        (string) dc_admin_partner_logo_spec()[
                                            'accept'
                                        ]
                                    ) ?>"
                                    <?= dc_admin_partner_logo_attributes() ?>
                                >

                                <?php
                                dc_admin_render_partner_logo_requirements();
                                ?>

                                <?php if (
                                    !empty(
                                        $partner[
                                            'logo_asset_id'
                                        ]
                                    )
                                ): ?>
                                    <div class="form-check mt-3">
                                        <input
                                            class="form-check-input"
                                            id="partner_remove_logo_<?= $partnerId ?>"
                                            name="remove_image"
                                            type="checkbox"
                                            value="1"
                                        >

                                        <label
                                            class="form-check-label"
                                            for="partner_remove_logo_<?= $partnerId ?>"
                                        >
                                            Remove current logo
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button
                            class="btn btn-primary mt-4"
                            type="submit"
                        >
                            Save Partner
                        </button>
                    </form>

                    <hr class="my-4">

                    <form
                        method="post"
                        onsubmit="return confirm('Permanently delete this partner?');"
                    >
                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="action"
                            value="delete_record"
                        >

                        <input
                            type="hidden"
                            name="record_type"
                            value="partner"
                        >

                        <input
                            type="hidden"
                            name="record_id"
                            value="<?= $partnerId ?>"
                        >

                        <button
                            class="btn btn-outline-danger btn-sm"
                            type="submit"
                        >
                            Delete Partner
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>