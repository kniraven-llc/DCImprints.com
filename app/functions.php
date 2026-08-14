<?php

declare(strict_types=1);

/**
 * Escape text for safe HTML output.
 */
function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Read the application configuration.
 */
function app_config(
    ?string $key = null
): mixed {
    static $config = null;

    $config ??= require
        APP_ROOT . '/config/app.php';

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? null;
}

/**
 * Create an absolute URL using the configured application URL.
 */
function url(string $path = ''): string
{
    $baseUrl = rtrim(
        (string) app_config('url'),
        '/'
    );

    if ($path === '') {
        return $baseUrl . '/';
    }

    return $baseUrl
        . '/'
        . ltrim($path, '/');
}

function is_post(): bool
{
    return strtoupper(
        (string) (
            $_SERVER['REQUEST_METHOD']
            ?? 'GET'
        )
    ) === 'POST';
}

/**
 * Redirect immediately to another URL or site-relative path.
 */
function redirect(string $location): never
{
    header(
        'Location: ' . $location
    );

    exit;
}

/**
 * Return the current session CSRF token, generating it when necessary.
 */
function csrf_token(): string
{
    if (
        !isset($_SESSION['csrf_token'])
        || !is_string(
            $_SESSION['csrf_token']
        )
        || $_SESSION['csrf_token'] === ''
    ) {
        $_SESSION['csrf_token'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Render the hidden CSRF field used by POST forms.
 */
function csrf_field(): string
{
    return sprintf(
        '<input type="hidden" '
        . 'name="csrf_token" '
        . 'value="%s">',
        e(csrf_token())
    );
}

function verify_csrf(
    ?string $token
): bool {
    return is_string($token)
        && $token !== ''
        && isset($_SESSION['csrf_token'])
        && is_string(
            $_SESSION['csrf_token']
        )
        && hash_equals(
            $_SESSION['csrf_token'],
            $token
        );
}

/**
 * Store or retrieve a one-time session message.
 *
 * Calling flash('success', 'Saved.') stores a message.
 * Calling flash('success') retrieves and removes it.
 */
function flash(
    string $key,
    ?string $value = null
): ?string {
    if ($value !== null) {
        $_SESSION['flash'][$key] =
            $value;

        return null;
    }

    $message =
        $_SESSION['flash'][$key]
        ?? null;

    unset(
        $_SESSION['flash'][$key]
    );

    return is_string($message)
        ? $message
        : null;
}

/**
 * Compatibility wrapper for the original website templates.
 *
 * The actual fallback values now live in app/content.php so there is only one
 * authoritative collection of default site copy.
 */
function default_content(
    string $key
): string {
    $defaults =
        dc_default_content_values();

    return $defaults[$key]
        ?? '';
}

/**
 * Compatibility wrapper for existing calls such as content('home_heading').
 *
 * New code may call dc_content() directly. Existing pages can continue using
 * content() while the templates are migrated.
 */
function content(string $key): string
{
    return dc_content($key);
}

/**
 * Compatibility wrapper for the current administration page.
 *
 * Only content records already present in site_content and marked editable
 * may be updated.
 *
 * @param array<string, mixed> $updates
 */
function update_content(
    array $updates
): bool {
    return dc_update_content_values(
        $updates
    );
}

/**
 * Return filenames from the original gallery upload directory.
 *
 * This remains temporarily available so the existing administration page
 * continues to function until it is replaced by the structured media editor.
 *
 * @return array<int, string>
 */
function gallery_images(): array
{
    $files = glob(
        PUBLIC_ROOT
        . '/uploads/gallery/'
        . '*.{jpg,jpeg,png,webp}',
        GLOB_BRACE
    ) ?: [];

    sort(
        $files,
        SORT_NATURAL | SORT_FLAG_CASE
    );

    return array_map(
        static fn (
            string $file
        ): string => basename($file),
        $files
    );
}

/**
 * Save an upload to the legacy gallery directory.
 *
 * New structured site media uses app/uploads.php instead.
 *
 * @param array<string, mixed> $file
 *
 * @return array{0: bool, 1: string}
 */
function save_gallery_upload(
    array $file
): array {
    $error = (int) (
        $file['error']
        ?? UPLOAD_ERR_NO_FILE
    );

    if ($error !== UPLOAD_ERR_OK) {
        return [
            false,
            dc_upload_error_message($error),
        ];
    }

    $size = (int) (
        $file['size']
        ?? 0
    );

    if ($size < 1) {
        return [
            false,
            'The selected image is empty.',
        ];
    }

    if (
        $size
        > 5 * 1024 * 1024
    ) {
        return [
            false,
            'The image must be 5 MB or smaller.',
        ];
    }

    $temporaryPath = (string) (
        $file['tmp_name']
        ?? ''
    );

    if (
        $temporaryPath === ''
        || !is_file($temporaryPath)
    ) {
        return [
            false,
            'The uploaded image could not be read.',
        ];
    }

    if (
        PHP_SAPI !== 'cli'
        && !is_uploaded_file(
            $temporaryPath
        )
    ) {
        return [
            false,
            'The submitted file was not a valid browser upload.',
        ];
    }

    try {
        $finfo = new finfo(
            FILEINFO_MIME_TYPE
        );

        $mime = $finfo->file(
            $temporaryPath
        );
    } catch (Throwable $exception) {
        log_message(
            'Unable to inspect gallery upload: '
            . $exception->getMessage()
        );

        return [
            false,
            'The server could not inspect the selected image.',
        ];
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (
        !is_string($mime)
        || !isset($extensions[$mime])
    ) {
        return [
            false,
            'Only JPG, PNG, and WebP images are allowed.',
        ];
    }

    $dimensions =
        @getimagesize(
            $temporaryPath
        );

    if ($dimensions === false) {
        return [
            false,
            'The uploaded file is not a valid image.',
        ];
    }

    $width = (int) (
        $dimensions[0]
        ?? 0
    );

    $height = (int) (
        $dimensions[1]
        ?? 0
    );

    if (
        $width < 1
        || $height < 1
        || $width * $height
            > DC_UPLOAD_MAX_IMAGE_PIXELS
    ) {
        return [
            false,
            'The image dimensions are invalid or too large.',
        ];
    }

    $directory =
        PUBLIC_ROOT
        . '/uploads/gallery';

    if (
        !is_dir($directory)
        && !mkdir(
            $directory,
            0755,
            true
        )
        && !is_dir($directory)
    ) {
        return [
            false,
            'The upload directory could not be created.',
        ];
    }

    if (!is_writable($directory)) {
        return [
            false,
            'The upload directory is not writable.',
        ];
    }

    try {
        $filename =
            date('Ymd-His')
            . '-'
            . bin2hex(
                random_bytes(8)
            )
            . '.'
            . $extensions[$mime];
    } catch (Throwable $exception) {
        log_message(
            'Unable to generate gallery filename: '
            . $exception->getMessage()
        );

        return [
            false,
            'The server could not prepare the image.',
        ];
    }

    $destination =
        $directory
        . '/'
        . $filename;

    $moved = move_uploaded_file(
        $temporaryPath,
        $destination
    );

    if (
        !$moved
        && PHP_SAPI === 'cli'
    ) {
        $moved = @rename(
            $temporaryPath,
            $destination
        );
    }

    if (!$moved) {
        return [
            false,
            'The image could not be saved.',
        ];
    }

    @chmod(
        $destination,
        0644
    );

    return [
        true,
        $filename,
    ];
}

/**
 * Write an application message to storage/logs/app.log.
 */
function log_message(
    string $message
): void {
    $directory =
        APP_ROOT
        . '/storage/logs';

    if (
        !is_dir($directory)
        && !@mkdir(
            $directory,
            0755,
            true
        )
        && !is_dir($directory)
    ) {
        return;
    }

    @file_put_contents(
        $directory . '/app.log',
        '['
        . date('c')
        . '] '
        . str_replace(
            [
                "\r\n",
                "\r",
                "\n",
            ],
            ' ',
            $message
        )
        . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}