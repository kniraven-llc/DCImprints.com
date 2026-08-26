'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const reducedMotion =
            window.matchMedia(
                '(prefers-reduced-motion: reduce)'
            ).matches;

        initializeSiteNavigation(
            reducedMotion
        );

        initializeTestimonials(
            reducedMotion
        );

        initializeHeroVideo(
            reducedMotion
        );

        initializeProfileBrowser(
            reducedMotion
        );

        initializeScrollReveals(
            reducedMotion
        );

        initializeGoogleAnalyticsEvents();
    }
);

function initializeSiteNavigation(
    reducedMotion
) {
    const header =
        document.getElementById(
            'site-header'
        );

    if (!header) {
        return;
    }

    const collapseElement =
        document.getElementById(
            'mainNavigation'
        );

    const navigationLinks =
        Array.from(
            document.querySelectorAll(
                '[data-nav-section]'
            )
        );

    const trackedSections = [
        'services',
        'reviews',
        'about',
        'catalogs',
        'contact',
        'location'
    ]
        .map((sectionId) =>
            document.getElementById(
                sectionId
            )
        )
        .filter(Boolean);

    let framePending = false;

    const setActiveSection = (
        sectionId
    ) => {
        navigationLinks.forEach(
            (link) => {
                const isActive =
                    link.dataset
                        .navSection
                    === sectionId;

                link.classList.toggle(
                    'active',
                    isActive
                );

                link.classList.toggle(
                    'is-active',
                    isActive
                );

                if (isActive) {
                    link.setAttribute(
                        'aria-current',
                        'location'
                    );
                } else {
                    link.removeAttribute(
                        'aria-current'
                    );
                }
            }
        );
    };

    const updateNavigationState = () => {
        framePending = false;

        header.classList.toggle(
            'is-scrolled',
            window.scrollY > 24
        );

        if (
            trackedSections.length === 0
        ) {
            return;
        }

        const activationLine =
            header.offsetHeight
            + Math.min(
                window.innerHeight * 0.22,
                160
            );

        let currentSection = '';

        trackedSections.forEach(
            (section) => {
                if (
                    section
                        .getBoundingClientRect()
                        .top
                    <= activationLine
                ) {
                    currentSection =
                        section.id;
                }
            }
        );

        if (window.scrollY < 80) {
            currentSection = '';
        }

        setActiveSection(
            currentSection
        );
    };

    const requestNavigationUpdate = () => {
        if (framePending) {
            return;
        }

        framePending = true;

        window.requestAnimationFrame(
            updateNavigationState
        );
    };

    window.addEventListener(
        'scroll',
        requestNavigationUpdate,
        {
            passive: true
        }
    );

    window.addEventListener(
        'resize',
        requestNavigationUpdate
    );

    if (collapseElement) {
        collapseElement.addEventListener(
            'show.bs.collapse',
            () => {
                header.classList.add(
                    'menu-open'
                );
            }
        );

        collapseElement.addEventListener(
            'hidden.bs.collapse',
            () => {
                header.classList.remove(
                    'menu-open'
                );
            }
        );
    }

    document
        .querySelectorAll(
            '[data-site-nav-link]'
        )
        .forEach((link) => {
            link.addEventListener(
                'click',
                () => {
                    const dropdown =
                        link.closest(
                            '.dropdown'
                        );

                    if (
                        dropdown
                        && typeof bootstrap
                            !== 'undefined'
                    ) {
                        const dropdownToggle =
                            dropdown
                                .querySelector(
                                    '.dropdown-toggle'
                                );

                        if (dropdownToggle) {
                            bootstrap.Dropdown
                                .getInstance(
                                    dropdownToggle
                                )
                                ?.hide();
                        }
                    }

                    if (
                        !collapseElement
                        || !collapseElement
                            .classList
                            .contains('show')
                        || typeof bootstrap
                            === 'undefined'
                    ) {
                        return;
                    }

                    bootstrap.Collapse
                        .getOrCreateInstance(
                            collapseElement,
                            {
                                toggle: false
                            }
                        )
                        .hide();
                }
            );
        });

    updateNavigationState();

    window.setTimeout(
        requestNavigationUpdate,
        reducedMotion
            ? 0
            : 350
    );
}

function initializeTestimonials(
    reducedMotion
) {
    if (
        typeof bootstrap === 'undefined'
    ) {
        return;
    }

    document
        .querySelectorAll(
            '.testimonial-carousel'
        )
        .forEach((element) => {
            const carousel =
                bootstrap.Carousel
                    .getOrCreateInstance(
                        element,
                        {
                            interval: 7000,
                            pause: 'hover',
                            ride: reducedMotion
                                ? false
                                : 'carousel',
                            touch: false,
                            wrap: true
                        }
                    );

            if (reducedMotion) {
                carousel.pause();
                return;
            }

            carousel.cycle();

            element.addEventListener(
                'focusin',
                () => carousel.pause()
            );

            element.addEventListener(
                'focusout',
                () => carousel.cycle()
            );
        });
}

