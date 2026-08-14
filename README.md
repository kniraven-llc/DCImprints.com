# DCImprints.com

A custom PHP and Bootstrap 5 website for DC Imprints.

## Site structure

The public-facing website intentionally uses a single-page layout.

Public routes:

- `/` — Main one-page website
- `/admin/` — Website administration
- `/admin/login/` — Administrator sign-in
- `/404.php` — Custom not-found response

The homepage contains sections for services, customer reviews, company
information, catalogs and partners, quote requests, and the business location.

Navigation links point to sections of the homepage. Separate public pages for
services, about, and contact are not part of the intended design.

## Document root

The web server document root must point to:

    DCImprints.com/public

Do not expose the project root as the public web directory.

## Local setup

1. Copy `.env.example` to `.env`.
2. Set `APP_URL`.
3. Create a MySQL database.
4. Import `database/schema.sql`.
5. Add the database credentials to `.env`.
6. Generate an administrator password hash.
7. Add the hash to `ADMIN_PASSWORD_HASH`.
8. Set `MAIL_TO` to the quote-request inbox.
9. Confirm that PHP email delivery is configured.

The actual `.env` file must not be committed.

## Writable directories

The web-server user must be able to write to:

- `storage/logs/`
- `public/uploads/gallery/`
- `public/uploads/content/`

## Static assets

Static website assets are stored under:

- `public/assets/css/`
- `public/assets/js/`
- `public/assets/images/brand/`
- `public/assets/images/content/`
- `public/assets/images/partners/`
- `public/assets/video/`

The primary brand mark is expected at:

    public/assets/images/brand/sasquatch-mark.svg

## Apache

Apache must allow overrides for the `public` directory so the included
`.htaccess` files work.

## Database behavior

The homepage contains fallback content and can render before the database is
configured. Publishing through the administration interface requires a working
database connection.

Quote submissions are sent by email and are not stored in the database.

## Requirements

- PHP 8.1 or newer
- MySQL or MariaDB
- Apache with mod_rewrite
- PHP Fileinfo extension
- PHP Mbstring extension
- PHP PDO extension
