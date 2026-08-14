<?php

declare(strict_types=1);

/**
 * Managed upload handling for DCImprints.com.
 *
 * Files are stored on disk under public/uploads/content/. MySQL stores only
 * the public path and file metadata. The administration interface never needs
 * to expose storage paths, generated filenames, MIME types, or database IDs.
 */

const DC_UPLOAD_IMAGE_MAX_BYTES = 10 * 1024 * 1024;
const DC_UPLOAD_VIDEO_MAX_BYTES = 50 * 1024 * 1024;
const DC_UPLOAD_ICON_MAX_BYTES = 2 * 1024 * 1024;
const DC_UPLOAD_MAX_IMAGE_PIXELS = 40_000_000;

/**
 * Return the rules for one administrator-facing upload purpose.
 *
 * SVG uploads are intentionally excluded. Existing trusted SVG files may stay
 * in assets/, but accepting arbitrary SVG uploads would allow active markup.
 * Transparent PNG and WebP files are supported for logos and brand artwork.
 *
 * @return array<string, mixed>|null
 */
function dc_upload_profile(string $purpose): ?array
{
    $imageMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $profiles = [
        'hero_image' => [
            'label' => 'hero image',
            'directory' => 'images',
            'max_bytes' => DC_UPLOAD_IMAGE_MAX_BYTES,
            'media_kind' => 'image',
            'mimes' => $imageMimes,
        ],
        'service_image' => [
            'label' => 'service image',
            'directory' => 'images',
            'max_bytes' => DC_UPLOAD_IMAGE_MAX_BYTES,
            'media_kind' => 'image',
            'mimes' => $imageMimes,
        ],
        'profile_image' => [
            'label' => 'profile image',
            'directory' => 'images',
            'max_bytes' => DC_UPLOAD_IMAGE_MAX_BYTES,
            'media_kind' => 'image',
            'mimes' => $imageMimes,
        ],
        'partner_logo' => [
            'label' => 'partner logo',
            'directory' => 'images',
            'max_bytes' => DC_UPLOAD_IMAGE_MAX_BYTES,
            'media_kind' => 'image',
            'mimes' => $imageMimes,
        ],
        'brand_mark' => [
            'label' => 'brand mark',
            'directory' => 'images',
            'max_bytes' => DC_UPLOAD_IMAGE_MAX_BYTES,
            'media_kind' => 'image',
            'mimes' => $imageMimes,
        ],
        'hero_video' => [
            'label' => 'hero video',
            'directory' => 'video',
            'max_bytes' => DC_UPLOAD_VIDEO_MAX_BYTES,
            'media_kind' => 'video',
            'mimes' => [
                'video/mp4' => 'mp4',
                'application/mp4' => 'mp4',
                'video/webm' => 'webm',
            ],
        ],
        'favicon' => [
            'label' => 'website icon',
            'directory' => 'icons',
            'max_bytes' => DC_UPLOAD_ICON_MAX_BYTES,
            'media_kind' => 'icon',
            'mimes' => [
                'image/png' => 'png',
                'image/vnd.microsoft.icon' => 'ico',
                'image/x-icon' => 'ico',
            ],
        ],
    ];

    return $profiles[$purpose] ?? null;
}

function dc_upload_help_text(string $purpose): string
{
    return match ($purpose) {
        'hero_video' =>
            'Upload an MP4 or WebM video no larger than 50 MB.',
        'favicon' =>
            'Upload a PNG or ICO file no larger than 2 MB.',
        'partner_logo', 'brand_mark' =>
            'Upload a JPG, PNG, or WebP file no larger than 10 MB. '
            . 'PNG or WebP is recommended for transparent logos.',
        default =>
            'Upload a JPG, PNG, or WebP image no larger than 10 MB.',
    };
}

function dc_upload_error_message(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_OK => '',
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE =>
            'The selected file is larger than the server allows.',
        UPLOAD_ERR_PARTIAL =>
            'The file was only partially uploaded. Please try again.',
        UPLOAD_ERR_NO_FILE =>
            'Select a file to upload.',
        UPLOAD_ERR_NO_TMP_DIR =>
            'The server upload folder is unavailable.',
        UPLOAD_ERR_CANT_WRITE =>
            'The server could not write the uploaded file.',
        UPLOAD_ERR_EXTENSION =>
            'A server extension stopped the upload.',
        default =>
            'The file could not be uploaded.',
    };
}

