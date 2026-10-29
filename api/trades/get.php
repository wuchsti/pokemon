<?php

// GET /api/trades/get.php?id=123
// Trade detail + full message history, for the chat view. Polled by the
// frontend every few seconds to approximate real-time chat without
// needing WebSocket infrastructure on shared hosting.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';
require_once __DIR__ . '/../lib/trades.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);
$tradeId = (int) ($_GET['id'] ?? 0);
if ($tradeId <= 0) {
    json_error('id ist erforderlich.');
}

$pdo = get_db($config);
$trade = get_trade_or_fail($pdo, $tradeId, $userId);

$stmt = $pdo->prepare(
    'SELECT tm.id, tm.body, tm.created_at, tm.sender_user_id, u.username AS sender_username
     FROM trade_messages tm
     JOIN users u ON u.id = tm.sender_user_id
     WHERE tm.trade_id = :trade_id
     ORDER BY tm.created_at ASC'
);
$stmt->execute(['trade_id' => $tradeId]);

$messages = array_map(static function (array $m) use ($userId) {
    return [
        'id' => (int) $m['id'],
        'body' => $m['body'],
        'created_at' => $m['created_at'],
        'from_me' => (int) $m['sender_user_id'] === $userId,
        'sender_username' => $m['sender_username'],
    ];
}, $stmt->fetchAll());

json_response([
    'trade' => format_trade($trade, $userId),
    'messages' => $messages,
]);
