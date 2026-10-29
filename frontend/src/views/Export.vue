<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api/client'

const router = useRouter()
const downloading = ref(false)
const error = ref('')

async function downloadCsv() {
  downloading.value = true
  error.value = ''
  try {
    await api.exportCsv()
  } catch (e) {
    error.value = e.message
  } finally {
    downloading.value = false
  }
}

const printUrl = router.resolve({ name: 'export-print' }).href
</script>

<template>
  <div>
    <h1 class="font-display">Export</h1>
    <p class="subtitle">Exportiere deine Sammlung für Tabellen, Archive oder die Versicherung.</p>

    <div v-if="error" class="error-banner">{{ error }}</div>

    <div class="export-grid">
      <div class="poke-panel export-card">
        <h2 class="font-display">📄 CSV-Export</h2>
        <p>Alle Karten aus Vault und Tausch-Binder inkl. Preis, Zustand und Grading – zum Öffnen in Excel oder Google Sheets.</p>
        <button class="btn btn-primary" :disabled="downloading" @click="downloadCsv">
          {{ downloading ? 'Export läuft...' : 'CSV herunterladen' }}
        </button>
      </div>

      <div class="poke-panel export-card">
        <h2 class="font-display">🖨️ Druckansicht / PDF</h2>
        <p>
          Eine saubere, druckfreundliche Inventarliste mit Gesamtwerten – ideal für
          Sammler-Dokumentation oder die Versicherung. Öffnet in einem neuen Tab;
          dort über die Browser-Druckfunktion (Strg/Cmd+P) als PDF speichern.
        </p>
        <a :href="printUrl" target="_blank" rel="noopener" class="btn btn-accent">Druckansicht öffnen</a>
      </div>
    </div>
  </div>
</template>

<style scoped>
.subtitle {
  color: var(--poke-gray);
  font-weight: 600;
  max-width: 560px;
  margin-top: -6px;
}

.export-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.export-card h2 {
  margin-top: 0;
  font-size: 18px;
}

.export-card p {
  color: var(--poke-gray);
  font-weight: 600;
  font-size: 14px;
  min-height: 60px;
}
</style>
