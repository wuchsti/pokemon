-- Technisches Fehler-Log für externe APIs (TCG/Vision), sichtbar unter
-- /admin/logs. Nutzer sehen nur noch eine generische Meldung, keine
-- rohen API-Fehler mehr - siehe api/lib/error_log.php.
-- Nur für bereits bestehende Installationen nötig - database/schema.sql
-- enthält das für neue Installationen schon direkt.

CREATE TABLE IF NOT EXISTS error_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    context VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_error_log_source (source),
    INDEX idx_error_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
