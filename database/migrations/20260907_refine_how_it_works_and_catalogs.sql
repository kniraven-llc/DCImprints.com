/*
 * Follow-up revision:
 * - The public call-to-action is now the How It Works section.
 * - How It Works is managed from the Call to Action admin section.
 * - The old standalone callout fields are retired.
 * - The catalog help heading/paragraph are retired.
 * - Existing seeded catalogs use their supplier names as logo placeholders.
 */

UPDATE site_content
SET section_key = 'services_callout'
WHERE content_key IN (
    'how_it_works_eyebrow',
    'how_it_works_heading',
    'how_it_works_step_1_title',
    'how_it_works_step_1_text',
    'how_it_works_step_2_title',
    'how_it_works_step_2_text',
    'how_it_works_step_3_title',
    'how_it_works_step_3_text',
    'how_it_works_step_4_title',
    'how_it_works_step_4_text',
    'how_it_works_button_label'
);

UPDATE site_content
SET admin_label = 'Heading above the catalog logos'
WHERE content_key = 'catalog_panel_eyebrow';

DELETE FROM site_content
WHERE content_key IN (
    'quote_band_heading',
    'quote_band_text',
    'quote_band_button_label',
    'catalog_panel_heading',
    'catalog_panel_intro'
);

UPDATE partners
SET placeholder_label = name
WHERE partner_type = 'catalog'
  AND (
      placeholder_label IS NULL
      OR TRIM(placeholder_label) = ''
      OR placeholder_label = 'Catalog'
  );
