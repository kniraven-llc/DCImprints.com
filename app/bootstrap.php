<?php

declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';
const PUBLIC_ROOT = APP_ROOT . '/public';

/**
 * Load simple KEY=VALUE pairs from .env without an external dependency.
 */
function load_env_file(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file(
        $path,
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if (
            $line === ''
            || str_starts_with($line, '#')
            || !str_contains($line, '=')
        ) {
            continue;
        }

        [$name, $value] = array_map(
            'trim',
            explode('=', $line, 2)
        );

        $value = trim(
            $value,
            " \t\n\r\0\x0B\"'"
        );

        if (
            $name !== ''
            && getenv($name) === false
        ) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

function env(
    string $key,
    ?string $default = null
): ?string {
    $value = getenv($key);

    return $value === false
        ? $default
        : $value;
}

load_env_file(
    APP_ROOT . '/.env'
);

$appConfig = require
    APP_ROOT . '/config/app.php';

date_default_timezone_set(
    (string) $appConfig['timezone']
);

if (
    session_status()
    !== PHP_SESSION_ACTIVE
) {
    session_name(
        (string) $appConfig[
            'session_name'
        ]
    );

    session_set_cookie_params([
        'httponly' => true,
        'secure' =>
            !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);

    session_start();
}

/*
 * Core application dependencies.
 *
 * Load order is intentional:
 *
 * 1. Database access
 * 2. General application helpers
 * 3. Database-backed website content
 * 4. Managed upload handling
 * 5. Administrator authentication
 * 6. Form validation
 * 7. Email delivery
 */
require_once
    APP_ROOT . '/app/database.php';

require_once
    APP_ROOT . '/app/functions.php';

require_once
    APP_ROOT . '/app/content.php';

require_once
    APP_ROOT . '/app/promotions.php';

require_once
    APP_ROOT . '/app/uploads.php';

require_once
    APP_ROOT . '/app/authentication.php';

require_once
    APP_ROOT . '/app/validation.php';

require_once
    APP_ROOT . '/app/mailer.php';