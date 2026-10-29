<?php

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/auth_middleware.php';
require_once __DIR__ . '/db.php';

/**
 * Like require_auth_user_id(), but additionally requires the authenticated
 * user to be an admin. Ends the request with a 403 otherwise.
 */
function require_admin_user_id(array $config): int
{
    $userId = require_auth_user_id($config);

    $pdo = get_db($config);
    $stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);

    if (!$stmt->fetchColumn()) {
        json_error('Kein Zugriff: Admin-Rechte erforderlich.', 403);
    }

    return $userId;
}
