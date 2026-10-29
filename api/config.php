<?php
// PokéVault API configuration.
// On Netcup/Plesk, set these as real environment variables in the Plesk panel
// (Hosting Settings -> PHP Settings -> environment / .htaccess SetEnv) rather
// than committing real secrets. A local .env file is also supported for
// convenience during development.

function pokevault_load_dotenv(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

pokevault_load_dotenv(__DIR__ . '/../.env');

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

return [
    'db' => [
        'host' => env('DB_HOST', 'localhost'),
        'name' => env('DB_NAME', 'pokevault'),
        'user' => env('DB_USER', 'pokevault'),
        'pass' => env('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],
    'jwt' => [
        'secret' => env('JWT_SECRET', 'change-this-secret-in-production'),
        'ttl' => (int) env('JWT_TTL', '604800'), // 7 days
    ],
    'tcg_api' => [
        'base_url' => env('TCG_API_BASE_URL', 'https://api.pokemontcg.io/v2'),
        'api_key' => env('TCG_API_KEY', ''),
    ],
    'vision_api' => [
        // OpenAI-compatible chat completions endpoint with vision input.
        // Used for the Phase 3 camera scan / OCR feature.
        'base_url' => env('VISION_API_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('VISION_API_KEY', ''),
        'model' => env('VISION_API_MODEL', 'gpt-4o-mini'),
    ],
    'cors' => [
        'allowed_origin' => env('CORS_ALLOWED_ORIGIN', '*'),
    ],
];
