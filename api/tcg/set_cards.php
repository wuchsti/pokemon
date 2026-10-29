<?php

// GET /api/tcg/set_cards.php?setId=xy1
// Returns every card in a set (paginating the upstream API internally),
// trimmed to the fields the "missing cards" / wishlist UI needs.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/tcg_client.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$setId = trim((string) ($_GET['setId'] ?? ''));
if ($setId === '') {
    json_error('setId ist erforderlich.');
}

$cards = [];
$page = 1;
$pageSize = 250;

do {
    $result = tcg_api_get($config, '/cards', [
        'q' => sprintf('set.id:%s', $setId),
        'page' => $page,
        'pageSize' => $pageSize,
        'orderBy' => 'number',
    ]);

    $batch = $result['data'] ?? [];
    foreach ($batch as $card) {
        $cards[] = [
            'id' => $card['id'],
            'number' => $card['number'] ?? '',
            'name' => $card['name'] ?? '',
            'image_small' => $card['images']['small'] ?? '',
        ];
    }

    $page++;
} while (count($batch) === $pageSize && $page <= 5);

json_response(['setId' => $setId, 'cards' => $cards]);
