<?php

declare(strict_types=1);

/*
 * Shared helpers used by partial-based administration sections.
 *
 * Section-specific form handling and markup remain in each section partial.
 * These helpers contain only behavior reused by multiple sections.
 */

function dc_admin_section_url(
    string $section,
    string $anchor = ''
): string {
    $url =
        '/admin/?section='
        . rawurlencode($section);

    if ($anchor !== '') {
        $url .= '#'
            . rawurlencode($anchor);
    }

    return $url;
}

function dc_admin_redirect(
    string $section,
    string $anchor = ''
): never {
    redirect(
        dc_admin_section_url(
            $section,
            $anchor
        )
    );
}

function dc_admin_finish(
    bool $success,
    string $successMessage,
    string $errorMessage,
    string $section,
    string $anchor = ''
): never {
    flash(
        $success
            ? 'success'
            : 'error',

        $success
            ? $successMessage
            : $errorMessage
    );

    dc_admin_redirect(
        $section,
        $anchor
    );
}

/**
 * @param array<string, mixed> $file
 */
function dc_admin_has_upload(
    array $file
): bool {
    return isset($file['error'])
        && (int) $file['error']
            !== UPLOAD_ERR_NO_FILE;
}

/**
 * @return array<string, int>
 */
function dc_admin_character_limits(): array
{
    return [
        'business_name' => 120,
        'tagline' => 140,
        'phone_number' => 30,
        'email_address' => 254,
        'street_address' => 160,
        'city' => 80,
        'state_code' => 2,
        'postal_code' => 12,
        'country_code' => 2,
        'location_note' => 120,
        'weekday_hours' => 60,
        'weekend_hours' => 60,

        'header_call_label' => 30,
        'header_quote_button_label' => 40,

        'page_title' => 70,
        'meta_description' => 160,

        'alt_text' => 300,
    ];
}

function dc_admin_limit(
    string $key
): int {
    $limits =
        dc_admin_character_limits();

    return $limits[$key]
        ?? 255;
}

/**
 * @param array<string, mixed> $values
 * @param array<string, array{
 *     label: string,
 *     limit: string,
 *     required?: bool
 * }> $rules
 */
function dc_admin_validate_text_values(
    array $values,
    array $rules
): ?string {
    foreach ($rules as $field => $rule) {
        $value = trim(
            (string) (
                $values[$field]
                ?? ''
            )
        );

        $label =
            (string) (
                $rule['label']
                ?? $field
            );

        $limitKey =
            (string) (
                $rule['limit']
                ?? $field
            );

        $maximum =
            dc_admin_limit($limitKey);

        $required =
            !empty($rule['required']);

        if (
            $required
            && $value === ''
        ) {
            return $label
                . ' is required.';
        }

        if (
            mb_strlen($value)
            > $maximum
        ) {
            return sprintf(
                '%s must be %d characters or fewer.',
                $label,
                $maximum
            );
        }
    }

    return null;
}

function dc_admin_ini_size_to_bytes(
    string $value
): int {
    $value = trim($value);

    if ($value === '') {
        return 0;
    }

    $unit =
        strtolower(
            substr($value, -1)
        );

    $number =
        (float) $value;

    return match ($unit) {
        'g' =>
            (int) round(
                $number
                * 1024
                * 1024
                * 1024
            ),

        'm' =>
            (int) round(
                $number
                * 1024
                * 1024
            ),

        'k' =>
            (int) round(
                $number
                * 1024
            ),

        default =>
            (int) round($number),
    };
}

/**
 * @return array<string, mixed>
 */
