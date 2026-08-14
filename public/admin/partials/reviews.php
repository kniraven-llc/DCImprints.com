<?php

declare(strict_types=1);

/*
 * Google Reviews administration partial.
 *
 * Handles:
 * - Google Reviews section introduction
 * - Google Reviews destination link
 * - Creating reviews
 * - Editing reviews
 * - Review rating and silhouette
 * - Visibility
 * - Display order
 * - Deletion
 *
 * All records managed here are automatically identified as Google Reviews.
 */

function dc_admin_reviews_find(
    int $testimonialId
): ?array {
    foreach (
        dc_testimonials(false, true)
        as $testimonial
    ) {
        if (
            (int) (
                $testimonial['id']
                ?? 0
            ) === $testimonialId
        ) {
            return $testimonial;
        }
    }

    return null;
}

/**
 * @return array<int, int>
 */
function dc_admin_review_ids(): array
{
    return array_values(
        array_map(
            static fn (
                array $testimonial
            ): int =>
                (int) (
                    $testimonial['id']
                    ?? 0
                ),
            dc_testimonials(false, true)
        )
    );
}

function dc_admin_reorder_review(
    int $testimonialId,
    string $direction
): bool {
    $ids =
        dc_admin_review_ids();

    $index =
        array_search(
            $testimonialId,
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

    return dc_reorder_testimonials(
        $ids
    );
}

function dc_admin_review_rating(
    mixed $value
): int {
    $rating =
        (int) $value;

    return min(
        5,
        max(
            1,
            $rating
        )
    );
}

function dc_admin_review_avatar(
    mixed $value
): string {
    $avatarStyle =
        trim(
            (string) $value
        );

    $allowedStyles = [
        'neutral',
        'masculine',
        'feminine',
    ];

    return in_array(
        $avatarStyle,
        $allowedStyles,
        true
    )
        ? $avatarStyle
        : 'neutral';
}

function dc_admin_validate_review(
    string $reviewerName,
    string $reviewText
): ?string {
    if ($reviewerName === '') {
        return 'Reviewer name is required.';
    }

    if (
        mb_strlen($reviewerName)
        > 100
    ) {
        return 'Reviewer name must be 100 characters or fewer.';
    }

    if ($reviewText === '') {
        return 'Review text is required.';
    }

    if (
        mb_strlen($reviewText)
        > 1000
    ) {
        return 'Review text must be 1,000 characters or fewer.';
    }

    return null;
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
     * Update the heading and introduction shown above the review cards.
     */
    if (
        $action
        === 'update_reviews_intro'
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
                'reviews_eyebrow',
                'reviews_heading',
                'reviews_intro',
            ],
            'reviews'
        );
    }

    /*
     * Update the public link leading to the full Google Reviews page.
     */
    if (
        $action
        === 'update_reviews_settings'
    ) {
        $reviewsUrl = trim(
            (string) (
                $_POST['google_reviews_url']
                ?? ''
            )
        );

        $reviewsLinkLabel = trim(
            (string) (
                $_POST['reviews_link_label']
                ?? ''
            )
        );

        if ($reviewsUrl === '') {
            flash(
                'error',
                'Google reviews page URL is required.'
            );

            dc_admin_redirect(
                'reviews',
                'google-reviews-link'
            );
        }

        if (
            mb_strlen($reviewsUrl)
            > 2048
        ) {
            flash(
                'error',
                'Google reviews page URL must be 2,048 characters or fewer.'
            );

            dc_admin_redirect(
                'reviews',
                'google-reviews-link'
            );
        }

        if (
            filter_var(
                $reviewsUrl,
                FILTER_VALIDATE_URL
            ) === false
        ) {
            flash(
                'error',
                'Enter a complete Google reviews URL beginning with http:// or https://.'
            );

            dc_admin_redirect(
                'reviews',
                'google-reviews-link'
            );
        }

        if ($reviewsLinkLabel === '') {
            flash(
                'error',
                'Google reviews link text is required.'
            );

            dc_admin_redirect(
                'reviews',
                'google-reviews-link'
            );
        }

        if (
            mb_strlen(
                $reviewsLinkLabel
            ) > 60
        ) {
            flash(
                'error',
                'Google reviews link text must be 60 characters or fewer.'
            );

            dc_admin_redirect(
                'reviews',
                'google-reviews-link'
            );
        }

        $settingsUpdated =
            dc_update_site_settings([
                'google_reviews_url' =>
                    $reviewsUrl,
            ]);

        $contentUpdated =
            dc_update_content_values([
                'reviews_link_label' =>
                    $reviewsLinkLabel,
            ]);

        dc_admin_finish(
            $settingsUpdated
                && $contentUpdated,
            'Google reviews link published.',
            'The Google reviews link could not be published.',
            'reviews',
            'google-reviews-link'
        );
    }

    /*
     * Create a new Google Review card.
     */
    if (
        $action
        === 'create_testimonial'
    ) {
        $reviewerName = trim(
            (string) (
                $_POST['reviewer_name']
                ?? ''
            )
        );

        $reviewText = trim(
            (string) (
                $_POST['review_text']
                ?? ''
            )
        );

        $validationError =
            dc_admin_validate_review(
                $reviewerName,
                $reviewText
            );

        if ($validationError !== null) {
            flash(
                'error',
                $validationError
            );

            dc_admin_redirect(
                'reviews',
                'add-review'
            );
        }

        $testimonialId =
            dc_create_testimonial([
                'reviewer_name' =>
                    $reviewerName,

                'review_text' =>
                    $reviewText,

                'rating' =>
                    dc_admin_review_rating(
                        $_POST['rating']
                        ?? 5
                    ),

                'source' =>
                    'Google Review',

                'avatar_style' =>
                    dc_admin_review_avatar(
                        $_POST['avatar_style']
                        ?? 'neutral'
                    ),

                'is_active' =>
                    isset(
                        $_POST[
                            'is_active'
                        ]
                    ),
            ]);

        dc_admin_finish(
            $testimonialId !== null,
            'Google review created.',
            'The Google review could not be created.',
            'reviews',
            $testimonialId !== null
                ? 'testimonial-' . $testimonialId
                : 'add-review'
        );
    }

    /*
     * Update an existing Google Review card.
     */
    if (
        $action
        === 'update_testimonial'
    ) {
        $testimonialId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        $testimonial =
            dc_admin_reviews_find(
                $testimonialId
            );

        if ($testimonial === null) {
            flash(
                'error',
                'The selected review could not be found.'
            );

            dc_admin_redirect(
                'reviews'
            );
        }

        $reviewerName = trim(
            (string) (
                $_POST['reviewer_name']
                ?? ''
            )
        );

        $reviewText = trim(
            (string) (
                $_POST['review_text']
                ?? ''
            )
        );

        $validationError =
            dc_admin_validate_review(
                $reviewerName,
                $reviewText
            );

        if ($validationError !== null) {
            flash(
                'error',
                $validationError
            );

            dc_admin_redirect(
                'reviews',
                'testimonial-' . $testimonialId
            );
        }

        dc_admin_finish(
            dc_update_testimonial(
                $testimonialId,
                [
                    'reviewer_name' =>
                        $reviewerName,

                    'review_text' =>
                        $reviewText,

                    'rating' =>
                        dc_admin_review_rating(
                            $_POST['rating']
                            ?? 5
                        ),

                    'source' =>
                        'Google Review',

                    'avatar_style' =>
                        dc_admin_review_avatar(
                            $_POST[
                                'avatar_style'
                            ]
                            ?? 'neutral'
                        ),

                    'is_active' =>
                        isset(
                            $_POST[
                                'is_active'
                            ]
                        ),
                ]
            ),
            'Google review updated.',
            'The Google review could not be updated.',
            'reviews',
            'testimonial-' . $testimonialId
        );
    }

    /*
     * Show or hide a review card.
     */
    if (
        $action
        === 'toggle_record'
        && (
            $_POST['record_type']
            ?? ''
        ) === 'testimonial'
    ) {
        $testimonialId =
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
            dc_set_testimonial_active(
                $testimonialId,
                $isActive
            ),
            $isActive
                ? 'Google review shown on the website.'
                : 'Google review hidden from the website.',
            'The review visibility could not be changed.',
            'reviews',
            'testimonial-' . $testimonialId
        );
    }

    /*
     * Move a review card up or down.
     */
    if (
        $action
        === 'move_record'
        && (
            $_POST['record_type']
            ?? ''
        ) === 'testimonial'
    ) {
        $testimonialId =
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
            dc_admin_reorder_review(
                $testimonialId,
                $direction
            ),
            'Google review order updated.',
            'The Google review order could not be updated.',
            'reviews',
            'testimonial-' . $testimonialId
        );
    }

    /*
     * Permanently delete a review card.
     */
    if (
        $action
        === 'delete_record'
        && (
            $_POST['record_type']
            ?? ''
        ) === 'testimonial'
    ) {
        $testimonialId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        dc_admin_finish(
            dc_delete_testimonial(
                $testimonialId
            ),
            'Google review permanently deleted.',
            'The Google review could not be deleted.',
            'reviews'
        );
    }

    return;
}

