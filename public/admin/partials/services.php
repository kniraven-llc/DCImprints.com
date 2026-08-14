<?php

declare(strict_types=1);

/*
 * Services administration partial.
 *
 * Handles:
 * - Services-section introduction
 * - Creating services
 * - Editing services
 * - Service images
 * - Visibility
 * - Display order
 * - Deletion
 */

function dc_admin_services_find(
    int $serviceId
): ?array {
    foreach (
        dc_services(false, true)
        as $service
    ) {
        if (
            (int) (
                $service['id']
                ?? 0
            ) === $serviceId
        ) {
            return $service;
        }
    }

    return null;
}

/**
 * @return array<string, mixed>
 */
function dc_admin_service_image_spec(): array
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

        'min_width' => 800,
        'min_height' => 500,
        'max_width' => 5000,
        'max_height' => 3200,
        'min_ratio' => 1.30,
        'max_ratio' => 1.90,

        'recommended' =>
            'WebP or JPG at 1600 × 1000 px, ideally 1.5 MB or smaller.',
    ];
}

function dc_admin_service_image_attributes(): string
{
    $spec =
        dc_admin_service_image_spec();

    $attributes = [
        'data-media-purpose' =>
            'service_image',

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

function dc_admin_render_service_image_requirements(): void
{
    $spec =
        dc_admin_service_image_spec();
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
function dc_admin_validate_service_image(
    array $file
): array {
    $spec =
        dc_admin_service_image_spec();

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
            'The uploaded service image could not be read.',
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
            'The service image must be no larger than '
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
            'Unable to inspect service image: '
            . $exception->getMessage()
        );

        return [
            false,
            'The server could not inspect the selected service image.',
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
            'Use a JPG, PNG, or WebP service image.',
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
                'Service images must be %d–%d px wide and %d–%d px tall.',
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
            'The service image is too narrow or too wide. '
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

function dc_admin_validate_service_text(
    string $name,
    string $description
): ?string {
    if ($name === '') {
        return 'Service name is required.';
    }

    if (
        mb_strlen($name)
        > 80
    ) {
        return 'Service name must be 80 characters or fewer.';
    }

    if ($description === '') {
        return 'Service description is required.';
    }

    if (
        mb_strlen($description)
        > 320
    ) {
        return 'Service description must be 320 characters or fewer.';
    }

    return null;
}

function dc_admin_reorder_service(
    int $serviceId,
    string $direction
): bool {
    $services =
        dc_services(false, true);

    $ids =
        array_values(
            array_map(
                static fn (
                    array $service
                ): int =>
                    (int) (
                        $service['id']
                        ?? 0
                    ),
                $services
            )
        );

    $index =
        array_search(
            $serviceId,
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

    return dc_reorder_services(
        $ids
    );
}

function dc_admin_delete_service_record(
    int $serviceId
): bool {
    $service =
        dc_admin_services_find(
            $serviceId
        );

    if ($service === null) {
        return false;
    }

    $assetId =
        isset(
            $service[
                'image_asset_id'
            ]
        )
            ? (int) $service[
                'image_asset_id'
            ]
            : null;

    $deleted =
        dc_delete_service(
            $serviceId
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

    if (
        $action
        === 'update_services'
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
                'services_eyebrow',
                'services_heading',
                'services_intro',
            ],
            'services'
        );
    }

    if (
        $action
        === 'create_service'
    ) {
        $serviceName = trim(
            (string) (
                $_POST['name']
                ?? ''
            )
        );

        $serviceDescription = trim(
            (string) (
                $_POST['description']
                ?? ''
            )
        );

        $validationError =
            dc_admin_validate_service_text(
                $serviceName,
                $serviceDescription
            );

        if ($validationError !== null) {
            flash(
                'error',
                $validationError
            );

            dc_admin_redirect(
                'services',
                'add-service'
            );
        }

        $imageFile =
            $_FILES['service_image']
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
                dc_admin_validate_service_image(
                    $imageFile
                );

            if (!$validImage) {
                flash(
                    'error',
                    $imageMessage
                );

                dc_admin_redirect(
                    'services',
                    'add-service'
                );
            }
        }

        $serviceId =
            dc_create_service([
                'name' =>
                    $serviceName,

                'description' =>
                    $serviceDescription,

                'navigation_label' =>
                    $serviceName,

                'navigation_summary' =>
                    mb_substr(
                        $serviceDescription,
                        0,
                        255
                    ),

                'quote_form_label' =>
                    $serviceName,

                'footer_label' =>
                    $serviceName,

                'is_active' =>
                    isset(
                        $_POST[
                            'is_active'
                        ]
                    ),
            ]);

        if ($serviceId === null) {
            flash(
                'error',
                'The service could not be created.'
            );

            dc_admin_redirect(
                'services',
                'add-service'
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
                dc_replace_service_image(
                    $serviceId,
                    $imageFile
                );

            if (!$uploaded) {
                flash(
                    'warning',
                    'Service created, but its image was not uploaded: '
                    . $message
                );

                dc_admin_redirect(
                    'services',
                    'service-' . $serviceId
                );
            }
        }

        flash(
            'success',
            'Service created.'
        );

        dc_admin_redirect(
            'services',
            'service-' . $serviceId
        );
    }

    if (
        $action
        === 'update_service'
    ) {
        $serviceId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        $service =
            dc_admin_services_find(
                $serviceId
            );

        if ($service === null) {
            flash(
                'error',
                'The selected service could not be found.'
            );

            dc_admin_redirect(
                'services'
            );
        }

        $serviceName = trim(
            (string) (
                $_POST['name']
                ?? ''
            )
        );

        $serviceDescription = trim(
            (string) (
                $_POST['description']
                ?? ''
            )
        );

        $validationError =
            dc_admin_validate_service_text(
                $serviceName,
                $serviceDescription
            );

        if ($validationError !== null) {
            flash(
                'error',
                $validationError
            );

            dc_admin_redirect(
                'services',
                'service-' . $serviceId
            );
        }

        $imageFile =
            $_FILES['service_image']
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
                dc_admin_validate_service_image(
                    $imageFile
                );

            if (!$validImage) {
                flash(
                    'error',
                    $imageMessage
                );

                dc_admin_redirect(
                    'services',
                    'service-' . $serviceId
                );
            }
        }

        $updated =
            dc_update_service(
                $serviceId,
                [
                    'name' =>
                        $serviceName,

                    'description' =>
                        $serviceDescription,

                    'navigation_label' =>
                        $serviceName,

                    'navigation_summary' =>
                        mb_substr(
                            $serviceDescription,
                            0,
                            255
                        ),

                    'quote_form_label' =>
                        $serviceName,

                    'footer_label' =>
                        $serviceName,

                    'image_asset_id' =>
                        $service[
                            'image_asset_id'
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
                'The service could not be updated.'
            );

            dc_admin_redirect(
                'services',
                'service-' . $serviceId
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
                dc_replace_service_image(
                    $serviceId,
                    $imageFile
                );

            if (!$uploaded) {
                flash(
                    'warning',
                    'Service details were saved, but the image was not replaced: '
                    . $message
                );

                dc_admin_redirect(
                    'services',
                    'service-' . $serviceId
                );
            }
        } elseif (
            isset(
                $_POST[
                    'remove_image'
                ]
            )
        ) {
            dc_remove_service_image(
                $serviceId
            );
        }

        flash(
            'success',
            'Service updated.'
        );

        dc_admin_redirect(
            'services',
            'service-' . $serviceId
        );
    }

    if (
        $action
        === 'toggle_record'
        && (
            $_POST['record_type']
            ?? ''
        ) === 'service'
    ) {
        $serviceId =
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
            dc_set_service_active(
                $serviceId,
                $isActive
            ),
            $isActive
                ? 'Service shown on the website.'
                : 'Service hidden from the website.',
            'The service visibility could not be changed.',
            'services',
            'service-' . $serviceId
        );
    }

    if (
        $action
        === 'move_record'
        && (
            $_POST['record_type']
            ?? ''
        ) === 'service'
    ) {
        $serviceId =
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
            dc_admin_reorder_service(
                $serviceId,
                $direction
            ),
            'Service order updated.',
            'The service order could not be updated.',
            'services',
            'service-' . $serviceId
        );
    }

    if (
        $action
        === 'delete_record'
        && (
            $_POST['record_type']
            ?? ''
        ) === 'service'
    ) {
        $serviceId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        dc_admin_finish(
            dc_admin_delete_service_record(
                $serviceId
            ),
            'Service permanently deleted.',
            'The service could not be deleted.',
            'services'
        );
    }

    return;
}

$serviceContentRecords = [];

foreach (
    dc_content_records(
        'services'
    )
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
                'services_eyebrow',
                'services_heading',
                'services_intro',
            ],
            true
        )
    ) {
        $serviceContentRecords[$key] =
            $record;
    }
}