function dc_admin_media_spec(
    string $purpose
): array {
    $specs = [
        'brand_mark' => [
            'label' =>
                'Brand mark',

            'accept' =>
                'image/png,image/webp',

            'mime_types' => [
                'image/png',
                'image/webp',
            ],

            'types' =>
                'PNG or WebP',

            'max_bytes' =>
                2 * 1024 * 1024,

            'recommended' =>
                'Transparent PNG or WebP at 1200 × 1200 px, ideally 500 KB or smaller.',

            'min_width' => 256,
            'min_height' => 256,
            'max_width' => 4096,
            'max_height' => 4096,
            'min_ratio' => 0.75,
            'max_ratio' => 1.34,
        ],

        'favicon' => [
            'label' =>
                'Website icon',

            'accept' =>
                'image/png,image/x-icon,image/vnd.microsoft.icon,.ico',

            'mime_types' => [
                'image/png',
                'image/x-icon',
                'image/vnd.microsoft.icon',
                'application/octet-stream',
            ],

            'types' =>
                'PNG or ICO',

            'max_bytes' =>
                1 * 1024 * 1024,

            'recommended' =>
                'Square PNG at 512 × 512 px or a multi-size ICO file, ideally 250 KB or smaller.',

            'min_width' => 32,
            'min_height' => 32,
            'max_width' => 512,
            'max_height' => 512,
            'min_ratio' => 0.90,
            'max_ratio' => 1.10,

            'skip_dimensions_for_mimes' => [
                'image/x-icon',
                'image/vnd.microsoft.icon',
                'application/octet-stream',
            ],
        ],

        'hero_image' => [
            'label' =>
                'Hero image',

            'accept' =>
                'image/jpeg,image/png,image/webp',

            'mime_types' => [
                'image/jpeg',
                'image/png',
                'image/webp',
            ],

            'types' =>
                'JPG, PNG, or WebP',

            'max_bytes' =>
                10 * 1024 * 1024,

            'recommended' =>
                'WebP or JPG at 1920 × 1080 px (16:9), ideally 2 MB or smaller.',

            'min_width' => 1280,
            'min_height' => 720,
            'max_width' => 7680,
            'max_height' => 4320,
            'min_ratio' => 1.50,
            'max_ratio' => 2.10,
        ],

        'hero_video' => [
            'label' =>
                'Hero video',

            'accept' =>
                'video/mp4,video/webm',

            'mime_types' => [
                'video/mp4',
                'video/webm',
            ],

            'types' =>
                'MP4 or WebM',

            'max_bytes' =>
                50 * 1024 * 1024,

            'recommended' =>
                'MP4 at 1920 × 1080 px (16:9), 3–30 seconds, muted and loop-ready, ideally 15 MB or smaller.',

            'min_width' => 1280,
            'min_height' => 720,
            'max_width' => 3840,
            'max_height' => 2160,
            'min_ratio' => 1.50,
            'max_ratio' => 2.10,
            'min_duration' => 3,
            'max_duration' => 30,
            'video' => true,
        ],
    ];

    $spec =
        $specs[$purpose]
        ?? [];

    if ($spec === []) {
        return [];
    }

    $configuredMaximum =
        (int) $spec['max_bytes'];

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

    if ($serverLimits !== []) {
        $effectiveMaximum =
            min(
                $configuredMaximum,
                ...$serverLimits
            );

        $spec['max_bytes'] =
            $effectiveMaximum;

        $spec['server_limited'] =
            $effectiveMaximum
            < $configuredMaximum;

        $spec['configured_max_bytes'] =
            $configuredMaximum;
    }

    return $spec;
}

function dc_admin_format_bytes(
    int $bytes
): string {
    if (
        $bytes
        >= 1024 * 1024
    ) {
        return rtrim(
            rtrim(
                number_format(
                    $bytes
                    / 1024
                    / 1024,
                    1
                ),
                '0'
            ),
            '.'
        ) . ' MB';
    }

    return (string) max(
        1,
        (int) ceil(
            $bytes / 1024
        )
    ) . ' KB';
}

function dc_admin_media_accept(
    string $purpose
): string {
    return (string) (
        dc_admin_media_spec(
            $purpose
        )['accept']
        ?? ''
    );
}

