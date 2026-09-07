<?php

declare(strict_types=1);

/**
 * Draft / preview / publish support for website content.
 *
 * Normal public requests read the live tables. The administration interface
 * and the private preview request define DC_CONTENT_MODE as "draft", causing
 * all editable website content reads and writes to use the shadow tables.
 */

/**
 * @return array<int, string>
 */
function dc_staging_tables(): array
{
    return [
        'site_settings',
        'site_content',
        'site_media_slots',
        'services',
        'testimonials',
        'profiles',
        'partners',
        'promotions',
    ];
}

function dc_content_mode(): string
{
    if (
        defined('DC_CONTENT_MODE')
        && DC_CONTENT_MODE === 'draft'
    ) {
        return 'draft';
    }

    return 'live';
}

function dc_is_draft_mode(): bool
{
    return dc_content_mode() === 'draft';
}

function dc_is_preview_mode(): bool
{
    return defined('DC_PREVIEW_MODE')
        && DC_PREVIEW_MODE === true;
}

/**
 * Resolve a trusted editable table to its live or draft storage name.
 */
function dc_staging_table(string $table): string
{
    if (
        dc_is_draft_mode()
        && in_array(
            $table,
            dc_staging_tables(),
            true
        )
    ) {
        return $table . '_draft';
    }

    return $table;
}

/**
 * Mark the persistent website draft as containing unpublished changes.
 */
function dc_staging_mark_dirty(): void
{
    if (!dc_is_draft_mode()) {
        return;
    }

    $pdo = database();

    if ($pdo === null) {
        return;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO site_draft_state (
                id,
                has_changes,
                draft_updated_at
             ) VALUES (
                1,
                1,
                CURRENT_TIMESTAMP
             )
             ON DUPLICATE KEY UPDATE
                has_changes = 1,
                draft_updated_at = CURRENT_TIMESTAMP'
        );

        $statement->execute();
    } catch (Throwable $exception) {
        log_message(
            'Unable to mark website draft as changed: '
            . $exception->getMessage()
        );
    }
}

/**
 * @return array<string, mixed>
 */
function dc_staging_status(): array
{
    $fallback = [
        'has_changes' => false,
        'draft_updated_at' => null,
        'last_published_at' => null,
    ];

    $pdo = database();

    if ($pdo === null) {
        return $fallback;
    }

    try {
        $statement = $pdo->query(
            'SELECT
                has_changes,
                draft_updated_at,
                last_published_at
             FROM site_draft_state
             WHERE id = 1
             LIMIT 1'
        );

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return $fallback;
        }

        return [
            'has_changes' =>
                (int) ($row['has_changes'] ?? 0) === 1,
            'draft_updated_at' =>
                $row['draft_updated_at'] ?? null,
            'last_published_at' =>
                $row['last_published_at'] ?? null,
        ];
    } catch (Throwable $exception) {
        log_message(
            'Unable to read website draft status: '
            . $exception->getMessage()
        );

        return $fallback;
    }
}

function dc_staging_has_changes(): bool
{
    return !empty(
        dc_staging_status()['has_changes']
    );
}

/**
 * Copy one entire table while preserving primary keys and record ordering.
 */
function dc_staging_copy_table(
    PDO $pdo,
    string $source,
    string $destination
): void {
    $allowed = [];

    foreach (dc_staging_tables() as $table) {
        $allowed[] = $table;
        $allowed[] = $table . '_draft';
    }

    if (
        !in_array($source, $allowed, true)
        || !in_array($destination, $allowed, true)
    ) {
        throw new InvalidArgumentException(
            'Unsupported staging table.'
        );
    }

    $pdo->exec(
        'DELETE FROM `' . $destination . '`'
    );

    $pdo->exec(
        'INSERT INTO `' . $destination . '` '
        . 'SELECT * FROM `' . $source . '`'
    );
}

function dc_staging_clear_runtime_caches(): void
{
    if (function_exists('dc_forget_content_cache')) {
        dc_forget_content_cache();
    }

    if (function_exists('dc_forget_promotion_cache')) {
        dc_forget_promotion_cache();
    }
}

function dc_staging_cleanup_media(): void
{
    if (
        function_exists(
            'dc_cleanup_unused_managed_media_assets'
        )
    ) {
        dc_cleanup_unused_managed_media_assets();
    }
}

function dc_staging_ensure_state_row(PDO $pdo): void
{
    $pdo->exec(
        'INSERT IGNORE INTO site_draft_state (
            id,
            has_changes,
            draft_updated_at
         ) VALUES (
            1,
            0,
            CURRENT_TIMESTAMP
         )'
    );
}

/**
 * Refuse to publish an obviously uninitialized or damaged draft snapshot.
 */
function dc_staging_draft_is_publishable(PDO $pdo): bool
{
    foreach (
        [
            'site_settings_draft',
            'site_content_draft',
            'site_media_slots_draft',
        ]
        as $table
    ) {
        $count = (int) $pdo
            ->query(
                'SELECT COUNT(*) FROM `' . $table . '`'
            )
            ->fetchColumn();

        if ($count < 1) {
            return false;
        }
    }

    return true;
}

/**
 * Atomically replace the live website content with the staged draft.
 */
function dc_staging_publish(): bool
{
    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    try {
        if (!dc_staging_draft_is_publishable($pdo)) {
            log_message(
                'Website draft publish was refused because the draft snapshot is incomplete.'
            );

            return false;
        }

        $pdo->beginTransaction();

        dc_staging_ensure_state_row($pdo);

        $pdo->query(
            'SELECT id
             FROM site_draft_state
             WHERE id = 1
             FOR UPDATE'
        );

        foreach (dc_staging_tables() as $table) {
            dc_staging_copy_table(
                $pdo,
                $table . '_draft',
                $table
            );
        }

        $statement = $pdo->prepare(
            'UPDATE site_draft_state
             SET
                has_changes = 0,
                last_published_at = CURRENT_TIMESTAMP
             WHERE id = 1'
        );

        $statement->execute();

        $pdo->commit();

        dc_staging_clear_runtime_caches();
        dc_staging_cleanup_media();

        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        log_message(
            'Unable to publish website draft: '
            . $exception->getMessage()
        );

        return false;
    }
}

/**
 * Discard every staged website change and rebuild the draft from live data.
 */
function dc_staging_discard(): bool
{
    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    try {
        $pdo->beginTransaction();

        dc_staging_ensure_state_row($pdo);

        $pdo->query(
            'SELECT id
             FROM site_draft_state
             WHERE id = 1
             FOR UPDATE'
        );

        foreach (dc_staging_tables() as $table) {
            dc_staging_copy_table(
                $pdo,
                $table,
                $table . '_draft'
            );
        }

        $statement = $pdo->prepare(
            'UPDATE site_draft_state
             SET
                has_changes = 0,
                draft_updated_at = CURRENT_TIMESTAMP
             WHERE id = 1'
        );

        $statement->execute();

        $pdo->commit();

        dc_staging_clear_runtime_caches();
        dc_staging_cleanup_media();

        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        log_message(
            'Unable to discard website draft: '
            . $exception->getMessage()
        );

        return false;
    }
}
