<?php

// Lists Pokémon TCG sets (for set-picker UI and set-progress calculations).
// GET /api/tcg/sets.php              -> list of all sets
// GET /api/tcg/sets.php?id=xy1       -> single set detail

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/tcg_client.php';

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

$result = tcg_api_get($config, '/sets', ['orderBy' => '-releaseDate']);
json_response(['sets' => $result['data'] ?? []]);
