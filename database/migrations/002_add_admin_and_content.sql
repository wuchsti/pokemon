-- Admin-Bereich: Nutzerverwaltung (sperren/freischalten) + editierbare
-- Impressum/Datenschutz-Inhalte + Scan-Statistik.
-- Nur für bereits bestehende Installationen nötig - database/schema.sql
-- enthält das für neue Installationen schon direkt.

ALTER TABLE users
    ADD COLUMN is_admin BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN is_blocked BOOLEAN NOT NULL DEFAULT FALSE;

CREATE TABLE IF NOT EXISTS scan_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_name VARCHAR(150) NULL,
    matched BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_scan_log_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_content (
    content_key VARCHAR(50) PRIMARY KEY,
    content_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Platzhalter-Startinhalt, über den Admin-Bereich (/admin/content) bearbeitbar.
-- INSERT IGNORE: schreibt nichts, falls schon vorhanden.
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

-- Danach einen Admin bestimmen (siehe README, Abschnitt "Ersten Admin
-- bestimmen"), z. B.:
--   UPDATE users SET is_admin = 1 WHERE username = 'DEIN_BENUTZERNAME';
