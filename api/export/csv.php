<?php

// GET /api/export/csv.php
// Streams the user's full collection (vault + trade binder) as a
// semicolon-separated CSV (Excel-DE friendly), including the latest known
// Cardmarket price - useful for spreadsheets or insurance documentation.

require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_middleware.php';
require_once __DIR__ . '/../lib/price_stats.php';

$config = require __DIR__ . '/../config.php';
send_cors_headers($config);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Methode nicht erlaubt.', 405);
}

$userId = require_auth_user_id($config);
$pdo = get_db($config);
$cards = get_user_cards_with_latest_price($pdo, $userId);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="pokevault-sammlung-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
// UTF-8 BOM so Excel on Windows shows umlauts correctly.
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
    'Ordner', 'Set', 'Kartennummer', 'Name', 'Zustand', 'Sprache',
    'Gegradet', 'Grading-Unternehmen', 'Grading-Score', 'Preis (EUR)',
], ';');

foreach ($cards as $card) {
    fputcsv($out, [
        $card['storage_type'] === 'vault' ? 'Vault' : 'Tausch-Binder',
        $card['set_id'],
        $card['card_number'],
        $card['card_name'],
        $card['card_condition'],
        $card['language'],
        $card['is_graded'] ? 'Ja' : 'Nein',
        $card['grading_company'] ?? '',
        $card['grading_score'] ?? '',
        $card['latest_price'] ?? '',
    ], ';');
}

fclose($out);
