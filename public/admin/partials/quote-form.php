<?php

declare(strict_types=1);

/*
 * Quote Form administration partial.
 *
 * Handles:
 * - Quote-form heading and introduction
 * - Quote-form submit-button text
 *
 * contact_intro remains synchronized with quote_form_intro so older public
 * templates continue receiving the same introductory text.
 */

/**
 * @return array<string, array<string, mixed>>
 */
function dc_admin_quote_content_records(): array
{
    $records = [];

    foreach (
        dc_content_records('quote')
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
 * Return the enforced character limit for a quote-form content field.
 *
 * contact_intro is mirrored into quote_form_intro, so it uses the smaller
 * limit when both records exist.
 *
 * @param array<string, array<string, mixed>> $records
 */
function dc_admin_quote_field_limit(
    string $key,
    array $records
): int {
    $maximum = isset(
        $records[$key]['max_length']
    ) && $records[$key]['max_length'] !== null
        ? max(
            1,
            (int) $records[$key]['max_length']
        )
        : 2000;

    if (
        $key === 'contact_intro'
        && isset(
            $records['quote_form_intro']
        )
    ) {
        $legacyMaximum = $records[
            'quote_form_intro'
        ]['max_length'] !== null
            ? max(
                1,
                (int) $records[
                    'quote_form_intro'
                ]['max_length']
            )
            : 2000;

        $maximum = min(
            $maximum,
            $legacyMaximum
        );
    }

    return $maximum;
}

/**
 * @param array<string, mixed> $postedContent
 * @param array<int, string> $keys
 * @param array<string, array<string, mixed>> $records
 */
function dc_admin_save_quote_content(
    array $postedContent,
    array $keys,
    array $records,
    string $successMessage,
    string $anchor
): never {
    $updates = [];

    foreach ($keys as $key) {
        $value = trim(
            (string) (
                $postedContent[$key]
                ?? ''
            )
        );

        $maximum =
            dc_admin_quote_field_limit(
                $key,
                $records
            );

        if ($value === '') {
            flash(
                'error',
                'Complete every field before publishing.'
            );

            dc_admin_redirect(
                'quote',
                $anchor
            );
        }

        if (
            mb_strlen($value)
            > $maximum
        ) {
            flash(
                'error',
                sprintf(
                    'The selected field must be %d characters or fewer.',
                    $maximum
                )
            );

            dc_admin_redirect(
                'quote',
                $anchor
            );
        }

        $updates[$key] = $value;
    }

    if (
        isset(
            $updates['contact_intro']
        )
    ) {
        $updates['quote_form_intro'] =
            $updates['contact_intro'];
    }

    dc_admin_finish(
        $updates !== []
            && dc_update_content_values(
                $updates
            ),
        $successMessage,
        'The Quote Form content could not be published.',
        'quote',
        $anchor
    );
}

/**
 * @param array<int, string> $keys
 * @param array<string, array<string, mixed>> $records
 * @param array<string, array<string, mixed>> $configuration
 */
function dc_admin_render_quote_form(
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

                        $maximum =
                            dc_admin_quote_field_limit(
                                $key,
                                $records
                            );

                        $value = (string) (
                            $record['content_value']
                            ?? dc_content($key)
                        );

                        $fieldId =
                            'quote_'
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

$quoteRecords =
    dc_admin_quote_content_records();

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
        === 'update_quote_intro'
    ) {
        dc_admin_save_quote_content(
            $postedContent,
            [
                'quote_eyebrow',
                'quote_heading',
                'contact_intro',
            ],
            $quoteRecords,
            'Quote Form introduction published.',
            'quote-form-introduction'
        );
    }

    if (
        $action
        === 'update_quote_button'
    ) {
        dc_admin_save_quote_content(
            $postedContent,
            [
                'quote_submit_label',
            ],
            $quoteRecords,
            'Quote Form send-button text published.',
            'quote-form-button'
        );
    }

    return;
}

$quoteConfiguration = [
    'quote_eyebrow' => [
        'label' =>
            'Small label above the Quote Form heading',

        'textarea' =>
            false,
    ],

    'quote_heading' => [
        'label' =>
            'Quote Form heading',

        'textarea' =>
            false,
    ],

    'contact_intro' => [
        'label' =>
            'Introduction above the Quote Form',

        'textarea' =>
            true,

        'rows' =>
            4,

        'help' =>
            'This paragraph appears directly above the form fields.',
    ],

    'quote_submit_label' => [
        'label' =>
            'Text on the button that sends the quote request',

        'textarea' =>
            false,

        'help' =>
            'This is the final submit button at the bottom of the Quote Form.',
    ],
];

dc_admin_render_quote_form(
    'update_quote_intro',
    'quote-form-introduction',
    'Quote Form Introduction',
    'Edit the heading and introduction shown above the form fields.',
    'Publish Quote Form Introduction',
    [
        'quote_eyebrow',
        'quote_heading',
        'contact_intro',
    ],
    $quoteRecords,
    $quoteConfiguration
);

dc_admin_render_quote_form(
    'update_quote_button',
    'quote-form-button',
    'Send Button',
    'Edit the text on the button that submits a completed quote request.',
    'Publish Send Button',
    [
        'quote_submit_label',
    ],
    $quoteRecords,
    $quoteConfiguration
);