function dc_format_file_size(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        return rtrim(
            rtrim(
                number_format(
                    $bytes / (1024 * 1024),
                    1,
                    '.',
                    ''
                ),
                '0'
            ),
            '.'
        ) . ' MB';
    }

    return max(1, (int) ceil($bytes / 1024)) . ' KB';
}

/**
 * Confirm that a file with an ambiguous MIME result is structurally an ICO.
 */
function dc_is_valid_ico_file(string $path): bool
{
    $handle = @fopen($path, 'rb');

    if ($handle === false) {
        return false;
    }

    $header = fread($handle, 4);
    fclose($handle);

    return $header === "\x00\x00\x01\x00"
        || $header === "\x00\x00\x02\x00";
}

/**
 * Normalize common MIME aliases returned by different operating systems.
 */
function dc_normalize_upload_mime(
    string $mime,
    string $temporaryPath,
    string $purpose
): string {
    $mime = strtolower(trim($mime));

    $aliases = [
        'image/pjpeg' => 'image/jpeg',
        'image/jpg' => 'image/jpeg',
        'image/x-png' => 'image/png',
        'video/x-m4v' => 'video/mp4',
    ];

    $mime = $aliases[$mime] ?? $mime;

    if (
        $purpose === 'favicon'
        && $mime === 'application/octet-stream'
        && dc_is_valid_ico_file($temporaryPath)
    ) {
        return 'image/x-icon';
    }

    return $mime;
}

/**
 * Validate an uploaded file and return trusted metadata.
 *
 * @param array<string, mixed> $file
 *
 * @return array{0: bool, 1: string, 2: array<string, mixed>|null}
 */
function dc_validate_managed_upload(
    array $file,
    string $purpose
): array {
    $profile = dc_upload_profile($purpose);

    if ($profile === null) {
        return [
            false,
            'This upload location is not supported.',
            null,
        ];
    }

    $error = (int) (
        $file['error']
        ?? UPLOAD_ERR_NO_FILE
    );

    if ($error !== UPLOAD_ERR_OK) {
        return [
            false,
            dc_upload_error_message($error),
            null,
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
            'The uploaded file could not be read.',
            null,
        ];
    }

    if (
        PHP_SAPI !== 'cli'
        && !is_uploaded_file($temporaryPath)
    ) {
        return [
            false,
            'The submitted file was not a valid browser upload.',
            null,
        ];
    }

    $size = (int) (
        $file['size']
        ?? filesize($temporaryPath)
        ?: 0
    );

    if ($size < 1) {
        return [
            false,
            'The selected file is empty.',
            null,
        ];
    }

    $maximumBytes = (int) $profile['max_bytes'];

    if ($size > $maximumBytes) {
        return [
            false,
            sprintf(
                'The %s must be %s or smaller.',
                (string) $profile['label'],
                dc_format_file_size($maximumBytes)
            ),
            null,
        ];
    }

    try {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($temporaryPath);
    } catch (Throwable $exception) {
        log_message(
            'Unable to inspect uploaded file: '
            . $exception->getMessage()
        );

        return [
            false,
            'The server could not inspect the selected file.',
            null,
        ];
    }

    if (!is_string($detectedMime)) {
        return [
            false,
            'The selected file type could not be identified.',
            null,
        ];
    }

    $mime = dc_normalize_upload_mime(
        $detectedMime,
        $temporaryPath,
        $purpose
    );

    /** @var array<string, string> $allowedMimes */
    $allowedMimes = $profile['mimes'];

    if (!isset($allowedMimes[$mime])) {
        return [
            false,
            dc_upload_help_text($purpose),
            null,
        ];
    }

    $width = null;
    $height = null;

    if (
        $profile['media_kind'] === 'image'
        || (
            $profile['media_kind'] === 'icon'
            && $mime === 'image/png'
        )
    ) {
        $dimensions = @getimagesize($temporaryPath);

        if ($dimensions === false) {
            return [
                false,
                'The selected file is not a valid image.',
                null,
            ];
        }

        $width = (int) ($dimensions[0] ?? 0);
        $height = (int) ($dimensions[1] ?? 0);

        if ($width < 1 || $height < 1) {
            return [
                false,
                'The selected image has invalid dimensions.',
                null,
            ];
        }

        if (
            $width * $height
            > DC_UPLOAD_MAX_IMAGE_PIXELS
        ) {
            return [
                false,
                'The image dimensions are too large. '
                . 'Use an image under 40 megapixels.',
                null,
            ];
        }
    }

    $originalFilename = basename(
        trim(
            (string) (
                $file['name']
                ?? 'upload'
            )
        )
    );

    if ($originalFilename === '') {
        $originalFilename = 'upload';
    }

    $originalFilename = mb_substr(
        $originalFilename,
        0,
        255
    );

    return [
        true,
        '',
        [
            'purpose' => $purpose,
            'temporary_path' => $temporaryPath,
            'original_filename' => $originalFilename,
            'mime_type' => $mime,
            'extension' => $allowedMimes[$mime],
            'media_kind' => (string) $profile['media_kind'],
            'directory' => (string) $profile['directory'],
            'size' => $size,
            'width' => $width,
            'height' => $height,
        ],
    ];
}

