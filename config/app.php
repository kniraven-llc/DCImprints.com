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
     * Google Analytics 4
     *
     * Leave the Measurement ID blank in the environment to disable
     * Google Analytics.
     */
    'google_analytics_measurement_id' => trim(
        env('GOOGLE_ANALYTICS_MEASUREMENT_ID', '')
    ),
];