<?php

declare(strict_types=1);

/*
 * About & Staff administration partial.
 *
 * Handles:
 * - About-section heading and introduction
 * - Permanent business overview
 * - Business overview image
 * - Creating staff cards
 * - Editing staff cards
 * - Staff photos
 * - Visibility
 * - Display order
 * - Deletion
 */

function dc_admin_profiles_find(
    int $profileId
): ?array {
    foreach (
        dc_profiles(false, true)
        as $profile
    ) {
        if (
            (int) (
                $profile['id']
                ?? 0
            ) === $profileId
        ) {
            return $profile;
        }
    }

    return null;
}

function dc_admin_profile_is_company(
    array $profile
): bool {
    return (
        $profile['profile_type']
        ?? $profile['type']
        ?? 'staff'
    ) === 'organization';
}

/**
 * @return array<int, int>
 */
function dc_admin_staff_profile_ids(): array
{
    $ids = [];

    foreach (
        dc_profiles(false, true)
        as $profile
    ) {
        if (
            dc_admin_profile_is_company(
                $profile
            )
        ) {
            continue;
        }

        $ids[] =
            (int) (
                $profile['id']
                ?? 0
            );
    }

    return $ids;
}

function dc_admin_reorder_staff_profile(
    int $profileId,
    string $direction
): bool {
    $ids =
        dc_admin_staff_profile_ids();

    $index =
        array_search(
            $profileId,
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

    return dc_reorder_profiles(
        $ids
    );
}

/**
 * @return array<string, mixed>
 */
function dc_admin_profile_image_spec(): array
{
    $configuredMaximum =
        8 * 1024 * 1024;

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
            'JPG, PNG, or WebP',

        'max_bytes' =>
            $effectiveMaximum,

        'configured_max_bytes' =>
            $configuredMaximum,

        'server_limited' =>
            $effectiveMaximum
            < $configuredMaximum,

        'min_width' => 600,
        'min_height' => 600,
        'max_width' => 4000,
        'max_height' => 4000,
        'min_ratio' => 0.65,
        'max_ratio' => 1.50,

        'recommended' =>
            'WebP or JPG at 1200 × 1200 px, ideally 1 MB or smaller.',
    ];
}

