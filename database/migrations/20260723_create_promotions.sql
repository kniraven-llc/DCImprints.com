CREATE TABLE IF NOT EXISTS promotions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    promotion_type VARCHAR(20) NOT NULL DEFAULT 'announcement',
    title VARCHAR(100) NOT NULL,
    message VARCHAR(300) NOT NULL,
    button_label VARCHAR(40) NULL,
    button_url VARCHAR(2048) NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    sort_order INT NOT NULL DEFAULT 10,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX idx_promotions_display (
        is_active,
        sort_order,
        starts_at,
        ends_at
    ),

    INDEX idx_promotions_schedule (
        starts_at,
        ends_at
    )
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8mb4
COLLATE=utf8mb4_unicode_ci;