function dc_managed_upload_directory(string $subdirectory): string
{
    return PUBLIC_ROOT
        . '/uploads/content/'
        . trim($subdirectory, '/\\');
}

function dc_managed_upload_public_directory(
    string $subdirectory
): string {
    return '/uploads/content/'
        . trim($subdirectory, '/\\');
}

function dc_ensure_upload_directory(string $directory): bool
{
    if (is_dir($directory)) {
        return is_writable($directory);
    }

    if (
        !mkdir($directory, 0755, true)
        && !is_dir($directory)
    ) {
        return false;
    }

    return is_writable($directory);
}

function dc_generate_upload_filename(string $extension): string
{
    return date('Ymd-His')
        . '-'
        . bin2hex(random_bytes(8))
        . '.'
        . strtolower($extension);
}

/**
 * Save a validated file and register it in media_assets.
 *
 * @param array<string, mixed> $file
 *
 * @return array{0: bool, 1: string, 2: array<string, mixed>|null}
 */
function dc_store_managed_upload(
    array $file,
    string $purpose,
    string $displayName,
    ?string $altText = null
): array {
    [
        $valid,
        $message,
        $metadata,
    ] = dc_validate_managed_upload(
        $file,
        $purpose
    );

    if (!$valid || $metadata === null) {
        return [false, $message, null];
    }

    if (database() === null) {
        return [
            false,
            'The database is unavailable, so the file was not saved.',
            null,
        ];
    }

    $directory = dc_managed_upload_directory(
        (string) $metadata['directory']
    );

    if (!dc_ensure_upload_directory($directory)) {
        return [
            false,
            'The website upload folder is not writable.',
            null,
        ];
    }

    try {
        $filename = dc_generate_upload_filename(
            (string) $metadata['extension']
        );
    } catch (Throwable $exception) {
        log_message(
            'Unable to generate upload filename: '
            . $exception->getMessage()
        );

        return [
            false,
            'The server could not prepare the uploaded file.',
            null,
        ];
    }

    $destination = $directory . '/' . $filename;
    $temporaryPath = (string) $metadata['temporary_path'];

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
            'The uploaded file could not be saved.',
            null,
        ];
    }

    @chmod($destination, 0644);

    $publicPath = dc_managed_upload_public_directory(
        (string) $metadata['directory']
    ) . '/' . $filename;

    $assetId = dc_create_media_asset([
        'display_name' => trim($displayName) !== ''
            ? trim($displayName)
            : 'Website media',
        'file_path' => $publicPath,
        'original_filename' =>
            (string) $metadata['original_filename'],
        'media_kind' =>
            (string) $metadata['media_kind'],
        'mime_type' =>
            (string) $metadata['mime_type'],
        'width' => $metadata['width'],
        'height' => $metadata['height'],
        'file_size_bytes' =>
            (int) $metadata['size'],
        'alt_text' =>
            $altText !== null
                ? trim($altText)
                : null,
        'is_managed_upload' => 1,
    ]);

    if ($assetId === null) {
        @unlink($destination);

        return [
            false,
            'The file was uploaded, but its website record '
            . 'could not be created.',
            null,
        ];
    }

    $asset = dc_media_asset($assetId);

    if ($asset === null) {
        return [
            true,
            'The file was uploaded.',
            [
                'id' => $assetId,
                'file_path' => $publicPath,
                'display_name' => $displayName,
                'media_kind' => $metadata['media_kind'],
                'mime_type' => $metadata['mime_type'],
                'width' => $metadata['width'],
                'height' => $metadata['height'],
                'file_size_bytes' => $metadata['size'],
                'alt_text' => $altText,
                'is_managed_upload' => 1,
            ],
        ];
    }

    return [
        true,
        'The file was uploaded.',
        $asset,
    ];
}

