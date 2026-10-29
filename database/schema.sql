-- PokéVault Database Schema (MariaDB / MySQL)
-- Run this once on the Netcup / Plesk MariaDB database.

SET NAMES utf8mb4;

-- 1. Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    public_binder_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    is_admin BOOLEAN NOT NULL DEFAULT FALSE,
    is_blocked BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. User Cards Collection (Sammlung, Trade-Binder & Grading)
CREATE TABLE IF NOT EXISTS user_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_api_id VARCHAR(100) NOT NULL,
    set_id VARCHAR(100) NOT NULL,
    card_number VARCHAR(20) NOT NULL,
    card_name VARCHAR(150) NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    storage_type ENUM('vault', 'trade_binder') DEFAULT 'vault',
    card_condition ENUM('Mint', 'Near Mint', 'Excellent', 'Good', 'Light Played', 'Played', 'Poor') DEFAULT 'Near Mint',
    language VARCHAR(10) DEFAULT 'DE',
    is_graded BOOLEAN DEFAULT FALSE,
    grading_company VARCHAR(50) NULL,
    grading_score DECIMAL(3,1) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_cards_user (user_id),
    INDEX idx_user_cards_set (set_id),
    INDEX idx_user_cards_api_id (card_api_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Price History Tracker
CREATE TABLE IF NOT EXISTS price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_api_id VARCHAR(100) NOT NULL,
    price_eur DECIMAL(10,2) NOT NULL,
    recorded_at DATE NOT NULL,
    UNIQUE KEY unique_card_date (card_api_id, recorded_at),
    INDEX idx_price_history_card (card_api_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Wishlist (fehlende Karten, die ein Nutzer sich für ein Set gemerkt hat)
CREATE TABLE IF NOT EXISTS wishlist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_api_id VARCHAR(100) NOT NULL,
    set_id VARCHAR(100) NOT NULL,
    card_number VARCHAR(20) NOT NULL,
    card_name VARCHAR(150) NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_card (user_id, card_api_id),
    INDEX idx_wishlist_user (user_id),
    INDEX idx_wishlist_set (set_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Trades (Tausch-Thread zwischen zwei Nutzern, per Nickname - nie per E-Mail)
CREATE TABLE IF NOT EXISTS trades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_a_id INT NOT NULL,
    user_b_id INT NOT NULL,
    status ENUM('open', 'completed', 'cancelled') NOT NULL DEFAULT 'open',
    user_a_confirmed BOOLEAN NOT NULL DEFAULT FALSE,
    user_b_confirmed BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_a_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_b_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_trades_user_a (user_a_id),
    INDEX idx_trades_user_b (user_b_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Trade-Nachrichten (Absprache zu Ablauf/Versand innerhalb eines Tauschs)
CREATE TABLE IF NOT EXISTS trade_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trade_id INT NOT NULL,
    sender_user_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_trade_messages_trade (trade_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Scan-Log (eine Zeile pro Foto-Scan-Versuch, für Admin-Statistiken)
CREATE TABLE IF NOT EXISTS scan_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_name VARCHAR(150) NULL,
    matched BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_scan_log_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Site Content (von Admins editierbare Texte, z.B. Impressum/Datenschutz)
CREATE TABLE IF NOT EXISTS site_content (
    content_key VARCHAR(50) PRIMARY KEY,
    content_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Platzhalter-Startinhalt, über den Admin-Bereich (/admin/content) bearbeitbar.
-- INSERT IGNORE: wird beim erneuten Einspielen von schema.sql nicht überschrieben.
INSERT IGNORE INTO site_content (content_key, content_value) VALUES
('impressum', '## Angaben gemäß §5 TMG/DDG

[Vor- und Nachname bzw. Firmenname]
[Straße und Hausnummer]
[PLZ und Ort]
[Land]

## Kontakt

Telefon: [Telefonnummer]
E-Mail: [E-Mail-Adresse]

## Umsatzsteuer-ID

Sofern vorhanden: Umsatzsteuer-Identifikationsnummer gemäß §27a Umsatzsteuergesetz: [USt-IdNr.]

## Verantwortlich für den Inhalt nach §18 Abs. 2 MStV

[Name], [Anschrift wie oben]

## Haftungshinweis

Trotz sorgfältiger inhaltlicher Kontrolle übernehmen wir keine Haftung für die Inhalte externer Links. Für den Inhalt der verlinkten Seiten sind ausschließlich deren Betreiber verantwortlich.

## Markenhinweis

PokéVault ist ein inoffizielles Fan-Projekt und steht in keiner Verbindung zu Nintendo, The Pokémon Company oder Game Freak. Alle genannten Marken- und Produktnamen sind Eigentum ihrer jeweiligen Rechteinhaber.'),
('datenschutz', '## 1. Verantwortlicher

Verantwortlich für die Datenverarbeitung auf dieser Website ist die auf der Impressum-Seite dieser Anwendung genannte Person/Stelle.

## 2. Welche Daten wir verarbeiten

Konto: Benutzername, E-Mail-Adresse und ein gehashtes Passwort (nie im Klartext gespeichert).

Sammlungsdaten: von dir erfasste Karten (Set, Nummer, Zustand, Sprache, Grading) sowie optional eine Wunschliste.

Foto-Erkennung: ein von dir aufgenommenes oder hochgeladenes Kartenfoto wird zur Texterkennung an den unten genannten Vision-API-Anbieter übertragen und von uns nicht dauerhaft gespeichert.

Öffentlicher Tausch-Binder: wenn du diese Option in den Einstellungen aktivierst, werden Kartenname, Nummer, Zustand, Sprache und Grading deines Tausch-Binders öffentlich unter einem Link mit deinem Nickname abrufbar. Deine E-Mail-Adresse und dein Vault bleiben davon unberührt und niemals öffentlich.

Tausch-Nachrichten: Nachrichten, die du in einem Tausch-Chat schreibst, sind für den jeweiligen Tauschpartner einsehbar.

## 3. Zweck der Verarbeitung

Die Daten werden ausschließlich zur Bereitstellung der PokéVault-Funktionen verarbeitet: Kontoverwaltung, Sammlungsverwaltung, Wertermittlung, Karten-Erkennung, Tauschpartner-Vermittlung und Tausch-Kommunikation.

## 4. Eingesetzte externe Dienste

Pokémon TCG API (pokemontcg.io): liefert Kartenstammdaten, Bilder und Marktpreise. Es werden dabei keine personenbezogenen Daten von dir übermittelt, nur Suchbegriffe.

Vision-API-Anbieter (aktuell konfiguriert: [Name des Anbieters, z. B. OpenAI], Sitz: [Land]): erhält das von dir zur Erkennung übermittelte Kartenfoto. Es gilt die Datenschutzerklärung des jeweiligen Anbieters.

## 5. Speicherdauer

Konto- und Sammlungsdaten werden gespeichert, bis du dein Konto löschen lässt (siehe Kontakt im Impressum). Preis-Historien-Einträge werden fortlaufend ergänzt und nicht automatisch gelöscht.

## 6. Cookies & lokale Speicherung

PokéVault setzt keine Tracking- oder Werbe-Cookies. Dein Anmelde-Token wird im localStorage deines Browsers gespeichert, damit du angemeldet bleibst, und verlässt dein Gerät nur bei API-Anfragen an diese Anwendung selbst.

## 7. Deine Rechte

Du hast nach der DSGVO insbesondere ein Recht auf Auskunft (Art. 15), Berichtigung (Art. 16), Löschung (Art. 17), Einschränkung der Verarbeitung (Art. 18), Datenübertragbarkeit (Art. 20) und Widerspruch (Art. 21) bezüglich deiner personenbezogenen Daten. Wende dich dazu an die im Impressum genannten Kontaktdaten.');

-- 9. Error Log (technische Fehler externer APIs - für Admins, nie für Nutzer sichtbar)
CREATE TABLE IF NOT EXISTS error_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    context VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_error_log_source (source),
    INDEX idx_error_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. API Cache (generischer Key-Value-Cache für selten wechselnde externe
-- API-Antworten, z.B. die TCG-Set-Liste - vermeidet, dass jeder Seitenaufruf
-- jedes Nutzers die externe API erneut anfragt, und dient als Fallback,
-- wenn die externe API gerade down ist)
CREATE TABLE IF NOT EXISTS api_cache (
    cache_key VARCHAR(100) PRIMARY KEY,
    cache_value LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
