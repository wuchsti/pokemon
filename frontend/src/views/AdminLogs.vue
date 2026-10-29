<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../api/client'
import AdminNav from '../components/AdminNav.vue'
import PokeballLoader from '../components/PokeballLoader.vue'

const logs = ref([])
const loading = ref(true)
const error = ref('')
const sourceFilter = ref('')

const sourceLabels = {
  tcg_api: 'Pokémon TCG API',
  vision_api: 'Vision API (Scan)',
}

function formatDate(value) {
  return new Date(value.replace(' ', 'T')).toLocaleString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const params = sourceFilter.value ? { source: sourceFilter.value } : {}
    const result = await api.adminLogs(params)
    logs.value = result.logs
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <h1 class="font-display">Admin-Bereich</h1>
    <AdminNav />

    <p class="subtitle">
      Technische Fehler externer APIs (Pokémon TCG API, Vision-API) - Nutzer sehen
      davon nur eine generische Meldung, hier stehen die echten Details. Die letzten
      200 Einträge.
    </p>

    <div v-if="error" class="error-banner">{{ error }}</div>

    <div class="filter-row">
      <select v-model="sourceFilter" @change="load">
        <option value="">Alle Quellen</option>
        <option value="tcg_api">Pokémon TCG API</option>
        <option value="vision_api">Vision API (Scan)</option>
      </select>
      <button class="btn btn-outline btn-sm" @click="load">🔄 Aktualisieren</button>
    </div>

    <PokeballLoader v-if="loading" />

    <p v-else-if="!error && logs.length === 0" class="no-logs">Keine Fehler protokolliert. ✓</p>

    <div v-else-if="!error" class="table-wrap poke-panel">
      <table class="logs-table">
        <thead>
          <tr>
            <th>Zeitpunkt</th>
            <th>Quelle</th>
            <th>Meldung</th>
            <th>Kontext</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id">
            <td>{{ formatDate(log.created_at) }}</td>
            <td><span class="badge badge-trade">{{ sourceLabels[log.source] ?? log.source }}</span></td>
            <td class="message-cell">{{ log.message }}</td>
            <td>{{ log.context }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.subtitle {
  color: var(--poke-gray);
  font-weight: 600;
  max-width: 640px;
  margin-top: -6px;
}

.filter-row {
  display: flex;
  gap: 10px;
  align-items: center;
  margin: 16px 0 20px;
}

.filter-row select {
  font-family: var(--font-body);
  font-size: 14px;
  padding: 8px 12px;
  border: var(--border-comic);
  border-radius: var(--radius-sm);
}

.no-logs {
  font-weight: 700;
  color: #1d9a4a;
}

.table-wrap {
  overflow-x: auto;
}

.logs-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.logs-table th,
.logs-table td {
  padding: 8px 12px;
  text-align: left;
  border-bottom: 1px solid var(--poke-bg);
  vertical-align: top;
}

.message-cell {
  max-width: 420px;
}
</style>
