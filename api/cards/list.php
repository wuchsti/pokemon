<?php

// GET /api/cards/list.php?storage_type=vault&set_id=xy1

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);
$pdo = get_db($config);

$sql = 'SELECT * FROM user_cards WHERE user_id = :user_id';
$params = ['user_id' => $userId];

$storageType = $_GET['storage_type'] ?? null;
if (in_array($storageType, ['vault', 'trade_binder'], true)) {
    $sql .= ' AND storage_type = :storage_type';
    $params['storage_type'] = $storageType;
}

$setId = trim((string) ($_GET['set_id'] ?? ''));
if ($setId !== '') {
    $sql .= ' AND set_id = :set_id';
    $params['set_id'] = $setId;
}

$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

json_response(['cards' => $stmt->fetchAll()]);
