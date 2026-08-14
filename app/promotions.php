<?php

declare(strict_types=1);

/**
 * Available announcement and promotion presentation types.
 *
 * @return array<string, array{
 *     label:string,
 *     description:string
 * }>
 */
function dc_promotion_type_options(): array
{
    return [
        'announcement' => [
            'label' => 'Announcement',
            'description' =>
                'General news, updates, and public information.',
        ],

        'special' => [
            'label' => 'Special / Sale',
            'description' =>
                'Temporary pricing, offers, and promotional campaigns.',
        ],

        'seasonal' => [
            'label' => 'Seasonal',
            'description' =>
                'Holiday, event, and seasonal messages.',
        ],

        'important' => [
            'label' => 'Important Notice',
            'description' =>
                'Closures, deadline changes, and urgent information.',
        ],
    ];
}

/**
 * @return array{
 *     label:string,
 *     description:string
 * }
 */
/**
 * Approved visual presets for Seasonal promotions.
 *
 * These decorate the Seasonal feature band without replacing the site's
 * underlying Sasquatch Sienna, White Yeti, or Bigfoot Black theme.
 *
 * @return array<string, array{
 *     label:string,
 *     description:string,
 *     eyebrow:string,
 *     icon:string
 * }>
 */
function dc_seasonal_theme_options(): array
{
    return [
        'default' => [
            'label' => 'Brand Seasonal',
            'description' =>
                'A neutral seasonal treatment using the active site theme.',
            'eyebrow' => 'Seasonal Feature',
            'icon' => '✦',
        ],

        'winter-new-year' => [
            'label' => 'Winter / New Year',
            'description' =>
                'Snow, evergreen accents, cool winter tones, and a fresh-start presentation.',
            'eyebrow' => 'Winter Feature',
            'icon' => '❄',
        ],

        'valentines' => [
            'label' => 'Valentine’s Day',
            'description' =>
                'Burgundy, blush, cream, and restrained heart details.',
            'eyebrow' => 'Valentine’s Feature',
            'icon' => '♥',
        ],

        'spring-easter' => [
            'label' => 'Spring / Easter',
            'description' =>
                'Fresh greens, soft spring colors, flowers, and subtle egg-inspired geometry.',
            'eyebrow' => 'Spring Feature',
            'icon' => '✿',
        ],

        'patriotic-summer' => [
            'label' => 'Patriotic Summer',
            'description' =>
                'Polished Americana for Memorial Day, Independence Day, and Labor Day campaigns.',
            'eyebrow' => 'Summer Feature',
            'icon' => '★',
        ],

        'graduation' => [
            'label' => 'Graduation / School Spirit',
            'description' =>
                'Confetti, pennants, achievement details, and adaptable school-spirit styling.',
            'eyebrow' => 'Graduation Feature',
            'icon' => '✦',
        ],

        'back-to-school' => [
            'label' => 'Back-to-School / Fall Sports',
            'description' =>
                'Varsity forms, pennants, fall accents, and team-oriented presentation.',
            'eyebrow' => 'School & Team Feature',
            'icon' => '◆',
        ],

        'halloween' => [
            'label' => 'Halloween',
            'description' =>
                'Near-black, purple, burnt orange, moonlight, and restrained spooky details.',
            'eyebrow' => 'Halloween Feature',
            'icon' => '☾',
        ],

        'harvest' => [
            'label' => 'Thanksgiving / Harvest',
            'description' =>
                'Copper, cranberry, wheat, and warm autumn styling.',
            'eyebrow' => 'Harvest Feature',
            'icon' => '❦',
        ],

        'holiday-christmas' => [
            'label' => 'Christmas / Holiday',
            'description' =>
                'Evergreen, burgundy, cream, warm gold, lights, and gift-inspired geometry.',
            'eyebrow' => 'Holiday Feature',
            'icon' => '✶',
        ],
    ];
}

/**
 * @return array{
 *     label:string,
 *     description:string,
 *     eyebrow:string,
 *     icon:string
 * }
 */
function dc_seasonal_theme_details(
    string $seasonalTheme
): array {
    $options =
        dc_seasonal_theme_options();

    return $options[$seasonalTheme]
        ?? $options['default'];
}
function dc_promotion_type_details(
    string $promotionType
): array {
    $options =
        dc_promotion_type_options();

    return $options[$promotionType]
        ?? $options['announcement'];
}

