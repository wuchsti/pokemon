<?php

// POST /api/trades/confirm.php
// Body: { trade_id }
// Marks the trade as successful from the current user's side. Once both
// participants have confirmed, the trade flips to 'completed'.

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
if ($tradeId <= 0) {
    json_error('trade_id ist erforderlich.');
}

$pdo = get_db($config);
$trade = get_trade_or_fail($pdo, $tradeId, $userId);

if ($trade['status'] !== 'open') {
    // Already completed or cancelled - nothing to confirm, just echo state.
    json_response(['trade' => format_trade($trade, $userId)]);
}

$isUserA = (int) $trade['user_a_id'] === $userId;
$column = $isUserA ? 'user_a_confirmed' : 'user_b_confirmed';

$pdo->prepare("UPDATE trades SET $column = 1 WHERE id = :id")->execute(['id' => $tradeId]);

$trade = get_trade_or_fail($pdo, $tradeId, $userId);
if ($trade['user_a_confirmed'] && $trade['user_b_confirmed']) {
    $pdo->prepare("UPDATE trades SET status = 'completed' WHERE id = :id")->execute(['id' => $tradeId]);
    $trade = get_trade_or_fail($pdo, $tradeId, $userId);
}

json_response(['trade' => format_trade($trade, $userId)]);
