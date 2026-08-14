<?php

declare(strict_types=1);

$promotionDisplayArea = trim(
    (string) (
        $promotionDisplayArea
        ?? ''
    )
);

$currentPromotions =
    dc_current_promotions_by_type();

$promotionExternalLink = static function (
    string $url
): bool {
    return preg_match(
        '/^https?:\/\//i',
        $url
    ) === 1;
};

if ($promotionDisplayArea === 'announcement') {
    $promotion =
        $currentPromotions['announcement']
        ?? null;

    if ($promotion === null) {
        return;
    }

    $promotionId =
        (int) ($promotion['id'] ?? 0);

    $buttonLabel = trim(
        (string) (
            $promotion['button_label']
            ?? ''
        )
    );

    $buttonUrl = trim(
        (string) (
            $promotion['button_url']
            ?? ''
        )
    );

    $isExternal =
        $promotionExternalLink($buttonUrl);
    ?>

    <section
        class="dc-promotion-announcement"
        aria-label="Current announcement"
        data-dc-announcement
        data-promotion-id="<?= $promotionId ?>"
    >
        <div class="dc-promotion-announcement__bar">
            <div class="container">
                <div class="dc-promotion-announcement__content">
                    <span
                        class="dc-promotion-announcement__icon"
                        aria-hidden="true"
                    >
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M4 10.5v3a2 2 0 0 0 2 2h1l1.2 4h2.6l-1.2-4H12l6 3V6l-6 3H6a2 2 0 0 0-2 1.5Z"
                            ></path>
                        </svg>
                    </span>

                    <div class="dc-promotion-announcement__copy">
                        <strong>
                            <?= e((string) $promotion['title']) ?>
                        </strong>

                        <span>
                            <?= e((string) $promotion['message']) ?>
                        </span>
                    </div>

                    <?php if (
                        $buttonLabel !== ''
                        && $buttonUrl !== ''
                    ): ?>
                        <a
                            class="dc-promotion-announcement__link"
                            href="<?= e($buttonUrl) ?>"
                            <?= $isExternal
                                ? 'target="_blank" rel="noopener noreferrer"'
                                : '' ?>
                        >
                            <?= e($buttonLabel) ?>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    <?php endif; ?>

                    <button
                        class="dc-promotion-announcement__close"
                        type="button"
                        aria-label="Dismiss announcement"
                        data-dc-dismiss-announcement
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <?php
    return;
}

