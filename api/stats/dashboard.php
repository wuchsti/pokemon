<?php

// GET /api/stats/dashboard.php
// Returns totals (vault/trade binder value), the top 5 treasures, a 30-day
// portfolio value series, and today's biggest gainer/loser.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';
require_once __DIR__ . '/../lib/price_stats.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);
$pdo = get_db($config);

$cardsWithPrice = get_user_cards_with_latest_price($pdo, $userId);

json_response([
    'totals' => summarize_totals($cardsWithPrice),
    'top_treasures' => get_top_treasures($cardsWithPrice, 5),
    'value_series' => get_portfolio_series($pdo, $userId, 30),
    'movers' => get_daily_movers($pdo, $userId),
    'card_count' => count($cardsWithPrice),
]);