/*
 * Render mode begins here.
 */

$reviewContentRecords = [];

foreach (
    dc_content_records('reviews')
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
                'reviews_eyebrow',
                'reviews_heading',
                'reviews_intro',
            ],
            true
        )
    ) {
        $reviewContentRecords[$key] =
            $record;
    }
}

$reviewContentFields = [
    'reviews_eyebrow' => [
        'label' =>
            'Small label above the Google Reviews heading',

        'textarea' =>
            false,
    ],

    'reviews_heading' => [
        'label' =>
            'Google Reviews heading',

        'textarea' =>
            false,
    ],

    'reviews_intro' => [
        'label' =>
            'Introduction above the review cards',

        'textarea' =>
            true,

        'rows' =>
            4,
    ],
];

$testimonials =
    dc_testimonials(false, true);

?>
<section
    class="card border-0 shadow-sm mb-4 admin-section-copy"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            Google Reviews Introduction
        </h2>

        <p class="small text-body-secondary mb-0">
            Edit the heading and introduction above the Google review cards.
        </p>
    </div>

    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="update_reviews_intro"
            >

            <div class="row g-4">
                <?php foreach (
                    $reviewContentFields
                    as $key => $configuration
                ): ?>
                    <?php
                    $record =
                        $reviewContentRecords[
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
                        'reviews_'
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
                Publish Reviews Introduction
            </button>
        </form>
    </div>
</section>

<section
    class="card border-0 shadow-sm mb-4 admin-content-type-card"
    id="google-reviews-link"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            Google Reviews Link
        </h2>

        <p class="small text-body-secondary mb-0">
            Controls the link visitors use to open the complete Google Reviews
            page.
        </p>
    </div>

    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="update_reviews_settings"
            >

            <div class="row g-4">
                <div class="col-12">
                    <label
                        class="form-label"
                        for="google_reviews_url"
                    >
                        Google reviews page URL
                    </label>

                    <input
                        class="form-control"
                        id="google_reviews_url"
                        name="google_reviews_url"
                        type="url"
                        maxlength="2048"
                        data-character-count
                        value="<?= e(
                            (string) (
                                $siteSettings[
                                    'google_reviews_url'
                                ]
                                ?? ''
                            )
                        ) ?>"
                        placeholder="https://"
                        required
                    >

                    <div class="form-text">
                        Paste the complete public URL for the business’s Google
                        Reviews page.
                    </div>
                </div>

                <div class="col-12">
                    <label
                        class="form-label"
                        for="reviews_link_label"
                    >
                        Link text shown above the review cards
                    </label>

                    <input
                        class="form-control"
                        id="reviews_link_label"
                        name="reviews_link_label"
                        maxlength="60"
                        data-character-count
                        value="<?= e(
                            dc_content(
                                'reviews_link_label'
                            )
                        ) ?>"
                        required
                    >
                </div>
            </div>

            <button
                class="btn btn-primary mt-4"
                type="submit"
            >
                Publish Google Reviews Link
            </button>
        </form>
    </div>
