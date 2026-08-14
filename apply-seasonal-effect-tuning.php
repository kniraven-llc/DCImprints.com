<?php

declare(strict_types=1);

$projectRoot = __DIR__;

$jsPath =
    $projectRoot
    . '/public/assets/js/seasonal-effects.js';

$cssPath =
    $projectRoot
    . '/public/assets/css/seasonal-effects.css';

$jsBackup =
    $jsPath
    . '.before-heart-firework-ghost-tuning';

$cssBackup =
    $cssPath
    . '.before-heart-firework-ghost-tuning';

function replaceOnce(
    string $text,
    string $old,
    string $new,
    string $label
): string {
    $count = substr_count(
        $text,
        $old
    );

    if ($count !== 1) {
        throw new RuntimeException(
            $label
            . ': expected one match, found '
            . $count
            . '.'
        );
    }

    return str_replace(
        $old,
        $new,
        $text
    );
}

function replaceWhenNeeded(
    string $text,
    string $old,
    string $new,
    string $label
): string {
    if (str_contains($text, $new)) {
        return $text;
    }

    return replaceOnce(
        $text,
        $old,
        $new,
        $label
    );
}

try {
    if (!is_file($jsPath)) {
        throw new RuntimeException(
            'Seasonal JavaScript file was not found.'
        );
    }

    if (!is_file($cssPath)) {
        throw new RuntimeException(
            'Seasonal CSS file was not found.'
        );
    }

    $js = file_get_contents($jsPath);
    $css = file_get_contents($cssPath);

    if (
        $js === false
        || strlen($js) < 1000
    ) {
        throw new RuntimeException(
            'Seasonal JavaScript is empty or unexpectedly small.'
        );
    }

    if (
        $css === false
        || strlen($css) < 1000
    ) {
        throw new RuntimeException(
            'Seasonal CSS is empty or unexpectedly small.'
        );
    }

    if (!copy($jsPath, $jsBackup)) {
        throw new RuntimeException(
            'Could not create the JavaScript backup.'
        );
    }

    if (!copy($cssPath, $cssBackup)) {
        throw new RuntimeException(
            'Could not create the CSS backup.'
        );
    }

    $js = str_replace(
        ["\r\n", "\r"],
        "\n",
        $js
    );

    $css = str_replace(
        ["\r\n", "\r"],
        "\n",
        $css
    );

    /*
     * Valentine hearts.
     */

    $js = replaceWhenNeeded(
        $js,
        'minimumSize: 0.65,',
        'minimumSize: 1.1,',
        'Valentine minimum heart size'
    );

    $js = replaceWhenNeeded(
        $js,
        'maximumSize: 1.35,',
        'maximumSize: 2.25,',
        'Valentine maximum heart size'
    );

    /*
     * Halloween mapping.
     */

    $js = replaceWhenNeeded(
        $js,
        "'halloween': 'bats',",
        "'halloween': 'ghosts',",
        'Halloween effect mapping'
    );

    /*
     * Halloween ghost generator.
     */

    if (
        !str_contains(
            $js,
            'const addGhosts = (count) => {'
        )
    ) {
        $ghostFunction = <<<'JAVASCRIPT'
    const addGhosts = (count) => {
        const ghostMarkup = [
            '<svg viewBox="0 0 64 76" aria-hidden="true" focusable="false">',
            '<path class="dc-seasonal-ghost__body" d="M32 3C17 3 7 15 7 31v38l8-8 8 8 9-8 9 8 8-8 8 8V31C57 15 47 3 32 3Z"/>',
            '<ellipse class="dc-seasonal-ghost__eye" cx="23" cy="31" rx="3.5" ry="5"/>',
            '<ellipse class="dc-seasonal-ghost__eye" cx="41" cy="31" rx="3.5" ry="5"/>',
            '<ellipse class="dc-seasonal-ghost__mouth" cx="32" cy="45" rx="4" ry="5.5"/>',
            '</svg>',
        ].join('');

        for (
            let index = 0;
            index < count;
            index += 1
        ) {
            const particle =
                document.createElement(
                    'span'
                );

            particle.className =
                'dc-seasonal-particle dc-seasonal-particle--ghost';

            particle.innerHTML =
                ghostMarkup;

            const leftToRight =
                Math.random() >= 0.5;

            const duration =
                random(14, 23);

            setParticleVariable(
                particle,
                '--particle-y',
                `${random(8, 58).toFixed(2)}vh`
            );

            setParticleVariable(
                particle,
                '--particle-size',
                `${random(
                    2.8,
                    5.2
                ).toFixed(2)}rem`
            );

            setParticleVariable(
                particle,
                '--particle-duration',
                `${duration.toFixed(2)}s`
            );

            setParticleVariable(
                particle,
                '--particle-delay',
                `${random(
                    -duration,
                    0
                ).toFixed(2)}s`
            );

            setParticleVariable(
                particle,
                '--particle-opacity',
                random(
                    0.24,
                    0.48
                ).toFixed(2)
            );

            setParticleVariable(
                particle,
                '--particle-from-x',
                leftToRight
                    ? '-14vw'
                    : '114vw'
            );

            setParticleVariable(
                particle,
                '--particle-quarter-x',
                leftToRight
                    ? '28vw'
                    : '72vw'
            );

            setParticleVariable(
                particle,
                '--particle-three-quarter-x',
                leftToRight
                    ? '78vw'
                    : '22vw'
            );

            setParticleVariable(
                particle,
                '--particle-to-x',
                leftToRight
                    ? '114vw'
                    : '-14vw'
            );

            setParticleVariable(
                particle,
                '--particle-drift-y',
                `${random(
                    -5,
                    6
                ).toFixed(2)}vh`
            );

            setParticleVariable(
                particle,
                '--particle-direction',
                leftToRight
                    ? '1'
                    : '-1'
            );

            root.append(particle);
        }
    };


JAVASCRIPT;

        $canvasMarker =
            '    const createCanvas = () => {';

        $js = replaceOnce(
            $js,
            $canvasMarker,
            $ghostFunction . $canvasMarker,
            'Ghost generator insertion point'
        );
    }

    /*
     * Larger and longer patriotic fireworks.
     */

    $oldBurstSettings = <<<'JAVASCRIPT'
        const burstTimes =
            isLowPower
                ? [
                    650,
                    2350,
                ]
                : [
                    550,
                    1850,
                    3150,
                ];

        const particlesPerBurst =
            isLowPower
                ? 18
                : 28;
JAVASCRIPT;

    $newBurstSettings = <<<'JAVASCRIPT'
        const burstTimes =
            isLowPower
                ? [
                    600,
                    2400,
                    4200,
                ]
                : [
                    450,
                    1750,
                    3150,
                    4900,
                ];

        const particlesPerBurst =
            isLowPower
                ? 24
                : 36;
JAVASCRIPT;

    $js = replaceWhenNeeded(
        $js,
        $oldBurstSettings,
        $newBurstSettings,
        'Firework burst settings'
    );

    $oldFireworkSpeed = <<<'JAVASCRIPT'
                const speed =
                    random(
                        1.8,
                        isLowPower
                            ? 3.7
                            : 4.8
                    );
JAVASCRIPT;

    $newFireworkSpeed = <<<'JAVASCRIPT'
                const speed =
                    random(
                        2.4,
                        isLowPower
                            ? 5.2
                            : 6.8
                    );
JAVASCRIPT;

    $js = replaceWhenNeeded(
        $js,
        $oldFireworkSpeed,
        $newFireworkSpeed,
        'Firework expansion speed'
    );

    $oldParticleSettings = <<<'JAVASCRIPT'
                    life: random(
                        45,
                        72
                    ),
                    maximumLife: 72,
                    size: random(
                        1.2,
                        2.4
                    ),
JAVASCRIPT;

    $newParticleSettings = <<<'JAVASCRIPT'
                    life: random(
                        62,
                        96
                    ),
                    maximumLife: 96,
                    size: random(
                        1.8,
                        3.8
                    ),
JAVASCRIPT;

    $js = replaceWhenNeeded(
        $js,
        $oldParticleSettings,
        $newParticleSettings,
        'Firework particle settings'
    );

    $js = replaceWhenNeeded(
        $js,
        'elapsed < 5100',
        'elapsed < 7400',
        'Firework duration'
    );

    /*
     * Replace the Halloween switch case.
     */

    $oldHalloweenCase = <<<'JAVASCRIPT'
        case 'bats':
            addBats(
                isLowPower
                    ? 3
                    : 5
            );

            break;
JAVASCRIPT;

    $newHalloweenCase = <<<'JAVASCRIPT'
        case 'ghosts':
            addGhosts(
                isLowPower
                    ? 3
                    : 5
            );

            break;
JAVASCRIPT;

    $js = replaceWhenNeeded(
        $js,
        $oldHalloweenCase,
        $newHalloweenCase,
        'Halloween switch case'
    );

    /*
     * Ghost styling.
     */

    if (
        !str_contains(
            $css,
            '/* Halloween ghosts */'
        )
    ) {
        $ghostCss = <<<'CSS'

/* Halloween ghosts */

.dc-seasonal-effects--ghosts::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            circle at 82% 13%,
            rgba(222, 215, 237, 0.1),
            transparent 17rem
        ),
        linear-gradient(
            180deg,
            rgba(42, 30, 53, 0.06),
            transparent 38%
        );
}

