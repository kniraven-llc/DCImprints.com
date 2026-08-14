<?php

declare(strict_types=1);

function send_quote_email(array $data): bool
{
    $config = require APP_ROOT . '/config/mail.php';

    if ($config['recipient'] === '') {
        log_message('MAIL_TO is not configured; contact form email was not sent.');
        return false;
    }

    $safeSubjectName = str_replace(["\r", "\n"], ' ', $data['name']);
    $subject = 'Website quote request from ' . $safeSubjectName;
    $body = implode(PHP_EOL, [
        'Name: ' . $data['name'],
        'Email: ' . $data['email'],
        'Phone: ' . ($data['phone'] !== '' ? $data['phone'] : 'Not provided'),
        'Organization: ' . ($data['organization'] !== '' ? $data['organization'] : 'Not provided'),
        '',
        'Project details:',
        $data['message'],
    ]);

    $safeFromName = str_replace(["\r", "\n"], '', $config['from_name']);
    $safeFromAddress = str_replace(["\r", "\n"], '', $config['from_address']);
    $safeReplyTo = str_replace(["\r", "\n"], '', $data['email']);

    $headers = [
        'From: ' . $safeFromName . ' <' . $safeFromAddress . '>',
        'Reply-To: ' . $safeReplyTo,
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/' . PHP_VERSION,
    ];

    $sent = mail($config['recipient'], $subject, $body, implode("\r\n", $headers));

    if (!$sent) {
        log_message('PHP mail() returned false for a quote request.');
    }

    return $sent;
}
