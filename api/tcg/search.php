<?php

// Searches cards via the Pokémon TCG API.
// GET /api/tcg/search.php?q=pikachu&page=1&pageSize=20
// GET /api/tcg/search.php?setId=xy1&number=12

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/tcg_client.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$query = trim((string) ($_GET['q'] ?? ''));
$setId = trim((string) ($_GET['setId'] ?? ''));
$number = trim((string) ($_GET['number'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$pageSize = min(50, max(1, (int) ($_GET['pageSize'] ?? 20)));

$filters = [];
if ($query !== '') {
    $escaped = str_replace('"', '', $query);
    $filters[] = sprintf('name:"*%s*"', $escaped);
}
if ($setId !== '') {
    $filters[] = sprintf('set.id:%s', $setId);
}
if ($number !== '') {
    $filters[] = sprintf('number:%s', $number);
}

if ($filters === []) {
    json_error('Bitte einen Suchbegriff, eine Set-ID oder eine Kartennummer angeben.');
}

$result = tcg_api_get($config, '/cards', [
    'q' => implode(' ', $filters),
    'page' => $page,
    'pageSize' => $pageSize,
    'orderBy' => 'name',
]);

json_response([
    'cards' => $result['data'] ?? [],
    'page' => $result['page'] ?? $page,
    'pageSize' => $result['pageSize'] ?? $pageSize,
    'count' => $result['count'] ?? 0,
    'totalCount' => $result['totalCount'] ?? 0,
]);
