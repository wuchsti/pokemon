<?php

// POST /api/cards/add.php
// Body: { card_api_id, set_id, card_number, card_name, image_url,
//         storage_type, card_condition, language, is_graded,
//         grading_company, grading_score }

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

$validStorageTypes = ['vault', 'trade_binder'];
$storageType = $body['storage_type'] ?? 'vault';
if (!in_array($storageType, $validStorageTypes, true)) {
    json_error('Ungültiger storage_type.');
}

$validConditions = ['Mint', 'Near Mint', 'Excellent', 'Good', 'Light Played', 'Played', 'Poor'];
$cardCondition = $body['card_condition'] ?? 'Near Mint';
if (!in_array($cardCondition, $validConditions, true)) {
    json_error('Ungültiger card_condition.');
}

$language = trim((string) ($body['language'] ?? 'DE')) ?: 'DE';
$isGraded = (bool) ($body['is_graded'] ?? false);

$gradingCompany = null;
$gradingScore = null;
if ($isGraded) {
    $validGradingCompanies = ['PSA', 'BGS', 'CGC', 'AP Grading'];
    $gradingCompany = trim((string) ($body['grading_company'] ?? ''));
    if (!in_array($gradingCompany, $validGradingCompanies, true)) {
        json_error('Ungültige grading_company.');
    }
    $gradingScore = isset($body['grading_score']) ? (float) $body['grading_score'] : null;
    if ($gradingScore === null || $gradingScore < 1.0 || $gradingScore > 10.0) {
        json_error('grading_score muss zwischen 1.0 und 10.0 liegen.');
    }
}

$pdo = get_db($config);
$stmt = $pdo->prepare(
    'INSERT INTO user_cards
        (user_id, card_api_id, set_id, card_number, card_name, image_url,
         storage_type, card_condition, language, is_graded, grading_company, grading_score)
     VALUES
        (:user_id, :card_api_id, :set_id, :card_number, :card_name, :image_url,
         :storage_type, :card_condition, :language, :is_graded, :grading_company, :grading_score)'
);
$stmt->execute([
    'user_id' => $userId,
    'card_api_id' => $cardApiId,
    'set_id' => $setId,
    'card_number' => $cardNumber,
    'card_name' => $cardName,
    'image_url' => $imageUrl,
    'storage_type' => $storageType,
    'card_condition' => $cardCondition,
    'language' => $language,
    'is_graded' => $isGraded ? 1 : 0,
    'grading_company' => $gradingCompany,
    'grading_score' => $gradingScore,
]);

$newId = (int) $pdo->lastInsertId();
$stmt = $pdo->prepare('SELECT * FROM user_cards WHERE id = :id');
$stmt->execute(['id' => $newId]);

json_response(['card' => $stmt->fetch()], 201);