/**
 * Count all current references to a media record.
 */
function dc_media_usage_count(int $assetId): int
{
    if ($assetId < 1) {
        return 0;
    }

    $pdo = database();

    if ($pdo === null) {
        return 0;
    }

    try {
        $statement = $pdo->prepare(
            'SELECT
                (
                    SELECT COUNT(*)
                    FROM site_media_slots
                    WHERE media_asset_id = :slot_id
                )
                +
                (
                    SELECT COUNT(*)
                    FROM services
                    WHERE image_asset_id = :service_id
                )
                +
                (
                    SELECT COUNT(*)
                    FROM profiles
                    WHERE image_asset_id = :profile_id
                )
                +
                (
                    SELECT COUNT(*)
                    FROM partners
                    WHERE logo_asset_id = :partner_id
                ) AS usage_count'
        );

        $statement->execute([
            'slot_id' => $assetId,
            'service_id' => $assetId,
            'profile_id' => $assetId,
            'partner_id' => $assetId,
        ]);

        return (int) $statement->fetchColumn();
    } catch (Throwable $exception) {
        log_message(
            'Unable to count media usage: '
            . $exception->getMessage()
        );

        return 1;
    }
}

/**
 * Return an absolute path only for files inside the managed upload area.
 */
function dc_managed_media_absolute_path(
    string $publicPath
): ?string {
    $path = parse_url(
        $publicPath,
        PHP_URL_PATH
    );

    if (
        !is_string($path)
        || !str_starts_with(
            $path,
            '/uploads/content/'
        )
        || str_contains($path, '..')
        || str_contains($path, "\0")
    ) {
        return null;
    }

    return PUBLIC_ROOT . $path;
}

/**
 * Delete a managed media record and its file after it is no longer referenced.
 * Trusted seeded assets under assets/ are never deleted by this function.
 */
function dc_delete_unused_managed_media_asset(
    ?int $assetId
): bool {
    if ($assetId === null || $assetId < 1) {
        return true;
    }

    $asset = dc_media_asset($assetId);

    if ($asset === null) {
        return true;
    }

    if (
        (int) (
            $asset['is_managed_upload']
            ?? 0
        ) !== 1
    ) {
        return true;
    }

    if (dc_media_usage_count($assetId) > 0) {
        return false;
    }

    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    $absolutePath = dc_managed_media_absolute_path(
        (string) (
            $asset['file_path']
            ?? ''
        )
    );

    try {
        $statement = $pdo->prepare(
            'DELETE FROM media_assets
             WHERE id = :id
               AND is_managed_upload = 1'
        );

        $statement->execute([
            'id' => $assetId,
        ]);

        if (
            $absolutePath !== null
            && is_file($absolutePath)
            && !@unlink($absolutePath)
        ) {
            log_message(
                'Unable to delete unused managed upload: '
                . $absolutePath
            );
        }

        return true;
    } catch (Throwable $exception) {
        log_message(
            'Unable to delete managed media record: '
            . $exception->getMessage()
        );

        return false;
    }
}

/**
 * Return the current media ID assigned to a fixed global slot.
 */
function dc_site_media_asset_id(
    string $slotKey
): ?int {
    $pdo = database();

    if ($pdo === null) {
        return null;
    }

    try {
        $statement = $pdo->prepare(
            'SELECT media_asset_id
             FROM site_media_slots
             WHERE slot_key = :slot_key
             LIMIT 1'
        );

        $statement->execute([
            'slot_key' => $slotKey,
        ]);

        $value = $statement->fetchColumn();

        return $value !== false
            && $value !== null
                ? (int) $value
                : null;
    } catch (Throwable $exception) {
        log_message(
            'Unable to read site media assignment: '
            . $exception->getMessage()
        );

        return null;
    }
}

/**
 * Replace one of the fixed global media slots.
 *
 * @param array<string, mixed> $file
 *
 * @return array{0: bool, 1: string, 2: array<string, mixed>|null}
 */