/**
 * Character limits shared by validation and the admin interface.
 *
 * @return array<string, int>
 */
function dc_promotion_limits(): array
{
    return [
        'title' => 100,
        'message' => 300,
        'button_label' => 40,
        'button_url' => 2048,
    ];
}

/**
 * Request-level cache for promotion records.
 *
 * @return array<string, mixed>
 */
function &dc_promotion_cache(): array
{
    static $cache = [];

    return $cache;
}

function dc_forget_promotion_cache(): void
{
    $cache =& dc_promotion_cache();
    $cache = [];
}

/**
 * Parse either a database DATETIME or an HTML datetime-local value.
 */
function dc_promotion_datetime(
    ?string $value
): ?DateTimeImmutable {
    $value = trim(
        (string) $value
    );

    if ($value === '') {
        return null;
    }

    $formats = [
        'Y-m-d H:i:s',
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i',
    ];

    foreach ($formats as $format) {
        $date = DateTimeImmutable::createFromFormat(
            '!' . $format,
            $value
        );

        $errors =
            DateTimeImmutable::getLastErrors();

        $isValid =
            $date instanceof DateTimeImmutable
            && (
                $errors === false
                || (
                    (int) $errors['warning_count'] === 0
                    && (int) $errors['error_count'] === 0
                )
            );

        if (
            $isValid
            && $date->format($format) === $value
        ) {
            return $date;
        }
    }

    return null;
}

/**
 * Convert an HTML datetime-local value to a database DATETIME value.
 */
function dc_promotion_database_datetime(
    string $value
): ?string {
    $value = trim($value);

    if ($value === '') {
        return null;
    }

    $date =
        dc_promotion_datetime($value);

    return $date?->format(
        'Y-m-d H:i:s'
    );
}

/**
 * Convert a database DATETIME value for an HTML datetime-local input.
 */
function dc_promotion_datetime_input_value(
    ?string $value
): string {
    $date =
        dc_promotion_datetime($value);

    return $date instanceof DateTimeImmutable
        ? $date->format('Y-m-d\TH:i')
        : '';
}

/**
 * Format a promotion date for administrator-facing status information.
 */
function dc_promotion_format_datetime(
    ?string $value
): string {
    $date =
        dc_promotion_datetime($value);

    return $date instanceof DateTimeImmutable
        ? $date->format('M j, Y \a\t g:i A T')
        : '';
}

/**
 * Validate an optional promotion destination.
 *
 * Accepted forms:
 *
 * - #contact
 * - /services
 * - https://example.com/
 * - http://example.com/
 * - mailto:name@example.com
 * - tel:+16085551234
 */
function dc_promotion_url_is_valid(
    string $value
): bool {
    $value = trim($value);

    if ($value === '') {
        return true;
    }

    if (
        preg_match(
            '/[\x00-\x1F\x7F]/',
            $value
        ) === 1
    ) {
        return false;
    }

    if (
        str_starts_with($value, '#')
    ) {
        return strlen($value) > 1;
    }

    if (
        str_starts_with($value, '/')
        && !str_starts_with($value, '//')
    ) {
        return true;
    }

    if (
        preg_match(
            '/^(mailto|tel):\S+$/i',
            $value
        ) === 1
    ) {
        return true;
    }

    if (
        filter_var(
            $value,
            FILTER_VALIDATE_URL
        ) === false
    ) {
        return false;
    }

    $scheme = strtolower(
        (string) parse_url(
            $value,
            PHP_URL_SCHEME
        )
    );

    return in_array(
        $scheme,
        ['http', 'https'],
        true
    );
}

/**
 * Validate and normalize submitted promotion values.
 *
 * @param array<string, mixed> $input
 * @return array{
 *     0: array<string, mixed>|null,
 *     1: string|null
 * }
 */
