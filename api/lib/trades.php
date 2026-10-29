<?php

require_once __DIR__ . '/response.php';

/**
 * Loads a trade and verifies the given user is one of its two participants.
 * Ends the request with a 404 otherwise - a non-participant must not even
 * learn that the trade exists.
 */
function get_trade_or_fail(PDO $pdo, int $tradeId, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT t.*, ua.username AS user_a_username, ub.username AS user_b_username
         FROM trades t
         JOIN users ua ON ua.id = t.user_a_id
         JOIN users ub ON ub.id = t.user_b_id
         WHERE t.id = :id'
    );
    $stmt->execute(['id' => $tradeId]);
    $trade = $stmt->fetch();

    if (!$trade || ((int) $trade['user_a_id'] !== $userId && (int) $trade['user_b_id'] !== $userId)) {
        json_error('Tausch nicht gefunden.', 404);
    }

    return $trade;
}

/**
 * Shapes a trade row for the current viewer: only their partner's nickname
 * is exposed (never an e-mail address), and "my"/"partner" confirmation
 * flags are resolved relative to who is asking.
 */
function format_trade(array $trade, int $userId): array
{
    $isUserA = (int) $trade['user_a_id'] === $userId;

    return [
        'id' => (int) $trade['id'],
        'status' => $trade['status'],
        'partner_username' => $isUserA ? $trade['user_b_username'] : $trade['user_a_username'],
        'my_confirmed' => (bool) ($isUserA ? $trade['user_a_confirmed'] : $trade['user_b_confirmed']),
        'partner_confirmed' => (bool) ($isUserA ? $trade['user_b_confirmed'] : $trade['user_a_confirmed']),
        'created_at' => $trade['created_at'],
        'updated_at' => $trade['updated_at'],
    ];
}
