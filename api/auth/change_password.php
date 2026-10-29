<?php

// PUT /api/auth/change_password.php
// Body: { current_password, new_password, new_password_confirm }

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

$currentPassword = (string) ($body['current_password'] ?? '');
$newPassword = (string) ($body['new_password'] ?? '');
$newPasswordConfirm = (string) ($body['new_password_confirm'] ?? '');

if ($currentPassword === '' || $newPassword === '' || $newPasswordConfirm === '') {
    json_error('current_password, new_password und new_password_confirm sind erforderlich.');
}
if ($newPassword !== $newPasswordConfirm) {
    json_error('Die neuen Passwörter stimmen nicht überein.');
}
if (strlen($newPassword) < 8) {
    json_error('Das neue Passwort muss mindestens 8 Zeichen lang sein.');
}

$pdo = get_db($config);

$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
    json_error('Aktuelles Passwort ist falsch.', 401);
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')->execute(['hash' => $newHash, 'id' => $userId]);

json_response(['success' => true]);
