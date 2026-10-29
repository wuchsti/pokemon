<?php

// POST /api/admin/users/block.php
// Body: { id }

require_once __DIR__ . '/../../lib/response.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/admin.php';

$config = require __DIR__ . '/../../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Methode nicht erlaubt.', 405);
}

$adminId = require_admin_user_id($config);
$body = read_json_body();

$targetId = (int) ($body['id'] ?? 0);
if ($targetId <= 0) {
    json_error('id ist erforderlich.');
}
if ($targetId === $adminId) {
    json_error('Du kannst dich nicht selbst sperren.');
}

$pdo = get_db($config);

$stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id');
$stmt->execute(['id' => $targetId]);
if (!$stmt->fetch()) {
    json_error('Nutzer nicht gefunden.', 404);
}

// Note: rowCount() isn't used to detect "not found" here - PDO's MySQL
// driver reports rows *changed*, not rows *matched*, so re-blocking an
// already-blocked user would report 0 even though it succeeded.
$pdo->prepare('UPDATE users SET is_blocked = 1 WHERE id = :id')->execute(['id' => $targetId]);

json_response(['success' => true]);
