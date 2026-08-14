<?php

declare(strict_types=1);

/*
 * Theme administration partial.
 *
 * This conversion preserves the existing theme records and public theme CSS.
 * White Yeti and Bigfoot Black will replace the current alternative themes
 * in the next stage.
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

    if ($action === 'set_theme') {
        $themeId =
            (int) (
                $_POST['theme_id']
                ?? 0
            );

        $selectedTheme = null;

        foreach (
            dc_available_themes(true)
            as $theme
        ) {
            if (
                (int) (
                    $theme['id']
                    ?? 0
                ) === $themeId
            ) {
                $selectedTheme = $theme;

                break;
            }
        }

        if ($selectedTheme === null) {
            flash(
                'error',
                'The selected theme is not available.'
            );

            dc_admin_redirect('theme');
        }

        dc_admin_finish(
            dc_set_active_theme($themeId),
            'Website theme published.',
            'The selected theme could not be published.',
            'theme'
        );
    }

    return;
}

$themes =
    dc_available_themes(true);

$currentThemeId =
    (int) (
        $activeTheme['id']
        ?? 0
    );

?>
<?php if ($themes === []): ?>
    <div
        class="alert alert-warning"
        role="alert"
    >
        No website themes are currently available.
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($themes as $theme): ?>
            <?php
            $themeId =
                (int) (
                    $theme['id']
                    ?? 0
                );

            $isActiveTheme =
                $themeId
                === $currentThemeId;
            ?>

            <div class="col-md-6 col-xl-4">
                <article
                    class="card shadow-sm h-100<?= $isActiveTheme
                        ? ' border-primary'
                        : ' border-0' ?>"
                >
                    <div class="card-body p-4">
                        <div
                            class="admin-theme-preview mb-3"
                            style="
                                --theme-primary:
                                    <?= e(
                                        (string) (
                                            $theme[
                                                'primary_color'
                                            ]
                                            ?? '#8f5f32'
                                        )
                                    ) ?>;
                                --theme-page:
                                    <?= e(
                                        (string) (
                                            $theme[
                                                'page_background_color'
                                            ]
                                            ?? '#ffffff'
                                        )
                                    ) ?>;
                                --theme-footer:
                                    <?= e(
                                        (string) (
                                            $theme[
                                                'footer_background_color'
                                            ]
                                            ?? '#211a16'
                                        )
                                    ) ?>;
                                --theme-text:
                                    <?= e(
                                        (string) (
                                            $theme[
                                                'charcoal_color'
                                            ]
                                            ?? '#24201c'
                                        )
                                    ) ?>;
                            "
                            aria-hidden="true"
                        >
                            <div
                                class="admin-theme-preview__header"
                            ></div>

                            <div
                                class="admin-theme-preview__body"
                            >
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>

                            <div
                                class="admin-theme-preview__footer"
                            ></div>
                        </div>

                        <div
                            class="d-flex flex-wrap gap-2 mb-3"
                            aria-hidden="true"
                        >
                            <?php foreach (
                                [
                                    'primary_color',
                                    'primary_dark_color',
                                    'primary_light_color',
                                    'primary_soft_color',
                                    'charcoal_color',
                                ]
                                as $colorKey
                            ): ?>
                                <span
                                    class="rounded-circle border"
                                    style="
                                        width: 2rem;
                                        height: 2rem;
                                        background:
                                            <?= e(
                                                (string) (
                                                    $theme[
                                                        $colorKey
                                                    ]
                                                    ?? '#ffffff'
                                                )
                                            ) ?>;
                                    "
                                ></span>
                            <?php endforeach; ?>
                        </div>

                        <h2 class="h5">
                            <?= e(
                                (string) (
                                    $theme['name']
                                    ?? 'Website Theme'
                                )
                            ) ?>
                        </h2>

                        <?php if (
                            trim(
                                (string) (
                                    $theme['description']
                                    ?? ''
                                )
                            ) !== ''
                        ): ?>
                            <p class="text-body-secondary">
                                <?= e(
                                    (string) $theme[
                                        'description'
                                    ]
                                ) ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($isActiveTheme): ?>
                            <span class="badge text-bg-success">
                                Current Theme
                            </span>
                        <?php else: ?>
                            <form method="post">
                                <?= csrf_field() ?>

                                <input
                                    type="hidden"
                                    name="action"
                                    value="set_theme"
                                >

                                <input
                                    type="hidden"
                                    name="theme_id"
                                    value="<?= $themeId ?>"
                                >

                                <button
                                    class="btn btn-primary"
                                    type="submit"
                                >
                                    Publish This Theme
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>