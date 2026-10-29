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
$username = trim((string) ($body['username'] ?? ''));
$email = trim((string) ($body['email'] ?? ''));
$password = (string) ($body['password'] ?? '');

if ($username === '' || $email === '' || $password === '') {
    json_error('Benutzername, E-Mail und Passwort sind erforderlich.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('Ungültige E-Mail-Adresse.');
}

if (strlen($password) < 8) {
    json_error('Das Passwort muss mindestens 8 Zeichen lang sein.');
}

if (!preg_match('/^[A-Za-z0-9_\-]{3,50}$/', $username)) {
    json_error('Benutzername darf nur Buchstaben, Zahlen, "_" und "-" enthalten (3-50 Zeichen).');
}

try {
    $pdo = get_db($config);

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
    $stmt->execute(['username' => $username, 'email' => $email]);
    if ($stmt->fetch()) {
        json_error('Benutzername oder E-Mail wird bereits verwendet.', 409);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)'
    );
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'password_hash' => $passwordHash,
    ]);

    $userId = (int) $pdo->lastInsertId();

    $token = jwt_encode(['sub' => $userId, 'username' => $username], $config['jwt']['secret'], $config['jwt']['ttl']);

    json_response([
        'token' => $token,
        'user' => ['id' => $userId, 'username' => $username, 'email' => $email, 'is_admin' => false],
    ], 201);
} catch (PDOException $e) {
    json_error('Registrierung fehlgeschlagen.', 500);
}
