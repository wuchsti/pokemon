<?php

// Helpers that turn user_cards + price_history into dashboard-ready numbers:
// totals per storage type, a daily portfolio value series, and day-over-day
// "biggest mover" cards (Phase 2 + the value-over-time / winners&losers idea).

/**
 * All of a user's card instances with their latest known Cardmarket price
 * (or null if no price has been synced for that card yet).
 */
function get_user_cards_with_latest_price(PDO $pdo, int $userId): array
{
    $sql = "
        SELECT uc.*, ph.price_eur AS latest_price, ph.recorded_at AS latest_price_date
        FROM user_cards uc
        LEFT JOIN (
            SELECT p1.card_api_id, p1.price_eur, p1.recorded_at
            FROM price_history p1
            INNER JOIN (
                SELECT card_api_id, MAX(recorded_at) AS max_date
                FROM price_history
                GROUP BY card_api_id
            ) latest ON latest.card_api_id = p1.card_api_id AND latest.max_date = p1.recorded_at
        ) ph ON ph.card_api_id = uc.card_api_id
        WHERE uc.user_id = :user_id
        ORDER BY ph.price_eur IS NULL, ph.price_eur DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll();
}

/**
 * Sums latest known value per storage type ('vault' / 'trade_binder').
 * Cards without a synced price are excluded from the sum (counted separately).
 */
function summarize_totals(array $cardsWithPrice): array
{
    $totals = ['vault' => 0.0, 'trade_binder' => 0.0];
    $unpriced = ['vault' => 0, 'trade_binder' => 0];

    foreach ($cardsWithPrice as $card) {
        $type = $card['storage_type'];
        if ($card['latest_price'] !== null) {
            $totals[$type] += (float) $card['latest_price'];
        } else {
            $unpriced[$type]++;
        }
    }

    return [
        'vault_value' => round($totals['vault'], 2),
        'trade_binder_value' => round($totals['trade_binder'], 2),
        'vault_unpriced_count' => $unpriced['vault'],
        'trade_binder_unpriced_count' => $unpriced['trade_binder'],
    ];
}

function get_top_treasures(array $cardsWithPrice, int $limit = 5): array
{
    $priced = array_values(array_filter($cardsWithPrice, static fn ($c) => $c['latest_price'] !== null));

    usort($priced, static fn ($a, $b) => $b['latest_price'] <=> $a['latest_price']);

    return array_slice($priced, 0, $limit);
}

/**
 * Builds a daily total-value series (last $days days) split by storage type,
 * forward-filling each card's last known price on days without a new sync.
 */
function get_portfolio_series(PDO $pdo, int $userId, int $days = 30): array
{
    $qtyStmt = $pdo->prepare(
        'SELECT card_api_id, storage_type, COUNT(*) AS qty
         FROM user_cards WHERE user_id = :user_id
         GROUP BY card_api_id, storage_type'
    );
    $qtyStmt->execute(['user_id' => $userId]);
    $holdings = $qtyStmt->fetchAll();

    if ($holdings === []) {
        return [];
    }

    $cardApiIds = array_values(array_unique(array_column($holdings, 'card_api_id')));
    $placeholders = implode(',', array_fill(0, count($cardApiIds), '?'));

    $startDate = (new DateTimeImmutable())->modify('-' . ($days - 1) . ' days')->format('Y-m-d');

    $priceStmt = $pdo->prepare(
        "SELECT card_api_id, price_eur, recorded_at
         FROM price_history
         WHERE card_api_id IN ($placeholders)
         ORDER BY card_api_id, recorded_at ASC"
    );
    $priceStmt->execute($cardApiIds);

    $pricesByCard = [];
    foreach ($priceStmt->fetchAll() as $row) {
        $pricesByCard[$row['card_api_id']][] = $row;
    }

    $dates = [];
    $cursor = new DateTimeImmutable($startDate);
    $today = new DateTimeImmutable('today');
    while ($cursor <= $today) {
        $dates[] = $cursor->format('Y-m-d');
        $cursor = $cursor->modify('+1 day');
    }

    $series = [];
    foreach ($dates as $date) {
        $series[$date] = [
            'date' => $date,
            'vault' => 0.0,
            'trade_binder' => 0.0,
            'has_data' => ['vault' => false, 'trade_binder' => false],
        ];
    }

    foreach ($holdings as $holding) {
        $cardApiId = $holding['card_api_id'];
        $type = $holding['storage_type'];
        $qty = (int) $holding['qty'];
        $history = $pricesByCard[$cardApiId] ?? [];

        $lastKnownPrice = null;
        $historyIndex = 0;
        $historyCount = count($history);

        foreach ($dates as $date) {
            while ($historyIndex < $historyCount && $history[$historyIndex]['recorded_at'] <= $date) {
                $lastKnownPrice = (float) $history[$historyIndex]['price_eur'];
                $historyIndex++;
            }

            if ($lastKnownPrice !== null) {
                $series[$date][$type] += $lastKnownPrice * $qty;
                $series[$date]['has_data'][$type] = true;
            }
        }
    }

    return array_map(static function (array $day) {
        return [
            'date' => $day['date'],
            'vault' => $day['has_data']['vault'] ? round($day['vault'], 2) : null,
            'trade_binder' => $day['has_data']['trade_binder'] ? round($day['trade_binder'], 2) : null,
        ];
    }, array_values($series));
}

