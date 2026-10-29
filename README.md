# PokéVault

Webanwendung zur Verwaltung, Bewertung und Foto-Erkennung von Pokémon-Sammelkarten.
Gebaut für ein Netcup Webhosting-Paket (Plesk, PHP 8.x, MariaDB).

Dieses Repository enthält alle vier funktionalen Phasen:
**Phase 1 (MVP Core & Authentication)**,
**Phase 2 (Wertermittlung, Preis-Historie & Profil-Dashboard)**,
**Phase 3 (Foto-Erkennung / Scanning)** und
**Phase 4 (Social Features & Export)**, sowie die öffentlichen Seiten
(Startseite, FAQ, Impressum, Datenschutz):
Benutzer-Registrierung/Login, Pokémon-TCG-Kartensuche, die Verwaltung der
eigenen Sammlung (Vault) und des Tausch-Binders inkl. Zustand, Sprache und
Grading, ein täglicher Cardmarket-Preis-Sync, ein Dashboard mit Gesamtwert
(getrennt nach Vault/Tauschbinder), Top-5-Schätzen, einem Wertverlaufs-Chart
und Tagesgewinnern/-verlierern, eine Wunschlisten-Funktion für fehlende
Karten, ein Kamera-/Foto-Scan-Flow zur schnellen Karten-Erfassung per
Vision-API, ein öffentlicher Tausch-Binder-Link mit Tauschpartner-Matchmaking,
ein Tausch-Chat mit beidseitiger Erfolgsbestätigung, CSV- und Druck-/PDF-Export,
ein Admin-Bereich zur Nutzerverwaltung und Content-Pflege, sowie ein
Profilbereich zum Ändern von E-Mail-Adresse und Passwort.

⚠️ **Vor dem Live-Gang:** Impressum und Datenschutzerklärung sind
strukturell vollständige, aber mit echten Platzhaltern (`[...]`) versehene
Entwürfe, die jetzt live im Admin-Bereich (`/admin/content`) bearbeitet
werden - siehe [Öffentliche Seiten](#öffentliche-seiten-landing-faq-impressum-datenschutz)
unten.

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
  lib/                # jwt.php, db.php, response.php, auth_middleware.php, admin.php,
                      # tcg_client.php, price_stats.php, vision_client.php
  auth/               # register.php, login.php, me.php, settings.php,
                      # change_email.php, change_password.php
  cards/              # list.php, add.php, update.php, delete.php (user_cards CRUD)
  tcg/                # search.php, sets.php, set_cards.php (Proxy zu pokemontcg.io)
  stats/              # dashboard.php (Gesamtwert, Top 5, Verlauf, Tagesgewinner/-verlierer)
  wishlist/           # list.php, add.php, remove.php
  scan/               # recognize.php (Vision-API-OCR + Auto-Match + Scan-Logging)
  public/             # binder.php (öffentlicher Tausch-Binder, keine Auth)
  matchmaking/        # find_traders.php (Wunschliste vs. öffentliche Tausch-Binder)
  export/             # cards.php (JSON), csv.php (CSV-Download)
  trades/             # create.php, list.php, get.php, message.php, confirm.php, cancel.php
  content/            # get.php (öffentlich, liefert Impressum/Datenschutz-Text)
  admin/              # stats.php, users/{list,block,unblock,delete}.php, content/update.php
database/
  schema.sql          # users, user_cards, price_history, wishlist_items, trades,
                      # trade_messages, scan_log, site_content
  migrations/         # ALTER-/CREATE-Skripte für bereits bestehende Installationen
frontend/
  src/
    views/            # Landing, FAQ, Impressum, Datenschutz, Login, Register, Dashboard,
                      # Collection, CardSearch, Wishlist, Scan, Settings, Export, PrintExport,
                      # PublicBinder, Trades, TradeDetail, AdminDashboard, AdminUsers, AdminContent
    components/       # Navbar, Footer, PokeballLoader, SetProgress, CardModal, ValueChart,
                      # CardScanner, LegalContent, AdminNav
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
   `001_add_public_binder_enabled.sql`, `002_add_admin_and_content.sql`) -
   `schema.sql` legt neue Spalten an bestehenden Tabellen nicht nachträglich an.
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

   ⚠️ **Netcup-Falle:** Geplante Aufgaben laufen bei Netcup/Plesk-Webhosting
   oft in einem eigenen Chroot, in dem der obige "offizielle" Pfad
   (`/var/www/vhosts/...`) **nicht** stimmt und PHP mit
   `Could not open input file: ...` abbricht, obwohl die Datei da ist. Den
   echten Pfad findet man, indem man testweise diesen Befehl als Aufgabe
   anlegt und einmal ausführt:
   ```bash
   sh -c "find / -maxdepth 4 -iname httpdocs 2>/dev/null; echo ---ENDE---"
   ```
   Die Ausgabe zeigt den tatsächlichen Pfad (bei diesem Hosting-Paket z. B.
   `/<domain>/httpdocs` statt `/var/www/vhosts/<domain>/httpdocs`). Den
   Cronjob-Befehl danach entsprechend anpassen, z. B.:
   ```bash
   php /<domain>/httpdocs/cron/sync_prices.php
   ```
