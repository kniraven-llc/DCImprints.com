<?php

declare(strict_types=1);

/*
 * Call-to-Action administration partial.
 *
 * The public call-to-action area is the "How It Works" process displayed
 * directly beneath the service cards.
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
        === 'update_how_it_works'
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
                'how_it_works_eyebrow',
                'how_it_works_heading',
                'how_it_works_step_1_title',
                'how_it_works_step_1_text',
                'how_it_works_step_2_title',
                'how_it_works_step_2_text',
                'how_it_works_step_3_title',
                'how_it_works_step_3_text',
                'how_it_works_step_4_title',
                'how_it_works_step_4_text',
                'how_it_works_button_label',
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
                'how_it_works_eyebrow',
                'how_it_works_heading',
                'how_it_works_step_1_title',
                'how_it_works_step_1_text',
                'how_it_works_step_2_title',
                'how_it_works_step_2_text',
                'how_it_works_step_3_title',
                'how_it_works_step_3_text',
                'how_it_works_step_4_title',
                'how_it_works_step_4_text',
                'how_it_works_button_label',
            ],
            true
        )
    ) {
        $ctaRecords[$key] =
            $record;
    }
}

$ctaFields = [
    'how_it_works_eyebrow' => [
        'label' =>
            'Small label above the How It Works heading',

        'textarea' =>
            false,
    ],

    'how_it_works_heading' => [
        'label' =>
            'How It Works heading',

        'textarea' =>
            false,
    ],

    'how_it_works_step_1_title' => [
        'label' =>
            'Step 1 title',

        'textarea' =>
            false,
    ],

    'how_it_works_step_1_text' => [
        'label' =>
            'Step 1 description',

        'textarea' =>
            true,

        'rows' =>
            3,
    ],

    'how_it_works_step_2_title' => [
        'label' =>
            'Step 2 title',

        'textarea' =>
            false,
    ],

    'how_it_works_step_2_text' => [
        'label' =>
            'Step 2 description',

        'textarea' =>
            true,

        'rows' =>
            3,
    ],

    'how_it_works_step_3_title' => [
        'label' =>
            'Step 3 title',

        'textarea' =>
            false,
    ],

    'how_it_works_step_3_text' => [
        'label' =>
            'Step 3 description',

        'textarea' =>
            true,

        'rows' =>
            3,
    ],

    'how_it_works_step_4_title' => [
        'label' =>
            'Step 4 title',

        'textarea' =>
            false,
    ],

    'how_it_works_step_4_text' => [
        'label' =>
            'Step 4 description',

        'textarea' =>
            true,

        'rows' =>
            3,
    ],

    'how_it_works_button_label' => [
        'label' =>
            'Start-project button label',

        'textarea' =>
            false,
    ],
];

?>
<section
    class="card border-0 shadow-sm mb-4 admin-section-copy"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            How It Works
        </h2>

        <p class="small text-body-secondary mb-0">
            Edit the four-step process shown directly beneath the service
            cards. The button always opens the Request a Quote form.
        </p>
    </div>

    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="update_how_it_works"
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

                    <div
                        class="<?= str_contains(
                            $key,
                            '_step_'
                        )
                            ? 'col-lg-6'
                            : 'col-12' ?>"
                    >
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
                Save How It Works to Draft
            </button>
        </form>
    </div>
</section>