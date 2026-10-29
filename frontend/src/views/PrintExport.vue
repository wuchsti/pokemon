<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '../api/client'

const loading = ref(true)
const error = ref('')
const username = ref('')
const generatedAt = ref('')
const cards = ref([])

function formatEuro(value) {
  if (value === null || value === undefined) return '–'
  return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(value)
}

function sectionFor(type) {
  return cards.value
    .filter((c) => c.storage_type === type)
    .sort((a, b) => a.set_id.localeCompare(b.set_id) || a.card_number.localeCompare(b.card_number))
}

const vaultCards = computed(() => sectionFor('vault'))
const tradeCards = computed(() => sectionFor('trade_binder'))

function total(list) {
  return list.reduce((sum, c) => sum + (c.latest_price ? Number(c.latest_price) : 0), 0)
}

const vaultTotal = computed(() => total(vaultCards.value))
const tradeTotal = computed(() => total(tradeCards.value))
const grandTotal = computed(() => vaultTotal.value + tradeTotal.value)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const result = await api.exportCardsJson()
    username.value = result.username
    generatedAt.value = result.generated_at
    cards.value = result.cards
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function printPage() {
  window.print()
}

onMounted(load)
</script>

<template>
  <div class="print-page">
    <div class="no-print toolbar">
      <button class="btn btn-primary" @click="printPage">🖨️ Drucken / Als PDF speichern</button>
    </div>

    <div v-if="error" class="error-banner no-print">{{ error }}</div>
    <p v-if="loading">Lädt...</p>

    <template v-else>
      <header class="doc-header">
        <h1>PokéVault – Sammlungsliste</h1>
        <p>Erstellt für {{ username }} am {{ generatedAt }} Uhr. Zur Dokumentation und Versicherung.</p>
      </header>

      <section class="doc-section">
        <h2>Vault ({{ vaultCards.length }} Karten) – Gesamtwert: {{ formatEuro(vaultTotal) }}</h2>
        <table>
          <thead>
            <tr>
              <th>Set</th><th>Nr.</th><th>Name</th><th>Zustand</th><th>Sprache</th><th>Grading</th><th>Preis</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="card in vaultCards" :key="card.id">
              <td>{{ card.set_id }}</td>
              <td>{{ card.card_number }}</td>
              <td>{{ card.card_name }}</td>
              <td>{{ card.card_condition }}</td>
              <td>{{ card.language }}</td>
              <td>{{ card.is_graded ? `${card.grading_company} ${card.grading_score}` : '–' }}</td>
              <td>{{ formatEuro(card.latest_price) }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="doc-section">
        <h2>Tausch-Binder ({{ tradeCards.length }} Karten) – Gesamtwert: {{ formatEuro(tradeTotal) }}</h2>
        <table>
          <thead>
            <tr>
              <th>Set</th><th>Nr.</th><th>Name</th><th>Zustand</th><th>Sprache</th><th>Grading</th><th>Preis</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="card in tradeCards" :key="card.id">
              <td>{{ card.set_id }}</td>
              <td>{{ card.card_number }}</td>
              <td>{{ card.card_name }}</td>
              <td>{{ card.card_condition }}</td>
              <td>{{ card.language }}</td>
              <td>{{ card.is_graded ? `${card.grading_company} ${card.grading_score}` : '–' }}</td>
              <td>{{ formatEuro(card.latest_price) }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <footer class="doc-footer">
        <strong>Gesamtwert aller Karten: {{ formatEuro(grandTotal) }}</strong>
        <p>Preise basieren auf dem letzten synchronisierten Cardmarket-Durchschnittswert, ohne Gewähr.</p>
      </footer>
    </template>
  </div>
</template>

<style scoped>
.print-page {
  background: #fff;
  color: #1a1a1a;
  max-width: 900px;
  margin: 0 auto;
  padding: 32px 24px 60px;
  font-family: 'Nunito', sans-serif;
}

.toolbar {
  margin-bottom: 20px;
}

.doc-header {
  border-bottom: 3px solid #2a2a2a;
  padding-bottom: 12px;
  margin-bottom: 24px;
}

.doc-header h1 {
  font-family: 'Fredoka', sans-serif;
  margin: 0 0 4px;
  font-size: 24px;
}

.doc-header p {
  margin: 0;
  color: #555;
  font-size: 13px;
}

.doc-section {
  margin-bottom: 28px;
}

.doc-section h2 {
  font-size: 16px;
  border-bottom: 1px solid #ccc;
  padding-bottom: 6px;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

th,
td {
  border: 1px solid #ccc;
  padding: 6px 8px;
  text-align: left;
}

th {
  background: #f0f0f0;
}

td:last-child,
th:last-child {
  text-align: right;
  font-variant-numeric: tabular-nums;
}

.doc-footer {
  border-top: 3px solid #2a2a2a;
  padding-top: 12px;
  font-size: 14px;
}

.doc-footer p {
  color: #555;
  font-size: 12px;
}

@media print {
  .no-print {
    display: none !important;
  }

  .print-page {
    padding: 0;
  }

  table {
    page-break-inside: auto;
  }

  tr {
    page-break-inside: avoid;
  }
}
</style>
