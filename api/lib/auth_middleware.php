<?php

require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/response.php';

/**
 * Reads the Authorization: Bearer <token> header, validates it, and returns
 * the authenticated user's id. Ends the request with a 401 if missing/invalid.
 */
function require_auth_user_id(array $config): int
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        json_error('Nicht authentifiziert.', 401);
    }

    $payload = jwt_decode($matches[1], $config['jwt']['secret']);
    if ($payload === null || !isset($payload['sub'])) {
        json_error('Ungültiges oder abgelaufenes Token.', 401);
    }

    return (int) $payload['sub'];
}
