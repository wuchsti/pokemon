<?php

// POST /api/trades/create.php
// Body: { partner_username }
// Starts a new trade thread by nickname - looked up via users.username,
// never e-mail. Multiple trade threads with the same partner are allowed
// (e.g. a second, separate trade later), so this doesn't check for an
// existing open thread.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';
require_once __DIR__ . '/../lib/trades.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);
$body = read_json_body();

$partnerUsername = trim((string) ($body['partner_username'] ?? ''));
if ($partnerUsername === '') {
    json_error('partner_username ist erforderlich.');
}

$pdo = get_db($config);

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
$stmt->execute(['username' => $partnerUsername]);
$partner = $stmt->fetch();

if (!$partner) {
    json_error('Dieser Nutzer wurde nicht gefunden.', 404);
}

$partnerId = (int) $partner['id'];
if ($partnerId === $userId) {
    json_error('Du kannst keinen Tausch mit dir selbst starten.');
}

$stmt = $pdo->prepare('INSERT INTO trades (user_a_id, user_b_id) VALUES (:a, :b)');
$stmt->execute(['a' => $userId, 'b' => $partnerId]);
$tradeId = (int) $pdo->lastInsertId();

$trade = get_trade_or_fail($pdo, $tradeId, $userId);

json_response(['trade' => format_trade($trade, $userId)], 201);
