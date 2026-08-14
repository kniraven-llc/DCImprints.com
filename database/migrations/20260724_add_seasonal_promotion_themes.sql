SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'promotions'
      AND COLUMN_NAME = 'seasonal_theme'
);

SET @column_sql = IF(
    @column_exists = 0,
    'ALTER TABLE promotions
        ADD COLUMN seasonal_theme varchar(40) NULL
        AFTER promotion_type',
    'SELECT 1'
);

PREPARE column_statement FROM @column_sql;
EXECUTE column_statement;
DEALLOCATE PREPARE column_statement;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'promotions'
      AND INDEX_NAME = 'idx_promotions_type_theme'
);

SET @index_sql = IF(
    @index_exists = 0,
    'ALTER TABLE promotions
        ADD INDEX idx_promotions_type_theme (
            promotion_type,
            seasonal_theme
        )',
    'SELECT 1'
);

PREPARE index_statement FROM @index_sql;
EXECUTE index_statement;
DEALLOCATE PREPARE index_statement;

UPDATE promotions
SET seasonal_theme = 'default'
WHERE promotion_type = 'seasonal'
  AND (
      seasonal_theme IS NULL
      OR seasonal_theme = ''
  );

UPDATE promotions
SET seasonal_theme = NULL
WHERE promotion_type <> 'seasonal';