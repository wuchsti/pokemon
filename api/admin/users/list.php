<?php

// GET /api/admin/users/list.php

require_once __DIR__ . '/../../lib/response.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/admin.php';

$config = require __DIR__ . '/../../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

require_admin_user_id($config);
$pdo = get_db($config);

$stmt = $pdo->query(
    "SELECT u.id, u.username, u.email, u.is_admin, u.is_blocked, u.public_binder_enabled, u.created_at,
            (SELECT COUNT(*) FROM user_cards uc WHERE uc.user_id = u.id) AS card_count,
            (SELECT COUNT(*) FROM trades t WHERE t.user_a_id = u.id OR t.user_b_id = u.id) AS trade_count,
            (SELECT COUNT(*) FROM scan_log sl WHERE sl.user_id = u.id) AS scan_count
     FROM users u
     ORDER BY u.created_at DESC"
);

json_response(['users' => $stmt->fetchAll()]);
