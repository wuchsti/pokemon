<?php

// POST /api/admin/content/update.php
// Body: { key, value }

require_once __DIR__ . '/../../lib/response.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/admin.php';

$config = require __DIR__ . '/../../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Methode nicht erlaubt.', 405);
}

require_admin_user_id($config);
$body = read_json_body();

$allowedKeys = ['impressum', 'datenschutz'];
$key = trim((string) ($body['key'] ?? ''));
$value = (string) ($body['value'] ?? '');

if (!in_array($key, $allowedKeys, true)) {
    json_error('Ungültiger content key.');
}
if (trim($value) === '') {
    json_error('value darf nicht leer sein.');
}

$pdo = get_db($config);
$stmt = $pdo->prepare(
    'INSERT INTO site_content (content_key, content_value) VALUES (:key, :value)
     ON DUPLICATE KEY UPDATE content_value = :value2'
);
$stmt->execute(['key' => $key, 'value' => $value, 'value2' => $value]);

$stmt = $pdo->prepare('SELECT content_key, content_value, updated_at FROM site_content WHERE content_key = :key');
$stmt->execute(['key' => $key]);

json_response(['content' => $stmt->fetch()]);
