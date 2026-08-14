(() => {
    'use strict';

    const seasonalFeature = document.querySelector(
        '.dc-promotion-seasonal[data-seasonal-theme]'
    );

    if (!seasonalFeature) {
        return;
    }

    const seasonalTheme = (
        seasonalFeature.dataset.seasonalTheme
        || 'default'
    ).trim();

    const effectByTheme = {
        'winter-new-year': 'snow',
        'valentines': 'hearts',
        'spring-easter': 'petals',
        'patriotic-summer': 'fireworks',
        'graduation': 'confetti',
        'back-to-school': 'leaves',
        'halloween': 'ghosts',
        'harvest': 'leaves',
        'holiday-christmas': 'holiday',
    };

    const effectName =
        effectByTheme[seasonalTheme]
        || '';

    if (effectName === '') {
        return;
    }

    const reducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;

    const connection =
        navigator.connection
        || navigator.mozConnection
        || navigator.webkitConnection
        || null;

    const saveData =
        connection?.saveData
        === true;

    if (
        reducedMotion
        || saveData
    ) {
        return;
    }

    const hardwareThreads =
        Number(
            navigator.hardwareConcurrency
            || 0
        );

    const isSmallViewport =
        window.matchMedia(
            '(max-width: 767.98px)'
        ).matches;

    const isLowPower =
        isSmallViewport
        || (
            hardwareThreads > 0
            && hardwareThreads <= 4
        );

    const root =
        document.createElement('div');

    root.className =
        `dc-seasonal-effects dc-seasonal-effects--${effectName}`;

    root.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.append(root);

    document.documentElement.dataset
        .seasonalEffect = effectName;

    document.addEventListener(
        'visibilitychange',
        () => {
            root.classList.toggle(
                'is-paused',
                document.hidden
            );
        },
        {
            passive: true,
        }
    );

    const random = (
        minimum,
        maximum
    ) => (
        minimum
        + Math.random()
        * (
            maximum
            - minimum
        )
    );

    const choose = (items) =>
        items[
            Math.floor(
                Math.random()
                * items.length
            )
        ];

    const setParticleVariable = (
        particle,
        property,
        value
    ) => {
        particle.style.setProperty(
            property,
            value
        );
    };

    const addFallingParticles = (
        kind,
        count,
        settings = {}
    ) => {
        const colors =
            settings.colors
            || [
                'rgba(255,255,255,0.82)',
            ];

        const symbols =
            settings.symbols
            || [];

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
                `dc-seasonal-particle dc-seasonal-particle--${kind}`;

            if (symbols.length > 0) {
                particle.textContent =
                    choose(symbols);
            }

            const duration = random(
                settings.minimumDuration
                    || 10,
                settings.maximumDuration
                    || 18
            );

            setParticleVariable(
                particle,
                '--particle-x',
                `${random(0, 100).toFixed(2)}vw`
            );

            setParticleVariable(
                particle,
                '--particle-drift',
                `${random(-10, 10).toFixed(2)}vw`
            );

            setParticleVariable(
                particle,
                '--particle-sway',
                `${random(3, 10).toFixed(2)}vw`
            );

            setParticleVariable(
                particle,
                '--particle-size',
                `${random(
                    settings.minimumSize
                        || 0.45,
                    settings.maximumSize
                        || 1.2
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
                '--particle-rotation',
                `${random(
                    240,
                    900
                ).toFixed(0)}deg`
            );

            setParticleVariable(
                particle,
                '--particle-opacity',
                random(
                    settings.minimumOpacity
                        || 0.28,
                    settings.maximumOpacity
                        || 0.72
                ).toFixed(2)
            );

            setParticleVariable(
                particle,
                '--particle-color',
                choose(colors)
            );

            root.append(particle);
        }
    };

    const addRisingHearts = (count) => {
        addFallingParticles(
            'heart',
            count,
            {
                symbols: [
                    '♥',
                    '♡',
                ],

                colors: [
                    'rgba(181,61,103,0.58)',
                    'rgba(226,105,145,0.52)',
                    'rgba(255,184,204,0.48)',
                ],

                minimumDuration: 12,
                maximumDuration: 20,
                minimumSize: 1.75,
                maximumSize: 3.5,
                minimumOpacity: 0.24,
                maximumOpacity: 0.58,
            }
        );
    };

    const addTwinkles = (count) => {
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
                'dc-seasonal-particle dc-seasonal-particle--twinkle';

            particle.textContent =
                choose([
                    '✦',
                    '✧',
                    '·',
                ]);

            const duration =
                random(2.8, 5.8);

            setParticleVariable(
                particle,
                '--particle-x',
                `${random(2, 98).toFixed(2)}%`
            );

            setParticleVariable(
                particle,
                '--particle-y',
                `${random(4, 94).toFixed(2)}%`
            );

            setParticleVariable(
                particle,
                '--particle-size',
                `${random(
                    0.45,
                    1.05
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
                    0.28,
                    0.7
                ).toFixed(2)
            );

            setParticleVariable(
                particle,
                '--particle-color',
                choose([
                    'rgba(249,218,138,0.72)',
                    'rgba(255,245,205,0.65)',
                    'rgba(232,194,104,0.58)',
                ])
            );

            root.append(particle);
        }
    };

    const addBats = (count) => {
        const batMarkup = [
            '<svg viewBox="0 0 64 28" aria-hidden="true">',
            '<path d="M2 13c7-8 14-9 22-3 2-5 5-8 8-8s6 3 8 8c8-6 15-5 22 3-5-1-9 1-12 5 2 2 3 5 3 8-7-4-13-5-19-2-1 1-2 2-2 3-1-1-2-2-2-3-6-3-12-2-19 2 0-3 1-6 3-8-3-4-7-6-12-5Z"/>',
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
                'dc-seasonal-particle dc-seasonal-particle--bat';

            particle.innerHTML =
                batMarkup;

            const leftToRight =
                Math.random() >= 0.5;

            const duration =
                random(12, 21);

            setParticleVariable(
                particle,
                '--particle-y',
                `${random(7, 46).toFixed(2)}vh`
            );

            setParticleVariable(
                particle,
                '--particle-size',
                `${random(
                    2.2,
                    4.2
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
                    0.22,
                    0.5
                ).toFixed(2)
            );

            setParticleVariable(
                particle,
                '--particle-scale',
                leftToRight
                    ? '1'
                    : '-1'
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
                    7
                ).toFixed(2)}vh`
            );

            root.append(particle);
        }
    };

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

    const createCanvas = () => {
        const canvas =
            document.createElement(
                'canvas'
            );

        canvas.className =
            'dc-seasonal-effects__canvas';

        root.append(canvas);

        const context =
            canvas.getContext(
                '2d',
                {
                    alpha: true,
                }
            );

        if (!context) {
            canvas.remove();

            return null;
        }

        const resize = () => {
            const ratio = Math.min(
                window.devicePixelRatio
                    || 1,
                1.5
            );

            const width =
                Math.max(
                    1,
                    window.innerWidth
                );

            const height =
                Math.max(
                    1,
                    window.innerHeight
                );

            canvas.width =
                Math.round(
                    width * ratio
                );

            canvas.height =
                Math.round(
                    height * ratio
                );

            canvas.style.width =
                `${width}px`;

            canvas.style.height =
                `${height}px`;

            context.setTransform(
                ratio,
                0,
                0,
                ratio,
                0,
                0
            );

            return {
                width,
                height,
            };
        };

        let dimensions =
            resize();

        const resizeHandler = () => {
            dimensions =
                resize();
        };

        window.addEventListener(
            'resize',
            resizeHandler,
            {
                passive: true,
            }
        );

        return {
            canvas,
            context,
            getDimensions: () =>
                dimensions,
            destroy: () => {
                window.removeEventListener(
                    'resize',
                    resizeHandler
                );

                canvas.remove();
            },
        };
    };

    const runFireworks = () => {
        const canvasSystem =
            createCanvas();

        if (!canvasSystem) {
            return;
        }

        const {
            canvas,
            context,
            getDimensions,
            destroy,
        } = canvasSystem;

        const palette = [
            '#f5d978',
            '#ffffff',
            '#d8565f',
            '#7fa9d7',
            '#f1a45f',
        ];

        const particles = [];

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

        let nextBurst = 0;

        const startTime =
            performance.now();

        const createBurst = () => {
            const {
                width,
                height,
            } = getDimensions();

            const originX =
                random(
                    width * 0.18,
                    width * 0.82
                );

            const originY =
                random(
                    height * 0.12,
                    height * 0.48
                );

            const color =
                choose(palette);

            for (
                let index = 0;
                index < particlesPerBurst;
                index += 1
            ) {
                const angle =
                    (
                        Math.PI * 2
                        * index
                        / particlesPerBurst
                    )
                    + random(
                        -0.08,
                        0.08
                    );

                const speed =
                    random(
                        2.4,
                        isLowPower
                            ? 5.2
                            : 6.8
                    );

                particles.push({
                    x: originX,
                    y: originY,
                    velocityX:
                        Math.cos(angle)
                        * speed,
                    velocityY:
                        Math.sin(angle)
                        * speed,
                    life: random(
                        62,
                        96
                    ),
                    maximumLife: 96,
                    size: random(
                        1.8,
                        3.8
                    ),
                    color,
                });
            }
        };

        const frame = (time) => {
            if (!canvas.isConnected) {
                return;
            }

            if (document.hidden) {
                requestAnimationFrame(frame);

                return;
            }

            const elapsed =
                time - startTime;

            while (
                nextBurst
                    < burstTimes.length
                && elapsed
                    >= burstTimes[nextBurst]
            ) {
                createBurst();
                nextBurst += 1;
            }

            const {
                width,
                height,
            } = getDimensions();

            context.clearRect(
                0,
                0,
                width,
                height
            );

            context.save();

            context.globalCompositeOperation =
                'lighter';

            for (
                let index =
                    particles.length - 1;
                index >= 0;
                index -= 1
            ) {
                const particle =
                    particles[index];

                particle.velocityX *=
                    0.985;

                particle.velocityY =
                    particle.velocityY
                    * 0.985
                    + 0.035;

                particle.x +=
                    particle.velocityX;

                particle.y +=
                    particle.velocityY;

                particle.life -= 1;

                if (particle.life <= 0) {
                    particles.splice(
                        index,
                        1
                    );

                    continue;
                }

                context.globalAlpha =
                    Math.max(
                        0,
                        particle.life
                        / particle.maximumLife
                    );

                context.fillStyle =
                    particle.color;

                context.beginPath();

                context.arc(
                    particle.x,
                    particle.y,
                    particle.size,
                    0,
                    Math.PI * 2
                );

                context.fill();
            }

            context.restore();

            if (
                elapsed < 7400
                || particles.length > 0
            ) {
                requestAnimationFrame(frame);
            } else {
                destroy();
            }
        };

        requestAnimationFrame(frame);
    };

    const runConfetti = () => {
        const canvasSystem =
            createCanvas();

        if (!canvasSystem) {
            return;
        }

        const {
            canvas,
            context,
            getDimensions,
            destroy,
        } = canvasSystem;

        const {
            width,
            height,
        } = getDimensions();

        const palette = [
            '#f0c75e',
            '#365d88',
            '#ffffff',
            '#9d4c3e',
            '#53785e',
        ];

        const pieceCount =
            isLowPower
                ? 24
                : 44;

        const pieces =
            Array.from(
                {
                    length: pieceCount,
                },
                () => ({
                    x: random(
                        0,
                        width
                    ),

                    y: random(
                        -height * 0.55,
                        -20
                    ),

                    velocityX:
                        random(
                            -0.75,
                            0.75
                        ),

                    velocityY:
                        random(
                            2.1,
                            4.6
                        ),

                    width:
                        random(
                            4,
                            9
                        ),

                    height:
                        random(
                            7,
                            14
                        ),

                    rotation:
                        random(
                            0,
                            Math.PI * 2
                        ),

                    rotationSpeed:
                        random(
                            -0.13,
                            0.13
                        ),

                    color:
                        choose(palette),
                })
            );

        const startTime =
            performance.now();

        const frame = (time) => {
            if (!canvas.isConnected) {
                return;
            }

            if (document.hidden) {
                requestAnimationFrame(frame);

                return;
            }

            const dimensions =
                getDimensions();

            context.clearRect(
                0,
                0,
                dimensions.width,
                dimensions.height
            );

            pieces.forEach((piece) => {
                piece.velocityY +=
                    0.012;

                piece.x +=
                    piece.velocityX;

                piece.y +=
                    piece.velocityY;

                piece.rotation +=
                    piece.rotationSpeed;

                context.save();

                context.translate(
                    piece.x,
                    piece.y
                );

                context.rotate(
                    piece.rotation
                );

                context.fillStyle =
                    piece.color;

                context.globalAlpha =
                    0.78;

                context.fillRect(
                    -piece.width / 2,
                    -piece.height / 2,
                    piece.width,
                    piece.height
                );

                context.restore();
            });

            if (
                time - startTime
                < 4300
            ) {
                requestAnimationFrame(frame);
            } else {
                destroy();
            }
        };

        requestAnimationFrame(frame);
    };

    switch (effectName) {
        case 'snow':
            addFallingParticles(
                'snow',
                isLowPower
                    ? 9
                    : 19,
                {
                    symbols: [
                        '•',
                        '❄',
                        '✦',
                    ],

                    colors: [
                        'rgba(255,255,255,0.82)',
                        'rgba(222,244,255,0.72)',
                    ],

                    minimumDuration: 11,
                    maximumDuration: 21,
                    minimumSize: 0.38,
                    maximumSize: 1.05,
                    minimumOpacity: 0.25,
                    maximumOpacity: 0.66,
                }
            );

            break;

        case 'hearts':
            addRisingHearts(
                isLowPower
                    ? 6
                    : 12
            );

            break;

        case 'petals':
            addFallingParticles(
                'petal',
                isLowPower
                    ? 7
                    : 14,
                {
                    colors: [
                        'rgba(255,211,222,0.60)',
                        'rgba(255,239,184,0.58)',
                        'rgba(232,202,242,0.55)',
                        'rgba(255,255,255,0.62)',
                    ],

                    minimumDuration: 12,
                    maximumDuration: 20,
                    minimumSize: 0.5,
                    maximumSize: 1.05,
                    minimumOpacity: 0.28,
                    maximumOpacity: 0.62,
                }
            );

            break;

        case 'leaves': {
            const harvestPalette =
                seasonalTheme === 'harvest'
                    ? [
                        'rgba(170,70,38,0.66)',
                        'rgba(208,120,50,0.68)',
                        'rgba(122,49,37,0.63)',
                        'rgba(222,165,72,0.67)',
                    ]
                    : [
                        'rgba(150,72,38,0.63)',
                        'rgba(207,116,45,0.66)',
                        'rgba(119,91,42,0.62)',
                        'rgba(178,139,61,0.64)',
                    ];

            addFallingParticles(
                'leaf',
                isLowPower
                    ? 7
                    : 14,
                {
                    colors:
                        harvestPalette,

                    minimumDuration: 12,
                    maximumDuration: 21,
                    minimumSize: 0.58,
                    maximumSize: 1.12,
                    minimumOpacity: 0.32,
                    maximumOpacity: 0.68,
                }
            );

            break;
        }

        case 'ghosts':
            addGhosts(
                isLowPower
                    ? 3
                    : 5
            );

            break;

        case 'fireworks':
            window.setTimeout(
                runFireworks,
                250
            );

            break;

        case 'confetti':
            window.setTimeout(
                runConfetti,
                300
            );

            break;

        case 'holiday':
            addFallingParticles(
                'snow',
                isLowPower
                    ? 8
                    : 16,
                {
                    symbols: [
                        '•',
                        '❄',
                    ],

                    colors: [
                        'rgba(255,255,255,0.78)',
                        'rgba(238,247,240,0.72)',
                    ],

                    minimumDuration: 12,
                    maximumDuration: 22,
                    minimumSize: 0.4,
                    maximumSize: 0.95,
                    minimumOpacity: 0.22,
                    maximumOpacity: 0.58,
                }
            );

            addTwinkles(
                isLowPower
                    ? 5
                    : 9
            );

            break;

        default:
            root.remove();
    }
})();