/**
 * Day-over-day winners/losers: compares each owned card's two most recent
 * distinct price syncs and returns the biggest percentage gainer and loser.
 */
function get_daily_movers(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT DISTINCT card_api_id, set_id, card_number, card_name, image_url
         FROM user_cards WHERE user_id = :user_id'
    );
    $stmt->execute(['user_id' => $userId]);
    $ownedCards = $stmt->fetchAll();

    if ($ownedCards === []) {
        return ['gainer' => null, 'loser' => null];
    }

    $cardApiIds = array_column($ownedCards, 'card_api_id');
    $placeholders = implode(',', array_fill(0, count($cardApiIds), '?'));

    $priceStmt = $pdo->prepare(
        "SELECT card_api_id, price_eur, recorded_at
         FROM price_history
         WHERE card_api_id IN ($placeholders)
         ORDER BY card_api_id, recorded_at DESC"
    );
    $priceStmt->execute($cardApiIds);

    $lastTwoByCard = [];
    foreach ($priceStmt->fetchAll() as $row) {
        $id = $row['card_api_id'];
        $lastTwoByCard[$id] ??= [];
        if (count($lastTwoByCard[$id]) < 2) {
            $lastTwoByCard[$id][] = $row;
        }
    }

    $cardMetaById = [];
    foreach ($ownedCards as $card) {
        $cardMetaById[$card['card_api_id']] = $card;
    }

    $movers = [];
    foreach ($lastTwoByCard as $cardApiId => $rows) {
        if (count($rows) < 2) {
            continue;
        }
        [$latest, $previous] = $rows;
        $oldPrice = (float) $previous['price_eur'];
        $newPrice = (float) $latest['price_eur'];
        if ($oldPrice <= 0) {
            continue;
        }

        $deltaEur = round($newPrice - $oldPrice, 2);
        $deltaPct = round(($deltaEur / $oldPrice) * 100, 1);

        if ($deltaEur === 0.0) {
            continue;
        }

        $meta = $cardMetaById[$cardApiId];
        $movers[] = [
            'card_api_id' => $cardApiId,
            'card_name' => $meta['card_name'],
            'set_id' => $meta['set_id'],
            'card_number' => $meta['card_number'],
            'image_url' => $meta['image_url'],
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'delta_eur' => $deltaEur,
            'delta_pct' => $deltaPct,
            'as_of' => $latest['recorded_at'],
        ];
    }

    if ($movers === []) {
        return ['gainer' => null, 'loser' => null];
    }

    usort($movers, static fn ($a, $b) => $b['delta_pct'] <=> $a['delta_pct']);

    $gainer = $movers[0]['delta_pct'] > 0 ? $movers[0] : null;
    $loser = end($movers);
    $loser = $loser['delta_pct'] < 0 ? $loser : null;

    return ['gainer' => $gainer, 'loser' => $loser];
}