$serviceContentFields = [
    'services_eyebrow' => [
        'label' =>
            'Small label above the Services heading',

        'textarea' =>
            false,
    ],

    'services_heading' => [
        'label' =>
            'Services heading',

        'textarea' =>
            false,
    ],

    'services_intro' => [
        'label' =>
            'Services introduction',

        'textarea' =>
            true,

        'rows' =>
            4,
    ],
];

$services =
    dc_services(false, true);

?>
<section
    class="card border-0 shadow-sm mb-4 admin-section-copy"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            Services Introduction
        </h2>

        <p class="small text-body-secondary mb-0">
            Edit the heading and short introduction above the service cards.
        </p>
    </div>

    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="update_services"
            >

            <div class="row g-4">
                <?php foreach (
                    $serviceContentFields
                    as $key => $configuration
                ): ?>
                    <?php
                    $record =
                        $serviceContentRecords[
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
                        'services_'
                        . $key;
                    ?>

                    <div class="col-12">
                        <label
                            class="form-label"
                            for="<?= e(
                                $fieldId
                            ) ?>"
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
                                id="<?= e(
                                    $fieldId
                                ) ?>"
                                name="content[<?= e(
                                    $key
                                ) ?>]"
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
                                id="<?= e(
                                    $fieldId
                                ) ?>"
                                name="content[<?= e(
                                    $key
                                ) ?>]"
                                value="<?= e(
                                    $value
                                ) ?>"
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
                Publish Services Introduction
            </button>
        </form>
    </div>
