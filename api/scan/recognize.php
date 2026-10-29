<?php

// POST /api/scan/recognize.php
// Body: { image: "data:image/jpeg;base64,..." }
//
// Runs the photo through the Vision API to read the card's name/number,
// then tries to auto-match it against the Pokémon TCG API so the frontend
// can offer ready-to-pick candidates (the "Fast-Add" flow).

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';
require_once __DIR__ . '/../lib/vision_client.php';
require_once __DIR__ . '/../lib/tcg_client.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);

$body = read_json_body();
$imageDataUri = (string) ($body['image'] ?? '');

if (!preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,([a-zA-Z0-9+\/=]+)$/', $imageDataUri, $matches)) {
    json_error('Ungültiges Bildformat. Erwartet wird eine JPEG/PNG/WebP-Daten-URI.');
}

$decodedSize = (int) (strlen($matches[2]) * 3 / 4);
if ($decodedSize > 4 * 1024 * 1024) {
    json_error('Bild zu groß (max. 4 MB). Bitte verkleinern oder erneut aufnehmen.');
}

$recognition = recognize_card_image($config, $imageDataUri);
if (!$recognition['ok']) {
    json_error($recognition['error'], 502);
}

$extracted = $recognition['data'];
$candidates = find_card_candidates($config, $extracted);

$pdo = get_db($config);
$pdo->prepare('INSERT INTO scan_log (user_id, card_name, matched) VALUES (:user_id, :card_name, :matched)')
    ->execute([
        'user_id' => $userId,
        'card_name' => $extracted['card_name'] ?? null,
        'matched' => $candidates !== [] ? 1 : 0,
    ]);

json_response([
    'extracted' => $extracted,
    'candidates' => $candidates,
]);

/**
 * Best-effort lookup against the Pokémon TCG API using whatever the vision
 * model could read. Falls back progressively (number+name -> number only ->
 * name only) so a shaky OCR read on one field doesn't kill the whole match.
 */
function find_card_candidates(array $config, array $extracted): array
{
    $number = $extracted['card_number'] ?? null;
    $name = $extracted['card_name'] ?? null;

    $attempts = [];
    if ($number && $name) {
        $attempts[] = sprintf('number:%s name:"*%s*"', $number, str_replace('"', '', $name));
    }
    if ($number) {
        $attempts[] = sprintf('number:%s', $number);
    }
    if ($name) {
        $attempts[] = sprintf('name:"*%s*"', str_replace('"', '', $name));
    }

    foreach ($attempts as $query) {
        $result = tcg_api_request($config, '/cards', [
            'q' => $query,
            'page' => 1,
            'pageSize' => 10,
            'orderBy' => 'name',
        ]);

        if ($result['ok'] && !empty($result['data']['data'])) {
            return $result['data']['data'];
        }
    }

    return [];
}