function dc_validate_promotion(
    array $input
): array {
    $types =
        dc_promotion_type_options();

    $seasonalThemes =
        dc_seasonal_theme_options();

    $limits =
        dc_promotion_limits();

    $promotionType = trim(
        (string) (
            $input['promotion_type']
            ?? 'announcement'
        )
    );

    $seasonalTheme = trim(
        (string) (
            $input['seasonal_theme']
            ?? 'default'
        )
    );

    $title = trim(
        (string) (
            $input['title']
            ?? ''
        )
    );

    $message = trim(
        (string) (
            $input['message']
            ?? ''
        )
    );

    $buttonLabel = trim(
        (string) (
            $input['button_label']
            ?? ''
        )
    );

    $buttonUrl = trim(
        (string) (
            $input['button_url']
            ?? ''
        )
    );

    $startsAtInput = trim(
        (string) (
            $input['starts_at']
            ?? ''
        )
    );

    $endsAtInput = trim(
        (string) (
            $input['ends_at']
            ?? ''
        )
    );

    if (!isset($types[$promotionType])) {
        return [
            null,
            'Select a valid promotion type.',
        ];
    }

    if ($promotionType === 'seasonal') {
        if (!isset($seasonalThemes[$seasonalTheme])) {
            return [
                null,
                'Select a valid seasonal visual preset.',
            ];
        }
    } else {
        $seasonalTheme = '';
    }

    if ($title === '') {
        return [
            null,
            'Enter a promotion title.',
        ];
    }

    if (
        mb_strlen($title)
        > $limits['title']
    ) {
        return [
            null,
            sprintf(
                'The promotion title cannot exceed %d characters.',
                $limits['title']
            ),
        ];
    }

    if ($message === '') {
        return [
            null,
            'Enter a promotion message.',
        ];
    }

    if (
        mb_strlen($message)
        > $limits['message']
    ) {
        return [
            null,
            sprintf(
                'The promotion message cannot exceed %d characters.',
                $limits['message']
            ),
        ];
    }

    if (
        mb_strlen($buttonLabel)
        > $limits['button_label']
    ) {
        return [
            null,
            sprintf(
                'The button text cannot exceed %d characters.',
                $limits['button_label']
            ),
        ];
    }

    if (
        mb_strlen($buttonUrl)
        > $limits['button_url']
    ) {
        return [
            null,
            'The button destination is too long.',
        ];
    }

    if (
        ($buttonLabel === '')
        !== ($buttonUrl === '')
    ) {
        return [
            null,
            'Enter both the button text and its destination, or leave both blank.',
        ];
    }

    if (
        !dc_promotion_url_is_valid(
            $buttonUrl
        )
    ) {
        return [
            null,
            'Enter a valid button destination such as #contact, /services, or a complete web address.',
        ];
    }

    $startsAt = null;

    if ($startsAtInput !== '') {
        $startsAt =
            dc_promotion_database_datetime(
                $startsAtInput
            );

        if ($startsAt === null) {
            return [
                null,
                'Enter a valid promotion start date and time.',
            ];
        }
    }

    $endsAt = null;

    if ($endsAtInput !== '') {
        $endsAt =
            dc_promotion_database_datetime(
                $endsAtInput
            );

        if ($endsAt === null) {
            return [
                null,
                'Enter a valid promotion expiration date and time.',
            ];
        }
    }

    if (
        $startsAt !== null
        && $endsAt !== null
        && $endsAt <= $startsAt
    ) {
        return [
            null,
            'The expiration must occur after the scheduled start.',
        ];
    }

    return [
        [
            'promotion_type' =>
                $promotionType,

            'seasonal_theme' =>
                $promotionType === 'seasonal'
                    ? $seasonalTheme
                    : null,

            'title' =>
                $title,

            'message' =>
                $message,

            'button_label' =>
                $buttonLabel !== ''
                    ? $buttonLabel
                    : null,

            'button_url' =>
                $buttonUrl !== ''
                    ? $buttonUrl
                    : null,

            'starts_at' =>
                $startsAt,

            'ends_at' =>
                $endsAt,

            'is_active' =>
                !empty($input['is_active'])
                    ? 1
                    : 0,
        ],
        null,
    ];
}

/**
 * Determine whether a promotion is active, scheduled, expired, or hidden.
 *
 * @param array<string, mixed> $promotion
 */
function dc_promotion_status(
    array $promotion,
    ?DateTimeImmutable $now = null
): string {
    if (
        (int) (
            $promotion['is_active']
            ?? 0
        ) !== 1
    ) {
        return 'hidden';
    }

    $now ??=
        new DateTimeImmutable('now');

    $startsAt =
        dc_promotion_datetime(
            isset($promotion['starts_at'])
                ? (string) $promotion['starts_at']
                : null
        );

    $endsAt =
        dc_promotion_datetime(
            isset($promotion['ends_at'])
                ? (string) $promotion['ends_at']
                : null
        );

    if (
        $startsAt instanceof DateTimeImmutable
        && $now < $startsAt
    ) {
        return 'scheduled';
    }

    if (
        $endsAt instanceof DateTimeImmutable
        && $now > $endsAt
    ) {
        return 'expired';
    }

    return 'active';
}

