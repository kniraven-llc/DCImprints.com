<?php

declare(strict_types=1);

/*
 * Footer administration partial.
 *
 * Handles:
 * - The large call to action immediately above the footer
 * - The short business summary beside the footer logo
 *
 * The footer quote button uses the shared quote-button text from Site
 * Settings.
 */

/**
 * @return array<string, array<string, mixed>>
 */
function dc_admin_footer_content_records(): array
{
    $records = [];

    foreach (
        dc_content_records('footer')
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
function dc_admin_render_footer_form(
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
                            'footer_'
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
        === 'update_footer_cta'
    ) {
        dc_admin_update_content_fields(
            $postedContent,
            [
                'footer_cta_eyebrow',
                'footer_cta_heading',
                'footer_cta_text',
            ],
            'footer'
        );
    }

    if (
        $action
        === 'update_footer_summary'
    ) {
        dc_admin_update_content_fields(
            $postedContent,
            [
                'footer_summary',
            ],
            'footer'
        );
    }

    return;
}

$footerRecords =
    dc_admin_footer_content_records();

$footerConfiguration = [
    'footer_cta_eyebrow' => [
        'label' =>
            'Small label above the footer call-to-action heading',

        'textarea' =>
            false,
    ],

    'footer_cta_heading' => [
        'label' =>
            'Footer call-to-action heading',

        'textarea' =>
            false,
    ],

    'footer_cta_text' => [
        'label' =>
            'Footer call-to-action paragraph',

        'textarea' =>
            true,

        'rows' =>
            4,

        'help' =>
            'The button beside this text uses the shared quote-button wording from Site Settings.',
    ],

    'footer_summary' => [
        'label' =>
            'Short business summary beside the footer logo',

        'textarea' =>
            true,

        'rows' =>
            5,

        'help' =>
            'Keep this concise because it appears in the compact footer area.',
    ],
];

dc_admin_render_footer_form(
    'update_footer_cta',
    'footer-call-to-action',
    'Footer Call to Action',
    'Edit the large project prompt shown immediately above the main footer.',
    'Publish Footer Call to Action',
    [
        'footer_cta_eyebrow',
        'footer_cta_heading',
        'footer_cta_text',
    ],
    $footerRecords,
    $footerConfiguration
);

dc_admin_render_footer_form(
    'update_footer_summary',
    'footer-business-summary',
    'Footer Business Summary',
    'Edit the short business description shown beside the footer logo.',
    'Publish Footer Summary',
    [
        'footer_summary',
    ],
    $footerRecords,
    $footerConfiguration
);