function dc_admin_profile_image_attributes(): string
{
    $spec =
        dc_admin_profile_image_spec();

    $attributes = [
        'data-media-purpose' =>
            'profile_image',

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

function dc_admin_render_profile_image_requirements(): void
{
    $spec =
        dc_admin_profile_image_spec();
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
            (string) $spec['types']
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
function dc_admin_validate_profile_image(
    array $file
): array {
    $spec =
        dc_admin_profile_image_spec();

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
            'The uploaded image could not be read.',
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
            'The image must be no larger than '
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
            'Unable to inspect profile image: '
            . $exception->getMessage()
        );

        return [
            false,
            'The server could not inspect the selected image.',
        ];
    }

    if (
        !is_string($mime)
        || !in_array(
            $mime,
            $spec['mime_types'],
            true
        )
    ) {
        return [
            false,
            'Use a JPG, PNG, or WebP image.',
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
                'Images must be %d–%d px wide and %d–%d px tall.',
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
            'The image is too narrow or too wide. '
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

function dc_admin_validate_staff_profile(
    string $name,
    string $role,
    string $biography
): ?string {
    if ($name === '') {
        return 'Staff member name is required.';
    }

    if (
        mb_strlen($name)
        > 100
    ) {
        return 'Staff member name must be 100 characters or fewer.';
    }

    if ($role === '') {
        return 'Role or specialty is required.';
    }

    if (
        mb_strlen($role)
        > 100
    ) {
        return 'Role or specialty must be 100 characters or fewer.';
    }

    if ($biography === '') {
        return 'Biography is required.';
    }

    if (
        mb_strlen($biography)
        > 1200
    ) {
        return 'Biography must be 1,200 characters or fewer.';
    }

    return null;
}

function dc_admin_validate_company_overview(
    string $biography
): ?string {
    if ($biography === '') {
        return 'Business overview is required.';
    }

    if (
        mb_strlen($biography)
        > 1200
    ) {
        return 'Business overview must be 1,200 characters or fewer.';
    }

    return null;
}

function dc_admin_delete_staff_profile(
    int $profileId
): bool {
    $profile =
        dc_admin_profiles_find(
            $profileId
        );

    if (
        $profile === null
        || dc_admin_profile_is_company(
            $profile
        )
    ) {
        return false;
    }

    $assetId =
        isset(
            $profile[
                'image_asset_id'
            ]
        )
            ? (int) $profile[
                'image_asset_id'
            ]
            : null;

    $deleted =
        dc_delete_profile(
            $profileId
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
     * Update the About-section heading and introduction.
     */
    if (
        $action
        === 'update_about_intro'
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
                'about_eyebrow',
                'about_heading',
                'about_intro',
            ],
            'profiles'
        );
    }

    /*
     * Create a staff profile.
     */
    if (
        $action
        === 'create_profile'
    ) {
        $profileName = trim(
            (string) (
                $_POST['name']
                ?? ''
            )
        );

        $profileRole = trim(
            (string) (
                $_POST['role_title']
                ?? ''
            )
        );

        $profileBiography = trim(
            (string) (
                $_POST['biography']
                ?? ''
            )
        );

        $validationError =
            dc_admin_validate_staff_profile(
                $profileName,
                $profileRole,
                $profileBiography
            );

        if ($validationError !== null) {
            flash(
                'error',
                $validationError
            );

            dc_admin_redirect(
                'profiles',
                'add-profile'
            );
        }

        $imageFile =
            $_FILES['profile_image']
            ?? [];

        if (
            is_array($imageFile)
            && dc_admin_has_upload(
                $imageFile
            )
        ) {
            [
                $validImage,
                $imageMessage,
            ] =
                dc_admin_validate_profile_image(
                    $imageFile
                );

            if (!$validImage) {
                flash(
                    'error',
                    $imageMessage
                );

                dc_admin_redirect(
                    'profiles',
                    'add-profile'
                );
            }
        }

        $profileId =
            dc_create_profile([
                'profile_type' =>
                    'staff',

                'name' =>
                    $profileName,

                'role_title' =>
                    $profileRole,

                'biography' =>
                    $profileBiography,

                'is_active' =>
                    isset(
                        $_POST[
                            'is_active'
                        ]
                    ),
            ]);

        if ($profileId === null) {
            flash(
                'error',
                'The staff profile could not be created.'
            );

            dc_admin_redirect(
                'profiles',
                'add-profile'
            );
        }

        if (
            is_array($imageFile)
            && dc_admin_has_upload(
                $imageFile
            )
        ) {
            [
                $uploaded,
                $message,
            ] =
                dc_replace_profile_image(
                    $profileId,
                    $imageFile,
                    $profileName
                );

            if (!$uploaded) {
                flash(
                    'warning',
                    'Staff profile created, but the photo was not uploaded: '
                    . $message
                );

                dc_admin_redirect(
                    'profiles',
                    'profile-' . $profileId
                );
            }
        }

        flash(
            'success',
            'Staff profile created.'
        );

        dc_admin_redirect(
            'profiles',
            'profile-' . $profileId
        );
    }

    /*
     * Update the permanent company overview or a staff profile.
     */
    if (
        $action
        === 'update_profile'
    ) {
        $profileId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        $profile =
            dc_admin_profiles_find(
                $profileId
            );

        if ($profile === null) {
            flash(
                'error',
                'The selected profile could not be found.'
            );

            dc_admin_redirect(
                'profiles'
            );
        }

        $isCompanyProfile =
            dc_admin_profile_is_company(
                $profile
            );

        $profileName =
            $isCompanyProfile
                ? (string) (
                    dc_site_settings()[
                        'business_name'
                    ]
                    ?? 'Business'
                )
                : trim(
                    (string) (
                        $_POST['name']
                        ?? ''
                    )
                );

        $profileRole =
            $isCompanyProfile
                ? 'About the Company'
                : trim(
                    (string) (
                        $_POST['role_title']
                        ?? ''
                    )
                );

        $profileBiography = trim(
            (string) (
                $_POST['biography']
                ?? ''
            )
        );

        $validationError =
            $isCompanyProfile
                ? dc_admin_validate_company_overview(
                    $profileBiography
                )
                : dc_admin_validate_staff_profile(
                    $profileName,
                    $profileRole,
                    $profileBiography
                );

        if ($validationError !== null) {
            flash(
                'error',
                $validationError
            );

            dc_admin_redirect(
                'profiles',
                'profile-' . $profileId
            );
        }

        $imageFile =
            $_FILES['profile_image']
            ?? [];

        if (
            is_array($imageFile)
            && dc_admin_has_upload(
                $imageFile
            )
        ) {
            [
                $validImage,
                $imageMessage,
            ] =
                dc_admin_validate_profile_image(
                    $imageFile
                );

            if (!$validImage) {
                flash(
                    'error',
                    $imageMessage
                );

                dc_admin_redirect(
                    'profiles',
                    'profile-' . $profileId
                );
            }
        }

        $updated =
            dc_update_profile(
                $profileId,
                [
                    'name' =>
                        $profileName,

                    'role_title' =>
                        $profileRole,

                    'biography' =>
                        $profileBiography,

                    'image_asset_id' =>
                        $profile[
                            'image_asset_id'
                        ]
                        ?? null,

                    'is_active' =>
                        $isCompanyProfile
                        || isset(
                            $_POST[
                                'is_active'
                            ]
                        ),
                ]
            );

        if (!$updated) {
            flash(
                'error',
                $isCompanyProfile
                    ? 'The business overview could not be updated.'
                    : 'The staff profile could not be updated.'
            );

            dc_admin_redirect(
                'profiles',
                'profile-' . $profileId
            );
        }

        if ($isCompanyProfile) {
            dc_update_content_values([
                'about_body' =>
                    mb_substr(
                        $profileBiography,
                        0,
                        500
                    ),
            ]);
        }

        if (
            is_array($imageFile)
            && dc_admin_has_upload(
                $imageFile
            )
        ) {
            [
                $uploaded,
                $message,
            ] =
                dc_replace_profile_image(
                    $profileId,
                    $imageFile,
                    $profileName
                );

            if (!$uploaded) {
                flash(
                    'warning',
                    (
                        $isCompanyProfile
                            ? 'Business overview'
                            : 'Staff profile'
                    )
                    . ' was saved, but the image was not replaced: '
                    . $message
                );

                dc_admin_redirect(
                    'profiles',
                    'profile-' . $profileId
                );
            }
        } elseif (
            isset(
                $_POST[
                    'remove_image'
                ]
            )
        ) {
            dc_remove_profile_image(
                $profileId
            );
        }

        flash(
            'success',
            $isCompanyProfile
                ? 'Business overview updated.'
                : 'Staff profile updated.'
        );

        dc_admin_redirect(
            'profiles',
            'profile-' . $profileId
        );
    }

    /*
     * Show or hide a staff profile.
     */
    if (
        $action
        === 'toggle_record'
        && (
            $_POST['record_type']
            ?? ''
        ) === 'profile'
    ) {
        $profileId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        $profile =
            dc_admin_profiles_find(
                $profileId
            );

        if (
            $profile === null
            || dc_admin_profile_is_company(
                $profile
            )
        ) {
            flash(
                'error',
                'The permanent business overview cannot be hidden.'
            );

            dc_admin_redirect(
                'profiles'
            );
        }

        $isActive =
            (int) (
                $_POST['is_active']
                ?? 0
            ) === 1;

        dc_admin_finish(
            dc_set_profile_active(
                $profileId,
                $isActive
            ),
            $isActive
                ? 'Staff profile shown on the website.'
                : 'Staff profile hidden from the website.',
            'The staff profile visibility could not be changed.',
            'profiles',
            'profile-' . $profileId
        );
    }

    /*
     * Move a staff profile up or down.
     */
    if (
        $action
        === 'move_record'
        && (
            $_POST['record_type']
            ?? ''
        ) === 'profile'
    ) {
        $profileId =
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
            dc_admin_reorder_staff_profile(
                $profileId,
                $direction
            ),
            'Staff order updated.',
            'The staff order could not be updated.',
            'profiles',
            'profile-' . $profileId
        );
    }

    /*
     * Delete a staff profile.
     */
    if (
        $action
        === 'delete_record'
        && (
            $_POST['record_type']
            ?? ''
        ) === 'profile'
    ) {
        $profileId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        dc_admin_finish(
            dc_admin_delete_staff_profile(
                $profileId
            ),
            'Staff profile permanently deleted.',
            'The staff profile could not be deleted. The business overview is protected.',
            'profiles'
        );
    }

    return;
}

/*
 * Render mode begins here.
 */

$aboutContentRecords = [];

foreach (
    dc_content_records('about')
    as $record
) {
    $key =
        (string) (
            $record['content_key']
            ?? ''
        );

    if (
        in_array(
            $key,
            [
                'about_eyebrow',
                'about_heading',
                'about_intro',
            ],
            true
        )
    ) {
        $aboutContentRecords[$key] =
            $record;
    }
}

$aboutContentFields = [
    'about_eyebrow' => [
        'label' =>
            'Small label above the About heading',

        'textarea' =>
            false,
    ],

    'about_heading' => [
        'label' =>
            'About section heading',

        'textarea' =>
            false,
    ],

    'about_intro' => [
        'label' =>
            'Introduction above the business and staff cards',

        'textarea' =>
            true,

        'rows' =>
            4,
    ],
];

$profiles =
    dc_profiles(false, true);

$companyProfile = null;
$staffProfiles = [];

foreach ($profiles as $profile) {
    if (
        dc_admin_profile_is_company(
            $profile
        )
    ) {
        $companyProfile ??=
            $profile;
    } else {
        $staffProfiles[] =
            $profile;
    }
}

?>
<section
    class="card border-0 shadow-sm mb-4 admin-section-copy"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            About Section
        </h2>

        <p class="small text-body-secondary mb-0">
            Edit the heading and introduction above the business and staff
            cards.
        </p>
    </div>

    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="update_about_intro"
            >

            <div class="row g-4">
                <?php foreach (
                    $aboutContentFields
                    as $key => $configuration
                ): ?>
                    <?php
                    $record =
                        $aboutContentRecords[
                            $key
                        ]
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
                        'about_'
                        . $key;
                    ?>

                    <div class="col-12">
                        <label
                            class="form-label"
                            for="<?= e($fieldId) ?>"
                        >
                            <?= e(
                                (string) $configuration[
                                    'label'
                                ]
                            ) ?>
                        </label>

                        <?php if (
                            !empty(
                                $configuration[
                                    'textarea'
                                ]
                            )
                        ): ?>
                            <textarea
                                class="form-control"
                                id="<?= e($fieldId) ?>"
                                name="content[<?= e($key) ?>]"
                                rows="<?= e(
                                    (string) (
                                        $configuration[
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
                                id="<?= e($fieldId) ?>"
                                name="content[<?= e($key) ?>]"
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
                Publish About Section
            </button>
        </form>
    </div>
</section>

<?php if ($companyProfile !== null): ?>
    <?php
    $companyProfileId =
        (int) $companyProfile['id'];
    ?>

    <article
        class="card border-0 shadow-sm mb-4 admin-company-card"
        id="profile-<?= $companyProfileId ?>"
    >
        <div class="card-header bg-white py-3">
            <div
                class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2"
            >
                <div>
                    <h2 class="h5 mb-1">
                        Business Overview
                    </h2>

                    <p class="small text-body-secondary mb-0">
                        This card always appears first. The business name comes
                        from Site Settings.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Always first
                </span>
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
                    value="update_profile"
                >

                <input
                    type="hidden"
                    name="record_id"
                    value="<?= $companyProfileId ?>"
                >

                <div class="row g-4">
                    <div class="col-lg-8">
                        <label
                            class="form-label"
                            for="company_biography"
                        >
                            About the business
                        </label>

                        <textarea
                            class="form-control"
                            id="company_biography"
                            name="biography"
                            rows="8"
                            maxlength="1200"
                            data-character-count
                            required
                        ><?= e(
                            (string) (
                                $companyProfile[
                                    'biography'
                                ]
                                ?? $companyProfile[
                                    'bio'
                                ]
                                ?? ''
                            )
                        ) ?></textarea>
                    </div>

                    <div class="col-lg-4">
                        <p class="form-label">
                            Current image
                        </p>

                        <?php if (
                            !empty(
                                $companyProfile[
                                    'image_exists'
                                ]
                            )
                        ): ?>
                            <img
                                class="img-fluid rounded border mb-3"
                                src="<?= e(
                                    (string) $companyProfile[
                                        'image'
                                    ]
                                ) ?>"
                                alt="<?= e(
                                    (string) (
                                        $companyProfile[
                                            'alt_text'
                                        ]
                                        ?? $siteSettings[
                                            'business_name'
                                        ]
                                        ?? ''
                                    )
                                ) ?>"
                            >
                        <?php else: ?>
                            <div
                                class="border rounded bg-body-tertiary p-4 text-center text-body-secondary mb-3"
                            >
                                No business image
                            </div>
                        <?php endif; ?>

                        <label
                            class="form-label"
                            for="company_profile_image"
                        >
                            Replace image
                        </label>

                        <input
                            class="form-control"
                            id="company_profile_image"
                            name="profile_image"
                            type="file"
                            accept="<?= e(
                                (string) dc_admin_profile_image_spec()[
                                    'accept'
                                ]
                            ) ?>"
                            <?= dc_admin_profile_image_attributes() ?>
                        >

                        <?php
                        dc_admin_render_profile_image_requirements();
                        ?>

                        <?php if (
                            !empty(
                                $companyProfile[
                                    'image_asset_id'
                                ]
                            )
                        ): ?>
                            <div class="form-check mt-3">
                                <input
                                    class="form-check-input"
                                    id="company_remove_image"
                                    name="remove_image"
                                    type="checkbox"
                                    value="1"
                                >

                                <label
                                    class="form-check-label"
                                    for="company_remove_image"
                                >
                                    Remove current image
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <button
                    class="btn btn-primary mt-4"
                    type="submit"
                >
                    Save Business Overview
                </button>
            </form>
        </div>
    </article>
<?php else: ?>
    <div
        class="alert alert-warning"
        role="alert"
    >
        The permanent business overview is missing from the database. Staff
        cards can still be managed, but the business overview should be
        restored before launch.
    </div>
<?php endif; ?>

<section
    class="card border-0 shadow-sm mb-4"
    id="add-profile"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            Add a Staff Member
        </h2>

        <p class="small text-body-secondary mb-0">
            Staff cards appear after the business overview.
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
                value="create_profile"
            >

            <div class="row g-4">
                <div class="col-md-6">
                    <label
                        class="form-label"
                        for="new_profile_name"
                    >
                        Name
                    </label>

                    <input
                        class="form-control"
                        id="new_profile_name"
                        name="name"
                        maxlength="100"
                        data-character-count
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label
                        class="form-label"
                        for="new_profile_role"
                    >
                        Role or specialty
                    </label>

                    <input
                        class="form-control"
                        id="new_profile_role"
                        name="role_title"
                        maxlength="100"
                        data-character-count
                        required
                    >
                </div>

                <div class="col-12">
                    <label
                        class="form-label"
                        for="new_profile_bio"
                    >
                        Biography
                    </label>

                    <textarea
                        class="form-control"
                        id="new_profile_bio"
                        name="biography"
                        rows="6"
                        maxlength="1200"
                        data-character-count
                        required
                    ></textarea>
                </div>

                <div class="col-lg-6">
                    <label
                        class="form-label"
                        for="new_profile_image"
                    >
                        Staff photo
                    </label>

                    <input
                        class="form-control"
                        id="new_profile_image"
                        name="profile_image"
                        type="file"
                        accept="<?= e(
                            (string) dc_admin_profile_image_spec()[
                                'accept'
                            ]
                        ) ?>"
                        <?= dc_admin_profile_image_attributes() ?>
                    >

                    <?php
                    dc_admin_render_profile_image_requirements();
                    ?>
                </div>

                <div
                    class="col-lg-6 d-flex align-items-end"
                >
                    <div class="form-check mb-2">
                        <input
                            class="form-check-input"
                            id="new_profile_active"
                            name="is_active"
                            type="checkbox"
                            value="1"
                            checked
                        >

                        <label
                            class="form-check-label"
                            for="new_profile_active"
                        >
                            Show this staff member on the website immediately
                        </label>
                    </div>
                </div>
            </div>

            <button
                class="btn btn-primary mt-4"
                type="submit"
            >
                Add Staff Member
            </button>
        </form>
    </div>