function initializeHeroVideo(
    reducedMotion
) {
    if (!reducedMotion) {
        return;
    }

    document
        .querySelectorAll(
            '.dc-hero video'
        )
        .forEach((video) => {
            video.pause();

            video.removeAttribute(
                'autoplay'
            );
        });
}

function initializeProfileBrowser(
    reducedMotion
) {
    const dataElement =
        document.getElementById(
            'profile-data'
        );

    const profileDetail =
        document.getElementById(
            'profile-detail'
        );

    if (
        !dataElement
        || !profileDetail
    ) {
        return;
    }

    let profileItems;

    try {
        profileItems = JSON.parse(
            dataElement.textContent
            || '[]'
        );
    } catch (error) {
        console.error(
            'Unable to read profile data.',
            error
        );

        return;
    }

    const profileButtons =
        document.querySelectorAll(
            '[data-profile-index]'
        );

    const detailImage =
        document.getElementById(
            'profile-detail-image'
        );

    const detailPlaceholder =
        document.getElementById(
            'profile-detail-placeholder'
        );

    const detailName =
        document.getElementById(
            'profile-detail-name'
        );

    const detailRole =
        document.getElementById(
            'profile-detail-role'
        );

    const detailBio =
        document.getElementById(
            'profile-detail-bio'
        );

    if (
        !detailImage
        || !detailPlaceholder
        || !detailName
        || !detailRole
        || !detailBio
    ) {
        return;
    }

    let profileSwapTimer = null;

    profileButtons.forEach(
        (button) => {
            button.addEventListener(
                'click',
                () => {
                    const profileIndex =
                        Number(
                            button.dataset
                                .profileIndex
                        );

                    const profile =
                        profileItems[
                            profileIndex
                        ];

                    if (!profile) {
                        return;
                    }

                    profileButtons.forEach(
                        (otherButton) => {
                            otherButton
                                .setAttribute(
                                    'aria-pressed',
                                    otherButton
                                        === button
                                        ? 'true'
                                        : 'false'
                                );
                        }
                    );

                    if (
                        window.innerWidth
                        < 768
                    ) {
                        button.scrollIntoView(
                            {
                                behavior:
                                    reducedMotion
                                        ? 'auto'
                                        : 'smooth',
                                block: 'nearest',
                                inline: 'center'
                            }
                        );
                    }

                    window.clearTimeout(
                        profileSwapTimer
                    );

                    if (!reducedMotion) {
                        profileDetail
                            .classList
                            .add(
                                'is-changing'
                            );
                    }

                    profileSwapTimer =
                        window.setTimeout(
                            () => {
                                detailName
                                    .textContent =
                                    profile.name;

                                detailRole
                                    .textContent =
                                    profile.role;

                                detailBio
                                    .textContent =
                                    profile.bio;

                                if (
                                    profile
                                        .image_exists
                                ) {
                                    detailImage.src =
                                        profile.image;

                                    detailImage.alt =
                                        profile.name;

                                    detailImage.hidden =
                                        false;

                                    detailPlaceholder
                                        .hidden =
                                        true;
                                } else {
                                    detailImage.hidden =
                                        true;

                                    detailPlaceholder
                                        .textContent =
                                        profile.initials;

                                    detailPlaceholder
                                        .hidden =
                                        false;
                                }

                                profileDetail
                                    .classList
                                    .remove(
                                        'is-changing'
                                    );
                            },
                            reducedMotion
                                ? 0
                                : 160
                        );
                }
            );
        }
    );
}

function initializeScrollReveals(
    reducedMotion
) {
    const revealElements =
        document.querySelectorAll(
            '[data-reveal]'
        );

    if (revealElements.length === 0) {
        return;
    }

    if (
        reducedMotion
        || !(
            'IntersectionObserver'
            in window
        )
    ) {
        revealElements.forEach(
            (element) => {
                element.classList.add(
                    'is-visible'
                );
            }
        );

        return;
    }

    document.documentElement
        .classList
        .add(
            'reveal-enabled'
        );

    const observer =
        new IntersectionObserver(
            (entries) => {
                entries.forEach(
                    (entry) => {
                        if (
                            !entry.isIntersecting
                        ) {
                            return;
                        }

                        entry.target
                            .classList
                            .add(
                                'is-visible'
                            );

                        observer.unobserve(
                            entry.target
                        );
                    }
                );
            },
            {
                threshold: 0.12,
                rootMargin:
                    '0px 0px -40px 0px'
            }
        );

    revealElements.forEach(
        (element, index) => {
            const delay =
                Math.min(
                    index % 6,
                    5
                ) * 70;

            element.style
                .setProperty(
                    '--reveal-delay',
                    `${delay}ms`
                );

            observer.observe(
                element
            );
        }
    );
}

function initializeGoogleAnalyticsEvents() {
    document
        .querySelectorAll(
            '[data-google-analytics-event]'
        )
        .forEach((element) => {
            const eventName =
                element.dataset
                    .googleAnalyticsEvent;

            if (
                !eventName
                || typeof window.gtag
                    !== 'function'
            ) {
                return;
            }

            window.gtag(
                'event',
                eventName
            );
        });
}