</section>

<section
    class="card border-0 shadow-sm mb-4"
    id="add-service"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            Add a Service
        </h2>

        <p class="small text-body-secondary mb-0">
            The service name will also be used in the navigation, quote form,
            and footer.
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
                value="create_service"
            >

            <div class="row g-4">
                <div class="col-lg-6">
                    <label
                        class="form-label"
                        for="new_service_name"
                    >
                        Service name
                    </label>

                    <input
                        class="form-control"
                        id="new_service_name"
                        name="name"
                        maxlength="80"
                        data-character-count
                        required
                    >
                </div>

                <div class="col-lg-6">
                    <label
                        class="form-label"
                        for="new_service_image"
                    >
                        Service image
                    </label>

                    <input
                        class="form-control"
                        id="new_service_image"
                        name="service_image"
                        type="file"
                        accept="<?= e(
                            (string) dc_admin_service_image_spec()[
                                'accept'
                            ]
                        ) ?>"
                        <?= dc_admin_service_image_attributes() ?>
                    >

                    <?php
                    dc_admin_render_service_image_requirements();
                    ?>
                </div>

                <div class="col-12">
                    <label
                        class="form-label"
                        for="new_service_description"
                    >
                        Description
                    </label>

                    <textarea
                        class="form-control"
                        id="new_service_description"
                        name="description"
                        rows="4"
                        maxlength="320"
                        data-character-count
                        required
                    ></textarea>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            id="new_service_active"
                            name="is_active"
                            type="checkbox"
                            value="1"
                            checked
                        >

                        <label
                            class="form-check-label"
                            for="new_service_active"
                        >
                            Show this service on the website immediately
                        </label>
                    </div>
                </div>
            </div>

            <button
                class="btn btn-primary mt-4"
                type="submit"
            >
                Create Service
            </button>
        </form>
    </div>
</section>

<div
    class="d-flex align-items-center justify-content-between mb-3"
>
    <h2 class="h4 mb-0">
        Existing Services
    </h2>

    <span class="badge text-bg-secondary">
        <?= count($services) ?> total
    </span>
</div>

<?php if ($services === []): ?>
    <div class="alert alert-light border">
        No services have been added.
    </div>