.dc-seasonal-particle--ghost {
    top: var(--particle-y);
    left: 0;
    width: var(--particle-size);
    opacity: var(--particle-opacity, 0.34);
    color: rgba(244, 241, 250, 0.9);
    filter:
        drop-shadow(
            0 0.45rem 0.75rem
            rgba(18, 8, 26, 0.22)
        );
    animation:
        dc-seasonal-ghost-drift
        var(--particle-duration)
        ease-in-out
        var(--particle-delay)
        infinite;
}

.dc-seasonal-particle--ghost svg {
    display: block;
    width: 100%;
    height: auto;
    overflow: visible;
}

.dc-seasonal-ghost__body {
    fill: currentColor;
}

.dc-seasonal-ghost__eye {
    fill: rgba(39, 27, 48, 0.82);
}

.dc-seasonal-ghost__mouth {
    fill: rgba(59, 40, 70, 0.66);
}

@keyframes dc-seasonal-ghost-drift {
    0% {
        opacity: 0;
        transform:
            translate3d(
                var(--particle-from-x),
                0,
                0
            )
            scaleX(
                var(--particle-direction)
            )
            rotate(-3deg);
    }

    12% {
        opacity: var(--particle-opacity, 0.34);
    }

    30% {
        transform:
            translate3d(
                var(--particle-quarter-x),
                calc(
                    var(--particle-drift-y)
                    - 2vh
                ),
                0
            )
            scaleX(
                var(--particle-direction)
            )
            rotate(2deg);
    }

    55% {
        transform:
            translate3d(
                50vw,
                var(--particle-drift-y),
                0
            )
            scaleX(
                var(--particle-direction)
            )
            rotate(-2deg);
    }

    78% {
        transform:
            translate3d(
                var(--particle-three-quarter-x),
                calc(
                    var(--particle-drift-y)
                    + 1.5vh
                ),
                0
            )
            scaleX(
                var(--particle-direction)
            )
            rotate(2deg);
    }

    88% {
        opacity: var(--particle-opacity, 0.34);
    }

    100% {
        opacity: 0;
        transform:
            translate3d(
                var(--particle-to-x),
                0,
                0
            )
            scaleX(
                var(--particle-direction)
            )
            rotate(-3deg);
    }
}
CSS;

        $css =
            rtrim($css)
            . "\n"
            . $ghostCss
            . "\n";
    }

    $requiredJsMarkers = [
        "'halloween': 'ghosts',",
        'const addGhosts = (count) => {',
        'minimumSize: 1.1,',
        'maximumSize: 2.25,',
        'elapsed < 7400',
        "case 'ghosts':",
    ];

    foreach (
        $requiredJsMarkers
        as $marker
    ) {
        if (!str_contains($js, $marker)) {
            throw new RuntimeException(
                'Missing JavaScript marker: '
                . $marker
            );
        }
    }

    if (
        !str_contains(
            $css,
            'dc-seasonal-ghost-drift'
        )
    ) {
        throw new RuntimeException(
            'Ghost CSS was not added.'
        );
    }

    if (
        file_put_contents(
            $jsPath,
            $js
        ) === false
    ) {
        throw new RuntimeException(
            'Could not write the JavaScript file.'
        );
    }

    if (
        file_put_contents(
            $cssPath,
            $css
        ) === false
    ) {
        throw new RuntimeException(
            'Could not write the CSS file.'
        );
    }

    echo PHP_EOL;
    echo "Seasonal effects updated successfully." . PHP_EOL;
    echo "Valentine hearts enlarged." . PHP_EOL;
    echo "Patriotic fireworks enlarged and extended." . PHP_EOL;
    echo "Halloween bats replaced with SVG ghosts." . PHP_EOL;
} catch (Throwable $exception) {
    if (is_file($jsBackup)) {
        copy($jsBackup, $jsPath);
    }

    if (is_file($cssBackup)) {
        copy($cssBackup, $cssPath);
    }

    fwrite(
        STDERR,
        $exception->getMessage()
        . PHP_EOL
    );

    exit(1);
}