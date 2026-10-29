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

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    // Most likely cause: database/migrations/003_add_error_log.sql hasn't
    // been run yet on this installation (git-deployed code can get ahead
    // of the database, since migrations aren't applied automatically).
    json_error(
        'Die error_log-Tabelle fehlt. Bitte database/migrations/003_add_error_log.sql '
        . '(oder die komplette schema.sql, ist idempotent) in der Datenbank einspielen.',
        500
    );
}

json_response(['logs' => $logs]);
