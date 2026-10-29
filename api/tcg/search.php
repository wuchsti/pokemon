<?php

// Searches cards via the Pokémon TCG API, across all sets.
// GET /api/tcg/search.php?q=pikachu&page=1&pageSize=20
// GET /api/tcg/search.php?q=28/94                  (treated as a number)
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

// A query that's purely digits (optionally "28/94", as printed on the
// card) is a collector number, not a card name - a name:"*28*" search
// would never match anything, which is exactly why number search silently
// failed before. Route it to the number filter instead.
if ($query !== '' && $number === '' && preg_match('/^\d+(\/\d+)?$/', $query)) {
    $number = $query;
    $query = '';
}

$filters = [];
if ($query !== '') {
    $escaped = str_replace('"', '', $query);
    $filters[] = sprintf('name:"*%s*"', $escaped);
}
if ($setId !== '') {
    $filters[] = sprintf('set.id:%s', $setId);
}
if ($number !== '') {
    $filters[] = build_number_filter($number);
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

$cards = array_map(function (array $card): array {
    $card['price_eur'] = extract_cardmarket_price($card);
    return $card;
}, $result['data'] ?? []);

json_response([
    'cards' => $cards,
    'page' => $result['page'] ?? $page,
    'pageSize' => $result['pageSize'] ?? $pageSize,
    'count' => $result['count'] ?? 0,
    'totalCount' => $result['totalCount'] ?? 0,
]);

/**
 * The upstream API stores collector numbers inconsistently (some sets keep
 * leading zeros like "028", others store "28"), and the number printed on
 * the card is usually "028/094" (number/total). Strip the "/total" part
 * and try both the raw and zero-stripped form so "028/094", "028" and "28"
 * all find the same card.
 */
function build_number_filter(string $number): string
{
    $digits = preg_replace('/\/.*$/', '', $number);
    $digits = preg_replace('/\D/', '', $digits);
    if ($digits === '') {
        return sprintf('number:%s', $number);
    }

    $stripped = ltrim($digits, '0');
    if ($stripped === '') {
        $stripped = '0';
    }

    $variants = array_unique([$digits, $stripped]);
    if (count($variants) === 1) {
        return sprintf('number:%s', $variants[0]);
    }

    return '(' . implode(' OR ', array_map(fn ($v) => "number:$v", $variants)) . ')';
}
