<script setup>
import CardScanner from '../components/CardScanner.vue'
</script>

<template>
  <div>
    <h1 class="font-display">Karte scannen</h1>
    <p class="subtitle">
      Fotografiere eine Karte – PokéVault erkennt Name und Kartennummer automatisch
      und schlägt passende Treffer zur schnellen Übernahme vor.
    </p>
    <CardScanner />
  </div>
</template>

<style scoped>
.subtitle {
  color: var(--poke-gray);
  font-weight: 600;
  max-width: 560px;
  margin-top: -6px;
}
</style>