if ($promotionDisplayArea === 'seasonal') {
    $promotion =
        $currentPromotions['seasonal']
        ?? null;

    if ($promotion === null) {
        return;
    }

    $promotionId =
        (int) (
            $promotion['id']
            ?? 0
        );

    $buttonLabel = trim(
        (string) (
            $promotion['button_label']
            ?? ''
        )
    );

    $buttonUrl = trim(
        (string) (
            $promotion['button_url']
            ?? ''
        )
    );

    $isExternal =
        $promotionExternalLink($buttonUrl);

    $seasonalThemes =
        dc_seasonal_theme_options();

    $seasonalTheme = trim(
        (string) (
            $promotion['seasonal_theme']
            ?? 'default'
        )
    );

    if (!isset($seasonalThemes[$seasonalTheme])) {
        $seasonalTheme = 'default';
    }

    $seasonalThemeDetails =
        dc_seasonal_theme_details(
            $seasonalTheme
        );

    $seasonalThemeClass = preg_replace(
        '/[^a-z0-9-]/',
        '',
        strtolower($seasonalTheme)
    );

    if (
        !is_string($seasonalThemeClass)
        || $seasonalThemeClass === ''
    ) {
        $seasonalThemeClass = 'default';
    }

    $seasonalEyebrow = trim(
        (string) (
            $promotion['seasonal_theme_eyebrow']
            ?? $seasonalThemeDetails['eyebrow']
        )
    );

    $seasonalIcon = trim(
        (string) (
            $promotion['seasonal_theme_icon']
            ?? $seasonalThemeDetails['icon']
        )
    );

    $seasonalTitleId =
        'seasonal-promotion-title-'
        . $promotionId;
    ?>

    <section
        class="
            dc-promotion-seasonal
            dc-promotion-seasonal--<?= e($seasonalThemeClass) ?>
        "
        aria-labelledby="<?= e($seasonalTitleId) ?>"
        data-seasonal-theme="<?= e($seasonalThemeClass) ?>"
    >
        <div
            class="dc-promotion-seasonal__decoration"
            aria-hidden="true"
        >
            <span
                class="
                    dc-promotion-seasonal__motif
                    dc-promotion-seasonal__motif--a
                "
            >
                <?= e($seasonalIcon) ?>
            </span>

            <span
                class="
                    dc-promotion-seasonal__motif
                    dc-promotion-seasonal__motif--b
                "
            >
                <?= e($seasonalIcon) ?>
            </span>

            <span
                class="
                    dc-promotion-seasonal__motif
                    dc-promotion-seasonal__motif--c
                "
            >
                <?= e($seasonalIcon) ?>
            </span>
        </div>

        <div class="container">
            <div class="dc-promotion-seasonal__panel">
                <div class="dc-promotion-seasonal__mark">
                    <span aria-hidden="true">
                        <?= e($seasonalIcon) ?>
                    </span>
                </div>

                <div class="dc-promotion-seasonal__copy">
                    <p class="dc-promotion-seasonal__eyebrow">
                        <?= e($seasonalEyebrow) ?>
                    </p>

                    <h2
                        class="dc-promotion-seasonal__title"
                        id="<?= e($seasonalTitleId) ?>"
                    >
                        <?= e(
                            (string) $promotion['title']
                        ) ?>
                    </h2>

                    <p class="dc-promotion-seasonal__message">
                        <?= e(
                            (string) $promotion['message']
                        ) ?>
                    </p>
                </div>

                <?php if (
                    $buttonLabel !== ''
                    && $buttonUrl !== ''
                ): ?>
                    <div class="dc-promotion-seasonal__action">
                        <a
                            class="
                                btn
                                btn-lg
                                dc-promotion-seasonal__button
                            "
                            href="<?= e($buttonUrl) ?>"
                            <?= $isExternal
                                ? 'target="_blank" rel="noopener noreferrer"'
                                : '' ?>
                        >
                            <?= e($buttonLabel) ?>

                            <span aria-hidden="true">
                                &rarr;
                            </span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php
    return;
}

if ($promotionDisplayArea !== 'global') {
    return;
}

$specialPromotion =
    $currentPromotions['special']
    ?? null;

$importantPromotion =
    $currentPromotions['important']
    ?? null;
?>

