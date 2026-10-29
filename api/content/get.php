<?php

// GET /api/content/get.php?key=impressum
// Public (no auth) - serves admin-editable site text for pages like
// Impressum/Datenschutz that must be readable by anyone.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$allowedKeys = ['impressum', 'datenschutz'];
$key = trim((string) ($_GET['key'] ?? ''));

if (!in_array($key, $allowedKeys, true)) {
    json_error('Ungültiger content key.');
}

$pdo = get_db($config);
$stmt = $pdo->prepare('SELECT content_key, content_value, updated_at FROM site_content WHERE content_key = :key');
$stmt->execute(['key' => $key]);
$row = $stmt->fetch();

if (!$row) {
    json_error('Inhalt noch nicht angelegt.', 404);
}

json_response(['content' => $row]);