function dc_admin_media_data_attributes(
    string $purpose
): string {
    $spec =
        dc_admin_media_spec(
            $purpose
        );

    if ($spec === []) {
        return '';
    }

    $attributes = [
        'data-media-purpose' =>
            $purpose,

        'data-max-bytes' =>
            (string) (
                $spec['max_bytes']
                ?? 0
            ),

        'data-min-width' =>
            (string) (
                $spec['min_width']
                ?? 0
            ),

        'data-min-height' =>
            (string) (
                $spec['min_height']
                ?? 0
            ),

        'data-max-width' =>
            (string) (
                $spec['max_width']
                ?? 0
            ),

        'data-max-height' =>
            (string) (
                $spec['max_height']
                ?? 0
            ),

        'data-min-ratio' =>
            (string) (
                $spec['min_ratio']
                ?? 0
            ),

        'data-max-ratio' =>
            (string) (
                $spec['max_ratio']
                ?? 0
            ),
    ];

    if (!empty($spec['video'])) {
        $attributes['data-media-video'] =
            '1';

        $attributes['data-min-duration'] =
            (string) (
                $spec['min_duration']
                ?? 0
            );

        $attributes['data-max-duration'] =
            (string) (
                $spec['max_duration']
                ?? 0
            );
    }

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

function dc_admin_render_media_requirements(
    string $purpose
): void {
    $spec =
        dc_admin_media_spec(
            $purpose
        );

    if ($spec === []) {
        return;
    }

    if (!empty($spec['video'])) {
        $dimensionText =
            sprintf(
                '%d–%d px wide, %d–%d px tall, and %d–%d seconds.',
                (int) $spec['min_width'],
                (int) $spec['max_width'],
                (int) $spec['min_height'],
                (int) $spec['max_height'],
                (int) $spec['min_duration'],
                (int) $spec['max_duration']
            );
    } elseif ($purpose === 'favicon') {
        $dimensionText =
            sprintf(
                'PNG files must be %d–%d px in each direction and square. ICO dimensions are handled by the icon file.',
                (int) $spec['min_width'],
                (int) $spec['max_width']
            );
    } else {
        $dimensionText =
            sprintf(
                '%d–%d px wide and %d–%d px tall.',
                (int) $spec['min_width'],
                (int) $spec['max_width'],
                (int) $spec['min_height'],
                (int) $spec['max_height']
            );
    }
    ?>
    <div
        class="admin-media-requirements alert alert-light border small mt-2 mb-0"
    >
        <strong>Recommended:</strong>
        <?= e(
            (string) $spec['recommended']
        ) ?>

        <br>

        <strong>Accepted and enforced:</strong>
        <?= e(
            (string) $spec['types']
        ) ?>,

        maximum
        <?= e(
            dc_admin_format_bytes(
                (int) $spec['max_bytes']
            )
        ) ?>.

        <?= e($dimensionText) ?>

        <?php if (
            !empty(
                $spec['server_limited']
            )
        ): ?>
            <br>

            <strong>Server limit:</strong>

            PHP is currently configured to accept no more than

            <?= e(
                dc_admin_format_bytes(
                    (int) $spec['max_bytes']
                )
            ) ?>

            per upload.
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
function dc_admin_validate_media_upload(
    array $file,
    string $purpose
): array {
    $spec =
        dc_admin_media_spec(
            $purpose
        );

    if ($spec === []) {
        return [
            false,
            'The selected media purpose is not supported.',
        ];
    }

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

    $maximumBytes =
        (int) (
            $spec['max_bytes']
            ?? 0
        );

    if (
        $temporaryPath === ''
        || !is_file($temporaryPath)
    ) {
        return [
            false,
            'The uploaded file could not be read.',
        ];
    }

    if (
        $size < 1
        || (
            $maximumBytes > 0
            && $size > $maximumBytes
        )
    ) {
        return [
            false,
            'The file must be no larger than '
            . dc_admin_format_bytes(
                $maximumBytes
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
            'Unable to inspect administrator media upload: '
            . $exception->getMessage()
        );

        return [
            false,
            'The server could not inspect the selected file.',
        ];
    }

    if (
        !is_string($mime)
        || !in_array(
            $mime,
            (array) (
                $spec['mime_types']
                ?? []
            ),
            true
        )
    ) {
        return [
            false,
            'Use one of these file types: '
            . (string) $spec['types']
            . '.',
        ];
    }

    if (!empty($spec['video'])) {
        return [
            true,
            '',
        ];
    }

    if (
        in_array(
            $mime,
            (array) (
                $spec[
                    'skip_dimensions_for_mimes'
                ]
                ?? []
            ),
            true
        )
    ) {
        return [
            true,
            '',
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
            < (int) $spec['min_width']
        || $height
            < (int) $spec['min_height']
        || $width
            > (int) $spec['max_width']
        || $height
            > (int) $spec['max_height']
    ) {
        return [
            false,
            sprintf(
                'The image dimensions must be %d–%d px wide and %d–%d px tall.',
                (int) $spec['min_width'],
                (int) $spec['max_width'],
                (int) $spec['min_height'],
                (int) $spec['max_height']
            ),
        ];
    }

    $ratio =
        $height > 0
            ? $width / $height
            : 0.0;

    if (
        $ratio
            < (float) $spec['min_ratio']
        || $ratio
            > (float) $spec['max_ratio']
    ) {
        return [
            false,
            'The image shape is outside the allowed range. '
            . (string) $spec['recommended'],
        ];
    }

    return [
        true,
        '',
    ];
}

function dc_admin_media_target_section(
    string $slotKey
): string {
    return in_array(
        $slotKey,
        [
            'hero_image',
            'hero_video',
        ],
        true
    )
        ? 'hero'
        : 'business';
}

/**
 * Process a global media action belonging to one administration section.
 *
 * Returns false only when the action was not a global media action.
 *
 * @param array<int, string> $allowedSlots
 */
function dc_admin_process_site_media_action(
    string $action,
    string $targetSection,
    array $allowedSlots
): bool {
    if (
        !in_array(
            $action,
            [
                'replace_site_media',
                'remove_site_media',
            ],
            true
        )
    ) {
        return false;
    }

    $slotKey = trim(
        (string) (
            $_POST['slot_key']
            ?? ''
        )
    );

    if (
        !in_array(
            $slotKey,
            $allowedSlots,
            true
        )
    ) {
        flash(
            'error',
            'That media field does not belong to this administration section.'
        );

        dc_admin_redirect(
            $targetSection
        );
    }

    if (
        $action
        === 'remove_site_media'
    ) {
        if (
            !in_array(
                $slotKey,
                [
                    'hero_image',
                    'hero_video',
                ],
                true
            )
        ) {
            flash(
                'error',
                'The brand mark and website icon must be replaced rather than removed.'
            );

            dc_admin_redirect(
                $targetSection,
                'media-' . $slotKey
            );
        }

        dc_admin_finish(
            dc_remove_site_media(
                $slotKey
            ),
            'Website media removed.',
            'The website media could not be removed.',
            $targetSection,
            'media-' . $slotKey
        );
    }

    $file =
        $_FILES['site_media_file']
        ?? [];

    if (
        !is_array($file)
        || !dc_admin_has_upload($file)
    ) {
        flash(
            'error',
            'Select a replacement file.'
        );

        dc_admin_redirect(
            $targetSection,
            'media-' . $slotKey
        );
    }

    [
        $validMedia,
        $mediaMessage,
    ] =
        dc_admin_validate_media_upload(
            $file,
            $slotKey
        );

    if (!$validMedia) {
        flash(
            'error',
            $mediaMessage
        );

        dc_admin_redirect(
            $targetSection,
            'media-' . $slotKey
        );
    }

    $altText = trim(
        (string) (
            $_POST['alt_text']
            ?? ''
        )
    );

    $altValidationError =
        dc_admin_validate_text_values(
            [
                'alt_text' =>
                    $altText,
            ],
            [
                'alt_text' => [
                    'label' =>
                        'Image description',

                    'limit' =>
                        'alt_text',
                ],
            ]
        );

    if ($altValidationError !== null) {
        flash(
            'error',
            $altValidationError
        );

        dc_admin_redirect(
            $targetSection,
            'media-' . $slotKey
        );
    }

    [
        $uploaded,
        $message,
    ] =
        dc_replace_site_media_upload(
            $slotKey,
            $file,
            $altText
        );

    dc_admin_finish(
        $uploaded,
        $message,
        $message,
        $targetSection,
        'media-' . $slotKey
    );
}

/**
 * @param array<int, string> $slotKeys
 */
function dc_admin_render_site_media_cards(
    array $slotKeys,
    string $targetSection,
    string $heading,
    string $description
): void {
    $mediaSlots =
        dc_site_media_slots(true);

    $mediaHelp = [
        'brand_mark' =>
            'Shown beside the business name in the site header and footer.',

        'favicon' =>
            'Shown in browser tabs, bookmarks, and saved shortcuts.',

        'hero_image' =>
            'Shown behind the hero text and used as the video fallback image.',

        'hero_video' =>
            'Loops behind the hero text. The hero image remains the fallback.',
    ];

    $selectedSlots = [];

    foreach ($slotKeys as $slotKey) {
        if (
            isset(
                $mediaSlots[$slotKey]
            )
        ) {
            $selectedSlots[$slotKey] =
                $mediaSlots[$slotKey];
        }
    }

    if ($selectedSlots === []) {
        return;
    }
    ?>
    <section
        class="card border-0 shadow-sm mb-4 admin-media-group"
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
            <div class="row g-4">
                <?php foreach (
                    $selectedSlots
                    as $slotKey => $slot
                ): ?>
                    <div class="col-xl-6">
                        <article
                            class="admin-media-item border rounded-3 h-100 p-3"
                            id="media-<?= e($slotKey) ?>"
                        >
                            <h3 class="h6 mb-1">
                                <?= e(
                                    (string) (
                                        $slot['admin_label']
                                        ?? $slotKey
                                    )
                                ) ?>
                            </h3>

                            <p class="small text-body-secondary">
                                <?= e(
                                    $mediaHelp[$slotKey]
                                    ?? ''
                                ) ?>
                            </p>

                            <?php if (
                                !empty(
                                    $slot['file_exists']
                                )
                                && (
                                    $slot['media_kind']
                                    ?? ''
                                ) === 'video'
                            ): ?>
                                <video
                                    class="w-100 rounded border mb-3"
                                    controls
                                    muted
                                    preload="metadata"
                                >
                                    <source
                                        src="<?= e(
                                            (string) $slot[
                                                'file_path'
                                            ]
                                        ) ?>"
                                        type="<?= e(
                                            (string) (
                                                $slot[
                                                    'mime_type'
                                                ]
                                                ?? 'video/mp4'
                                            )
                                        ) ?>"
                                    >
                                </video>
                            <?php elseif (
                                !empty(
                                    $slot['file_exists']
                                )
                            ): ?>
                                <div
                                    class="admin-media-preview border rounded bg-white p-3 mb-3 text-center"
                                >
                                    <img
                                        class="img-fluid"
                                        src="<?= e(
                                            (string) $slot[
                                                'file_path'
                                            ]
                                        ) ?>"
                                        alt="<?= e(
                                            (string) (
                                                $slot[
                                                    'alt_text'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>"
                                    >
                                </div>
                            <?php else: ?>
                                <div
                                    class="border rounded bg-body-tertiary p-4 text-center text-body-secondary mb-3"
                                >
                                    No file is currently assigned.
                                </div>
                            <?php endif; ?>

                            <form
                                method="post"
                                enctype="multipart/form-data"
                            >
                                <?= csrf_field() ?>

                                <input
                                    type="hidden"
                                    name="action"
                                    value="replace_site_media"
                                >

                                <input
                                    type="hidden"
                                    name="slot_key"
                                    value="<?= e($slotKey) ?>"
                                >

                                <div class="mb-3">
                                    <label
                                        class="form-label"
                                        for="site_media_file_<?= e($slotKey) ?>"
                                    >
                                        Choose a replacement file
                                    </label>

                                    <input
                                        class="form-control"
                                        id="site_media_file_<?= e($slotKey) ?>"
                                        name="site_media_file"
                                        type="file"
                                        accept="<?= e(
                                            dc_admin_media_accept(
                                                $slotKey
                                            )
                                        ) ?>"
                                        <?= dc_admin_media_data_attributes(
                                            $slotKey
                                        ) ?>
                                        required
                                    >

                                    <?php
                                    dc_admin_render_media_requirements(
                                        $slotKey
                                    );
                                    ?>
                                </div>

                                <?php if (
                                    $slotKey !== 'hero_video'
                                    && $slotKey !== 'favicon'
                                ): ?>
                                    <div class="mb-3">
                                        <label
                                            class="form-label"
                                            for="site_media_alt_<?= e($slotKey) ?>"
                                        >
                                            Image description for accessibility
                                        </label>

                                        <input
                                            class="form-control"
                                            id="site_media_alt_<?= e($slotKey) ?>"
                                            name="alt_text"
                                            maxlength="<?= dc_admin_limit(
                                                'alt_text'
                                            ) ?>"
                                            data-character-count
                                            value="<?= e(
                                                (string) (
                                                    $slot[
                                                        'alt_text'
                                                    ]
                                                    ?? ''
                                                )
                                            ) ?>"
                                        >
                                    </div>
                                <?php endif; ?>

                                <button
                                    class="btn btn-primary"
                                    type="submit"
                                >
                                    Upload Replacement
                                </button>
                            </form>

                            <?php if (
                                in_array(
                                    $slotKey,
                                    [
                                        'hero_image',
                                        'hero_video',
                                    ],
                                    true
                                )
                                && !empty(
                                    $slot[
                                        'media_asset_id'
                                    ]
                                )
                            ): ?>
                                <hr class="my-4">

                                <form
                                    method="post"
                                    onsubmit="return confirm('Remove this hero media file?');"
                                >
                                    <?= csrf_field() ?>

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="remove_site_media"
                                    >

                                    <input
                                        type="hidden"
                                        name="slot_key"
                                        value="<?= e($slotKey) ?>"
                                    >

                                    <button
                                        class="btn btn-outline-danger btn-sm"
                                        type="submit"
                                    >
                                        Remove Current File
                                    </button>
                                </form>
                            <?php endif; ?>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

/**
 * @param array<string, mixed> $postedContent
 * @param array<int, string> $allowedKeys
 */
function dc_admin_update_content_fields(
    array $postedContent,
    array $allowedKeys,
    string $targetSection
): never {
    $metadata = [];

    foreach (
        dc_content_records()
        as $record
    ) {
        $key =
            (string) (
                $record['content_key']
                ?? ''
            );

        if (
            $key === ''
            || !in_array(
                $key,
                $allowedKeys,
                true
            )
        ) {
            continue;
        }

        $metadata[$key] =
            $record['max_length']
                !== null
                    ? max(
                        1,
                        (int) $record[
                            'max_length'
                        ]
                    )
                    : 2000;
    }

    $updates = [];

    foreach ($allowedKeys as $key) {
        $value = trim(
            (string) (
                $postedContent[$key]
                ?? ''
            )
        );

        if (!isset($metadata[$key])) {
            continue;
        }

        if (
            mb_strlen($value)
            > $metadata[$key]
        ) {
            flash(
                'error',
                sprintf(
                    'The selected field must be %d characters or fewer.',
                    $metadata[$key]
                )
            );

            dc_admin_redirect(
                $targetSection
            );
        }

        $updates[$key] =
            $value;
    }

    dc_admin_finish(
        $updates !== []
            && dc_update_content_values(
                $updates
            ),
        'Section text published.',
        'The section text could not be published. Check the field lengths.',
        $targetSection
    );
}