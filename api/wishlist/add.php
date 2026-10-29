<?php

// POST /api/wishlist/add.php
// Body: { card_api_id, set_id, card_number, card_name, image_url }

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);
$body = read_json_body();

$cardApiId = trim((string) ($body['card_api_id'] ?? ''));
$setId = trim((string) ($body['set_id'] ?? ''));
$cardNumber = trim((string) ($body['card_number'] ?? ''));
$cardName = trim((string) ($body['card_name'] ?? ''));
$imageUrl = trim((string) ($body['image_url'] ?? ''));

if ($cardApiId === '' || $setId === '' || $cardNumber === '' || $cardName === '' || $imageUrl === '') {
    json_error('card_api_id, set_id, card_number, card_name und image_url sind erforderlich.');
}

$pdo = get_db($config);

$stmt = $pdo->prepare(
    'INSERT INTO wishlist_items (user_id, card_api_id, set_id, card_number, card_name, image_url)
     VALUES (:user_id, :card_api_id, :set_id, :card_number, :card_name, :image_url)
     ON DUPLICATE KEY UPDATE card_number = card_number'
);
$stmt->execute([
    'user_id' => $userId,
    'card_api_id' => $cardApiId,
    'set_id' => $setId,
    'card_number' => $cardNumber,
    'card_name' => $cardName,
    'image_url' => $imageUrl,
]);

$stmt = $pdo->prepare('SELECT * FROM wishlist_items WHERE user_id = :user_id AND card_api_id = :card_api_id');
$stmt->execute(['user_id' => $userId, 'card_api_id' => $cardApiId]);

json_response(['item' => $stmt->fetch()], 201);
