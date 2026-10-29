<?php

// DELETE /api/wishlist/remove.php
// Body: { id } or { card_api_id }

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
$cardApiId = trim((string) ($body['card_api_id'] ?? ''));

if ($id <= 0 && $cardApiId === '') {
    json_error('id oder card_api_id ist erforderlich.');
}

$pdo = get_db($config);

if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM wishlist_items WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
} else {
    $stmt = $pdo->prepare('DELETE FROM wishlist_items WHERE card_api_id = :card_api_id AND user_id = :user_id');
    $stmt->execute(['card_api_id' => $cardApiId, 'user_id' => $userId]);
}

if ($stmt->rowCount() === 0) {
    json_error('Eintrag nicht gefunden.', 404);
}

json_response(['success' => true]);
