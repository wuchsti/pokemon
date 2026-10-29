<?php

// PUT /api/auth/change_email.php
// Body: { new_email, current_password }
// Requires the current password as confirmation, same as changing it on
// most services - a leaked/stolen session token alone shouldn't be enough
// to take over the account's e-mail address.

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

$newEmail = trim((string) ($body['new_email'] ?? ''));
$currentPassword = (string) ($body['current_password'] ?? '');

if ($newEmail === '' || $currentPassword === '') {
    json_error('new_email und current_password sind erforderlich.');
}
if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    json_error('Ungültige E-Mail-Adresse.');
}

$pdo = get_db($config);

$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
    json_error('Aktuelles Passwort ist falsch.', 401);
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
$stmt->execute(['email' => $newEmail, 'id' => $userId]);
if ($stmt->fetch()) {
    json_error('Diese E-Mail-Adresse wird bereits verwendet.', 409);
}

$pdo->prepare('UPDATE users SET email = :email WHERE id = :id')->execute(['email' => $newEmail, 'id' => $userId]);

json_response(['email' => $newEmail]);
