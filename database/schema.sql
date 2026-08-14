CREATE TABLE IF NOT EXISTS site_content (
    content_key VARCHAR(100) NOT NULL PRIMARY KEY,
    content_value TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO site_content (content_key, content_value) VALUES
    ('home_heading', 'Custom apparel and promotional products for local organizations.'),
    ('home_intro', 'DC Imprints provides screen printing, embroidery, promotional products, corporate apparel, and custom web stores from DeForest, Wisconsin.'),
    ('services_intro', 'We help businesses, schools, teams, and community organizations create apparel and promotional products that represent them well.'),
    ('about_body', 'DC Imprints is a locally operated imprinting business serving DeForest, Dane County, and surrounding communities.'),
    ('contact_intro', 'Tell us what you need, the quantity, and your preferred timeline. We will follow up to discuss the project.'),
    ('supplier_links', 'Supplier catalog links will be added here.');
