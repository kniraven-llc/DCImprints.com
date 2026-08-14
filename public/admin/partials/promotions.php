<?php

declare(strict_types=1);

/*
 * Announcements and Promotions administration partial.
 *
 * Promotions are prioritized within their visual type. One currently
 * eligible Announcement, Special, Seasonal feature, and Important Notice
 * may therefore be displayed on the public website simultaneously.
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
        !in_array(
            $action,
            [
                'create_promotion',
                'update_promotion',
                'move_promotion',
                'delete_promotion',
            ],
            true
        )
    ) {
        return;
    }

    if ($action === 'create_promotion') {
        [
            $validatedPromotion,
            $validationError,
        ] = dc_validate_promotion($_POST);

        if (
            $validatedPromotion === null
            || $validationError !== null
        ) {
            flash(
                'error',
                $validationError
                    ?? 'The promotion could not be validated.'
            );

            dc_admin_redirect(
                'promotions',
                'add-promotion'
            );
        }

        $promotionId =
            dc_create_promotion($_POST);

        dc_admin_finish(
            $promotionId !== null,
            'Promotion created.',
            'The promotion could not be created.',
            'promotions',
            $promotionId !== null
                ? 'promotion-' . $promotionId
                : 'add-promotion'
        );
    }

    if ($action === 'update_promotion') {
        $promotionId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        if (
            dc_promotion(
                $promotionId,
                true
            ) === null
        ) {
            flash(
                'error',
                'The selected promotion could not be found.'
            );

            dc_admin_redirect('promotions');
        }

        [
            $validatedPromotion,
            $validationError,
        ] = dc_validate_promotion($_POST);

        if (
            $validatedPromotion === null
            || $validationError !== null
        ) {
            flash(
                'error',
                $validationError
                    ?? 'The promotion could not be validated.'
            );

            dc_admin_redirect(
                'promotions',
                'promotion-' . $promotionId
            );
        }

        dc_admin_finish(
            dc_update_promotion(
                $promotionId,
                $_POST
            ),
            'Promotion updated.',
            'The promotion could not be updated.',
            'promotions',
            'promotion-' . $promotionId
        );
    }

    if ($action === 'move_promotion') {
        $promotionId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        $direction = trim(
            (string) (
                $_POST['direction']
                ?? ''
            )
        );

        dc_admin_finish(
            dc_reorder_promotion(
                $promotionId,
                $direction
            ),
            'Promotion priority updated.',
            'The promotion priority could not be updated.',
            'promotions',
            'promotion-' . $promotionId
        );
    }

    if ($action === 'delete_promotion') {
        $promotionId =
            (int) (
                $_POST['record_id']
                ?? 0
            );

        dc_admin_finish(
            dc_delete_promotion(
                $promotionId
            ),
            'Promotion permanently deleted.',
            'The promotion could not be deleted.',
            'promotions'
        );
    }

    return;
}

$promotions =
    dc_promotions(true);

$currentPromotions =
    dc_current_promotions_by_type(true);

$currentPromotionIds = [];

foreach (
    $currentPromotions
    as $currentPromotion
) {
    $currentPromotionId =
        (int) (
            $currentPromotion['id']
            ?? 0
        );

    if ($currentPromotionId > 0) {
        $currentPromotionIds[] =
            $currentPromotionId;
    }
}

$currentPromotionIds =
    array_values(
        array_unique(
            $currentPromotionIds
        )
    );

$promotionTypes =
    dc_promotion_type_options();

$seasonalThemes =
    dc_seasonal_theme_options();

$promotionLimits =
    dc_promotion_limits();

$promotionTimezone =
    date_default_timezone_get();

?>

<section
    class="
        card
        border-0
        shadow-sm
        mb-4
        admin-content-type-card
    "
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            Current Promotion Features
        </h2>

        <p class="small text-body-secondary mb-0">
            Each promotion type controls a different public website
            feature. One eligible promotion from every type may be
            displayed simultaneously.
        </p>
    </div>

    <div class="card-body p-4">
        <div class="row g-3">
            <?php foreach (
                $promotionTypes
                as $typeKey => $type
            ): ?>
                <?php
                $activePromotion =
                    $currentPromotions[$typeKey]
                    ?? null;
                ?>

                <div class="col-md-6">
                    <article class="card h-100 border">
                        <div class="card-body">
                            <div
                                class="
                                    d-flex
                                    flex-wrap
                                    align-items-center
                                    gap-2
                                    mb-3
                                "
                            >
                                <span
                                    class="
                                        badge
                                        <?= $activePromotion !== null
                                            ? 'text-bg-success'
                                            : 'text-bg-secondary' ?>
                                    "
                                >
                                    <?= $activePromotion !== null
                                        ? 'Currently Displayed'
                                        : 'Nothing Displayed' ?>
                                </span>

                                <span
                                    class="
                                        badge
                                        text-bg-light
                                        border
                                    "
                                >
                                    <?= e(
                                        (string) (
                                            $type['label']
                                            ?? $typeKey
                                        )
                                    ) ?>
                                </span>

                                <?php if (
                                    $typeKey === 'seasonal'
                                    && $activePromotion !== null
                                ): ?>
                                    <span class="badge text-bg-warning">
                                        <?= e(
                                            (string) (
                                                $activePromotion[
                                                    'seasonal_theme_label'
                                                ]
                                                ?? 'Brand Seasonal'
                                            )
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (
                                $activePromotion === null
                            ): ?>
                                <p
                                    class="
                                        small
                                        text-body-secondary
                                        mb-0
                                    "
                                >
                                    No published promotion of this
                                    type is currently within its
                                    scheduled display period.
                                </p>
                            <?php else: ?>
                                <h3 class="h6 mb-2">
                                    <?= e(
                                        (string) (
                                            $activePromotion['title']
                                            ?? ''
                                        )
                                    ) ?>
                                </h3>

                                <p
                                    class="
                                        small
                                        text-body-secondary
                                        mb-3
                                    "
                                >
                                    <?= e(
                                        (string) (
                                            $activePromotion['message']
                                            ?? ''
                                        )
                                    ) ?>
                                </p>

                                <a
                                    class="
                                        btn
                                        btn-outline-primary
                                        btn-sm
                                    "
                                    href="#promotion-<?= (int) (
                                        $activePromotion['id']
                                        ?? 0
                                    ) ?>"
                                >
                                    Edit This Feature
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section
    class="
        card
        border-0
        shadow-sm
        mb-4
        admin-content-type-card
    "
    id="add-promotion"
>
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">
            Add an Announcement or Promotion
        </h2>

        <p class="small text-body-secondary mb-0">
            Leave both schedule fields blank to display the
            promotion whenever it is published and becomes the
            highest-priority active promotion of its type.
        </p>
    </div>

    <div class="card-body p-4">
        <form
            method="post"
            data-promotion-form
        >
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="create_promotion"
            >

            <div class="row g-4">
                <div class="col-lg-5">
                    <label
                        class="form-label"
                        for="new_promotion_type"
                    >
                        Promotion type
                    </label>

                    <select
                        class="form-select"
                        id="new_promotion_type"
                        name="promotion_type"
                        data-promotion-type-select
                        required
                    >
                        <?php foreach (
                            $promotionTypes
                            as $typeKey => $type
                        ): ?>
                            <option
                                value="<?= e($typeKey) ?>"
                            >
                                <?= e(
                                    (string) $type['label']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="form-text">
                        Each type uses a different public website
                        presentation.
                    </div>
                </div>

                <div class="col-lg-7">
                    <label
                        class="form-label"
                        for="new_promotion_title"
                    >
                        Title
                    </label>

                    <input
                        class="form-control"
                        id="new_promotion_title"
                        name="title"
                        maxlength="<?= (int) $promotionLimits['title'] ?>"
                        data-character-count
                        required
                    >

                    <div
                        class="form-text"
                        data-character-count-output
                    >
                        Maximum
                        <?= (int) $promotionLimits['title'] ?>
                        characters.
                    </div>
                </div>

                <div
                    class="col-12"
                    data-seasonal-theme-field
                    hidden
                >
                    <label
                        class="form-label"
                        for="new_promotion_seasonal_theme"
                    >
                        Seasonal visual preset
                    </label>

                    <select
                        class="form-select"
                        id="new_promotion_seasonal_theme"
                        name="seasonal_theme"
                    >
                        <?php foreach (
                            $seasonalThemes
                            as $themeKey => $theme
                        ): ?>
                            <option
                                value="<?= e($themeKey) ?>"
                            >
                                <?= e(
                                    (string) $theme['label']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="form-text">
                        Used only for Seasonal promotions. This
                        changes the Seasonal feature band without
                        replacing the active website theme.
                    </div>
                </div>

                <div class="col-12">
                    <label
                        class="form-label"
                        for="new_promotion_message"
                    >
                        Message
                    </label>

                    <textarea
                        class="form-control"
                        id="new_promotion_message"
                        name="message"
                        rows="4"
                        maxlength="<?= (int) $promotionLimits['message'] ?>"
                        data-character-count
                        required
                    ></textarea>

                    <div
                        class="form-text"
                        data-character-count-output
                    >
                        Maximum
                        <?= (int) $promotionLimits['message'] ?>
                        characters.
                    </div>
                </div>

                <div class="col-lg-5">
                    <label
                        class="form-label"
                        for="new_promotion_button_label"
                    >
                        Optional button text
                    </label>

                    <input
                        class="form-control"
                        id="new_promotion_button_label"
                        name="button_label"
                        maxlength="<?= (int) $promotionLimits['button_label'] ?>"
                        data-character-count
                        placeholder="Learn More"
                    >

                    <div
                        class="form-text"
                        data-character-count-output
                    >
                        Maximum
                        <?= (int) $promotionLimits['button_label'] ?>
                        characters.
                    </div>
                </div>

                <div class="col-lg-7">
                    <label
                        class="form-label"
                        for="new_promotion_button_url"
                    >
                        Optional button destination
                    </label>

                    <input
                        class="form-control"
                        id="new_promotion_button_url"
                        name="button_url"
                        maxlength="<?= (int) $promotionLimits['button_url'] ?>"
                        placeholder="#contact, /services, or https://"
                    >

                    <div class="form-text">
                        Button text and destination must either
                        both be completed or both be blank.
                    </div>
                </div>

                <div class="col-md-6">
                    <label
                        class="form-label"
                        for="new_promotion_starts_at"
                    >
                        Scheduled start
                    </label>

                    <input
                        class="form-control"
                        id="new_promotion_starts_at"
                        name="starts_at"
                        type="datetime-local"
                    >

                    <div class="form-text">
                        Optional. Uses
                        <?= e($promotionTimezone) ?>.
                    </div>
                </div>

                <div class="col-md-6">
                    <label
                        class="form-label"
                        for="new_promotion_ends_at"
                    >
                        Scheduled expiration
                    </label>

                    <input
                        class="form-control"
                        id="new_promotion_ends_at"
                        name="ends_at"
                        type="datetime-local"
                    >

                    <div class="form-text">
                        Optional. The promotion disappears
                        automatically after this time.
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            id="new_promotion_active"
                            name="is_active"
                            type="checkbox"
                            value="1"
                            checked
                        >

                        <label
                            class="form-check-label"
                            for="new_promotion_active"
                        >
                            Publish this promotion
                        </label>
                    </div>
                </div>
            </div>

            <button
                class="btn btn-primary mt-4"
                type="submit"
            >
                Create Promotion
            </button>
        </form>
    </div>
</section>

<div
    class="
        d-flex
        flex-column
        flex-sm-row
        align-items-sm-center
        justify-content-between
        gap-2
        mb-3
    "
>
    <div>
        <p
            class="
                text-uppercase
                text-primary
                fw-semibold
                small
                mb-1
            "
        >
            Display Priority
        </p>

        <h2 class="h4 mb-0">
            Saved Promotions
        </h2>

        <p class="small text-body-secondary mb-0 mt-1">
            Priority determines which record is used when multiple
            eligible promotions share the same type.
        </p>
    </div>

    <span class="badge text-bg-secondary">
        <?= count($promotions) ?> total
    </span>
</div>

<?php if ($promotions === []): ?>
    <div class="alert alert-light border">
        No promotions have been created yet.
    </div>
<?php else: ?>
    <div class="vstack gap-4">
        <?php foreach (
            $promotions
            as $index => $promotion
        ): ?>
            <?php
            $promotionId =
                (int) (
                    $promotion['id']
                    ?? 0
                );

            $promotionType =
                (string) (
                    $promotion['promotion_type']
                    ?? 'announcement'
                );

            $isSeasonalPromotion =
                $promotionType === 'seasonal';

            $isCurrentPromotion =
                in_array(
                    $promotionId,
                    $currentPromotionIds,
                    true
                );

            $status =
                (string) (
                    $promotion['status']
                    ?? 'hidden'
                );

            if ($isCurrentPromotion) {
                $statusLabel =
                    'Currently Displayed';

                $statusBadge =
                    'text-bg-success';
            } elseif ($status === 'active') {
                $statusLabel =
                    'Eligible — Same Type Has Priority';

                $statusBadge =
                    'text-bg-primary';
            } else {
                $statusLabel =
                    dc_promotion_status_label(
                        $status
                    );

                $statusBadge =
                    dc_promotion_status_badge(
                        $status
                    );
            }

            $startsAt =
                (string) (
                    $promotion['starts_at']
                    ?? ''
                );

            $endsAt =
                (string) (
                    $promotion['ends_at']
                    ?? ''
                );

            $seasonalTheme =
                (string) (
                    $promotion['seasonal_theme']
                    ?? 'default'
                );

            $seasonalThemeLabel =
                (string) (
                    $promotion['seasonal_theme_label']
                    ?? (
                        $seasonalThemes[
                            $seasonalTheme
                        ]['label']
                        ?? 'Brand Seasonal'
                    )
                );
            ?>

            <article
                class="
                    card
                    border-0
                    shadow-sm
                "
                id="promotion-<?= $promotionId ?>"
            >
                <div
                    class="
                        card-header
                        bg-white
                        d-flex
                        flex-column
                        flex-xl-row
                        align-items-xl-center
                        justify-content-between
                        gap-3
                        py-3
                    "
                >
                    <div>
                        <div
                            class="
                                d-flex
                                flex-wrap
                                align-items-center
                                gap-2
                                mb-2
                            "
                        >
                            <span
                                class="
                                    badge
                                    <?= e($statusBadge) ?>
                                "
                            >
                                <?= e($statusLabel) ?>
                            </span>

                            <span
                                class="
                                    badge
                                    text-bg-light
                                    border
                                "
                            >
                                <?= e(
                                    (string) (
                                        $promotion['type_label']
                                        ?? 'Announcement'
                                    )
                                ) ?>
                            </span>

                            <?php if (
                                $isSeasonalPromotion
                            ): ?>
                                <span class="badge text-bg-warning">
                                    <?= e($seasonalThemeLabel) ?>
                                </span>
                            <?php endif; ?>

                            <span
                                class="
                                    badge
                                    text-bg-light
                                    border
                                "
                            >
                                Priority
                                <?= $index + 1 ?>
                            </span>
                        </div>

                        <h3 class="h5 mb-0">
                            <?= e(
                                (string) (
                                    $promotion['title']
                                    ?? ''
                                )
                            ) ?>
                        </h3>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <form method="post">
                            <?= csrf_field() ?>

                            <input
                                type="hidden"
                                name="action"
                                value="move_promotion"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $promotionId ?>"
                            >

                            <input
                                type="hidden"
                                name="direction"
                                value="up"
                            >

                            <button
                                class="
                                    btn
                                    btn-outline-secondary
                                    btn-sm
                                "
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
                                value="move_promotion"
                            >

                            <input
                                type="hidden"
                                name="record_id"
                                value="<?= $promotionId ?>"
                            >

                            <input
                                type="hidden"
                                name="direction"
                                value="down"
                            >

                            <button
                                class="
                                    btn
                                    btn-outline-secondary
                                    btn-sm
                                "
                                type="submit"
                                <?= $index
                                    === count($promotions) - 1
                                    ? 'disabled'
                                    : '' ?>
                            >
                                Move Down
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form
                        method="post"
                        data-promotion-form
                    >
                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="action"
                            value="update_promotion"
                        >

                        <input
                            type="hidden"
                            name="record_id"
                            value="<?= $promotionId ?>"
                        >

                        <div class="row g-4">
                            <div class="col-lg-5">
                                <label
                                    class="form-label"
                                    for="promotion_type_<?= $promotionId ?>"
                                >
                                    Promotion type
                                </label>

                                <select
                                    class="form-select"
                                    id="promotion_type_<?= $promotionId ?>"
                                    name="promotion_type"
                                    data-promotion-type-select
                                    required
                                >
                                    <?php foreach (
                                        $promotionTypes
                                        as $typeKey => $type
                                    ): ?>
                                        <option
                                            value="<?= e($typeKey) ?>"
                                            <?= $promotionType
                                                === $typeKey
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= e(
                                                (string) $type['label']
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-lg-7">
                                <label
                                    class="form-label"
                                    for="promotion_title_<?= $promotionId ?>"
                                >
                                    Title
                                </label>

                                <input
                                    class="form-control"
                                    id="promotion_title_<?= $promotionId ?>"
                                    name="title"
                                    maxlength="<?= (int) $promotionLimits['title'] ?>"
                                    data-character-count
                                    value="<?= e(
                                        (string) (
                                            $promotion['title']
                                            ?? ''
                                        )
                                    ) ?>"
                                    required
                                >

                                <div
                                    class="form-text"
                                    data-character-count-output
                                >
                                    Maximum
                                    <?= (int) $promotionLimits['title'] ?>
                                    characters.
                                </div>
                            </div>

                            <div
                                class="col-12"
                                data-seasonal-theme-field
                                <?= $isSeasonalPromotion
                                    ? ''
                                    : 'hidden' ?>
                            >
                                <label
                                    class="form-label"
                                    for="promotion_seasonal_theme_<?= $promotionId ?>"
                                >
                                    Seasonal visual preset
                                </label>

                                <select
                                    class="form-select"
                                    id="promotion_seasonal_theme_<?= $promotionId ?>"
                                    name="seasonal_theme"
                                >
                                    <?php foreach (
                                        $seasonalThemes
                                        as $themeKey => $theme
                                    ): ?>
                                        <option
                                            value="<?= e($themeKey) ?>"
                                            <?= $seasonalTheme
                                                === $themeKey
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= e(
                                                (string) $theme['label']
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <div class="form-text">
                                    Used only for Seasonal promotions.
                                </div>
                            </div>

                            <div class="col-12">
                                <label
                                    class="form-label"
                                    for="promotion_message_<?= $promotionId ?>"
                                >
                                    Message
                                </label>

                                <textarea
                                    class="form-control"
                                    id="promotion_message_<?= $promotionId ?>"
                                    name="message"
                                    rows="4"
                                    maxlength="<?= (int) $promotionLimits['message'] ?>"
                                    data-character-count
                                    required
                                ><?= e(
                                    (string) (
                                        $promotion['message']
                                        ?? ''
                                    )
                                ) ?></textarea>

                                <div
                                    class="form-text"
                                    data-character-count-output
                                >
                                    Maximum
                                    <?= (int) $promotionLimits['message'] ?>
                                    characters.
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <label
                                    class="form-label"
                                    for="promotion_button_label_<?= $promotionId ?>"
                                >
                                    Optional button text
                                </label>

                                <input
                                    class="form-control"
                                    id="promotion_button_label_<?= $promotionId ?>"
                                    name="button_label"
                                    maxlength="<?= (int) $promotionLimits['button_label'] ?>"
                                    data-character-count
                                    value="<?= e(
                                        (string) (
                                            $promotion['button_label']
                                            ?? ''
                                        )
                                    ) ?>"
                                >

                                <div
                                    class="form-text"
                                    data-character-count-output
                                >
                                    Maximum
                                    <?= (int) $promotionLimits['button_label'] ?>
                                    characters.
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <label
                                    class="form-label"
                                    for="promotion_button_url_<?= $promotionId ?>"
                                >
                                    Optional button destination
                                </label>

                                <input
                                    class="form-control"
                                    id="promotion_button_url_<?= $promotionId ?>"
                                    name="button_url"
                                    maxlength="<?= (int) $promotionLimits['button_url'] ?>"
                                    value="<?= e(
                                        (string) (
                                            $promotion['button_url']
                                            ?? ''
                                        )
                                    ) ?>"
                                    placeholder="#contact, /services, or https://"
                                >
                            </div>

                            <div class="col-md-6">
                                <label
                                    class="form-label"
                                    for="promotion_starts_at_<?= $promotionId ?>"
                                >
                                    Scheduled start
                                </label>

                                <input
                                    class="form-control"
                                    id="promotion_starts_at_<?= $promotionId ?>"
                                    name="starts_at"
                                    type="datetime-local"
                                    value="<?= e(
                                        dc_promotion_datetime_input_value(
                                            $startsAt
                                        )
                                    ) ?>"
                                >

                                <?php if ($startsAt !== ''): ?>
                                    <div class="form-text">
                                        Starts
                                        <?= e(
                                            dc_promotion_format_datetime(
                                                $startsAt
                                            )
                                        ) ?>.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label
                                    class="form-label"
                                    for="promotion_ends_at_<?= $promotionId ?>"
                                >
                                    Scheduled expiration
                                </label>

                                <input
                                    class="form-control"
                                    id="promotion_ends_at_<?= $promotionId ?>"
                                    name="ends_at"
                                    type="datetime-local"
                                    value="<?= e(
                                        dc_promotion_datetime_input_value(
                                            $endsAt
                                        )
                                    ) ?>"
                                >

                                <?php if ($endsAt !== ''): ?>
                                    <div class="form-text">
                                        Expires
                                        <?= e(
                                            dc_promotion_format_datetime(
                                                $endsAt
                                            )
                                        ) ?>.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        id="promotion_active_<?= $promotionId ?>"
                                        name="is_active"
                                        type="checkbox"
                                        value="1"
                                        <?= (int) (
                                            $promotion['is_active']
                                            ?? 0
                                        ) === 1
                                            ? 'checked'
                                            : '' ?>
                                    >

                                    <label
                                        class="form-check-label"
                                        for="promotion_active_<?= $promotionId ?>"
                                    >
                                        Publish this promotion
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button
                            class="btn btn-primary mt-4"
                            type="submit"
                        >
                            Save Promotion
                        </button>
                    </form>

                    <hr class="my-4">

                    <form
                        method="post"
                        onsubmit="
                            return confirm(
                                'Permanently delete this promotion?'
                            );
                        "
                    >
                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="action"
                            value="delete_promotion"
                        >

                        <input
                            type="hidden"
                            name="record_id"
                            value="<?= $promotionId ?>"
                        >

                        <button
                            class="
                                btn
                                btn-outline-danger
                                btn-sm
                            "
                            type="submit"
                        >
                            Delete Promotion
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
(() => {
    'use strict';

    document
        .querySelectorAll(
            '[data-promotion-form]'
        )
        .forEach((form) => {
            const typeSelect =
                form.querySelector(
                    '[data-promotion-type-select]'
                );

            const seasonalField =
                form.querySelector(
                    '[data-seasonal-theme-field]'
                );

            if (
                !typeSelect
                || !seasonalField
            ) {
                return;
            }

            const updateSeasonalField = () => {
                seasonalField.hidden =
                    typeSelect.value
                    !== 'seasonal';
            };

            typeSelect.addEventListener(
                'change',
                updateSeasonalField
            );

            updateSeasonalField();
        });
})();
</script>