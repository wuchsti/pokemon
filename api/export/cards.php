<?php

// GET /api/export/cards.php
// JSON payload for the frontend print/PDF view: every card the user owns,
// with the latest known Cardmarket price, plus who/when for the cover block.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';
require_once __DIR__ . '/../lib/price_stats.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);
$pdo = get_db($config);

$stmt = $pdo->prepare('SELECT username FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
$username = $stmt->fetchColumn();

json_response([
    'username' => $username,
    'generated_at' => date('Y-m-d H:i'),
    'cards' => get_user_cards_with_latest_price($pdo, $userId),
]);
