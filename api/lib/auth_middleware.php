<?php

require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';

/**
 * Reads the Authorization: Bearer <token> header, validates it, and returns
 * the authenticated user's id. Ends the request with a 401 if missing/invalid,
 * and with a 403 if the account has since been blocked by an admin - a JWT
 * can otherwise stay valid for days after a block, so this is checked fresh
 * on every request rather than only at login.
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

    $userId = (int) $payload['sub'];

    $pdo = get_db($config);
    $stmt = $pdo->prepare('SELECT is_blocked FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $isBlocked = $stmt->fetchColumn();

    if ($isBlocked === false) {
        json_error('Benutzer nicht gefunden.', 401);
    }
    if ((bool) $isBlocked) {
        json_error('Dieser Account wurde gesperrt.', 403);
    }

    return $userId;
}
