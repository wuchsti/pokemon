<?php

// Lists Pokémon TCG sets (for set-picker UI and set-progress calculations).
// GET /api/tcg/sets.php              -> list of all sets
// GET /api/tcg/sets.php?id=xy1       -> single set detail
//
// Die Set-Liste ändert sich kaum (neue Sets erscheinen alle paar Monate),
// daher wird sie serverseitig in der api_cache-Tabelle gecacht: einmal pro
// Tag von der externen API holen, den Rest des Tages aus der DB bedienen.
// Fällt die API mal aus, wird als Fallback der letzte bekannte Stand
// ausgeliefert statt eines Fehlers.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/tcg_client.php';
require_once __DIR__ . '/../lib/api_cache.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$setId = trim((string) ($_GET['id'] ?? ''));

if ($setId !== '') {
    $result = tcg_api_get($config, '/sets/' . rawurlencode($setId));
    json_response(['set' => $result['data'] ?? null]);
}

const SETS_CACHE_TTL_SECONDS = 86400; // 1 Tag

$pdo = get_db($config);

$data = tcg_cached_fetch(
    $pdo,
    $config,
    // v2: the cached value shape changed (now {sets: [...]} instead of the
    // raw decoded API body) when set_cards.php/search.php got the same
    // caching treatment - a different key avoids misreading an older,
    // differently-shaped row still sitting in the DB from before this
    // change as a (silently empty) "fresh" cache hit.
    'tcg_sets_all_v2',
    SETS_CACHE_TTL_SECONDS,
    fn () => fetch_all_sets_live($config),
    '/sets'
);

json_response(['sets' => $data['sets'] ?? []]);
