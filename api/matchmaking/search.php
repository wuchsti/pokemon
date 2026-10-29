<?php

// GET /api/matchmaking/search.php?q=Pikachu
// Searches every public trade binder for a card name, regardless of the
// current user's wishlist - complements find_traders.php (which only
// matches against the wishlist) for "who has card X to trade?" lookups.
// Same shape as find_traders.php: grouped per trader, most matches first.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);

$query = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($query) < 2) {
    json_error('Bitte mindestens 2 Zeichen eingeben.');
}

$pdo = get_db($config);

// Escape LIKE wildcards in the user's own search text so a literal "%" or
// "_" in a card name search doesn't act as a wildcard. Using "!" as the
// escape character (rather than backslash) because backslash-in-a-string-
// literal is a MySQL-specific quirk, not standard SQL - SQLite and others
// don't collapse '\\' to one backslash, which breaks a single-char ESCAPE.
// addcslashes() always prefixes with backslash regardless of the character
// list passed in, so it can't be used here - escape manually instead.
$escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query);

$stmt = $pdo->prepare(
    "SELECT u.username, uc.card_api_id, uc.set_id, uc.card_number, uc.card_name,
            uc.image_url, uc.card_condition
     FROM user_cards uc
     INNER JOIN users u ON u.id = uc.user_id
     WHERE uc.storage_type = 'trade_binder'
       AND u.public_binder_enabled = 1
       AND u.id != :my_id
       AND uc.card_name LIKE :query ESCAPE '!'
     ORDER BY u.username, uc.card_name"
);
$stmt->execute(['my_id' => $userId, 'query' => '%' . $escaped . '%']);

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
