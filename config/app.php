<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'DC Imprints'),
    'environment' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'url' => rtrim(env('APP_URL', 'http://localhost'), '/'),
    'timezone' => env('APP_TIMEZONE', 'America/Chicago'),
    'session_name' => env('SESSION_NAME', 'dcimprints_session'),

    /*
     * Quote form anti-spam protection.
     *
     * These checks are intentionally invisible to legitimate visitors.
     *
     * minimum_completion_seconds:
     * A form submitted faster than this is treated as automated.
     *
     * maximum_form_age_seconds:
     * Form timing tokens older than this expire normally rather than being
     * treated as spam.
     *
     * rate_limit_window_seconds:
     * Rolling period used by the submission rate limiter.
     *
     * rate_limit_max_attempts:
     * Maximum number of otherwise-valid quote submissions accepted from the
     * same network address during the rolling window.
     */
    'quote_spam' => [
        'minimum_completion_seconds' => 3,
        'maximum_form_age_seconds' => 7200,
        'rate_limit_window_seconds' => 600,
        'rate_limit_max_attempts' => 5,
    ],

    /*
     * Google Analytics 4
     *
     * Leave the Measurement ID blank in the environment to disable
     * Google Analytics.
     */
    'google_analytics_measurement_id' => trim(
        env('GOOGLE_ANALYTICS_MEASUREMENT_ID', '')
    ),
];