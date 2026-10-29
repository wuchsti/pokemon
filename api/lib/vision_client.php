<?php

require_once __DIR__ . '/response.php';

const VISION_RECOGNITION_PROMPT = <<<'PROMPT'
You are a precise OCR assistant for Pokémon Trading Card Game cards.
You will be shown a single photo of one physical card. Extract:

- card_name: the card's name as printed in the large title text (keep the
  original language/spelling, do not translate it).
- card_number: the collector number printed near the bottom edge, usually
  formatted as "NN/NNN" - return ONLY the part before the slash (e.g. "12").
- set_total: the part after the slash in that same "NN/NNN" print (e.g.
  "135"), representing the set's total card count.
- language_guess: your best guess at the card's printed language, one of
  "DE", "EN", "JP", "FR", "IT", "ES", or "OTHER".
- confidence: your own confidence in this extraction as a number from 0 to 1.

Respond with ONLY a single raw JSON object, no markdown fences, no
explanation, exactly matching this shape:
{"card_name": string|null, "card_number": string|null, "set_total": string|null, "language_guess": string|null, "confidence": number}

If a field is not clearly legible in the photo, use null for it instead of
guessing.
PROMPT;

/**
 * Sends a card photo to the configured vision-capable chat completions API
 * and returns the parsed extraction fields.
 *
 * @param string $imageDataUri A "data:image/...;base64,..." URI.
 * @return array{ok: bool, data: array, error: ?string}
 */
function recognize_card_image(array $config, string $imageDataUri): array
{
    $apiKey = $config['vision_api']['api_key'] ?? '';
    if ($apiKey === '') {
        return [
            'ok' => false,
            'data' => [],
            'error' => 'Die Foto-Erkennung ist nicht konfiguriert (VISION_API_KEY fehlt).',
        ];
    }

    $url = rtrim($config['vision_api']['base_url'], '/') . '/chat/completions';

    $payload = [
        'model' => $config['vision_api']['model'],
        'temperature' => 0,
        'max_tokens' => 300,
        'messages' => [
            ['role' => 'system', 'content' => VISION_RECOGNITION_PROMPT],
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => 'Extract the fields from this Pokémon card photo.'],
                    ['type' => 'image_url', 'image_url' => ['url' => $imageDataUri]],
                ],
            ],
        ],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $responseBody = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($responseBody === false) {
        return ['ok' => false, 'data' => [], 'error' => "Vision-API nicht erreichbar: $curlError"];
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'data' => [], 'error' => 'Ungültige Antwort der Vision-API.'];
    }

    if ($statusCode >= 400) {
        return ['ok' => false, 'data' => [], 'error' => $decoded['error']['message'] ?? 'Fehler bei der Vision-API.'];
    }

    $content = $decoded['choices'][0]['message']['content'] ?? null;
    if (!is_string($content)) {
        return ['ok' => false, 'data' => [], 'error' => 'Leere Antwort der Vision-API.'];
    }

    $extracted = parse_vision_json_response($content);
    if ($extracted === null) {
        return ['ok' => false, 'data' => [], 'error' => 'Antwort der Vision-API konnte nicht ausgewertet werden.'];
    }

    return ['ok' => true, 'data' => $extracted, 'error' => null];
}

/**
 * Vision models sometimes wrap JSON in markdown code fences despite
 * instructions not to. Strip those before decoding, and normalize to the
 * expected field shape.
 */
function parse_vision_json_response(string $content): ?array
{
    $trimmed = trim($content);
    $trimmed = preg_replace('/^```(json)?/i', '', $trimmed);
    $trimmed = preg_replace('/```$/', '', $trimmed);
    $trimmed = trim($trimmed);

    $decoded = json_decode($trimmed, true);
    if (!is_array($decoded)) {
        return null;
    }

    return [
        'card_name' => is_string($decoded['card_name'] ?? null) ? trim($decoded['card_name']) : null,
        'card_number' => is_string($decoded['card_number'] ?? null) ? trim($decoded['card_number']) : null,
        'set_total' => is_string($decoded['set_total'] ?? null) ? trim($decoded['set_total']) : null,
        'language_guess' => is_string($decoded['language_guess'] ?? null) ? trim($decoded['language_guess']) : null,
        'confidence' => is_numeric($decoded['confidence'] ?? null) ? (float) $decoded['confidence'] : 0.0,
    ];
}
