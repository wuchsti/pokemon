# PokéVault

Webanwendung zur Verwaltung, Bewertung und Foto-Erkennung von Pokémon-Sammelkarten.
Gebaut für ein Netcup Webhosting-Paket (Plesk, PHP 8.x, MariaDB).

Dieses Repository enthält aktuell **Phase 1 (MVP Core & Authentication)**:
Benutzer-Registrierung/Login, Pokémon-TCG-Kartensuche, und die Verwaltung der
eigenen Sammlung (Vault) und des Tausch-Binders inkl. Zustand, Sprache und
Grading. Phasen 2–4 (Preis-Historie & Dashboard-Statistiken, Foto-Erkennung,
Social Features & Export) folgen als nächste Ausbaustufen.

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
  lib/                # jwt.php, db.php, response.php, auth_middleware.php, tcg_client.php
  auth/               # register.php, login.php, me.php
  cards/              # list.php, add.php, update.php, delete.php (user_cards CRUD)
  tcg/                # search.php, sets.php (Proxy zu pokemontcg.io)
database/
  schema.sql          # users, user_cards, price_history
frontend/
  src/
    views/            # Login, Register, Dashboard, Collection, CardSearch
    components/       # Navbar, PokeballLoader, SetProgress, CardModal
    stores/auth.js     # Pinia Auth-Store
    api/client.js      # Fetch-Wrapper für /api/*
cron/
  (Phase 2) sync_prices.php für den täglichen Cardmarket-Preis-Sync
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
6. (Phase 2) Plesk Task Scheduler: täglicher Cronjob für `cron/sync_prices.php`.

## Design

Comic/Manga-Pokémon-Ästhetik: Pokéball-Rot (`#FF0000`/`#CC0000`),
Pikachu-Gelb (`#FFDE00`), Pokémon-Blau (`#3B4CCA`), abgerundete Ecken,
fette Comic-Outlines, Drop-Shadows, Fredoka/Nunito-Schriftarten und ein
rotierender Pokéball als Lade-Animation. Siehe `frontend/src/style.css`.

## Nächste Schritte (Phase 2–4)

- Preis-Historie + täglicher Cronjob (`cron/sync_prices.php`), Dashboard mit
  Gesamtwert, Top-5-Schätze, Wertentwicklungs-Chart (Chart.js).
- Kamera-/Foto-Erkennung (OCR) zum Schnell-Erfassen von Karten.
- Öffentliche Sammler-Profile, Tausch-Matchmaking, CSV/PDF-Export.
