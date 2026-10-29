<?php

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/jwt.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Methode nicht erlaubt.', 405);
}

$body = read_json_body();
$identifier = trim((string) ($body['username'] ?? $body['email'] ?? ''));
$password = (string) ($body['password'] ?? '');

if ($identifier === '' || $password === '') {
    json_error('Benutzername/E-Mail und Passwort sind erforderlich.');
}

$pdo = get_db($config);

$stmt = $pdo->prepare('SELECT id, username, email, password_hash FROM users WHERE username = :identifier OR email = :identifier LIMIT 1');
$stmt->execute(['identifier' => $identifier]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_error('Ungültige Anmeldedaten.', 401);
}

$token = jwt_encode(
    ['sub' => (int) $user['id'], 'username' => $user['username']],
    $config['jwt']['secret'],
    $config['jwt']['ttl']
);

json_response([
    'token' => $token,
    'user' => ['id' => (int) $user['id'], 'username' => $user['username'], 'email' => $user['email']],
]);
