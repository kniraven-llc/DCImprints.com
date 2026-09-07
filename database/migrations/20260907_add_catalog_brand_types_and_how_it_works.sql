SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partners'
      AND COLUMN_NAME = 'partner_type'
);

SET @column_sql = IF(
    @column_exists = 0,
    'ALTER TABLE partners
        ADD COLUMN partner_type VARCHAR(20) NOT NULL DEFAULT ''brand''
        AFTER id',
    'SELECT 1'
);

PREPARE column_statement FROM @column_sql;
EXECUTE column_statement;
DEALLOCATE PREPARE column_statement;

UPDATE partners
SET partner_type = 'brand'
WHERE partner_type IS NULL
   OR partner_type NOT IN ('brand', 'catalog');

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partners'
      AND INDEX_NAME = 'idx_partners_type_active_order'
);

SET @index_sql = IF(
    @index_exists = 0,
    'ALTER TABLE partners
        ADD INDEX idx_partners_type_active_order (
            partner_type,
            is_active,
            sort_order
        )',
    'SELECT 1'
);

PREPARE index_statement FROM @index_sql;
EXECUTE index_statement;
DEALLOCATE PREPARE index_statement;

INSERT INTO partners (
    partner_type,
    name,
    catalog_url,
    logo_asset_id,
    placeholder_label,
    sort_order,
    is_active
)
SELECT
    'catalog',
    'Sanmar',
    'https://www.sanmar.com/',
    NULL,
    'Catalog',
    10,
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM partners
    WHERE partner_type = 'catalog'
      AND name = 'Sanmar'
);

INSERT INTO partners (
    partner_type,
    name,
    catalog_url,
    logo_asset_id,
    placeholder_label,
    sort_order,
    is_active
)
SELECT
    'catalog',
    'S&S Activewear',
    'https://www.ssactivewear.com/',
    NULL,
    'Catalog',
    20,
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM partners
    WHERE partner_type = 'catalog'
      AND name = 'S&S Activewear'
);

INSERT INTO partners (
    partner_type,
    name,
    catalog_url,
    logo_asset_id,
    placeholder_label,
    sort_order,
    is_active
)
SELECT
    'catalog',
    'Driving Impressions',
    'https://www.drivingi.com/?v=home',
    NULL,
    'Catalog',
    30,
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM partners
    WHERE partner_type = 'catalog'
      AND name = 'Driving Impressions'
);

INSERT INTO partners (
    partner_type,
    name,
    catalog_url,
    logo_asset_id,
    placeholder_label,
    sort_order,
    is_active
)
SELECT
    'catalog',
    'White Bear Clothing',
    'https://www.whitebearclothing.com/20/home.htm',
    NULL,
    'Catalog',
    40,
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM partners
    WHERE partner_type = 'catalog'
      AND name = 'White Bear Clothing'
);

INSERT INTO site_content (
    content_key,
    section_key,
    admin_label,
    content_value,
    field_type,
    max_length,
    sort_order,
    is_editable
) VALUES
    (
        'how_it_works_eyebrow',
        'services',
        'How It Works small heading',
        'How It Works',
        'text',
        60,
        50,
        1
    ),
    (
        'how_it_works_heading',
        'services',
        'How It Works heading',
        'From Idea to Finished Product',
        'text',
        120,
        60,
        1
    ),
    (
        'how_it_works_step_1_title',
        'services',
        'How It Works step 1 title',
        'Tell us what you need',
        'text',
        100,
        70,
        1
    ),
    (
        'how_it_works_step_1_text',
        'services',
        'How It Works step 1 description',
        'Send us your idea, artwork, quantity and deadline.',
        'textarea',
        260,
        80,
        1
    ),
    (
        'how_it_works_step_2_title',
        'services',
        'How It Works step 2 title',
        'We’ll help you figure it out',
        'text',
        100,
        90,
        1
    ),
    (
        'how_it_works_step_2_text',
        'services',
        'How It Works step 2 description',
        'We’ll recommend the right products, decoration method and options.',
        'textarea',
        260,
        100,
        1
    ),
    (
        'how_it_works_step_3_title',
        'services',
        'How It Works step 3 title',
        'Approve your project',
        'text',
        100,
        110,
        1
    ),
    (
        'how_it_works_step_3_text',
        'services',
        'How It Works step 3 description',
        'Review artwork and pricing.',
        'textarea',
        260,
        120,
        1
    ),
    (
        'how_it_works_step_4_title',
        'services',
        'How It Works step 4 title',
        'We make it',
        'text',
        100,
        130,
        1
    ),
    (
        'how_it_works_step_4_text',
        'services',
        'How It Works step 4 description',
        'We produce your apparel and products and get them to you.',
        'textarea',
        260,
        140,
        1
    ),
    (
        'how_it_works_button_label',
        'services',
        'How It Works button label',
        'Start Your Project',
        'text',
        50,
        150,
        1
    ),
    (
        'catalog_brands_label',
        'catalogs',
        'Heading above brand logos',
        'Some of our brands',
        'text',
        80,
        35,
        1
    )
ON DUPLICATE KEY UPDATE
    section_key = VALUES(section_key),
    admin_label = VALUES(admin_label),
    field_type = VALUES(field_type),
    max_length = VALUES(max_length),
    sort_order = VALUES(sort_order),
    is_editable = VALUES(is_editable);

UPDATE site_content
SET content_value = 'Some of our catalogs'
WHERE content_key = 'catalog_panel_eyebrow'
  AND content_value = 'Browse Catalogs';
