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
