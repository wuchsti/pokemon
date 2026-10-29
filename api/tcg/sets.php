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
require_once __DIR__ . '/../lib/error_log.php';

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

const SETS_CACHE_KEY = 'tcg_sets_all';
const SETS_CACHE_TTL_SECONDS = 86400; // 1 Tag

$pdo = get_db($config);

$cached = cache_get($pdo, SETS_CACHE_KEY, SETS_CACHE_TTL_SECONDS);
if ($cached !== null) {
    json_response(['sets' => $cached['data'] ?? []]);
}

// Explicit pageSize: the upstream API's own default page size is smaller
// than the number of sets that exist, which would silently cut off older
// sets from the "all collections" browser.
$result = tcg_api_request($config, '/sets', ['orderBy' => '-releaseDate', 'pageSize' => 250]);

if ($result['ok']) {
    cache_set($pdo, SETS_CACHE_KEY, $result['data']);
    json_response(['sets' => $result['data']['data'] ?? []]);
}

log_api_error($config, 'tcg_api', $result['error'] ?? 'Unbekannter Fehler', '/sets (HTTP ' . $result['status'] . ')');

$stale = cache_get_stale($pdo, SETS_CACHE_KEY);
if ($stale !== null) {
    json_response(['sets' => $stale['data'] ?? []]);
}

json_error(
    'Pokémon-Kartendaten sind gerade nicht erreichbar. Bitte versuch es in ein paar Minuten erneut.',
    $result['status']
);
