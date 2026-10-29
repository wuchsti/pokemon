-- Server-seitiger Cache für selten wechselnde externe API-Antworten
-- (aktuell: die Liste der Pokémon-TCG-Sets), damit nicht jeder Seitenaufruf
-- jedes Nutzers die externe API neu anfragt - und als Fallback, wenn die
-- API gerade down oder überlastet ist.
-- Nur für bereits bestehende Installationen nötig - database/schema.sql
-- enthält das für neue Installationen schon direkt.

CREATE TABLE IF NOT EXISTS api_cache (
    cache_key VARCHAR(100) PRIMARY KEY,
    cache_value LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
