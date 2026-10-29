<?php

// DELETE /api/cards/delete.php
// Body: { id }

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);
$body = read_json_body();

$id = (int) ($body['id'] ?? 0);
if ($id <= 0) {
    json_error('id ist erforderlich.');
}

$pdo = get_db($config);
$stmt = $pdo->prepare('DELETE FROM user_cards WHERE id = :id AND user_id = :user_id');
$stmt->execute(['id' => $id, 'user_id' => $userId]);

if ($stmt->rowCount() === 0) {
    json_error('Karte nicht gefunden.', 404);
}

json_response(['success' => true]);
