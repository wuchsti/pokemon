<?php

// POST /api/trades/message.php
// Body: { trade_id, body }

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

$tradeId = (int) ($body['trade_id'] ?? 0);
$text = trim((string) ($body['body'] ?? ''));

if ($tradeId <= 0 || $text === '') {
    json_error('trade_id und body sind erforderlich.');
}
if (mb_strlen($text) > 2000) {
    json_error('Nachricht zu lang (max. 2000 Zeichen).');
}

$pdo = get_db($config);
$trade = get_trade_or_fail($pdo, $tradeId, $userId);

if ($trade['status'] === 'cancelled') {
    json_error('Dieser Tausch wurde abgebrochen und ist geschlossen.', 409);
}

$stmt = $pdo->prepare('INSERT INTO trade_messages (trade_id, sender_user_id, body) VALUES (:trade_id, :sender, :body)');
$stmt->execute(['trade_id' => $tradeId, 'sender' => $userId, 'body' => $text]);
$messageId = (int) $pdo->lastInsertId();

$pdo->prepare('UPDATE trades SET updated_at = CURRENT_TIMESTAMP WHERE id = :id')->execute(['id' => $tradeId]);

$stmt = $pdo->prepare('SELECT username FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
$myUsername = $stmt->fetchColumn();

json_response([
    'message' => [
        'id' => $messageId,
        'body' => $text,
        'created_at' => date('Y-m-d H:i:s'),
        'from_me' => true,
        'sender_username' => $myUsername,
    ],
], 201);
