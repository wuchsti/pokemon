<?php

// Generischer Key-Value-Cache für selten wechselnde externe API-Antworten
// (z.B. die Liste der Pokémon-TCG-Sets). Vermeidet, dass jeder Seitenaufruf
// jedes Nutzers die externe API neu anfragt, und dient als Fallback, wenn
// die API gerade nicht erreichbar ist.

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/error_log.php';

/**
 * Liefert den gecachten Wert zurück, wenn er noch frisch genug ist
 * (jünger als $maxAgeSeconds), sonst null.
 */
function cache_get(PDO $pdo, string $key, int $maxAgeSeconds): ?array
{
    $stmt = $pdo->prepare(
        'SELECT cache_value, updated_at FROM api_cache WHERE cache_key = :key LIMIT 1'
    );
    $stmt->execute(['key' => $key]);
    $row = $stmt->fetch();

    if ($row === false) {
        return null;
    }

    $updatedAt = strtotime((string) $row['updated_at']);
    if ($updatedAt === false || (time() - $updatedAt) > $maxAgeSeconds) {
        return null;
    }

    $decoded = json_decode((string) $row['cache_value'], true);
    return is_array($decoded) ? $decoded : null;
}

/**
 * Liefert den gecachten Wert unabhängig von seinem Alter zurück (oder
 * null, wenn noch nie etwas gecacht wurde). Gedacht als letzter Fallback,
 * wenn die externe API gerade nicht erreichbar ist.
 */
function cache_get_stale(PDO $pdo, string $key): ?array
{
    $stmt = $pdo->prepare('SELECT cache_value FROM api_cache WHERE cache_key = :key LIMIT 1');
    $stmt->execute(['key' => $key]);
    $row = $stmt->fetch();

    if ($row === false) {
        return null;
    }

    $decoded = json_decode((string) $row['cache_value'], true);
    return is_array($decoded) ? $decoded : null;
}

function cache_set(PDO $pdo, string $key, array $value): void
{
    $json = json_encode($value);
    if ($json === false) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO api_cache (cache_key, cache_value) VALUES (:key, :value)
         ON DUPLICATE KEY UPDATE cache_value = :value2'
    );
    $stmt->execute(['key' => $key, 'value' => $json, 'value2' => $json]);
}

/**
 * Standard-Ablauf für jeden gecachten externen API-Abruf:
 * 1. frischen Cache-Treffer zurückgeben, falls vorhanden
 * 2. sonst live abrufen (über $fetch) und bei Erfolg cachen
 * 3. bei Fehlschlag: Fehler loggen, veralteten Cache als Fallback probieren
 * 4. sonst generische Fehlermeldung an den Client (beendet die Anfrage)
 *
 * $fetch muss array{ok: bool, status: int, data: array, error: ?string}
 * liefern (wie tcg_api_request()). Bei Erfolg wird genau $result['data']
 * gecacht und zurückgegeben.
 */
function tcg_cached_fetch(PDO $pdo, array $config, string $cacheKey, int $ttlSeconds, callable $fetch, string $errorContext): array
{
    $cached = cache_get($pdo, $cacheKey, $ttlSeconds);
    if ($cached !== null) {
        return $cached;
    }

    $result = $fetch();

    if ($result['ok']) {
        cache_set($pdo, $cacheKey, $result['data']);
        return $result['data'];
    }

    log_api_error($config, 'tcg_api', $result['error'] ?? 'Unbekannter Fehler', $errorContext . ' (HTTP ' . $result['status'] . ')');

    $stale = cache_get_stale($pdo, $cacheKey);
    if ($stale !== null) {
        return $stale;
    }

    json_error(
        'Pokémon-Kartendaten sind gerade nicht erreichbar. Bitte versuch es in ein paar Minuten erneut.',
        $result['status']
    );
}
