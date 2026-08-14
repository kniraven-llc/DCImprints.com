(() => {
    'use strict';

    const titleInput = document.querySelector(
        '[data-search-preview-title-input]'
    );
    const descriptionInput = document.querySelector(
        '[data-search-preview-description-input]'
    );
    const titleOutput = document.querySelector(
        '[data-search-preview-title]'
    );
    const descriptionOutput = document.querySelector(
        '[data-search-preview-description]'
    );

    const updateSearchPreview = () => {
        if (titleInput && titleOutput) {
            titleOutput.textContent =
                titleInput.value.trim() || 'Page title preview';
        }

        if (descriptionInput && descriptionOutput) {
            descriptionOutput.textContent =
                descriptionInput.value.trim()
                || 'Meta description preview.';
        }
    };

    titleInput?.addEventListener('input', updateSearchPreview);
    descriptionInput?.addEventListener('input', updateSearchPreview);
    updateSearchPreview();

    document.querySelectorAll('[data-character-count]').forEach((field) => {
        const container = field.closest('.col-12, .col-md-6, .mb-3');
        const output = container?.querySelector(
            '[data-character-count-output]'
        );
        const maximum = Number.parseInt(
            field.getAttribute('maxlength') || '',
            10
        );

        if (!output || !Number.isFinite(maximum)) {
            return;
        }

        const updateCount = () => {
            const length = field.value.length;
            const remaining = Math.max(0, maximum - length);

            output.textContent =
                `${length} of ${maximum} characters used; `
                + `${remaining} remaining.`;

            output.classList.toggle(
                'is-near-limit',
                length >= maximum * 0.85 && length < maximum
            );
            output.classList.toggle(
                'is-at-limit',
                length >= maximum
            );
        };

        field.addEventListener('input', updateCount);
        updateCount();
    });
})();