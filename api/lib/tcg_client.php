<?php

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/error_log.php';

/**
 * Performs a raw GET request against the Pokémon TCG API (api.pokemontcg.io).
 * Never ends the script; always returns a result shape so both the HTTP
 * endpoints and the CLI cronjob can decide how to react to a failure.
 *
 * @return array{ok: bool, status: int, data: array, error: ?string}
 */
function tcg_api_request(array $config, string $path, array $query = []): array
{
    $url = rtrim($config['tcg_api']['base_url'], '/') . '/' . ltrim($path, '/');
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    $headers = ['Accept: application/json'];
    if (!empty($config['tcg_api']['api_key'])) {
        $headers[] = 'X-Api-Key: ' . $config['tcg_api']['api_key'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $responseBody = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($responseBody === false) {
        return ['ok' => false, 'status' => 502, 'data' => [], 'error' => "Nicht erreichbar: $curlError"];
    }

    $decoded = json_decode($responseBody, true);

    if ($statusCode === 429) {
        return [
            'ok' => false,
            'status' => 429,
            'data' => is_array($decoded) ? $decoded : [],
            'error' => 'Zu viele Anfragen an die Pokémon TCG API (Rate-Limit). '
                . 'Ohne TCG_API_KEY in der .env ist das Limit sehr niedrig - '
                . 'kostenlosen Key auf pokemontcg.io/developers holen und eintragen.',
        ];
    }

    if (!is_array($decoded)) {
        return [
            'ok' => false,
            'status' => 502,
            'data' => [],
            'error' => "Ungültige Antwort der Pokémon TCG API (HTTP $statusCode).",
        ];
    }

    if ($statusCode >= 400) {
        return [
            'ok' => false,
            'status' => $statusCode,
            'data' => $decoded,
            'error' => $decoded['error']['message'] ?? "Fehler bei der Pokémon TCG API (HTTP $statusCode).",
        ];
    }

    return ['ok' => true, 'status' => $statusCode, 'data' => $decoded, 'error' => null];
}

/**
 * Same as tcg_api_request(), but ends the HTTP request with a proxied error
 * status on failure. Only use this from api/*.php endpoints, never from CLI
 * scripts (the cronjob should keep going after one card fails).
 *
 * The real, technical error (rate limits, upstream HTTP status, etc.) is
 * logged for admins under /admin/logs rather than shown to the user - a
 * regular user gets a short, friendly message instead and has no way to
 * act on "HTTP 429" or similar details anyway.
 */
function tcg_api_get(array $config, string $path, array $query = []): array
{
    $result = tcg_api_request($config, $path, $query);

    if (!$result['ok']) {
        log_api_error($config, 'tcg_api', $result['error'] ?? 'Unbekannter Fehler', "$path (HTTP {$result['status']})");
        json_error(
            'Pokémon-Kartendaten sind gerade nicht erreichbar. Bitte versuch es in ein paar Minuten erneut.',
            $result['status']
        );
    }

    return $result['data'];
}

/**
 * Cache key for a set's card list. Versioned because the cached shape
 * keeps gaining fields after older rows were already live in production
 * (v2: image_large: v3: price_updated_at) - bumping the key avoids
 * serving stale rows that silently lack a new field. Bump again whenever
 * fetch_set_cards_live()'s returned shape changes. Shared by
 * tcg/set_cards.php and admin/warm_cache.php so both read/write the
 * exact same cache entries.
 */
function set_cards_cache_key(string $setId): string
{
    return 'tcg_set_cards_v3_' . $setId;
}

/**
 * Live (uncached) fetch of every card in a set, trimmed to the fields the
 * UI needs plus the current Cardmarket EUR price. Paginates internally.
 * Shared by tcg/set_cards.php (per-set, cached) and the admin cache-warmer
 * (bulk, across all sets) so both stay in sync.
 *
 * @return array{ok: bool, status: int, data: array, error: ?string}
 */
function fetch_set_cards_live(array $config, string $setId): array
{
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
                'image_large' => $card['images']['large'] ?? ($card['images']['small'] ?? ''),
                'price_eur' => extract_cardmarket_price($card),
                'price_updated_at' => extract_cardmarket_updated_at($card),
            ];
        }

        $page++;
    } while (count($batch) === $pageSize && $page <= 5);

    return ['ok' => true, 'status' => 200, 'data' => ['cards' => $cards], 'error' => null];
}

/**
 * Live (uncached) fetch of the full set list. Shared by tcg/sets.php
 * (cached) and the admin cache-warmer (to know which sets to iterate).
 *
 * @return array{ok: bool, status: int, data: array, error: ?string}
 */
function fetch_all_sets_live(array $config): array
{
    // Explicit pageSize: the upstream API's own default page size is
    // smaller than the number of sets that exist, which would silently
    // cut off older sets from the "all collections" browser.
    $result = tcg_api_request($config, '/sets', ['orderBy' => '-releaseDate', 'pageSize' => 250]);
    if (!$result['ok']) {
        return $result;
    }
    return ['ok' => true, 'status' => 200, 'data' => ['sets' => $result['data']['data'] ?? []], 'error' => null];
}

/**
 * Extracts the best available Cardmarket EUR price from a card's API data,
 * or null if no Cardmarket pricing is available for that card.
 */
function extract_cardmarket_price(array $card): ?float
{
    $prices = $card['cardmarket']['prices'] ?? null;
    if (!is_array($prices)) {
        return null;
    }

    foreach (['trendPrice', 'averageSellPrice', 'avg30', 'avg7', 'avg1', 'lowPrice'] as $key) {
        if (isset($prices[$key]) && is_numeric($prices[$key]) && $prices[$key] > 0) {
            return round((float) $prices[$key], 2);
        }
    }

    return null;
}

/**
 * Datum, an dem die Pokémon TCG API ihren Cardmarket-Preis-Schnappschuss
 * für diese Karte zuletzt aktualisiert hat (nicht der heutige Tag - die
 * API fragt Cardmarket nicht live ab, sondern pflegt einen periodisch
 * aktualisierten Snapshot, der gerade bei Karten mit vielen Reprints
 * spürbar veraltet sein kann). Wird in der UI angezeigt, damit ein alter
 * Preis nicht mit einem aktuellen verwechselt wird.
 */
function extract_cardmarket_updated_at(array $card): ?string
{
    $value = $card['cardmarket']['updatedAt'] ?? null;
    return is_string($value) && $value !== '' ? $value : null;
}
