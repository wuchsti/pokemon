# PokéVault

Webanwendung zur Verwaltung, Bewertung und Foto-Erkennung von Pokémon-Sammelkarten.
Gebaut für ein Netcup Webhosting-Paket (Plesk, PHP 8.x, MariaDB).

Dieses Repository enthält aktuell alle vier funktionalen Phasen:
**Phase 1 (MVP Core & Authentication)**,
**Phase 2 (Wertermittlung, Preis-Historie & Profil-Dashboard)**,
**Phase 3 (Foto-Erkennung / Scanning)** und
**Phase 4 (Social Features & Export)**:
Benutzer-Registrierung/Login, Pokémon-TCG-Kartensuche, die Verwaltung der
eigenen Sammlung (Vault) und des Tausch-Binders inkl. Zustand, Sprache und
Grading, ein täglicher Cardmarket-Preis-Sync, ein Dashboard mit Gesamtwert
(getrennt nach Vault/Tauschbinder), Top-5-Schätzen, einem Wertverlaufs-Chart
und Tagesgewinnern/-verlierern, eine Wunschlisten-Funktion für fehlende
Karten, ein Kamera-/Foto-Scan-Flow zur schnellen Karten-Erfassung per
Vision-API, ein öffentlicher Tausch-Binder-Link mit Tauschpartner-Matchmaking,
ein Tausch-Chat mit beidseitiger Erfolgsbestätigung, sowie CSV- und
Druck-/PDF-Export. Offen ist nur noch das in der README dokumentierte
**Backlog** (Startseite, FAQ, Impressum, Datenschutz).

## Architektur

- **Frontend**: Vue.js 3 + Vite, Pinia (State), Vue Router. Baut nach
  `frontend/dist`, das als `httpdocs`-Inhalt deployed wird.
- **Backend**: Natives PHP 8.2+, PDO mit Prepared Statements, kein Framework
  (für einfaches Netcup/Plesk-Hosting ohne Composer-Zwang). Dateien unter
  `/api/` sind direkt per URL aufrufbare Endpunkte.
- **Datenbank**: MariaDB/MySQL, Schema in `database/schema.sql`.
- **Auth**: Eigenes, abhängigkeitsfreies HS256-JWT (`api/lib/jwt.php`),
  übertragen per `Authorization: Bearer <token>`-Header.
