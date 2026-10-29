<script setup>
const faqs = [
  {
    q: 'Was ist PokéVault?',
    a: 'PokéVault ist eine Webanwendung zum Verwalten deiner Pokémon-Sammelkarten: Sammlung und Tausch-Binder getrennt verwalten, Marktwerte verfolgen, Karten per Foto erfassen und mit anderen Nutzern tauschen.',
  },
  {
    q: 'Woher kommen die Kartendaten und Preise?',
    a: 'Kartendaten, Bilder und Set-Informationen stammen von der Pokémon TCG API (pokemontcg.io). Die Preise basieren auf Cardmarket-Durchschnittswerten und werden einmal täglich automatisch synchronisiert - ohne Gewähr auf Richtigkeit oder Aktualität.',
  },
  {
    q: 'Unterstützt PokéVault gegradete Karten?',
    a: 'Ja. Beim Erfassen einer Karte kannst du angeben, ob sie gegradet ist, von welchem Unternehmen (PSA, BGS, CGC oder AP Grading) und mit welcher Note (1.0-10.0).',
  },
  {
    q: 'Was passiert mit Fotos, die ich zum Scannen aufnehme?',
    a: 'Ein aufgenommenes oder hochgeladenes Foto wird ausschließlich zur Texterkennung an den konfigurierten Vision-API-Anbieter übermittelt und von PokéVault selbst nicht dauerhaft gespeichert. Details dazu stehen in der Datenschutzerklärung.',
  },
  {
    q: 'Ist mein Tausch-Binder für andere sichtbar?',
    a: 'Nur wenn du das unter Einstellungen aktivst (Opt-in, standardmäßig aus). Dein Vault, deine E-Mail-Adresse und Kartenwerte bleiben dabei immer privat - ein öffentlicher Tausch-Binder zeigt nur Kartenname, Nummer, Zustand, Sprache und Grading.',
  },
  {
    q: 'Sehen andere Nutzer meine E-Mail-Adresse, wenn ich tausche?',
    a: 'Nein. Beim Tauschpartner-Matchmaking, im öffentlichen Tausch-Binder und im Tausch-Chat wirst du immer nur mit deinem Nickname (Benutzernamen) angezeigt.',
  },
  {
    q: 'Wie funktioniert das Tauschpartner-Matchmaking?',
    a: 'Deine Wunschliste (fehlende Karten aus deinen Sets) wird mit den öffentlichen Tausch-Bindern anderer Nutzer verglichen. Du siehst, wer passende Karten hat, und kannst direkt einen Tausch-Chat starten.',
  },
  {
    q: 'Wie bestätige ich, dass ein Tausch erfolgreich war?',
    a: 'Im Tausch-Chat gibt es einen Bestätigen-Button für jede Seite. Erst wenn beide Tauschpartner bestätigt haben, gilt der Tausch als abgeschlossen. Ein noch offener Tausch kann jederzeit von beiden Seiten abgebrochen werden.',
  },
  {
    q: 'Kann ich meine Sammlung exportieren?',
    a: 'Ja, unter Export: als CSV-Datei für Excel/Sheets, oder als druckfreundliche Inventarliste, die du über die Browser-Druckfunktion als PDF speichern kannst - praktisch für Dokumentation oder Versicherung.',
  },
  {
    q: 'Wie kann ich meine Daten löschen lassen?',
    a: 'Wende dich dazu an die im Impressum genannten Kontaktdaten des Betreibers dieser Instanz.',
  },
]
</script>

<template>
  <div class="faq-page">
    <h1 class="font-display">Häufige Fragen</h1>
    <p class="subtitle">Antworten zu Preisdaten, Grading, Foto-Erkennung, Tauschen und Datenschutz.</p>

    <div class="faq-list">
      <details v-for="item in faqs" :key="item.q" class="poke-panel faq-item">
        <summary class="font-display">{{ item.q }}</summary>
        <p>{{ item.a }}</p>
      </details>
    </div>
  </div>
</template>

<style scoped>
.faq-page {
  max-width: 720px;
}

.subtitle {
  color: var(--poke-gray);
  font-weight: 600;
  margin-top: -6px;
}

.faq-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 20px;
}

.faq-item {
  padding: 16px 20px;
}

.faq-item summary {
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
}

.faq-item p {
  margin: 12px 0 0;
  color: var(--poke-gray);
  font-weight: 600;
  font-size: 14px;
  line-height: 1.5;
}
</style>
