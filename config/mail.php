<?php

declare(strict_types=1);

return [
    'recipient' => env('MAIL_TO', ''),
    'from_address' => env('MAIL_FROM_ADDRESS', 'website@dcimprints.com'),
    'from_name' => env('MAIL_FROM_NAME', 'DC Imprints Website'),
];