- **Externe API**: [pokemontcg.io](https://pokemontcg.io/) für Kartendaten,
  Bilder und Cardmarket-Preise.

## Projektstruktur

```
api/
  config.php         # Zentrale Konfiguration (liest ENV / .env)
  lib/                # jwt.php, db.php, response.php, auth_middleware.php,
                      # tcg_client.php, price_stats.php, vision_client.php
  auth/               # register.php, login.php, me.php, settings.php
  cards/              # list.php, add.php, update.php, delete.php (user_cards CRUD)
  tcg/                # search.php, sets.php, set_cards.php (Proxy zu pokemontcg.io)
  stats/              # dashboard.php (Gesamtwert, Top 5, Verlauf, Tagesgewinner/-verlierer)
  wishlist/           # list.php, add.php, remove.php
  scan/               # recognize.php (Vision-API-OCR + Auto-Match)
  public/             # binder.php (öffentlicher Tausch-Binder, keine Auth)
  matchmaking/        # find_traders.php (Wunschliste vs. öffentliche Tausch-Binder)
  export/             # cards.php (JSON), csv.php (CSV-Download)
  trades/             # create.php, list.php, get.php, message.php, confirm.php, cancel.php
database/
  schema.sql          # users, user_cards, price_history, wishlist_items, trades, trade_messages
  migrations/         # ALTER-Skripte für bereits bestehende Installationen
frontend/
  src/
    views/            # Login, Register, Dashboard, Collection, CardSearch, Wishlist,
                      # Scan, Settings, Export, PrintExport, PublicBinder, Trades, TradeDetail
    components/       # Navbar, PokeballLoader, SetProgress, CardModal, ValueChart, CardScanner
    utils/image.js     # Foto-Resize/Komprimierung (Kamera + Upload) vor dem Hochladen
    stores/auth.js     # Pinia Auth-Store
    api/client.js      # Fetch-Wrapper für /api/*
cron/
  sync_prices.php     # Täglicher Cardmarket-Preis-Sync
```

## Lokale Entwicklung

### Backend

1. MariaDB-Datenbank anlegen und `database/schema.sql` einspielen.
2. `.env.example` nach `.env` kopieren (im Projekt-Root) und Werte setzen
   (DB-Zugang, `JWT_SECRET`, optional `TCG_API_KEY`).
3. Mit PHPs eingebautem Server starten:
   ```bash
   php -S localhost:8080 -t .
   ```
   Die Endpunkte sind dann z. B. unter `http://localhost:8080/api/auth/login.php` erreichbar.

### Frontend

```bash
cd frontend
npm install
npm run dev
```

Vite proxyt `/api/*`-Requests im Dev-Modus auf `http://localhost:8080`
(siehe `frontend/vite.config.js`).

## Deployment auf Netcup/Plesk

1. `cd frontend && npm run build` → Inhalt von `frontend/dist/` in `httpdocs/` kopieren.
2. Ordner `api/` ebenfalls nach `httpdocs/api/` kopieren.
3. In Plesk: MariaDB-Datenbank anlegen, `database/schema.sql` importieren.
   Bei einer **bereits bestehenden** Installation zusätzlich die Dateien in
   `database/migrations/` in aufsteigender Reihenfolge einspielen (aktuell:
   `001_add_public_binder_enabled.sql`) - `schema.sql` legt neue Spalten an
   bestehenden Tabellen nicht nachträglich an.
4. In Plesk unter "PHP-Einstellungen" bzw. per `.env`-Datei neben `api/config.php`
   die Variablen aus `.env.example` setzen (insbesondere `JWT_SECRET` auf einen
   langen, zufälligen Wert ändern!).
5. `.htaccess` in `httpdocs/` (aus `frontend/public/.htaccess`, wird beim Build
   automatisch mit kopiert) sorgt für das SPA-Routing; `api/.htaccess` schützt
   interne Dateien.
6. In Plesk unter "Geplante Aufgaben" (Task Scheduler) einen täglichen Cronjob
   für `cron/sync_prices.php` einrichten, z. B.:
   ```bash
   php /var/www/vhosts/<domain>/httpdocs/cron/sync_prices.php
   ```
   Das Skript fragt für jede in `user_cards` vorhandene Karte den aktuellen
   Cardmarket-Preis ab und schreibt einen Eintrag pro Tag in `price_history`.
   Mehrfaches Ausführen am selben Tag aktualisiert nur den heutigen Eintrag.

## Design

Comic/Manga-Pokémon-Ästhetik: Pokéball-Rot (`#FF0000`/`#CC0000`),
Pikachu-Gelb (`#FFDE00`), Pokémon-Blau (`#3B4CCA`), abgerundete Ecken,
fette Comic-Outlines, Drop-Shadows, Fredoka/Nunito-Schriftarten und ein
rotierender Pokéball als Lade-Animation. Siehe `frontend/src/style.css`.

## Phase 2 im Detail

- **Preis-Sync**: `cron/sync_prices.php` holt pro Karte den besten verfügbaren
  Cardmarket-EUR-Preis (`trendPrice` → `averageSellPrice` → Tagesdurchschnitte
  als Fallback) und schreibt ihn in `price_history`.
- **Dashboard-Statistiken** (`api/stats/dashboard.php`, `api/lib/price_stats.php`):
  - Gesamtwert getrennt nach Vault und Tauschbinder.
  - Top-5-Schätze: die wertvollsten einzelnen Karteninstanzen.
  - 30-Tage-Wertverlauf (mit Forward-Fill für Tage ohne neuen Sync), als
    Comic-gestylter Chart.js-Chart mit Legende, Crosshair-Tooltip, Endwert-
    Beschriftung und Tabellen-Ansicht als Fallback.
  - **Tagesgewinner/-verlierer**: vergleicht die letzten zwei Preis-Syncs
    jeder Karte und hebt die größte prozentuale Bewegung nach oben/unten
    hervor – wie bei einem Krypto-/Aktien-Portfolio.
- **Wunschliste**: In der Sammlung kann pro Set "Fehlende Karten anzeigen"
  geöffnet werden (lädt alle Karten des Sets via `api/tcg/set_cards.php` und
  vergleicht sie mit den eigenen Kartennummern). Von dort lassen sich fehlende
  Karten per Klick zur Wunschliste hinzufügen (`api/wishlist/*`, Tabelle
  `wishlist_items`), einsehbar unter `/wishlist`.

## Phase 3 im Detail

- **Kamera & Upload**: `/scan` (CardScanner.vue) nutzt `getUserMedia` für eine
  Live-Kamera-Vorschau (Rückkamera bevorzugt) mit Foto-Aufnahme-Button, plus
  einen Datei-Upload-Fallback für Geräte ohne Kamera-Zugriff oder ohne HTTPS
  (Kamera-API erfordert einen sicheren Kontext). Fotos werden vor dem Upload
  clientseitig auf max. 1024px / JPEG q=0.82 verkleinert (`utils/image.js`).
- **Vision-API-OCR**: `api/scan/recognize.php` schickt das Foto an einen
  konfigurierbaren, OpenAI-kompatiblen Vision-Endpunkt (`api/lib/vision_client.php`)
  mit einem Prompt, der Kartenname, Kartennummer (z. B. "12" aus "12/135"),
  Set-Gesamtzahl, Sprachvermutung und eine Konfidenz zurückgibt. Es wurde
  bewusst eine Vision-API statt Tesseract.js gewählt, da kleine/glänzende
  Kartennummern mit klassischer OCR erfahrungsgemäß deutlich schlechter
  erkannt werden.
- **Auto-Match & Fast-Add**: Mit den erkannten Feldern sucht das Backend
  automatisch passende Karten über die Pokémon-TCG-API (gestaffelt:
  Nummer+Name → nur Nummer → nur Name) und liefert bis zu 10 Kandidaten.
  Die Vorschläge erscheinen als klickbare Kachel-Liste; ein Klick öffnet den
  bestehenden Speichern-Dialog (CardModal: Ordner, Zustand, Sprache, Grading),
  genau wie bei der manuellen Suche. Bei niedriger Konfidenz oder keinem
  Treffer wird auf die manuelle Kartensuche verwiesen.
- **Konfiguration**: `VISION_API_BASE_URL` / `VISION_API_KEY` / `VISION_API_MODEL`
  in `.env` (Standard: OpenAI `gpt-4o-mini`, aber jeder OpenAI-kompatible
  Chat-Completions-Endpunkt mit Bild-Input funktioniert).

## Phase 4 im Detail

- **Öffentlicher Sammler-Link**: Unter `/settings` kann jeder Nutzer seinen
  Tausch-Binder (nicht den Vault) über einen Schalter öffentlich freigeben
  (`users.public_binder_enabled`, Opt-in, Standard: aus). Der Link
  `/user/:username/binder` (`api/public/binder.php`, ohne Login erreichbar)
  zeigt nur Kartenname, Nummer, Zustand, Sprache und Grading - niemals
  E-Mail-Adresse, Vault-Inhalt oder Kartenwerte.
- **Tauschpartner-Matchmaking**: Auf `/wishlist` vergleicht "Tauschpartner
  finden" (`api/matchmaking/find_traders.php`) die eigene Wunschliste gegen
  die öffentlichen Tausch-Binder aller Nutzer und zeigt, wer welche gesuchten
  Karten bereits hat - sortiert nach Trefferzahl, mit direktem Link zum
  jeweiligen Tausch-Binder. Bewusst nur in diese Richtung (meine Wunschliste
  vs. deren öffentlicher Binder): die Wunschlisten anderer Nutzer bleiben
  privat, da dafür kein Opt-in existiert.
- **Tausch-Chat & Bestätigung** (`/trades`, Tabellen `trades` + `trade_messages`):
  Aus der Tauschpartner-Suche oder einem öffentlichen Tausch-Binder heraus
  (oder direkt per Nickname auf `/trades`) lässt sich ein Tausch-Thread
  starten. Beide Seiten sehen sich dort **ausschließlich beim Nickname**
  (nie per E-Mail), können Nachrichten zu Ablauf/Versand austauschen
  (`api/trades/message.php`, Polling alle 6s statt WebSockets) und den
  Tausch einzeln bestätigen (`api/trades/confirm.php`); erst wenn **beide**
  bestätigt haben, wechselt der Status zu "completed". Ein offener Tausch
  kann jederzeit abgebrochen werden (`api/trades/cancel.php`), ein
  abgeschlossener nicht mehr.
- **Export** (`/export`):
  - **CSV** (`api/export/csv.php`): komplette Sammlung (Vault + Tausch-Binder)
    inkl. Preis, Zustand, Sprache und Grading, Semikolon-getrennt für
    Excel/Sheets, mit UTF-8-BOM für korrekte Umlaute.
  - **Druckansicht/PDF** (`/export/print`, `PrintExport.vue`): eine bewusst
    schlichte, dokumentartige Inventarliste (getrennt nach Vault/Tausch-Binder,
    mit Zwischen- und Gesamtsumme) ohne Navbar, mit eigenem Print-Stylesheet.
    Es wird keine PHP-PDF-Bibliothek vendort - die Browser-eigene
    Druckfunktion ("Als PDF speichern") erzeugt das PDF, passend zum
    Composer-freien Hosting-Ansatz dieses Projekts.

## Backlog (noch nicht terminiert)

Vom Projektinhaber gewünscht, aber bewusst zurückgestellt bis nach den
funktionalen Phasen 1–4:

- **Startseite / Landingpage**: Eine öffentliche Seite vor dem Login, die auf
  einen Blick zeigt, wofür PokéVault gedacht ist – mit Beispielbildern der
  App (Dashboard, Scan-Flow, Sammlung) und einer klaren Mehrwert-Botschaft
  ("Warum PokéVault?"). Aktuell landet jeder direkt auf `/login`.
- **FAQ-Seite**: Häufige Fragen (Datenquelle der Preise, Unterstützung für
  gegradete Karten, Datenschutz bei der Foto-Erkennung, etc.).
- **Impressum**: in Deutschland für so eine Webanwendung Pflicht, sobald sie
  nicht rein privat/unter Freunden betrieben wird (§5 TMG/DDG). Muss Anbieter-
  Kontaktdaten enthalten.
- **Datenschutzerklärung**: insbesondere relevant, weil die App Kamera-/
  Fotodaten (Phase 3) an einen externen Vision-API-Anbieter sendet,
  Nutzerkonten mit E-Mail-Adressen führt, und seit Phase 4 Nutzer ihren
  Tausch-Binder öffentlich zugänglich machen können (Opt-in, aber
  dokumentationspflichtig) – DSGVO-Informationspflichten.

Wenn es konkret losgehen soll: einfach sagen "bau jetzt die Startseite/FAQ/
Impressum/Datenschutz" und ich lege los.