</section>

<div
    class="admin-records-heading d-flex align-items-center justify-content-between gap-3 mb-3"
>
    <div>
        <p
            class="text-uppercase text-primary fw-semibold small mb-1"
        >
            Review Cards
        </p>

        <h2 class="h4 mb-0">
            Google Reviews
        </h2>
    </div>

    <span class="badge text-bg-secondary">
        <?= count($testimonials) ?> total
    </span>
</div>

<section
    class="card border-0 shadow-sm mb-4"
    id="add-review"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            Add a Google Review
        </h2>

        <p class="small text-body-secondary mb-0">
            Enter the reviewer’s name and approved review text. The source is
            automatically recorded as Google Review.
        </p>
    </div>

    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="create_testimonial"
            >

            <div class="row g-4">
                <div class="col-md-6">
                    <label
                        class="form-label"
                        for="new_reviewer_name"
                    >
                        Reviewer name
                    </label>

                    <input
                        class="form-control"
                        id="new_reviewer_name"
                        name="reviewer_name"
                        maxlength="100"
                        data-character-count
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label
                        class="form-label"
                        for="new_review_rating"
                    >
                        Rating
                    </label>

                    <select
                        class="form-select"
                        id="new_review_rating"
                        name="rating"
                    >
                        <?php for (
                            $rating = 5;
                            $rating >= 1;
                            $rating--
                        ): ?>
                            <option value="<?= $rating ?>">
                                <?= $rating ?>
                                <?= $rating === 1
                                    ? 'star'
                                    : 'stars' ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label
                        class="form-label"
                        for="new_review_avatar"
                    >
                        Reviewer silhouette
                    </label>

                    <select
                        class="form-select"
                        id="new_review_avatar"
                        name="avatar_style"
                    >
                        <option value="neutral">
                            Neutral
                        </option>

                        <option value="masculine">
                            Masculine
                        </option>

                        <option value="feminine">
                            Feminine
                        </option>
                    </select>
                </div>

                <div class="col-12">
                    <label
                        class="form-label"
                        for="new_review_text"
                    >
                        Review text
                    </label>

                    <textarea
                        class="form-control"
                        id="new_review_text"
                        name="review_text"
                        rows="5"
                        maxlength="1000"
                        data-character-count
                        required
                    ></textarea>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            id="new_review_active"
                            name="is_active"
                            type="checkbox"
                            value="1"
                            checked
                        >

                        <label
                            class="form-check-label"
                            for="new_review_active"
                        >
                            Show this review on the website immediately
                        </label>
                    </div>
                </div>
            </div>

            <button
                class="btn btn-primary mt-4"
                type="submit"
            >
                Create Google Review
            </button>
        </form>
    </div>
