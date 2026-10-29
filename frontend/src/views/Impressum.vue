<script setup></script>

<template>
  <div class="legal-page">
    <h1 class="font-display">Impressum</h1>

    <div class="error-banner placeholder-notice">
      ⚠️ Platzhalter-Entwurf: Die Felder unten müssen vor dem Live-Gang durch die
      echten Angaben des Betreibers ersetzt werden (Pflichtangaben nach §5
      TMG/DDG). Ohne korrekte, echte Angaben ist diese Seite nicht rechtsgültig.
    </div>

    <section class="poke-panel">
      <h2 class="font-display">Angaben gemäß §5 TMG/DDG</h2>
      <p>
        [Vor- und Nachname bzw. Firmenname]<br />
        [Straße und Hausnummer]<br />
        [PLZ und Ort]<br />
        [Land]
      </p>

      <h2 class="font-display">Kontakt</h2>
      <p>
        Telefon: [Telefonnummer]<br />
        E-Mail: [E-Mail-Adresse]
      </p>

      <h2 class="font-display">Umsatzsteuer-ID</h2>
      <p>
        Sofern vorhanden: Umsatzsteuer-Identifikationsnummer gemäß §27a
        Umsatzsteuergesetz: [USt-IdNr.]
      </p>

      <h2 class="font-display">Verantwortlich für den Inhalt nach §18 Abs. 2 MStV</h2>
      <p>
        [Name], [Anschrift wie oben]
      </p>

      <h2 class="font-display">Haftungshinweis</h2>
      <p>
        Trotz sorgfältiger inhaltlicher Kontrolle übernehmen wir keine Haftung für
        die Inhalte externer Links. Für den Inhalt der verlinkten Seiten sind
        ausschließlich deren Betreiber verantwortlich.
      </p>

      <h2 class="font-display">Markenhinweis</h2>
      <p>
        PokéVault ist ein inoffizielles Fan-Projekt und steht in keiner
        Verbindung zu Nintendo, The Pokémon Company oder Game Freak. Alle
        genannten Marken- und Produktnamen sind Eigentum ihrer jeweiligen
        Rechteinhaber.
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
