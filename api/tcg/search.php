<?php

// Searches cards via the Pokémon TCG API, across all sets.
// GET /api/tcg/search.php?q=pikachu&page=1&pageSize=20
// GET /api/tcg/search.php?q=28/94                  (treated as a number)
// GET /api/tcg/search.php?setId=xy1&number=12

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/tcg_client.php';
require_once __DIR__ . '/../lib/api_cache.php';

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

// "SFA 064/188" / "SFA064/188" style input: the printed set abbreviation
// (ptcgoCode - the same 2-5 letter code stamped bottom-left on the
// physical card, right next to the collector number) followed by that
// number. Modern (Scarlet & Violet-era) sets share the exact same
// set/number structure across English and European-language prints -
// only the card text is translated - so this also resolves German/
// French/Italian/Spanish cards correctly, not just English ones.
if ($query !== '' && $setId === '' && preg_match('/^([A-Za-z]{2,5})[\s\-]*(\d+)(\/\d+)?$/', $query, $m)) {
    $pdo = get_db($config);
    $resolvedSetId = resolve_set_id_by_ptcgo_code($pdo, $config, $m[1]);
    if ($resolvedSetId !== null) {
        $setId = $resolvedSetId;
        $number = $m[2];
        $query = '';
    }
}

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
    $card['price_updated_at'] = extract_cardmarket_updated_at($card);
    $card['price_usd'] = extract_tcgplayer_price($card);
    $card['price_usd_updated_at'] = extract_tcgplayer_updated_at($card);
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

/**
 * Looks up a set's internal API id by its printed abbreviation
 * (ptcgoCode), e.g. "SFA" -> the Shrouded Fable set's id. Uses the same
 * cached set list as tcg/sets.php (no extra API call for the common case).
 * Returns null if no set with that abbreviation is known.
 */
function resolve_set_id_by_ptcgo_code(PDO $pdo, array $config, string $code): ?string
{
    $setsData = tcg_cached_fetch($pdo, $config, 'tcg_sets_all_v2', 86400, fn () => fetch_all_sets_live($config), '/sets');
    $needle = strtoupper($code);

    foreach ($setsData['sets'] ?? [] as $set) {
        if (isset($set['ptcgoCode']) && strtoupper((string) $set['ptcgoCode']) === $needle) {
            return $set['id'];
        }
    }

    return null;
}
