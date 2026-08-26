<?php

declare(strict_types=1);

/**
 * @param array<string, mixed> $data
 * @param array{name:string,tmp_name:string,mime:string,size:int}|null $attachment
 */
function send_quote_email(
    array $data,
    ?array $attachment = null,
    string $businessName = 'DC Imprints'
): bool {
    $config = require APP_ROOT . '/config/mail.php';

    $requiredConfig = [
        'recipient' => 'MAIL_TO',
        'from_address' => 'MAIL_FROM_ADDRESS',
        'graph_tenant_id' => 'MS_GRAPH_TENANT_ID',
        'graph_client_id' => 'MS_GRAPH_CLIENT_ID',
        'graph_client_secret' => 'MS_GRAPH_CLIENT_SECRET',
    ];

    foreach ($requiredConfig as $key => $environmentVariable) {
        if (trim((string) ($config[$key] ?? '')) === '') {
            log_message(
                $environmentVariable
                . ' is not configured; quote form email was not sent.'
            );
            return false;
        }
    }

    $accessToken = dc_graph_access_token($config);

    if ($accessToken === null) {
        return false;
    }

    $subjectName = dc_mail_single_line((string) ($data['name'] ?? ''));
    $subject = 'Website quote request from ' . $subjectName;

    $body = implode(PHP_EOL, [
        'Website: ' . $businessName,
        'Name: ' . (string) ($data['name'] ?? ''),
        'Email: ' . (string) ($data['email'] ?? ''),
        'Phone: ' . ((string) ($data['phone'] ?? '') !== ''
            ? (string) $data['phone']
            : 'Not provided'),
        'Organization: ' . ((string) ($data['organization'] ?? '') !== ''
            ? (string) $data['organization']
            : 'Not provided'),
        'Service: ' . (string) ($data['service'] ?? ''),
        'Design/logo assistance requested: '
            . (!empty($data['design_help']) ? 'Yes' : 'No'),
        '',
        'Project details:',
        (string) ($data['message'] ?? ''),
    ]);

    $message = [
        'subject' => $subject,
        'body' => [
            'contentType' => 'Text',
            'content' => $body,
        ],
        'toRecipients' => [[
            'emailAddress' => [
                'address' => (string) $config['recipient'],
            ],
        ]],
        'replyTo' => [[
            'emailAddress' => [
                'name' => dc_mail_single_line((string) ($data['name'] ?? '')),
                'address' => dc_mail_single_line((string) ($data['email'] ?? '')),
            ],
        ]],
    ];

    if ($attachment !== null) {
        $contents = file_get_contents($attachment['tmp_name']);

        if ($contents === false) {
            log_message('Artwork attachment could not be read for a quote request.');
            return false;
        }

        $message['attachments'] = [[
            '@odata.type' => '#microsoft.graph.fileAttachment',
            'name' => $attachment['name'],
            'contentType' => $attachment['mime'],
            'contentBytes' => base64_encode($contents),
        ]];
    }

    $payload = json_encode(
        [
            'message' => $message,
            'saveToSentItems' => true,
        ],
        JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );

    if ($payload === false) {
        log_message('Unable to encode Microsoft Graph quote email payload.');
        return false;
    }

    $sender = rawurlencode((string) $config['from_address']);
    $url = 'https://graph.microsoft.com/v1.0/users/' . $sender . '/sendMail';

    [$statusCode, $responseBody, $curlError] = dc_graph_request(
        $url,
        [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        $payload
    );

    if ($curlError !== null) {
        log_message('Microsoft Graph sendMail request failed: ' . $curlError);
        return false;
    }

    if ($statusCode !== 202) {
        log_message(
            'Microsoft Graph sendMail returned HTTP '
            . $statusCode
            . dc_graph_error_suffix($responseBody)
        );
        return false;
    }

    return true;
}

/**
 * @param array<string, mixed> $config
 */
function dc_graph_access_token(array $config): ?string
{
    $tenantId = rawurlencode((string) $config['graph_tenant_id']);
    $url = 'https://login.microsoftonline.com/'
        . $tenantId
        . '/oauth2/v2.0/token';

    $postBody = http_build_query(
        [
            'client_id' => (string) $config['graph_client_id'],
            'client_secret' => (string) $config['graph_client_secret'],
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ],
        '',
        '&',
        PHP_QUERY_RFC3986
    );

    [$statusCode, $responseBody, $curlError] = dc_graph_request(
        $url,
        [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ],
        $postBody
    );

    if ($curlError !== null) {
        log_message('Microsoft Graph token request failed: ' . $curlError);
        return null;
    }

    if ($statusCode !== 200) {
        log_message(
            'Microsoft Graph token request returned HTTP '
            . $statusCode
            . dc_graph_error_suffix($responseBody)
        );
        return null;
    }

    $decoded = json_decode($responseBody, true);

    if (
        !is_array($decoded)
        || !isset($decoded['access_token'])
        || !is_string($decoded['access_token'])
        || $decoded['access_token'] === ''
    ) {
        log_message('Microsoft Graph token response did not contain an access token.');
        return null;
    }

    return $decoded['access_token'];
}

/**
 * @param array<int, string> $headers
 * @return array{0:int,1:string,2:string|null}
 */
function dc_graph_request(string $url, array $headers, string $body): array
{
    $handle = curl_init($url);

    if ($handle === false) {
        return [0, '', 'Unable to initialize cURL.'];
    }

    curl_setopt_array($handle, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($handle);
    $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $error = curl_error($handle);
    curl_close($handle);

    if ($response === false) {
        return [
            $statusCode,
            '',
            $error !== '' ? $error : 'Unknown cURL error.',
        ];
    }

    return [$statusCode, (string) $response, null];
}

function dc_graph_error_suffix(string $responseBody): string
{
    if ($responseBody === '') {
        return '.';
    }

    $decoded = json_decode($responseBody, true);

    if (!is_array($decoded)) {
        return '.';
    }

    $code = $decoded['error']['code'] ?? null;
    $message = $decoded['error']['message'] ?? null;
    $parts = [];

    if (is_string($code) && $code !== '') {
        $parts[] = $code;
    }

    if (is_string($message) && $message !== '') {
        $parts[] = dc_mail_single_line($message);
    }

    return $parts === [] ? '.' : ': ' . implode(' - ', $parts);
}

function dc_mail_single_line(string $value): string
{
    return str_replace(["\r", "\n"], ' ', trim($value));
}
