<?php

// GET /api/tcg/set_cards.php?setId=xy1
// Returns every card in a set (paginating the upstream API internally),
// trimmed to the fields the "missing cards" / wishlist UI needs, plus the
// current Cardmarket EUR price where available.
//
// Wie die Set-Liste ändert sich der Karten-Pool eines Sets so gut wie nie
// (fertig gedruckte Sets bleiben fix), daher wird auch das hier pro Set
// gecacht - vermeidet wiederholte Live-Abfragen beim Aufklappen einer
// Sammlung und liefert bei API-Ausfall den letzten bekannten Stand.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/tcg_client.php';
require_once __DIR__ . '/../lib/api_cache.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$setId = trim((string) ($_GET['setId'] ?? ''));
if ($setId === '') {
    json_error('setId ist erforderlich.');
}

const SET_CARDS_CACHE_TTL_SECONDS = 86400; // 1 Tag

$pdo = get_db($config);

$data = tcg_cached_fetch(
    $pdo,
    $config,
    'tcg_set_cards_' . $setId,
    SET_CARDS_CACHE_TTL_SECONDS,
    function () use ($config, $setId) {
        $cards = [];
        $page = 1;
        $pageSize = 250;

        do {
            $result = tcg_api_request($config, '/cards', [
                'q' => sprintf('set.id:%s', $setId),
                'page' => $page,
                'pageSize' => $pageSize,
                'orderBy' => 'number',
            ]);

            if (!$result['ok']) {
                return $result;
            }

            $batch = $result['data']['data'] ?? [];
            foreach ($batch as $card) {
                $cards[] = [
                    'id' => $card['id'],
                    'number' => $card['number'] ?? '',
                    'name' => $card['name'] ?? '',
                    'image_small' => $card['images']['small'] ?? '',
                    'price_eur' => extract_cardmarket_price($card),
                ];
            }

            $page++;
        } while (count($batch) === $pageSize && $page <= 5);

        return ['ok' => true, 'status' => 200, 'data' => ['cards' => $cards], 'error' => null];
    },
    "/cards?set.id=$setId"
);

json_response(['setId' => $setId, 'cards' => $data['cards'] ?? []]);