</section>

<?php if ($testimonials === []): ?>
    <div class="alert alert-light border">
        No Google review cards have been added.
    </div>
<?php else: ?>
    <div class="vstack gap-4">
        <?php foreach (
            $testimonials
            as $index => $testimonial
        ): ?>
            <?php
            $testimonialId =
                (int) $testimonial['id'];

            $isActive =
                (int) (
                    $testimonial[
                        'is_active'
                    ]
                    ?? 0
                ) === 1;

            $avatarStyle =
                dc_admin_review_avatar(
                    $testimonial[
                        'avatar_style'
                    ]
                    ?? 'neutral'
                );
            ?>

            <article
                class="card border-0 shadow-sm"
                id="testimonial-<?= $testimonialId ?>"
            >
                <div
                    class="card-header bg-white d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 py-3"
                >
                    <div>
                        <h3 class="h5 mb-1">
                            <?= e(
                                (string) $testimonial[
                                    'reviewer_name'
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

                        <span class="badge text-bg-light border">
                            Google Review
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
                                value="testimonial"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $testimonialId ?>"
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
                                value="testimonial"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $testimonialId ?>"
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
                                        $testimonials
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
                                value="testimonial"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $testimonialId ?>"
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
                    <form method="post">
                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="action"
                            value="update_testimonial"
                        >

                        <input
                            type="hidden"
                            name="record_id"
                            value="<?= $testimonialId ?>"
                        >

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label
                                    class="form-label"
                                    for="reviewer_<?= $testimonialId ?>"
                                >
                                    Reviewer name
                                </label>

                                <input
                                    class="form-control"
                                    id="reviewer_<?= $testimonialId ?>"
                                    name="reviewer_name"
                                    maxlength="100"
                                    data-character-count
                                    value="<?= e(
                                        (string) $testimonial[
                                            'reviewer_name'
                                        ]
                                    ) ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-3">
                                <label
                                    class="form-label"
                                    for="rating_<?= $testimonialId ?>"
                                >
                                    Rating
                                </label>

                                <select
                                    class="form-select"
                                    id="rating_<?= $testimonialId ?>"
                                    name="rating"
                                >
                                    <?php for (
                                        $rating = 5;
                                        $rating >= 1;
                                        $rating--
                                    ): ?>
                                        <option
                                            value="<?= $rating ?>"
                                            <?= (int) (
                                                $testimonial[
                                                    'rating'
                                                ]
                                                ?? 5
                                            ) === $rating
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= $rating ?>
                                            <?= $rating === 1
                                                ? 'star'
                                                : 'stars' ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label
                                    class="form-label"
                                    for="avatar_<?= $testimonialId ?>"
                                >
                                    Reviewer silhouette
                                </label>

                                <select
                                    class="form-select"
                                    id="avatar_<?= $testimonialId ?>"
                                    name="avatar_style"
                                >
                                    <?php foreach (
                                        [
                                            'neutral' =>
                                                'Neutral',

                                            'masculine' =>
                                                'Masculine',

                                            'feminine' =>
                                                'Feminine',
                                        ]
                                        as $value => $label
                                    ): ?>
                                        <option
                                            value="<?= e($value) ?>"
                                            <?= $avatarStyle
                                                === $value
                                                    ? 'selected'
                                                    : '' ?>
                                        >
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label
                                    class="form-label"
                                    for="review_text_<?= $testimonialId ?>"
                                >
                                    Review text
                                </label>

                                <textarea
                                    class="form-control"
                                    id="review_text_<?= $testimonialId ?>"
                                    name="review_text"
                                    rows="5"
                                    maxlength="1000"
                                    data-character-count
                                    required
                                ><?= e(
                                    (string) $testimonial[
                                        'review_text'
                                    ]
                                ) ?></textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        id="review_active_<?= $testimonialId ?>"
                                        name="is_active"
                                        type="checkbox"
                                        value="1"
                                        <?= $isActive
                                            ? 'checked'
                                            : '' ?>
                                    >

                                    <label
                                        class="form-check-label"
                                        for="review_active_<?= $testimonialId ?>"
                                    >
                                        Show this review on the website
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button
                            class="btn btn-primary mt-4"
                            type="submit"
                        >
                            Save Google Review
                        </button>
                    </form>

                    <hr class="my-4">

                    <form
                        method="post"
                        onsubmit="return confirm('Permanently delete this Google review?');"
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
                            value="testimonial"
                        >

                        <input
                            type="hidden"
                            name="record_id"
                            value="<?= $testimonialId ?>"
                        >

                        <button
                            class="btn btn-outline-danger btn-sm"
                            type="submit"
                        >
                            Delete Google Review
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>