</section>

<div
    class="d-flex align-items-center justify-content-between mb-3"
>
    <h2 class="h4 mb-0">
        Staff Cards
    </h2>

    <span class="badge text-bg-secondary">
        <?= count($staffProfiles) ?> total
    </span>
</div>

<?php if ($staffProfiles === []): ?>
    <div class="alert alert-light border">
        No staff cards have been added.
    </div>
<?php else: ?>
    <div class="vstack gap-4">
        <?php foreach (
            $staffProfiles
            as $index => $profile
        ): ?>
            <?php
            $profileId =
                (int) $profile['id'];

            $isActive =
                (int) (
                    $profile[
                        'is_active'
                    ]
                    ?? 0
                ) === 1;
            ?>

            <article
                class="card border-0 shadow-sm"
                id="profile-<?= $profileId ?>"
            >
                <div
                    class="card-header bg-white d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 py-3"
                >
                    <div>
                        <h3 class="h5 mb-1">
                            <?= e(
                                (string) $profile[
                                    'name'
                                ]
                            ) ?>
                        </h3>

                        <span class="badge text-bg-light border">
                            Staff
                        </span>

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
                                value="profile"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $profileId ?>"
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
                                value="profile"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $profileId ?>"
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
                                    === count(
                                        $staffProfiles
                                    ) - 1
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
                                value="profile"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $profileId ?>"
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
                            value="update_profile"
                        >

                        <input
                            type="hidden"
                            name="record_id"
                            value="<?= $profileId ?>"
                        >

                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label
                                        class="form-label"
                                        for="profile_name_<?= $profileId ?>"
                                    >
                                        Name
                                    </label>

                                    <input
                                        class="form-control"
                                        id="profile_name_<?= $profileId ?>"
                                        name="name"
                                        maxlength="100"
                                        data-character-count
                                        value="<?= e(
                                            (string) $profile[
                                                'name'
                                            ]
                                        ) ?>"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label
                                        class="form-label"
                                        for="profile_role_<?= $profileId ?>"
                                    >
                                        Role or specialty
                                    </label>

                                    <input
                                        class="form-control"
                                        id="profile_role_<?= $profileId ?>"
                                        name="role_title"
                                        maxlength="100"
                                        data-character-count
                                        value="<?= e(
                                            (string) (
                                                $profile[
                                                    'role_title'
                                                ]
                                                ?? $profile[
                                                    'role'
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
                                        for="profile_bio_<?= $profileId ?>"
                                    >
                                        Biography
                                    </label>

                                    <textarea
                                        class="form-control"
                                        id="profile_bio_<?= $profileId ?>"
                                        name="biography"
                                        rows="7"
                                        maxlength="1200"
                                        data-character-count
                                        required
                                    ><?= e(
                                        (string) (
                                            $profile[
                                                'biography'
                                            ]
                                            ?? $profile[
                                                'bio'
                                            ]
                                            ?? ''
                                        )
                                    ) ?></textarea>
                                </div>

                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        id="profile_active_<?= $profileId ?>"
                                        name="is_active"
                                        type="checkbox"
                                        value="1"
                                        <?= $isActive
                                            ? 'checked'
                                            : '' ?>
                                    >

                                    <label
                                        class="form-check-label"
                                        for="profile_active_<?= $profileId ?>"
                                    >
                                        Show this staff member on the website
                                    </label>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <p class="form-label">
                                    Current photo
                                </p>

                                <?php if (
                                    !empty(
                                        $profile[
                                            'image_exists'
                                        ]
                                    )
                                ): ?>
                                    <img
                                        class="img-fluid rounded border mb-3"
                                        src="<?= e(
                                            (string) $profile[
                                                'image'
                                            ]
                                        ) ?>"
                                        alt="<?= e(
                                            (string) (
                                                $profile[
                                                    'alt_text'
                                                ]
                                                ?? $profile[
                                                    'name'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>"
                                    >
                                <?php else: ?>
                                    <div
                                        class="border rounded bg-body-tertiary p-4 text-center text-body-secondary mb-3"
                                    >
                                        No staff photo
                                    </div>
                                <?php endif; ?>

                                <label
                                    class="form-label"
                                    for="profile_image_<?= $profileId ?>"
                                >
                                    Replace photo
                                </label>

                                <input
                                    class="form-control"
                                    id="profile_image_<?= $profileId ?>"
                                    name="profile_image"
                                    type="file"
                                    accept="<?= e(
                                        (string) dc_admin_profile_image_spec()[
                                            'accept'
                                        ]
                                    ) ?>"
                                    <?= dc_admin_profile_image_attributes() ?>
                                >

                                <?php
                                dc_admin_render_profile_image_requirements();
                                ?>

                                <?php if (
                                    !empty(
                                        $profile[
                                            'image_asset_id'
                                        ]
                                    )
                                ): ?>
                                    <div class="form-check mt-3">
                                        <input
                                            class="form-check-input"
                                            id="profile_remove_image_<?= $profileId ?>"
                                            name="remove_image"
                                            type="checkbox"
                                            value="1"
                                        >

                                        <label
                                            class="form-check-label"
                                            for="profile_remove_image_<?= $profileId ?>"
                                        >
                                            Remove current photo
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button
                            class="btn btn-primary mt-4"
                            type="submit"
                        >
                            Save Staff Member
                        </button>
                    </form>

                    <hr class="my-4">

                    <form
                        method="post"
                        onsubmit="return confirm('Permanently delete this staff member?');"
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
                            value="profile"
                        >

                        <input
                            type="hidden"
                            name="record_id"
                            value="<?= $profileId ?>"
                        >

                        <button
                            class="btn btn-outline-danger btn-sm"
                            type="submit"
                        >
                            Delete Staff Member
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>