function dc_promotion_status_label(
    string $status
): string {
    return match ($status) {
        'active' => 'Currently Displayed',
        'scheduled' => 'Scheduled',
        'expired' => 'Expired',
        'hidden' => 'Hidden',
        default => 'Unknown',
    };
}

function dc_promotion_status_badge(
    string $status
): string {
    return match ($status) {
        'active' => 'text-bg-success',
        'scheduled' => 'text-bg-info',
        'expired' => 'text-bg-secondary',
        'hidden' => 'text-bg-dark',
        default => 'text-bg-light',
    };
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function dc_normalize_promotion(
    array $row
): array {
    $row['id'] =
        (int) (
            $row['id']
            ?? 0
        );

    $row['sort_order'] =
        (int) (
            $row['sort_order']
            ?? 0
        );

    $row['is_active'] =
        (int) (
            $row['is_active']
            ?? 0
        );

    foreach (
        [
            'promotion_type',
            'seasonal_theme',
            'title',
            'message',
            'button_label',
            'button_url',
            'starts_at',
            'ends_at',
        ]
        as $key
    ) {
        $row[$key] = trim(
            (string) (
                $row[$key]
                ?? ''
            )
        );
    }

    $typeDetails =
        dc_promotion_type_details(
            (string) $row[
                'promotion_type'
            ]
        );

    $row['type_label'] =
        $typeDetails['label'];

    $row['type_description'] =
        $typeDetails['description'];

    $seasonalThemes =
        dc_seasonal_theme_options();

    $seasonalTheme = trim(
        (string) (
            $row['seasonal_theme']
            ?? ''
        )
    );

    if (
        (string) $row['promotion_type']
        !== 'seasonal'
    ) {
        $seasonalTheme = '';
    } elseif (
        !isset($seasonalThemes[$seasonalTheme])
    ) {
        $seasonalTheme = 'default';
    }

    $seasonalThemeDetails =
        dc_seasonal_theme_details(
            $seasonalTheme
        );

    $row['seasonal_theme'] =
        $seasonalTheme;

    $row['seasonal_theme_label'] =
        $seasonalTheme !== ''
            ? $seasonalThemeDetails['label']
            : '';

    $row['seasonal_theme_description'] =
        $seasonalTheme !== ''
            ? $seasonalThemeDetails['description']
            : '';

    $row['seasonal_theme_eyebrow'] =
        $seasonalTheme !== ''
            ? $seasonalThemeDetails['eyebrow']
            : '';

    $row['seasonal_theme_icon'] =
        $seasonalTheme !== ''
            ? $seasonalThemeDetails['icon']
            : '';

    $row['status'] =
        dc_promotion_status($row);

    return $row;
}

/**
 * Retrieve every promotion in administrator priority order.
 *
 * @return array<int, array<string, mixed>>
 */
function dc_promotions(
    bool $refresh = false
): array {
    $cache =&
        dc_promotion_cache();

    if (
        !$refresh
        && array_key_exists(
            'all',
            $cache
        )
    ) {
        return $cache['all'];
    }

    $promotions = [];
    $pdo = database();

    if ($pdo === null) {
        $cache['all'] = [];

        return [];
    }

    try {
        $statement = $pdo->query(
            'SELECT *
             FROM promotions
             ORDER BY
                sort_order,
                id'
        );

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $promotions[] =
                    dc_normalize_promotion(
                        $row
                    );
            }
        }
    } catch (Throwable $exception) {
        log_message(
            'Unable to read promotions: '
            . $exception->getMessage()
        );
    }

    $cache['all'] =
        $promotions;

    return $promotions;
}

/**
 * Retrieve one promotion by ID.
 *
 * @return array<string, mixed>|null
 */
function dc_promotion(
    int $promotionId,
    bool $refresh = false
): ?array {
    if ($promotionId < 1) {
        return null;
    }

    foreach (
        dc_promotions($refresh)
        as $promotion
    ) {
        if (
            (int) $promotion['id']
            === $promotionId
        ) {
            return $promotion;
        }
    }

    return null;
}

/**
 * Retrieve the highest-priority promotion that is currently eligible.
 *
 * @return array<string, mixed>|null
 */