<?php else: ?>
    <div class="vstack gap-4">
        <?php foreach (
            $services
            as $index => $service
        ): ?>
            <?php
            $serviceId =
                (int) $service['id'];

            $isActive =
                (int) $service[
                    'is_active'
                ] === 1;
            ?>

            <article
                class="card border-0 shadow-sm"
                id="service-<?= $serviceId ?>"
            >
                <div
                    class="card-header bg-white d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 py-3"
                >
                    <div>
                        <h3 class="h5 mb-1">
                            <?= e(
                                (string) $service[
                                    'name'
                                ]
                            ) ?>
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
                                value="service"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $serviceId ?>"
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
                                value="service"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $serviceId ?>"
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
                                    === count($services) - 1
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
                                value="service"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $serviceId ?>"
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
                            value="update_service"
                        >

                        <input
                            type="hidden"
                            name="record_id"
                            value="<?= $serviceId ?>"
                        >

                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label
                                        class="form-label"
                                        for="service_name_<?= $serviceId ?>"
                                    >
                                        Service name
                                    </label>

                                    <input
                                        class="form-control"
                                        id="service_name_<?= $serviceId ?>"
                                        name="name"
                                        maxlength="80"
                                        data-character-count
                                        value="<?= e(
                                            (string) $service[
                                                'name'
                                            ]
                                        ) ?>"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label
                                        class="form-label"
                                        for="service_description_<?= $serviceId ?>"
                                    >
                                        Description
                                    </label>

                                    <textarea
                                        class="form-control"
                                        id="service_description_<?= $serviceId ?>"
                                        name="description"
                                        rows="5"
                                        maxlength="320"
                                        data-character-count
                                        required
                                    ><?= e(
                                        (string) $service[
                                            'description'
                                        ]
                                    ) ?></textarea>
                                </div>

                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        id="service_active_<?= $serviceId ?>"
                                        name="is_active"
                                        type="checkbox"
                                        value="1"
                                        <?= $isActive
                                            ? 'checked'
                                            : '' ?>
                                    >

                                    <label
                                        class="form-check-label"
                                        for="service_active_<?= $serviceId ?>"
                                    >
                                        Show on the website
                                    </label>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <p class="form-label">
                                    Current image
                                </p>

                                <?php if (
                                    !empty(
                                        $service[
                                            'image_exists'
                                        ]
                                    )
                                ): ?>
                                    <img
                                        class="img-fluid rounded border mb-3"
                                        src="<?= e(
                                            (string) $service[
                                                'image'
                                            ]
                                        ) ?>"
                                        alt="<?= e(
                                            (string) (
                                                $service[
                                                    'image_alt'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>"
                                    >
                                <?php else: ?>
                                    <div
                                        class="border rounded bg-body-tertiary p-4 text-center text-body-secondary mb-3"
                                    >
                                        No service image
                                    </div>
                                <?php endif; ?>

                                <label
                                    class="form-label"
                                    for="service_image_<?= $serviceId ?>"
                                >
                                    Replace image
                                </label>

                                <input
                                    class="form-control"
                                    id="service_image_<?= $serviceId ?>"
                                    name="service_image"
                                    type="file"
                                    accept="<?= e(
                                        (string) dc_admin_service_image_spec()[
                                            'accept'
                                        ]
                                    ) ?>"
                                    <?= dc_admin_service_image_attributes() ?>
                                >

                                <?php
                                dc_admin_render_service_image_requirements();
                                ?>

                                <?php if (
                                    !empty(
                                        $service[
                                            'image_asset_id'
                                        ]
                                    )
                                ): ?>
                                    <div class="form-check mt-3">
                                        <input
                                            class="form-check-input"
                                            id="service_remove_image_<?= $serviceId ?>"
                                            name="remove_image"
                                            type="checkbox"
                                            value="1"
                                        >

                                        <label
                                            class="form-check-label"
                                            for="service_remove_image_<?= $serviceId ?>"
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
                            Save Service
                        </button>
                    </form>

                    <hr class="my-4">

                    <form
                        method="post"
                        onsubmit="return confirm('Permanently delete this service?');"
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
                            value="service"
                        >

                        <input
                            type="hidden"
                            name="record_id"
                            value="<?= $serviceId ?>"
                        >

                        <button
                            class="btn btn-outline-danger btn-sm"
                            type="submit"
                        >
                            Delete Service
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>