<?php if ($specialPromotion !== null): ?>
    <?php
    $specialId =
        (int) ($specialPromotion['id'] ?? 0);

    $specialButtonLabel = trim(
        (string) (
            $specialPromotion['button_label']
            ?? ''
        )
    );

    $specialButtonUrl = trim(
        (string) (
            $specialPromotion['button_url']
            ?? ''
        )
    );

    $specialIsExternal =
        $promotionExternalLink(
            $specialButtonUrl
        );
    ?>

    <button
        class="dc-promotion-special-trigger"
        type="button"
        data-bs-toggle="offcanvas"
        data-bs-target="#dc-special-promotion"
        aria-controls="dc-special-promotion"
    >
        <span
            class="dc-promotion-special-trigger__badge"
            aria-hidden="true"
        >
            %
        </span>

        <span class="dc-promotion-special-trigger__copy">
            <span>Current Special</span>

            <strong>
                <?= e((string) $specialPromotion['title']) ?>
            </strong>
        </span>

        <span
            class="dc-promotion-special-trigger__arrow"
            aria-hidden="true"
        >
            &rarr;
        </span>
    </button>

    <aside
        class="offcanvas offcanvas-end dc-promotion-special-drawer"
        tabindex="-1"
        id="dc-special-promotion"
        aria-labelledby="dc-special-promotion-title"
        data-promotion-id="<?= $specialId ?>"
    >
        <div class="dc-promotion-special-drawer__hero">
            <div class="offcanvas-header">
                <span class="dc-promotion-special-drawer__label">
                    Special Offer
                </span>

                <button
                    class="btn-close btn-close-white"
                    type="button"
                    data-bs-dismiss="offcanvas"
                    aria-label="Close special offer"
                ></button>
            </div>

            <div class="dc-promotion-special-drawer__symbol">
                <span aria-hidden="true">%</span>
            </div>
        </div>

        <div class="offcanvas-body dc-promotion-special-drawer__body">
            <p class="dc-promotion-special-drawer__eyebrow">
                Featured Promotion
            </p>

            <h2
                class="dc-promotion-special-drawer__title"
                id="dc-special-promotion-title"
            >
                <?= e((string) $specialPromotion['title']) ?>
            </h2>

            <p class="dc-promotion-special-drawer__message">
                <?= e((string) $specialPromotion['message']) ?>
            </p>

            <?php if (
                $specialButtonLabel !== ''
                && $specialButtonUrl !== ''
            ): ?>
                <a
                    class="btn btn-primary btn-lg w-100 mt-3"
                    href="<?= e($specialButtonUrl) ?>"
                    <?= $specialIsExternal
                        ? 'target="_blank" rel="noopener noreferrer"'
                        : '' ?>
                >
                    <?= e($specialButtonLabel) ?>
                </a>
            <?php endif; ?>
        </div>
    </aside>
<?php endif; ?>

<?php if ($importantPromotion !== null): ?>
    <?php
    $importantId =
        (int) ($importantPromotion['id'] ?? 0);

    $importantButtonLabel = trim(
        (string) (
            $importantPromotion['button_label']
            ?? ''
        )
    );

    $importantButtonUrl = trim(
        (string) (
            $importantPromotion['button_url']
            ?? ''
        )
    );

    $importantIsExternal =
        $promotionExternalLink(
            $importantButtonUrl
        );
    ?>

    <div
        class="modal fade dc-promotion-important-modal"
        id="dc-important-promotion"
        tabindex="-1"
        aria-labelledby="dc-important-promotion-title"
        aria-describedby="dc-important-promotion-message"
        data-dc-important-promotion
        data-promotion-id="<?= $importantId ?>"
    >
        <div
            class="
                modal-dialog
                modal-dialog-centered
                modal-dialog-scrollable
            "
        >
            <article class="modal-content">
                <div class="dc-promotion-important-modal__header">
                    <div class="dc-promotion-important-modal__icon">
                        <span aria-hidden="true">!</span>
                    </div>

                    <div>
                        <p class="dc-promotion-important-modal__eyebrow">
                            Important Notice
                        </p>

                        <h2
                            class="modal-title"
                            id="dc-important-promotion-title"
                        >
                            <?= e(
                                (string) $importantPromotion['title']
                            ) ?>
                        </h2>
                    </div>

                    <button
                        class="btn-close btn-close-white"
                        type="button"
                        data-bs-dismiss="modal"
                        aria-label="Close important notice"
                    ></button>
                </div>

                <div class="modal-body">
                    <p
                        class="dc-promotion-important-modal__message"
                        id="dc-important-promotion-message"
                    >
                        <?= e(
                            (string) $importantPromotion['message']
                        ) ?>
                    </p>
                </div>

                <div class="modal-footer">
                    <button
                        class="btn btn-outline-secondary"
                        type="button"
                        data-bs-dismiss="modal"
                    >
                        Close
                    </button>

                    <?php if (
                        $importantButtonLabel !== ''
                        && $importantButtonUrl !== ''
                    ): ?>
                        <a
                            class="btn btn-primary"
                            href="<?= e($importantButtonUrl) ?>"
                            <?= $importantIsExternal
                                ? 'target="_blank" rel="noopener noreferrer"'
                                : '' ?>
                        >
                            <?= e($importantButtonLabel) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </article>
        </div>
    </div>
<?php endif; ?>