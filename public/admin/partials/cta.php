<?php

declare(strict_types=1);

/*
 * Call-to-Action administration partial.
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
        $action
        === 'update_cta'
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
                'quote_band_heading',
                'quote_band_text',
            ],
            'cta'
        );
    }

    return;
}

$ctaRecords = [];

foreach (
    dc_content_records(
        'services_callout'
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
                'quote_band_heading',
                'quote_band_text',
            ],
            true
        )
    ) {
        $ctaRecords[$key] =
            $record;
    }
}

$ctaFields = [
    'quote_band_heading' => [
        'label' =>
            'Call-to-action heading',

        'help' =>
            'The heading shown in the project prompt directly beneath the service cards.',

        'rows' =>
            2,
    ],

    'quote_band_text' => [
        'label' =>
            'Call-to-action paragraph',

        'help' =>
            'The supporting text shown beneath the heading.',

        'rows' =>
            4,
    ],
];

?>
<section
    class="card border-0 shadow-sm mb-4 admin-section-copy"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            Call-to-Action Text
        </h2>

        <p class="small text-body-secondary mb-0">
            This prompt appears directly beneath the service cards. Its button
            uses the shared quote-button text from Site Settings.
        </p>
    </div>

    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="update_cta"
            >

            <div class="row g-4">
                <?php foreach (
                    $ctaFields
                    as $key => $configuration
                ): ?>
                    <?php
                    $record =
                        $ctaRecords[$key]
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
                        'cta_'
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

                        <textarea
                            class="form-control"
                            id="<?= e(
                                $fieldId
                            ) ?>"
                            name="content[<?= e(
                                $key
                            ) ?>]"
                            rows="<?= e(
                                (string) $configuration[
                                    'rows'
                                ]
                            ) ?>"
                            maxlength="<?= e(
                                (string) $maximum
                            ) ?>"
                            data-character-count
                            required
                        ><?= e($value) ?></textarea>

                        <div class="form-text">
                            <?= e(
                                (string) $configuration[
                                    'help'
                                ]
                            ) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button
                class="btn btn-primary mt-4"
                type="submit"
            >
                Publish Call to Action
            </button>
        </form>
    </div>
</section>