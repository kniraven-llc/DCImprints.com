<?php

/*
 * Hero Section administration partial.
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
            'hero',
            [
                'hero_image',
                'hero_video',
            ]
        )
    ) {
        return;
    }

    if (
        $action
        === 'update_hero'
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
                'home_heading',
                'home_intro',
                'hero_secondary_button_label',
            ],
            'hero'
        );
    }

    return;
}

$heroRecords = [];

foreach (
    dc_content_records('hero')
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
                'home_heading',
                'home_intro',
                'hero_secondary_button_label',
            ],
            true
        )
    ) {
        $heroRecords[$key] =
            $record;
    }
}

$heroFields = [
    'home_heading' => [
        'label' =>
            'Main hero heading',

        'help' =>
            'The large headline shown over the hero image or video.',

        'textarea' =>
            true,

        'rows' =>
            3,
    ],

    'home_intro' => [
        'label' =>
            'Hero introduction',

        'help' =>
            'The paragraph directly beneath the main hero heading.',

        'textarea' =>
            true,

        'rows' =>
            4,
    ],

    'hero_secondary_button_label' => [
        'label' =>
            'Text on the button that scrolls to Services',

        'help' =>
            'This is the secondary hero button. Quote buttons use the shared text in Site Settings.',

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
            Hero Text
        </h2>

        <p class="small text-body-secondary mb-0">
            Edit the opening headline, introduction, and Services button.
        </p>
    </div>

    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="update_hero"
            >

            <div class="row g-4">
                <?php foreach (
                    $heroFields
                    as $key => $configuration
                ): ?>
                    <?php
                    $record =
                        $heroRecords[$key]
                        ?? [];

                    $maximum =
                        isset(
                            $record['max_length']
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
                        'hero_'
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
                Publish Hero Text
            </button>
        </form>
    </div>
</section>

<?php
dc_admin_render_site_media_cards(
    [
        'hero_image',
        'hero_video',
    ],
    'hero',
    'Hero Media',
    'Choose the image or video shown behind the hero text.'
);
?>