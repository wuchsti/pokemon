<?php

// GET /api/admin/stats.php
// Aggregate counts for the admin dashboard.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

require_admin_user_id($config);
$pdo = get_db($config);

$users = $pdo->query(
    "SELECT COUNT(*) AS total, SUM(is_blocked) AS blocked, SUM(is_admin) AS admins
     FROM users"
)->fetch();

$cards = $pdo->query(
    "SELECT COUNT(*) AS total,
            SUM(storage_type = 'vault') AS vault,
            SUM(storage_type = 'trade_binder') AS trade_binder
     FROM user_cards"
)->fetch();

$trades = $pdo->query(
    "SELECT COUNT(*) AS total,
            SUM(status = 'open') AS open,
            SUM(status = 'completed') AS completed,
            SUM(status = 'cancelled') AS cancelled
     FROM trades"
)->fetch();

$scans = $pdo->query(
    "SELECT COUNT(*) AS total, SUM(matched) AS matched FROM scan_log"
)->fetch();

$wishlistTotal = (int) $pdo->query('SELECT COUNT(*) FROM wishlist_items')->fetchColumn();
$publicBindersTotal = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE public_binder_enabled = 1')->fetchColumn();

json_response([
    'users' => [
        'total' => (int) $users['total'],
        'blocked' => (int) $users['blocked'],
        'active' => (int) $users['total'] - (int) $users['blocked'],
        'admins' => (int) $users['admins'],
    ],
    'cards' => [
        'total' => (int) $cards['total'],
        'vault' => (int) $cards['vault'],
        'trade_binder' => (int) $cards['trade_binder'],
    ],
    'trades' => [
        'total' => (int) $trades['total'],
        'open' => (int) $trades['open'],
        'completed' => (int) $trades['completed'],
        'cancelled' => (int) $trades['cancelled'],
    ],
    'scans' => [
        'total' => (int) $scans['total'],
        'matched' => (int) $scans['matched'],
    ],
    'wishlist_items_total' => $wishlistTotal,
    'public_binders_total' => $publicBindersTotal,
]);
