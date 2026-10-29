<?php

// GET /api/admin/logs.php?source=tcg_api
// Recent technical errors from external API integrations (never shown to
// regular users - see api/lib/error_log.php).

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

$sql = 'SELECT id, source, message, context, created_at FROM error_log';
$params = [];

$source = trim((string) ($_GET['source'] ?? ''));
if ($source !== '') {
    $sql .= ' WHERE source = :source';
    $params['source'] = $source;
}

$sql .= ' ORDER BY created_at DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

json_response(['logs' => $stmt->fetchAll()]);