function dc_current_promotion(
    bool $refresh = false
): ?array {
    $cache =&
        dc_promotion_cache();

    if (
        !$refresh
        && array_key_exists(
            'current',
            $cache
        )
    ) {
        return $cache['current'];
    }

    $pdo = database();

    if ($pdo === null) {
        $cache['current'] = null;

        return null;
    }

    $now =
        date('Y-m-d H:i:s');

    try {
        $statement = $pdo->prepare(
            'SELECT *
             FROM promotions
             WHERE is_active = 1
               AND (
                    starts_at IS NULL
                    OR starts_at <= :starts_now
               )
               AND (
                    ends_at IS NULL
                    OR ends_at >= :ends_now
               )
             ORDER BY
                sort_order,
                id DESC
             LIMIT 1'
        );

        $statement->execute([
            'starts_now' => $now,
            'ends_now' => $now,
        ]);

        $row =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        $promotion =
            is_array($row)
                ? dc_normalize_promotion($row)
                : null;

        $cache['current'] =
            $promotion;

        return $promotion;
    } catch (Throwable $exception) {
        log_message(
            'Unable to read the current promotion: '
            . $exception->getMessage()
        );

        $cache['current'] = null;

        return null;
    }
}

/**
 * @param array<string, mixed> $input
 */
function dc_create_promotion(
    array $input
): ?int {
    [
        $promotion,
        $validationError,
    ] = dc_validate_promotion($input);

    if (
        $promotion === null
        || $validationError !== null
    ) {
        return null;
    }

    $pdo = database();

    if ($pdo === null) {
        return null;
    }

    try {
        $sortOrder = (int) $pdo
            ->query(
                'SELECT
                    COALESCE(
                        MAX(sort_order),
                        0
                    ) + 10
                 FROM promotions'
            )
            ->fetchColumn();

        $statement = $pdo->prepare(
            'INSERT INTO promotions (
                promotion_type,
                seasonal_theme,
                title,
                message,
                button_label,
                button_url,
                starts_at,
                ends_at,
                sort_order,
                is_active
             ) VALUES (
                :promotion_type,
                :seasonal_theme,
                :title,
                :message,
                :button_label,
                :button_url,
                :starts_at,
                :ends_at,
                :sort_order,
                :is_active
             )'
        );

        $statement->execute([
            'promotion_type' =>
                $promotion[
                    'promotion_type'
                ],

            'seasonal_theme' =>
                $promotion[
                    'seasonal_theme'
                ],

            'title' =>
                $promotion['title'],

            'message' =>
                $promotion['message'],

            'button_label' =>
                $promotion[
                    'button_label'
                ],

            'button_url' =>
                $promotion[
                    'button_url'
                ],

            'starts_at' =>
                $promotion['starts_at'],

            'ends_at' =>
                $promotion['ends_at'],

            'sort_order' =>
                max(10, $sortOrder),

            'is_active' =>
                $promotion['is_active'],
        ]);

        dc_forget_promotion_cache();

        return (int) $pdo
            ->lastInsertId();
    } catch (Throwable $exception) {
        log_message(
            'Unable to create promotion: '
            . $exception->getMessage()
        );

        return null;
    }
}

/**
 * @param array<string, mixed> $input
 */
function dc_update_promotion(
    int $promotionId,
    array $input
): bool {
    if ($promotionId < 1) {
        return false;
    }

    [
        $promotion,
        $validationError,
    ] = dc_validate_promotion($input);

    if (
        $promotion === null
        || $validationError !== null
    ) {
        return false;
    }

    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'UPDATE promotions
             SET
                promotion_type =
                    :promotion_type,

                seasonal_theme =
                    :seasonal_theme,

                title =
                    :title,

                message =
                    :message,

                button_label =
                    :button_label,

                button_url =
                    :button_url,

                starts_at =
                    :starts_at,

                ends_at =
                    :ends_at,

                is_active =
                    :is_active

             WHERE id = :id'
        );

        $updated = $statement->execute([
            'promotion_type' =>
                $promotion[
                    'promotion_type'
                ],

            'seasonal_theme' =>
                $promotion[
                    'seasonal_theme'
                ],

            'title' =>
                $promotion['title'],

            'message' =>
                $promotion['message'],

            'button_label' =>
                $promotion[
                    'button_label'
                ],

            'button_url' =>
                $promotion[
                    'button_url'
                ],

            'starts_at' =>
                $promotion['starts_at'],

            'ends_at' =>
                $promotion['ends_at'],

            'is_active' =>
                $promotion['is_active'],

            'id' =>
                $promotionId,
        ]);

        if ($updated) {
            dc_forget_promotion_cache();
        }

        return $updated;
    } catch (Throwable $exception) {
        log_message(
            'Unable to update promotion: '
            . $exception->getMessage()
        );

        return false;
    }
}

