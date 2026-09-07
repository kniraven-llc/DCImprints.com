/*
 * Add persistent website staging.
 *
 * The existing tables remain the published/live source of truth.
 * Matching *_draft tables hold all administrator edits until the global
 * Publish Changes action copies the complete draft to the live tables.
 *
 * media_assets is intentionally shared. Uploaded files may be referenced by
 * either live or draft records, while the application prevents deletion until
 * an asset is unused by both states.
 */

CREATE TABLE IF NOT EXISTS site_draft_state (
    id TINYINT UNSIGNED NOT NULL,
    has_changes TINYINT(1) NOT NULL DEFAULT 0,
    draft_updated_at TIMESTAMP NULL DEFAULT NULL,
    last_published_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO site_draft_state (
    id,
    has_changes,
    draft_updated_at,
    last_published_at
) VALUES (
    1,
    0,
    CURRENT_TIMESTAMP,
    NULL
);

CREATE TABLE IF NOT EXISTS site_settings_draft LIKE site_settings;
CREATE TABLE IF NOT EXISTS site_content_draft LIKE site_content;
CREATE TABLE IF NOT EXISTS site_media_slots_draft LIKE site_media_slots;
CREATE TABLE IF NOT EXISTS services_draft LIKE services;
CREATE TABLE IF NOT EXISTS testimonials_draft LIKE testimonials;
CREATE TABLE IF NOT EXISTS profiles_draft LIKE profiles;
CREATE TABLE IF NOT EXISTS partners_draft LIKE partners;
CREATE TABLE IF NOT EXISTS promotions_draft LIKE promotions;

/*
 * Seed only empty draft tables. Re-running this migration must never overwrite
 * an existing unpublished draft.
 */
INSERT INTO site_settings_draft
SELECT *
FROM site_settings
WHERE NOT EXISTS (
    SELECT 1
    FROM site_settings_draft
    LIMIT 1
)
AND COALESCE(
    (
        SELECT has_changes
        FROM site_draft_state
        WHERE id = 1
        LIMIT 1
    ),
    0
) = 0;

INSERT INTO site_content_draft
SELECT *
FROM site_content
WHERE NOT EXISTS (
    SELECT 1
    FROM site_content_draft
    LIMIT 1
)
AND COALESCE(
    (
        SELECT has_changes
        FROM site_draft_state
        WHERE id = 1
        LIMIT 1
    ),
    0
) = 0;

INSERT INTO site_media_slots_draft
SELECT *
FROM site_media_slots
WHERE NOT EXISTS (
    SELECT 1
    FROM site_media_slots_draft
    LIMIT 1
)
AND COALESCE(
    (
        SELECT has_changes
        FROM site_draft_state
        WHERE id = 1
        LIMIT 1
    ),
    0
) = 0;

INSERT INTO services_draft
SELECT *
FROM services
WHERE NOT EXISTS (
    SELECT 1
    FROM services_draft
    LIMIT 1
)
AND COALESCE(
    (
        SELECT has_changes
        FROM site_draft_state
        WHERE id = 1
        LIMIT 1
    ),
    0
) = 0;

INSERT INTO testimonials_draft
SELECT *
FROM testimonials
WHERE NOT EXISTS (
    SELECT 1
    FROM testimonials_draft
    LIMIT 1
)
AND COALESCE(
    (
        SELECT has_changes
        FROM site_draft_state
        WHERE id = 1
        LIMIT 1
    ),
    0
) = 0;

INSERT INTO profiles_draft
SELECT *
FROM profiles
WHERE NOT EXISTS (
    SELECT 1
    FROM profiles_draft
    LIMIT 1
)
AND COALESCE(
    (
        SELECT has_changes
        FROM site_draft_state
        WHERE id = 1
        LIMIT 1
    ),
    0
) = 0;

INSERT INTO partners_draft
SELECT *
FROM partners
WHERE NOT EXISTS (
    SELECT 1
    FROM partners_draft
    LIMIT 1
)
AND COALESCE(
    (
        SELECT has_changes
        FROM site_draft_state
        WHERE id = 1
        LIMIT 1
    ),
    0
) = 0;

INSERT INTO promotions_draft
SELECT *
FROM promotions
WHERE NOT EXISTS (
    SELECT 1
    FROM promotions_draft
    LIMIT 1
)
AND COALESCE(
    (
        SELECT has_changes
        FROM site_draft_state
        WHERE id = 1
        LIMIT 1
    ),
    0
) = 0;
