<?php

// PUT /api/auth/settings.php
// Body: { public_binder_enabled: bool }

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);
$body = read_json_body();

if (!array_key_exists('public_binder_enabled', $body)) {
    json_error('public_binder_enabled ist erforderlich.');
}

$enabled = (bool) $body['public_binder_enabled'];

$pdo = get_db($config);
$stmt = $pdo->prepare('UPDATE users SET public_binder_enabled = :enabled WHERE id = :id');
$stmt->execute(['enabled' => $enabled ? 1 : 0, 'id' => $userId]);

$stmt = $pdo->prepare('SELECT id, username, email, public_binder_enabled, created_at FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);

json_response(['user' => $stmt->fetch()]);
