<?php

// POST /api/admin/warm_cache.php
//
// Manuell auslösbares "jetzt alle Kartendaten & Preise holen" für den
// Admin-Bereich. Füllt den Set-Karten-Cache für jedes Set, das noch
// keinen frischen Eintrag hat, und synchronisiert den Preis für jede
// Karte, die heute noch keinen price_history-Eintrag hat.
//
// Läuft zeitlich begrenzt (siehe TIME_BUDGET_SECONDS), damit eine einzelne
// Anfrage sicher innerhalb typischer Webserver-Timeouts bleibt - nicht
// erledigte Arbeit wird einfach beim nächsten Klick fortgesetzt (bereits
// frische Sets/Preise werden übersprungen, der Aufruf ist also von sich
// aus fortsetzbar). Das Frontend ruft diesen Endpunkt wiederholt auf, bis
// "done" true ist.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin.php';
require_once __DIR__ . '/../lib/tcg_client.php';
require_once __DIR__ . '/../lib/api_cache.php';
require_once __DIR__ . '/../lib/price_sync.php';
require_once __DIR__ . '/../lib/error_log.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Methode nicht erlaubt.', 405);
}

require_admin_user_id($config);
$pdo = get_db($config);

const TIME_BUDGET_SECONDS = 20.0;
const CACHE_TTL_SECONDS = 86400;

$startTime = microtime(true);
$timeIsUp = fn () => (microtime(true) - $startTime) > TIME_BUDGET_SECONDS;

$setsUpdated = 0;
$setsSkippedFresh = 0;
$setsFailed = 0;
$setsRemaining = 0;

// --- Phase 1: Kartenliste pro Set im Cache auffrischen ---
$setsData = tcg_cached_fetch($pdo, $config, 'tcg_sets_all_v2', CACHE_TTL_SECONDS, fn () => fetch_all_sets_live($config), '/sets');
$allSets = $setsData['sets'] ?? [];

foreach ($allSets as $set) {
    $setId = $set['id'] ?? null;
    if ($setId === null) {
        continue;
    }

    $cacheKey = 'tcg_set_cards_' . $setId;
    if (cache_get($pdo, $cacheKey, CACHE_TTL_SECONDS) !== null) {
        $setsSkippedFresh++;
        continue;
    }

    if ($timeIsUp()) {
        $setsRemaining++;
        continue;
    }

    $result = fetch_set_cards_live($config, $setId);
    if ($result['ok']) {
        cache_set($pdo, $cacheKey, $result['data']);
        $setsUpdated++;
    } else {
        log_api_error($config, 'tcg_api', $result['error'] ?? 'Unbekannter Fehler', "/cards?set.id=$setId (warm_cache)");
        $setsFailed++;
    }

    usleep(150_000); // Rate-Limit der kostenlosen API schonen
}

// --- Phase 2: Preise für heute noch nicht synchronisierte Karten holen ---
$pricesUpdated = 0;
$pricesSkipped = 0;
$pricesFailed = 0;
$pricesRemaining = 0;

$today = date('Y-m-d');
$stmt = $pdo->prepare(
    "SELECT DISTINCT uc.card_api_id FROM user_cards uc
     LEFT JOIN price_history ph ON ph.card_api_id = uc.card_api_id AND ph.recorded_at = :today
     WHERE ph.card_api_id IS NULL"
);
$stmt->execute(['today' => $today]);
$cardIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($cardIds as $cardApiId) {
    if ($timeIsUp()) {
        $pricesRemaining++;
        continue;
    }

    $result = sync_card_price($pdo, $config, $cardApiId, $today);
    if ($result['status'] === 'updated') {
        $pricesUpdated++;
    } elseif ($result['status'] === 'skipped') {
        $pricesSkipped++;
    } else {
        log_api_error($config, 'tcg_api', $result['error'] ?? 'Unbekannter Fehler', "/cards/$cardApiId (warm_cache price sync)");
        $pricesFailed++;
    }

    usleep(150_000);
}

json_response([
    'done' => $setsRemaining === 0 && $pricesRemaining === 0,
    'sets' => [
        'updated' => $setsUpdated,
        'skipped_fresh' => $setsSkippedFresh,
        'failed' => $setsFailed,
        'remaining' => $setsRemaining,
        'total' => count($allSets),
    ],
    'prices' => [
        'updated' => $pricesUpdated,
        'skipped' => $pricesSkipped,
        'failed' => $pricesFailed,
        'remaining' => $pricesRemaining,
        'total' => count($cardIds),
    ],
]);
