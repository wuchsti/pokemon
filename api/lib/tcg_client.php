<?php

require_once __DIR__ . '/response.php';

/**
 * Performs a GET request against the Pokémon TCG API (api.pokemontcg.io)
 * and returns the decoded JSON body. Ends the request with a proxied error
 * status on failure.
 */
function tcg_api_get(array $config, string $path, array $query = []): array
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
        json_error('Pokémon TCG API nicht erreichbar: ' . $curlError, 502);
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        json_error('Ungültige Antwort der Pokémon TCG API.', 502);
    }

    if ($statusCode >= 400) {
        json_error($decoded['error']['message'] ?? 'Fehler bei der Pokémon TCG API.', $statusCode);
    }

    return $decoded;
}
