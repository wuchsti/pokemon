<?php

// GET /api/trades/list.php
// All trade threads the current user is part of, newest activity first.

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
$pdo = get_db($config);

$stmt = $pdo->prepare(
    'SELECT t.*, ua.username AS user_a_username, ub.username AS user_b_username
     FROM trades t
     JOIN users ua ON ua.id = t.user_a_id
     JOIN users ub ON ub.id = t.user_b_id
     WHERE t.user_a_id = :uid OR t.user_b_id = :uid2
     ORDER BY t.updated_at DESC'
);
$stmt->execute(['uid' => $userId, 'uid2' => $userId]);

$trades = array_map(
    static fn (array $row) => format_trade($row, $userId),
    $stmt->fetchAll()
);

json_response(['trades' => $trades]);