7. **Ersten Admin bestimmen**: Es gibt keinen Einrichtungsassistenten dafür -
   nach der Registrierung des ersten echten Accounts einmalig per SQL:
   ```sql
   UPDATE users SET is_admin = 1 WHERE username = 'DEIN_BENUTZERNAME';
   ```
   Danach ist `/admin` für diesen Account freigeschaltet und weitere Admins
   lassen sich bei Bedarf genauso bestimmen.

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

## Admin-Bereich

Sichtbar in der Navbar (🛠️ Admin) nur für Accounts mit `is_admin = 1` -
siehe [Ersten Admin bestimmen](#deployment-auf-netcupplesk) beim Deployment.
Jeder Admin-Endpunkt prüft serverseitig `api/lib/admin.php` (`require_admin_user_id`),
die Navigation ist also kein Sicherheitsmechanismus, nur UI.

- **Übersicht** (`/admin`, `api/admin/stats.php`): Nutzer gesamt/aktiv/gesperrt,
  Karten gesamt (Vault/Tauschbinder), abgeschlossene/offene/abgebrochene Tausche,
  gescannte Karten (inkl. automatisch zugeordneter), Wunschlisten-Einträge,
  öffentliche Tausch-Binder. "Gescannte Karten" kommt aus einem neuen
  `scan_log`-Eintrag pro Scan-Versuch (`api/scan/recognize.php`); "Tausche"
  zählt Tausch-*Threads*, da Tausche in diesem Modell nicht einzelkartenweise
  erfasst werden.
- **Nutzer** (`/admin/users`, `api/admin/users/*.php`): Tabelle mit
  Kartenanzahl, Tausch- und Scan-Zahlen pro Nutzer. Aktionen:
  - **Sperren/Freischalten** (`is_blocked`): ein gesperrter Account kann sich
    nicht mehr einloggen und wird auch bei einem noch gültigen Token sofort
    abgemeldet (`auth_middleware.php` prüft `is_blocked` bei jedem
    authentifizierten Request neu, nicht nur beim Login).
  - **Löschen**: entfernt den Account vollständig; Karten, Wunschliste,
    Tausche/Nachrichten und Scan-Log-Einträge löschen über die
    `ON DELETE CASCADE`-Fremdschlüssel automatisch mit.
  - Ein Admin kann sich selbst weder sperren noch über diese Seite löschen
    (Aussperr-Schutz).
- **Impressum & Datenschutz** (`/admin/content`, `api/admin/content/update.php`
  + `api/content/get.php`): Die Texte liegen als einfacher Text in der Tabelle
  `site_content` und werden über einen Textbereich bearbeitet (Konvention:
  eine Zeile `## Titel` wird zur Überschrift, Leerzeile = neuer Absatz - siehe
  `components/LegalContent.vue`). Die öffentlichen Seiten `/impressum` und
  `/datenschutz` laden diesen Text live, Änderungen erscheinen sofort.

## Profil (Einstellungen)

Unter `/settings` kann jeder Nutzer neben dem öffentlichen Tausch-Binder-Link:

- **E-Mail-Adresse ändern** (`api/auth/change_email.php`): erfordert das
  aktuelle Passwort zur Bestätigung und prüft, dass die neue Adresse nicht
  schon vergeben ist.
- **Passwort ändern** (`api/auth/change_password.php`): erfordert ebenfalls
  das aktuelle Passwort, verlangt die Eingabe des neuen Passworts zweimal
  (Bestätigung) und mindestens 8 Zeichen.

## Öffentliche Seiten (Landing, FAQ, Impressum, Datenschutz)

- **Startseite** (`/`, `Landing.vue`): Hero mit Value-Proposition, vier
  Feature-Kacheln ("Warum PokéVault?") und drei im echten Design nachgebaute
  Vorschau-Panels (Dashboard-Ausschnitt, Scan-Flow, Tausch-Chat) statt echter
  Screenshots - in dieser Umgebung lässt sich die App nicht gegen eine echte
  Datenbank rendern, um Screenshots zu erzeugen. Für eingeloggte Nutzer leitet
  `/` automatisch zu `/dashboard` weiter.
- **FAQ** (`/faq`): Akkordeon mit Fragen zu Preisdaten, Grading, was mit
  Scan-Fotos passiert, öffentlichem Tausch-Binder, Nickname-only beim Tauschen,
  Bestätigung und Export.
- **Impressum** (`/impressum`) & **Datenschutzerklärung** (`/datenschutz`):
  laden ihren Text jetzt live aus `site_content` (siehe Admin-Bereich oben)
  statt ihn im Code zu hartcodieren. Der mitgelieferte Startinhalt ist ein
  vollständiger, aber mit `[Platzhaltern]` versehener Entwurf; der
  entsprechende Warn-Hinweis dazu sitzt im Admin-Editor (`/admin/content`),
  wo er tatsächlich wirkt. **Vor dem Live-Gang** müssen die echten
  Anbieter-/Kontaktdaten eingetragen und die Texte von einer fachkundigen
  Person geprüft werden - Claude kann hier keine rechtssichere Beratung
  leisten, nur eine inhaltlich korrekte Struktur (welche Daten die App
  technisch verarbeitet: Konto, Sammlung, Scan-Fotos via Vision-API,
  öffentlicher Tausch-Binder, Tausch-Nachrichten).
- **Footer** (`Footer.vue`, auf jeder Seite außer der Druckansicht): verlinkt
  FAQ/Impressum/Datenschutz, damit sie nicht nur per direkter URL erreichbar
  sind.
