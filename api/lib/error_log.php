<?php

require_once __DIR__ . '/db.php';

/**
 * Records a technical failure (external API errors, etc.) for admins to
 * review under /admin/logs. Never shown to regular users directly - callers
 * should surface a generic, friendly message to the user instead and log
 * the real details here.
 *
 * Deliberately swallows its own failures: logging must never be the reason
 * a request breaks (e.g. if this table doesn't exist yet on an
 * installation that hasn't run the 003 migration).
 */
function log_api_error(array $config, string $source, string $message, ?string $context = null): void
{
    try {
        $pdo = get_db($config);
        $stmt = $pdo->prepare(
            'INSERT INTO error_log (source, message, context) VALUES (:source, :message, :context)'
        );
        $stmt->execute([
            'source' => $source,
            'message' => mb_substr($message, 0, 2000),
            'context' => $context !== null ? mb_substr($context, 0, 255) : null,
        ]);
    } catch (Throwable $e) {
        // Swallow - see docblock.
    }
}