function dc_replace_site_media_upload(
    string $slotKey,
    array $file,
    ?string $altText = null
): array {
    $slotPurposes = [
        'brand_mark' => 'brand_mark',
        'favicon' => 'favicon',
        'hero_image' => 'hero_image',
        'hero_video' => 'hero_video',
    ];

    if (!isset($slotPurposes[$slotKey])) {
        return [
            false,
            'This website image location is not editable.',
            null,
        ];
    }

    $slot = dc_site_media($slotKey);

    if ($slot === null) {
        return [
            false,
            'The website image location could not be found.',
            null,
        ];
    }

    $oldAssetId = dc_site_media_asset_id($slotKey);

    [
        $uploaded,
        $message,
        $asset,
    ] = dc_store_managed_upload(
        $file,
        $slotPurposes[$slotKey],
        (string) (
            $slot['admin_label']
            ?? 'Website media'
        ),
        $altText
    );

    if (!$uploaded || $asset === null) {
        return [false, $message, null];
    }

    $newAssetId = (int) $asset['id'];

    if (!dc_assign_site_media($slotKey, $newAssetId)) {
        dc_delete_unused_managed_media_asset(
            $newAssetId
        );

        return [
            false,
            'The file was saved, but it could not be assigned '
            . 'to the website.',
            null,
        ];
    }

    dc_delete_unused_managed_media_asset(
        $oldAssetId
    );

    dc_forget_content_cache(
        'site_media_slots'
    );

    return [
        true,
        'The website media was updated.',
        $asset,
    ];
}

function dc_remove_site_media(string $slotKey): bool
{
    $allowedSlots = [
        'brand_mark',
        'favicon',
        'hero_image',
        'hero_video',
    ];

    if (!in_array($slotKey, $allowedSlots, true)) {
        return false;
    }

    $oldAssetId = dc_site_media_asset_id($slotKey);

    if (!dc_assign_site_media($slotKey, null)) {
        return false;
    }

    dc_delete_unused_managed_media_asset(
        $oldAssetId
    );

    dc_forget_content_cache(
        'site_media_slots'
    );

    return true;
}

/**
 * Describe the table and media column for a repeatable content record.
 *
 * @return array<string, string>|null
 */
function dc_record_media_profile(
    string $recordType
): ?array {
    $profiles = [
        'service' => [
            'table' => 'services',
            'column' => 'image_asset_id',
            'purpose' => 'service_image',
            'label' => 'service image',
            'cache_active' => 'services_active',
            'cache_all' => 'services_all',
        ],
        'profile' => [
            'table' => 'profiles',
            'column' => 'image_asset_id',
            'purpose' => 'profile_image',
            'label' => 'profile image',
            'cache_active' => 'profiles_active',
            'cache_all' => 'profiles_all',
        ],
        'partner' => [
            'table' => 'partners',
            'column' => 'logo_asset_id',
            'purpose' => 'partner_logo',
            'label' => 'partner logo',
            'cache_active' => 'partners_active',
            'cache_all' => 'partners_all',
        ],
    ];

    return $profiles[$recordType] ?? null;
}

/**
 * @return array<string, mixed>|null
 */
function dc_record_media_context(
    string $recordType,
    int $recordId
): ?array {
    $profile = dc_record_media_profile(
        $recordType
    );

    if ($profile === null || $recordId < 1) {
        return null;
    }

    $pdo = database();

    if ($pdo === null) {
        return null;
    }

    $nameColumn = $recordType === 'partner'
        ? 'COALESCE(name, placeholder_label)'
        : 'name';

    try {
        $statement = $pdo->prepare(
            'SELECT
                id,
                '
            . $nameColumn
            . ' AS record_name,
                '
            . $profile['column']
            . ' AS media_asset_id
             FROM '
            . $profile['table']
            . '
             WHERE id = :id
             LIMIT 1'
        );

        $statement->execute([
            'id' => $recordId,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        $row['id'] = (int) $row['id'];
        $row['media_asset_id'] =
            $row['media_asset_id'] !== null
                ? (int) $row['media_asset_id']
                : null;

        return array_replace(
            $profile,
            $row
        );
    } catch (Throwable $exception) {
        log_message(
            'Unable to read record media context: '
            . $exception->getMessage()
        );

        return null;
    }
}

/**
 * Update the media foreign key for a service, profile, or partner.
 */
function dc_assign_record_media(
    string $recordType,
    int $recordId,
    ?int $assetId
): bool {
    $profile = dc_record_media_profile(
        $recordType
    );

    if ($profile === null || $recordId < 1) {
        return false;
    }

    $pdo = database();

    if ($pdo === null) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'UPDATE '
            . $profile['table']
            . '
             SET '
            . $profile['column']
            . ' = :asset_id
             WHERE id = :id'
        );

        $statement->bindValue(
            ':asset_id',
            $assetId,
            $assetId === null
                ? PDO::PARAM_NULL
                : PDO::PARAM_INT
        );

        $statement->bindValue(
            ':id',
            $recordId,
            PDO::PARAM_INT
        );

        $statement->execute();

        if ($statement->rowCount() === 0) {
            $check = $pdo->prepare(
                'SELECT 1
                 FROM '
                . $profile['table']
                . '
                 WHERE id = :id'
            );

            $check->execute([
                'id' => $recordId,
            ]);

            if ($check->fetchColumn() === false) {
                return false;
            }
        }

        dc_forget_content_cache(
            $profile['cache_active']
        );

        dc_forget_content_cache(
            $profile['cache_all']
        );

        return true;
    } catch (Throwable $exception) {
        log_message(
            'Unable to assign record media: '
            . $exception->getMessage()
        );

        return false;
    }
}

