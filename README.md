# PokéVault

Webanwendung zur Verwaltung, Bewertung und Foto-Erkennung von Pokémon-Sammelkarten.
Gebaut für ein Netcup Webhosting-Paket (Plesk, PHP 8.x, MariaDB).

Dieses Repository enthält aktuell **Phase 1 (MVP Core & Authentication)** und
**Phase 2 (Wertermittlung, Preis-Historie & Profil-Dashboard)**:
Benutzer-Registrierung/Login, Pokémon-TCG-Kartensuche, die Verwaltung der
eigenen Sammlung (Vault) und des Tausch-Binders inkl. Zustand, Sprache und
Grading, ein täglicher Cardmarket-Preis-Sync, ein Dashboard mit Gesamtwert
(getrennt nach Vault/Tauschbinder), Top-5-Schätzen, einem Wertverlaufs-Chart
und Tagesgewinnern/-verlierern, sowie eine Wunschlisten-Funktion für fehlende
Karten. Phasen 3–4 (Foto-Erkennung, Social Features & Export) folgen als
nächste Ausbaustufen.

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
                      # tcg_client.php, price_stats.php
  auth/               # register.php, login.php, me.php
  cards/              # list.php, add.php, update.php, delete.php (user_cards CRUD)
  tcg/                # search.php, sets.php, set_cards.php (Proxy zu pokemontcg.io)
  stats/              # dashboard.php (Gesamtwert, Top 5, Verlauf, Tagesgewinner/-verlierer)
  wishlist/           # list.php, add.php, remove.php
database/
  schema.sql          # users, user_cards, price_history, wishlist_items
frontend/
  src/
    views/            # Login, Register, Dashboard, Collection, CardSearch, Wishlist
    components/       # Navbar, PokeballLoader, SetProgress, CardModal, ValueChart
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

## Nächste Schritte (Phase 3–4)

- Kamera-/Foto-Erkennung (OCR) zum Schnell-Erfassen von Karten.
- Öffentliche Sammler-Profile, Tausch-Matchmaking (auch unter Einbeziehung der
  Wunschliste), CSV/PDF-Export.
