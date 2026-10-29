<?php

// PUT /api/cards/update.php
// Body: { id, storage_type?, card_condition?, language?, is_graded?, grading_company?, grading_score? }

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);
$body = read_json_body();

$id = (int) ($body['id'] ?? 0);
if ($id <= 0) {
    json_error('id ist erforderlich.');
}

$pdo = get_db($config);
$stmt = $pdo->prepare('SELECT * FROM user_cards WHERE id = :id AND user_id = :user_id');
$stmt->execute(['id' => $id, 'user_id' => $userId]);
$existing = $stmt->fetch();

if (!$existing) {
    json_error('Karte nicht gefunden.', 404);
}

$updates = [];
$params = ['id' => $id, 'user_id' => $userId];

if (isset($body['storage_type'])) {
    if (!in_array($body['storage_type'], ['vault', 'trade_binder'], true)) {
        json_error('Ungültiger storage_type.');
    }
    $updates[] = 'storage_type = :storage_type';
    $params['storage_type'] = $body['storage_type'];
}

if (isset($body['card_condition'])) {
    $validConditions = ['Mint', 'Near Mint', 'Excellent', 'Good', 'Light Played', 'Played', 'Poor'];
    if (!in_array($body['card_condition'], $validConditions, true)) {
        json_error('Ungültiger card_condition.');
    }
    $updates[] = 'card_condition = :card_condition';
    $params['card_condition'] = $body['card_condition'];
}

if (isset($body['language'])) {
    $updates[] = 'language = :language';
    $params['language'] = trim((string) $body['language']) ?: 'DE';
}

if (isset($body['is_graded'])) {
    $isGraded = (bool) $body['is_graded'];
    $updates[] = 'is_graded = :is_graded';
    $params['is_graded'] = $isGraded ? 1 : 0;

    if ($isGraded) {
        $validGradingCompanies = ['PSA', 'BGS', 'CGC', 'AP Grading'];
        $gradingCompany = trim((string) ($body['grading_company'] ?? ''));
        $gradingScore = isset($body['grading_score']) ? (float) $body['grading_score'] : null;

        if (!in_array($gradingCompany, $validGradingCompanies, true)) {
            json_error('Ungültige grading_company.');
        }
        if ($gradingScore === null || $gradingScore < 1.0 || $gradingScore > 10.0) {
            json_error('grading_score muss zwischen 1.0 und 10.0 liegen.');
        }

        $updates[] = 'grading_company = :grading_company';
        $params['grading_company'] = $gradingCompany;
        $updates[] = 'grading_score = :grading_score';
        $params['grading_score'] = $gradingScore;
    } else {
        $updates[] = 'grading_company = NULL';
        $updates[] = 'grading_score = NULL';
    }
}

if ($updates === []) {
    json_error('Keine Änderungen angegeben.');
}

$sql = 'UPDATE user_cards SET ' . implode(', ', $updates) . ' WHERE id = :id AND user_id = :user_id';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$stmt = $pdo->prepare('SELECT * FROM user_cards WHERE id = :id');
$stmt->execute(['id' => $id]);

json_response(['card' => $stmt->fetch()]);
