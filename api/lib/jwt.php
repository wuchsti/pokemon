<?php

// Minimal dependency-free HS256 JWT helper. Good enough for first-party
// session tokens between the PokéVault frontend and its own API; avoids
// requiring Composer on shared hosting.

function jwt_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function jwt_base64url_decode(string $data): string
{
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_encode(array $payload, string $secret, int $ttlSeconds): string
{
    $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    $payload['iat'] = time();
    $payload['exp'] = time() + $ttlSeconds;

    $segments = [
        jwt_base64url_encode(json_encode($header)),
        jwt_base64url_encode(json_encode($payload)),
    ];

    $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
    $segments[] = jwt_base64url_encode($signature);

    return implode('.', $segments);
}

/**
 * Returns the decoded payload, or null if the token is missing, malformed,
 * has an invalid signature, or has expired.
 */
function jwt_decode(string $token, string $secret): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    [$headerB64, $payloadB64, $signatureB64] = $parts;

    $expectedSignature = hash_hmac('sha256', "$headerB64.$payloadB64", $secret, true);
    $actualSignature = jwt_base64url_decode($signatureB64);

    if (!hash_equals($expectedSignature, $actualSignature)) {
        return null;
    }

    $payload = json_decode(jwt_base64url_decode($payloadB64), true);
    if (!is_array($payload)) {
        return null;
    }

    if (isset($payload['exp']) && time() >= $payload['exp']) {
        return null;
    }

    return $payload;
}
