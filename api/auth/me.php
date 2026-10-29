<?php

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);
$pdo = get_db($config);

$stmt = $pdo->prepare('SELECT id, username, email, created_at FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    json_error('Benutzer nicht gefunden.', 404);
}

json_response(['user' => $user]);
