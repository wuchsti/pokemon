<?php

// GET /api/matchmaking/find_traders.php
// Compares the authenticated user's wishlist against every other user's
// *public* trade binder and returns who already owns which of the wanted
// cards - grouped per trader, biggest match count first.
//
// Deliberately one-directional (my wishlist vs. their public trade binder)
// rather than also showing who wants *my* cards: other users' wishlists are
// private data they never opted into sharing, only their trade binder is.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);
$pdo = get_db($config);

$stmt = $pdo->prepare(
    "SELECT u.username, uc.card_api_id, uc.set_id, uc.card_number, uc.card_name,
            uc.image_url, uc.card_condition
     FROM user_cards uc
     INNER JOIN users u ON u.id = uc.user_id
     WHERE uc.storage_type = 'trade_binder'
       AND u.public_binder_enabled = 1
       AND u.id != :my_id
       AND uc.card_api_id IN (SELECT card_api_id FROM wishlist_items WHERE user_id = :my_id_2)
     ORDER BY u.username, uc.card_name"
);
$stmt->execute(['my_id' => $userId, 'my_id_2' => $userId]);

$grouped = [];
foreach ($stmt->fetchAll() as $row) {
    $username = $row['username'];
    $grouped[$username] ??= ['username' => $username, 'matches' => []];
    $grouped[$username]['matches'][] = [
        'card_api_id' => $row['card_api_id'],
        'set_id' => $row['set_id'],
        'card_number' => $row['card_number'],
        'card_name' => $row['card_name'],
        'image_url' => $row['image_url'],
        'card_condition' => $row['card_condition'],
    ];
}

$traders = array_values($grouped);
usort($traders, static fn ($a, $b) => count($b['matches']) <=> count($a['matches']));

json_response(['traders' => $traders]);
