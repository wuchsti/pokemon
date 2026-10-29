<?php

// GET /api/tcg/set_cards.php?setId=xy1
// Returns every card in a set (paginating the upstream API internally),
// trimmed to the fields the "missing cards" / wishlist UI needs, plus the
// current Cardmarket EUR price where available.
//
// Wie die Set-Liste ändert sich der Karten-Pool eines Sets so gut wie nie
// (fertig gedruckte Sets bleiben fix), daher wird auch das hier pro Set
// gecacht - vermeidet wiederholte Live-Abfragen beim Aufklappen einer
// Sammlung und liefert bei API-Ausfall den letzten bekannten Stand.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/tcg_client.php';
require_once __DIR__ . '/../lib/api_cache.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$setId = trim((string) ($_GET['setId'] ?? ''));
if ($setId === '') {
    json_error('setId ist erforderlich.');
}

const SET_CARDS_CACHE_TTL_SECONDS = 86400; // 1 Tag

$pdo = get_db($config);

$data = tcg_cached_fetch(
    $pdo,
    $config,
    set_cards_cache_key($setId),
    SET_CARDS_CACHE_TTL_SECONDS,
    fn () => fetch_set_cards_live($config, $setId),
    "/cards?set.id=$setId"
);

json_response(['setId' => $setId, 'cards' => $data['cards'] ?? []]);
