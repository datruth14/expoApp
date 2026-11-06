<?php
declare(strict_types=1);

function kingschat_configured(): bool
{
    global $settings;
    return $settings['kingschat_client_id'] !== '' && $settings['kingschat_api_key'] !== '';
}

function current_delegate(): array|false
{
    if (empty($_SESSION['delegate_id']) || (int) ($_SESSION['delegate_expires_at'] ?? 0) <= time()) {
        unset($_SESSION['delegate_id'], $_SESSION['delegate_expires_at']);
        return false;
    }
    return db()->query('SELECT id, kingschat_user_id, handle, display_name FROM delegates WHERE id = ?', [(int) $_SESSION['delegate_id']])->fetch();
}

function feedback_return_url(string $slug, string $area): string
{
    return 'program.php?' . http_build_query(['slug' => $slug, 'area' => $area], '', '&', PHP_QUERY_RFC3986) . '#feedback';
}

function delegate_login_url(string $slug, string $area): string
{
    return 'delegate-login.php?' . http_build_query(['program' => $slug, 'area' => $area], '', '&', PHP_QUERY_RFC3986);
}

function kingschat_api_request(string $path, ?array $body = null, ?string $accessToken = null): array
{
    global $settings;
    if (!extension_loaded('curl')) {
        throw new RuntimeException('KingsChat sign-in requires the PHP cURL extension.');
    }
    $base = rtrim((string) $settings['kingschat_api_base_url'], '/');
    // Only the integration tests may direct verification to a local mock server.
    $localTest = $settings['app_env'] === 'test' && $settings['db_driver'] === 'sqlite'
        && preg_match('~^http://127\.0\.0\.1:[0-9]+$~', $base);
    if ($base !== 'https://connect.kingsch.at' && !$localTest) {
        throw new RuntimeException('KingsChat API base URL must use the official HTTPS endpoint.');
    }

    $headers = ['Accept: application/json'];
    if ($accessToken !== null) {
        if (preg_match('/[\r\n]/', $accessToken)) {
            throw new RuntimeException('KingsChat returned an invalid access token.');
        }
        $headers[] = 'Authorization: Bearer ' . $accessToken;
        $headers[] = 'api-key: ' . $settings['kingschat_api_key'];
    }
    $curl = curl_init($base . $path);
    $response = '';
    $options = [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => $localTest ? CURLPROTO_HTTP : CURLPROTO_HTTPS,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$response): int {
            if (strlen($response) + strlen($chunk) > 1024 * 1024) {
                return 0;
            }
            $response .= $chunk;
            return strlen($chunk);
        },
    ];
    if ($body !== null) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
    }
    curl_setopt_array($curl, $options);
    $success = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $errorCode = curl_errno($curl);
    curl_close($curl);
    // Never log authorization codes, bearer tokens, API keys, or response bodies.
    if ($success === false || $status < 200 || $status >= 300) {
        throw new RuntimeException('KingsChat request failed (HTTP ' . $status . ', transport ' . $errorCode . ').');
    }
    $payload = json_decode($response, true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($payload)) {
        throw new RuntimeException('KingsChat returned an invalid response.');
    }
    return $payload;
}

function verify_kingschat_code(string $code): array
{
    global $settings;
    $tokens = kingschat_api_request('/developer/api/oauth2/token', [
        'grant_type' => 'code',
        'client_id' => $settings['kingschat_client_id'],
        'code' => $code,
    ]);
    $accessToken = $tokens['access_token'] ?? null;
    $expiresMillis = $tokens['expires_in_millis'] ?? null;
    if (!is_string($accessToken) || $accessToken === '' || strlen($accessToken) > 16384
        || !is_numeric($expiresMillis) || (float) $expiresMillis < 1000) {
        throw new RuntimeException('KingsChat returned invalid token metadata.');
    }
    $response = kingschat_api_request('/developer/api/user/profile', null, $accessToken);
    $profile = $response['profile'] ?? null;
    if (!is_array($profile)) {
        throw new RuntimeException('KingsChat did not return a verified profile.');
    }
    $userId = $profile['id'] ?? null;
    $handle = $profile['username'] ?? null;
    $name = $profile['name'] ?? $handle;
    if (!is_string($userId) || $userId === '' || strlen($userId) > 128
        || !is_string($handle) || $handle === '' || mb_strlen($handle) > 180
        || !is_string($name) || mb_strlen($name) > 180) {
        throw new RuntimeException('KingsChat returned an incomplete profile.');
    }
    return ['user_id' => $userId, 'handle' => $handle, 'name' => $name,
        'expires_at' => time() + (int) min(28800, floor((float) $expiresMillis / 1000))];
}

function save_delegate_profile(array $profile): int
{
    global $settings;
    $sql = 'INSERT INTO delegates (kingschat_user_id, handle, display_name) VALUES (?, ?, ?)';
    $sql .= $settings['db_driver'] === 'sqlite'
        ? ' ON CONFLICT(kingschat_user_id) DO UPDATE SET handle = excluded.handle, display_name = excluded.display_name, updated_at = CURRENT_TIMESTAMP'
        : ' ON DUPLICATE KEY UPDATE handle = VALUES(handle), display_name = VALUES(display_name), updated_at = CURRENT_TIMESTAMP';
    db()->query($sql, [$profile['user_id'], $profile['handle'], $profile['name']]);
    $delegate = db()->query('SELECT id FROM delegates WHERE kingschat_user_id = ?', [$profile['user_id']])->fetch();
    return (int) $delegate['id'];
}