function dc_delete_promotion(
    int $promotionId
): bool {
    if ($promotionId < 1) {
        return false;
    }

    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'DELETE FROM promotions
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $promotionId,
        ]);

        $deleted =
            $statement->rowCount() > 0;

        if ($deleted) {
            dc_forget_promotion_cache();
        }

        return $deleted;
    } catch (Throwable $exception) {
        log_message(
            'Unable to delete promotion: '
            . $exception->getMessage()
        );

        return false;
    }
}

/**
 * Move a promotion one position higher or lower in display priority.
 */
function dc_reorder_promotion(
    int $promotionId,
    string $direction
): bool {
    if (
        $promotionId < 1
        || !in_array(
            $direction,
            ['up', 'down'],
            true
        )
    ) {
        return false;
    }

    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    try {
        $statement = $pdo->query(
            'SELECT id
             FROM promotions
             ORDER BY
                sort_order,
                id'
        );

        $promotionIds = array_map(
            'intval',
            $statement->fetchAll(
                PDO::FETCH_COLUMN
            )
        );

        $currentIndex =
            array_search(
                $promotionId,
                $promotionIds,
                true
            );

        if ($currentIndex === false) {
            return false;
        }

        $targetIndex =
            $direction === 'up'
                ? $currentIndex - 1
                : $currentIndex + 1;

        if (
            !isset(
                $promotionIds[
                    $targetIndex
                ]
            )
        ) {
            return false;
        }

        [
            $promotionIds[$currentIndex],
            $promotionIds[$targetIndex],
        ] = [
            $promotionIds[$targetIndex],
            $promotionIds[$currentIndex],
        ];

        $pdo->beginTransaction();

        $update = $pdo->prepare(
            'UPDATE promotions
             SET sort_order = :sort_order
             WHERE id = :id'
        );

        foreach (
            $promotionIds
            as $index => $id
        ) {
            $update->execute([
                'sort_order' =>
                    ($index + 1) * 10,

                'id' =>
                    $id,
            ]);
        }

        $pdo->commit();

        dc_forget_promotion_cache();

        return true;
    } catch (Throwable $exception) {
        if (
            $pdo->inTransaction()
        ) {
            $pdo->rollBack();
        }

        log_message(
            'Unable to reorder promotion: '
            . $exception->getMessage()
        );

        return false;
    }
}

/**
 * Retrieve the highest-priority currently eligible promotion of each type.
 *
 * Announcement, special, seasonal, and important promotions may therefore
 * appear simultaneously. When multiple eligible records share a type, the
 * first record in administrator priority order is selected for that slot.
 *
 * @return array<string, array<string, mixed>>
 */
function dc_current_promotions_by_type(
    bool $refresh = false
): array {
    $currentPromotions = [];
    $validTypes = dc_promotion_type_options();

    foreach (dc_promotions($refresh) as $promotion) {
        $promotionType = (string) (
            $promotion['promotion_type']
            ?? ''
        );

        if (
            !isset($validTypes[$promotionType])
            || isset($currentPromotions[$promotionType])
            || dc_promotion_status($promotion) !== 'active'
        ) {
            continue;
        }

        $currentPromotions[$promotionType] = $promotion;
    }

    return $currentPromotions;
}

/**
 * Retrieve the currently displayed record for one visual promotion type.
 *
 * @return array<string, mixed>|null
 */
function dc_current_promotion_for_type(
    string $promotionType,
    bool $refresh = false
): ?array {
    $currentPromotions =
        dc_current_promotions_by_type($refresh);

    return $currentPromotions[$promotionType]
        ?? null;
}

/**
 * @return array<int, int>
 */
function dc_current_promotion_ids(
    bool $refresh = false
): array {
    return array_values(
        array_map(
            static fn (array $promotion): int =>
                (int) ($promotion['id'] ?? 0),
            dc_current_promotions_by_type($refresh)
        )
    );
}