/**
 * Upload and assign media to a service, profile, or partner.
 *
 * @param array<string, mixed> $file
 *
 * @return array{0: bool, 1: string, 2: array<string, mixed>|null}
 */
function dc_replace_record_media_upload(
    string $recordType,
    int $recordId,
    array $file,
    ?string $altText = null
): array {
    $context = dc_record_media_context(
        $recordType,
        $recordId
    );

    if ($context === null) {
        return [
            false,
            'The content item could not be found.',
            null,
        ];
    }

    $oldAssetId = $context['media_asset_id'];
    $recordName = trim(
        (string) (
            $context['record_name']
            ?? ''
        )
    );

    $displayName = trim(
        $recordName
        . ' '
        . (string) $context['label']
    );

    [
        $uploaded,
        $message,
        $asset,
    ] = dc_store_managed_upload(
        $file,
        (string) $context['purpose'],
        $displayName,
        $altText
    );

    if (!$uploaded || $asset === null) {
        return [false, $message, null];
    }

    $newAssetId = (int) $asset['id'];

    if (
        !dc_assign_record_media(
            $recordType,
            $recordId,
            $newAssetId
        )
    ) {
        dc_delete_unused_managed_media_asset(
            $newAssetId
        );

        return [
            false,
            'The file was saved, but it could not be assigned '
            . 'to the content item.',
            null,
        ];
    }

    dc_delete_unused_managed_media_asset(
        is_int($oldAssetId)
            ? $oldAssetId
            : null
    );

    return [
        true,
        ucfirst((string) $context['label'])
        . ' updated.',
        $asset,
    ];
}

function dc_remove_record_media(
    string $recordType,
    int $recordId
): bool {
    $context = dc_record_media_context(
        $recordType,
        $recordId
    );

    if ($context === null) {
        return false;
    }

    $oldAssetId = $context['media_asset_id'];

    if (
        !dc_assign_record_media(
            $recordType,
            $recordId,
            null
        )
    ) {
        return false;
    }

    dc_delete_unused_managed_media_asset(
        is_int($oldAssetId)
            ? $oldAssetId
            : null
    );

    return true;
}

/**
 * Convenience wrappers used by the administration controller.
 *
 * @param array<string, mixed> $file
 *
 * @return array{0: bool, 1: string, 2: array<string, mixed>|null}
 */
function dc_replace_service_image(
    int $serviceId,
    array $file
): array {
    return dc_replace_record_media_upload(
        'service',
        $serviceId,
        $file
    );
}

function dc_remove_service_image(
    int $serviceId
): bool {
    return dc_remove_record_media(
        'service',
        $serviceId
    );
}

/**
 * @param array<string, mixed> $file
 *
 * @return array{0: bool, 1: string, 2: array<string, mixed>|null}
 */
function dc_replace_profile_image(
    int $profileId,
    array $file,
    ?string $altText = null
): array {
    return dc_replace_record_media_upload(
        'profile',
        $profileId,
        $file,
        $altText
    );
}

function dc_remove_profile_image(
    int $profileId
): bool {
    return dc_remove_record_media(
        'profile',
        $profileId
    );
}

/**
 * @param array<string, mixed> $file
 *
 * @return array{0: bool, 1: string, 2: array<string, mixed>|null}
 */
function dc_replace_partner_logo(
    int $partnerId,
    array $file,
    ?string $altText = null
): array {
    return dc_replace_record_media_upload(
        'partner',
        $partnerId,
        $file,
        $altText
    );
}

function dc_remove_partner_logo(
    int $partnerId
): bool {
    return dc_remove_record_media(
        'partner',
        $partnerId
    );
}