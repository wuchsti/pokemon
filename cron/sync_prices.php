<?php

// Daily price sync. Run via the Netcup/Plesk Task Scheduler, e.g.:
//   php /path/to/httpdocs/cron/sync_prices.php
//
// For every card currently held by any user (in the vault or the trade
// binder), fetches the current Cardmarket EUR price from the Pokémon TCG
// API and stores one row per day in price_history. Safe to re-run: a
// second run on the same day just updates today's row.

require_once __DIR__ . '/../api/lib/db.php';
require_once __DIR__ . '/../api/lib/tcg_client.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Dieses Skript darf nur über die Kommandozeile/den Cronjob ausgeführt werden.');
}

$config = require __DIR__ . '/../api/config.php';
$pdo = get_db($config);

$cardIds = $pdo->query('SELECT DISTINCT card_api_id FROM user_cards')->fetchAll(PDO::FETCH_COLUMN);

if ($cardIds === []) {
    echo "Keine Karten in user_cards gefunden, nichts zu synchronisieren.\n";
    exit(0);
}

$today = date('Y-m-d');
$upsert = $pdo->prepare(
    'INSERT INTO price_history (card_api_id, price_eur, recorded_at)
     VALUES (:card_api_id, :price_eur, :recorded_at)
     ON DUPLICATE KEY UPDATE price_eur = VALUES(price_eur)'
);

$updated = 0;
$skipped = 0;
$failed = 0;

foreach ($cardIds as $cardApiId) {
    $result = tcg_api_request($config, '/cards/' . rawurlencode($cardApiId));

    if (!$result['ok']) {
        $failed++;
        echo "[FEHLER] $cardApiId: {$result['error']}\n";
        usleep(200_000);
        continue;
    }

    $price = extract_cardmarket_price($result['data']['data'] ?? []);

    if ($price === null) {
        $skipped++;
        echo "[ÜBERSPRUNGEN] $cardApiId: kein Cardmarket-Preis verfügbar.\n";
        usleep(200_000);
        continue;
    }

    $upsert->execute([
        'card_api_id' => $cardApiId,
        'price_eur' => $price,
        'recorded_at' => $today,
    ]);
    $updated++;
    echo "[OK] $cardApiId: {$price} EUR\n";

    // Be gentle with the free-tier Pokémon TCG API rate limit.
    usleep(200_000);
}

echo "\nFertig: $updated aktualisiert, $skipped übersprungen, $failed fehlgeschlagen (von " . count($cardIds) . " Karten).\n";
