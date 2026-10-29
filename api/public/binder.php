<?php

// GET /api/public/binder.php?username=ash
// No authentication - this is the publicly shareable trade-binder link
// (/user/:username/binder on the frontend). Only returns data for users who
// have explicitly opted in via public_binder_enabled, and never exposes the
// e-mail address or any pricing/value data.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$username = trim((string) ($_GET['username'] ?? ''));
if ($username === '') {
    json_error('username ist erforderlich.');
}

$pdo = get_db($config);

$stmt = $pdo->prepare('SELECT id, username, public_binder_enabled FROM users WHERE username = :username LIMIT 1');
$stmt->execute(['username' => $username]);
$user = $stmt->fetch();

if (!$user || !$user['public_binder_enabled']) {
    json_error('Dieser Tausch-Binder ist nicht öffentlich oder existiert nicht.', 404);
}

$stmt = $pdo->prepare(
    "SELECT card_api_id, set_id, card_number, card_name, image_url, card_condition,
            language, is_graded, grading_company, grading_score
     FROM user_cards
     WHERE user_id = :user_id AND storage_type = 'trade_binder'
     ORDER BY set_id, card_number"
);
$stmt->execute(['user_id' => $user['id']]);

json_response([
    'username' => $user['username'],
    'cards' => $stmt->fetchAll(),
]);
