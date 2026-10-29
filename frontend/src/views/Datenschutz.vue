<script setup></script>

<template>
  <div class="legal-page">
    <h1 class="font-display">Datenschutzerklärung</h1>

    <div class="error-banner placeholder-notice">
      ⚠️ Platzhalter-Entwurf: Diese Seite beschreibt strukturell, welche Daten
      die Anwendung technisch verarbeitet. Die eckigen Klammern müssen durch
      echte Angaben ersetzt werden, und der Text sollte vor dem Live-Gang von
      einer fachkundigen Person (z. B. Datenschutzbeauftragte/r oder
      Rechtsanwalt/-anwältin) geprüft werden.
    </div>

    <section class="poke-panel">
      <h2 class="font-display">1. Verantwortlicher</h2>
      <p>
        Verantwortlich für die Datenverarbeitung auf dieser Website ist die im
        <router-link to="/impressum">Impressum</router-link> genannte Person/Stelle.
      </p>

      <h2 class="font-display">2. Welche Daten wir verarbeiten</h2>
      <p>
        <strong>Konto:</strong> Benutzername, E-Mail-Adresse und ein gehashtes
        Passwort (nie im Klartext gespeichert).<br />
        <strong>Sammlungsdaten:</strong> von dir erfasste Karten (Set, Nummer,
        Zustand, Sprache, Grading) sowie optional eine Wunschliste.<br />
        <strong>Foto-Erkennung:</strong> ein von dir aufgenommenes oder
        hochgeladenes Kartenfoto wird zur Texterkennung an den unten genannten
        Vision-API-Anbieter übertragen und von uns nicht dauerhaft
        gespeichert.<br />
        <strong>Öffentlicher Tausch-Binder:</strong> wenn du diese Option in den
        Einstellungen aktivierst, werden Kartenname, Nummer, Zustand, Sprache
        und Grading deines Tausch-Binders öffentlich unter einem Link mit
        deinem Nickname abrufbar. Deine E-Mail-Adresse und dein Vault bleiben
        davon unberührt und niemals öffentlich.<br />
        <strong>Tausch-Nachrichten:</strong> Nachrichten, die du in einem
        Tausch-Chat schreibst, sind für den jeweiligen Tauschpartner
        einsehbar.
      </p>

      <h2 class="font-display">3. Zweck der Verarbeitung</h2>
      <p>
        Die Daten werden ausschließlich zur Bereitstellung der
        PokéVault-Funktionen verarbeitet: Kontoverwaltung, Sammlungsverwaltung,
        Wertermittlung, Karten-Erkennung, Tauschpartner-Vermittlung und
        Tausch-Kommunikation.
      </p>

      <h2 class="font-display">4. Eingesetzte externe Dienste</h2>
      <p>
        <strong>Pokémon TCG API</strong> (pokemontcg.io): liefert
        Kartenstammdaten, Bilder und Marktpreise. Es werden dabei keine
        personenbezogenen Daten von dir übermittelt, nur Suchbegriffe.<br />
        <strong>Vision-API-Anbieter</strong> (aktuell konfiguriert: [Name des
        Anbieters, z. B. OpenAI], Sitz: [Land]): erhält das von dir zur
        Erkennung übermittelte Kartenfoto. Es gilt die Datenschutzerklärung
        des jeweiligen Anbieters.
      </p>

      <h2 class="font-display">5. Speicherdauer</h2>
      <p>
        Konto- und Sammlungsdaten werden gespeichert, bis du dein Konto löschen
        lässt (siehe Kontakt im <router-link to="/impressum">Impressum</router-link>).
        Preis-Historien-Einträge werden fortlaufend ergänzt und nicht
        automatisch gelöscht.
      </p>

      <h2 class="font-display">6. Cookies &amp; lokale Speicherung</h2>
      <p>
        PokéVault setzt keine Tracking- oder Werbe-Cookies. Dein Anmelde-Token
        wird im <code>localStorage</code> deines Browsers gespeichert, damit du
        angemeldet bleibst, und verlässt dein Gerät nur bei API-Anfragen an
        diese Anwendung selbst.
      </p>

      <h2 class="font-display">7. Deine Rechte</h2>
      <p>
        Du hast nach der DSGVO insbesondere ein Recht auf Auskunft (Art. 15),
        Berichtigung (Art. 16), Löschung (Art. 17), Einschränkung der
        Verarbeitung (Art. 18), Datenübertragbarkeit (Art. 20) und Widerspruch
        (Art. 21) bezüglich deiner personenbezogenen Daten. Wende dich dazu an
        die im <router-link to="/impressum">Impressum</router-link> genannten
        Kontaktdaten.
      </p>
    </section>
  </div>
</template>

<style scoped>
.legal-page {
  max-width: 720px;
}

.placeholder-notice {
  margin-bottom: 20px;
}

h2 {
  font-size: 16px;
  margin: 20px 0 8px;
}

h2:first-child {
  margin-top: 0;
}

p {
  color: var(--poke-ink);
  font-weight: 600;
  font-size: 14px;
  line-height: 1.6;
}
</style>
