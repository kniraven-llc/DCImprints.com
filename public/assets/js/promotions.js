(() => {
    'use strict';

    const safeSessionGet = (key) => {
        try {
            return window.sessionStorage.getItem(key);
        } catch {
            return null;
        }
    };

    const safeSessionSet = (key, value) => {
        try {
            window.sessionStorage.setItem(key, value);
        } catch {
            // The feature still works when session storage is unavailable.
        }
    };

    const announcement = document.querySelector(
        '[data-dc-announcement]'
    );

    if (announcement) {
        const promotionId =
            announcement.dataset.promotionId || 'current';

        const storageKey =
            `dc-announcement-dismissed-${promotionId}`;

        if (safeSessionGet(storageKey) === '1') {
            announcement.remove();
        } else {
            const dismissButton = announcement.querySelector(
                '[data-dc-dismiss-announcement]'
            );

            dismissButton?.addEventListener('click', () => {
                safeSessionSet(storageKey, '1');
                announcement.remove();
            });
        }
    }

    const importantNotice = document.querySelector(
        '[data-dc-important-promotion]'
    );

    if (
        importantNotice
        && window.bootstrap?.Modal
    ) {
        const promotionId =
            importantNotice.dataset.promotionId || 'current';

        const storageKey =
            `dc-important-seen-${promotionId}`;

        if (safeSessionGet(storageKey) !== '1') {
            window.setTimeout(() => {
                const modal =
                    window.bootstrap.Modal.getOrCreateInstance(
                        importantNotice,
                        {
                            backdrop: true,
                            focus: true,
                            keyboard: true,
                        }
                    );

                modal.show();
            }, 1200);
        }

        importantNotice.addEventListener(
            'hidden.bs.modal',
            () => {
                safeSessionSet(storageKey, '1');
            }
        );
    }
})();