<?php

declare(strict_types=1);

/*
 * Location administration partial.
 *
 * The address, phone number, location note, and business hours come from Site
 * Settings. This section controls only the text surrounding that information
 * and the Google Maps button.
 */

/**
 * @return array<string, array<string, mixed>>
 */
function dc_admin_location_content_records(): array
{
    $records = [];

    foreach (
        dc_content_records('location')
        as $record
    ) {
        $key = (string) (
            $record['content_key']
            ?? ''
        );

        if ($key !== '') {
            $records[$key] = $record;
        }
    }

    return $records;
}

/**
 * @param array<int, string> $keys
 * @param array<string, array<string, mixed>> $records
 * @param array<string, array<string, mixed>> $configuration
 */
function dc_admin_render_location_form(
    string $action,
    string $anchor,
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
        id="<?= e($anchor) ?>"
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

                        $field =
                            $configuration[$key]
                            ?? [];

                        $maximum = isset(
                            $record['max_length']
                        ) && $record['max_length'] !== null
                            ? max(
                                1,
                                (int) $record[
                                    'max_length'
                                ]
                            )
                            : 2000;

                        $value = (string) (
                            $record['content_value']
                            ?? dc_content($key)
                        );

                        $fieldId =
                            'location_'
                            . $key;

                        $isTextarea =
                            !empty(
                                $field['textarea']
                            );
                        ?>

                        <div class="col-12">
                            <label
                                class="form-label"
                                for="<?= e($fieldId) ?>"
                            >
                                <?= e(
                                    (string) (
                                        $field['label']
                                        ?? $key
                                    )
                                ) ?>
                            </label>

                            <?php if ($isTextarea): ?>
                                <textarea
                                    class="form-control"
                                    id="<?= e($fieldId) ?>"
                                    name="content[<?= e($key) ?>]"
                                    rows="<?= e(
                                        (string) (
                                            $field['rows']
                                            ?? 4
                                        )
                                    ) ?>"
                                    maxlength="<?= $maximum ?>"
                                    data-character-count
                                    required
                                ><?= e($value) ?></textarea>
                            <?php else: ?>
                                <input
                                    class="form-control"
                                    id="<?= e($fieldId) ?>"
                                    name="content[<?= e($key) ?>]"
                                    value="<?= e($value) ?>"
                                    maxlength="<?= $maximum ?>"
                                    data-character-count
                                    required
                                >
                            <?php endif; ?>

                            <?php if (
                                !empty(
                                    $field['help']
                                )
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

    $postedContent =
        $_POST['content']
        ?? [];

    if (!is_array($postedContent)) {
        $postedContent = [];
    }

    if (
        $action
        === 'update_location_intro'
    ) {
        dc_admin_update_content_fields(
            $postedContent,
            [
                'location_eyebrow',
                'location_heading',
                'location_intro',
            ],
            'location'
        );
    }

    if (
        $action
        === 'update_location_button'
    ) {
        dc_admin_update_content_fields(
            $postedContent,
            [
                'location_directions_label',
            ],
            'location'
        );
    }

    return;
}

$locationRecords =
    dc_admin_location_content_records();

$locationConfiguration = [
    'location_eyebrow' => [
        'label' =>
            'Small label above the Location heading',

        'textarea' =>
            false,
    ],

    'location_heading' => [
        'label' =>
            'Location heading',

        'textarea' =>
            false,
    ],

    'location_intro' => [
        'label' =>
            'Introduction above the address and map',

        'textarea' =>
            true,

        'rows' =>
            4,

        'help' =>
            'The actual address and business hours are managed in Site Settings.',
    ],

    'location_directions_label' => [
        'label' =>
            'Text on the button that opens Google Maps',

        'textarea' =>
            false,
    ],
];

dc_admin_render_location_form(
    'update_location_intro',
    'location-introduction',
    'Location Introduction',
    'Edit the heading and introduction above the business address and map.',
    'Publish Location Introduction',
    [
        'location_eyebrow',
        'location_heading',
        'location_intro',
    ],
    $locationRecords,
    $locationConfiguration
);

dc_admin_render_location_form(
    'update_location_button',
    'location-directions-button',
    'Directions Button',
    'Edit the text on the button that opens the business location in Google Maps.',
    'Publish Directions Button',
    [
        'location_directions_label',
    ],
    $locationRecords,
    $locationConfiguration
);