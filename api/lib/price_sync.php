<?php

// Shared price-sync logic for one card, used both by the nightly cron
// (cron/sync_prices.php) and the admin "jetzt synchronisieren"-Knopf
// (api/admin/warm_cache.php), so both stay in sync.

require_once __DIR__ . '/tcg_client.php';

/**
 * Fetches the current Cardmarket EUR price for one card and stores it as
 * today's price_history row (idempotent: a second call the same day just
 * updates that row, thanks to the unique_card_date key).
 *
 * @return array{status: 'updated'|'skipped'|'failed', price?: float, error?: string}
 */
function sync_card_price(PDO $pdo, array $config, string $cardApiId, string $today): array
{
    $result = tcg_api_request($config, '/cards/' . rawurlencode($cardApiId));

    if (!$result['ok']) {
        return ['status' => 'failed', 'error' => $result['error'] ?? 'Unbekannter Fehler'];
    }

    $price = extract_cardmarket_price($result['data']['data'] ?? []);
    if ($price === null) {
        return ['status' => 'skipped'];
    }

    $stmt = $pdo->prepare(
        'INSERT INTO price_history (card_api_id, price_eur, recorded_at)
         VALUES (:card_api_id, :price_eur, :recorded_at)
         ON DUPLICATE KEY UPDATE price_eur = VALUES(price_eur)'
    );
    $stmt->execute(['card_api_id' => $cardApiId, 'price_eur' => $price, 'recorded_at' => $today]);

    return ['status' => 'updated', 'price' => $price];
}
