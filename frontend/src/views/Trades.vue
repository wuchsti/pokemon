<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api/client'
import PokeballLoader from '../components/PokeballLoader.vue'

const router = useRouter()
const trades = ref([])
const loading = ref(true)
const error = ref('')

const newPartnerUsername = ref('')
const starting = ref(false)

const statusLabels = {
  open: 'Offen',
  completed: 'Abgeschlossen',
  cancelled: 'Abgebrochen',
}

function formatDate(value) {
  return new Date(value.replace(' ', 'T')).toLocaleString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const result = await api.listTrades()
    trades.value = result.trades
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function startTrade() {
  if (!newPartnerUsername.value.trim()) return
  starting.value = true
  error.value = ''
  try {
    const { trade } = await api.createTrade({ partner_username: newPartnerUsername.value.trim() })
    router.push(`/trades/${trade.id}`)
  } catch (e) {
    error.value = e.message
  } finally {
    starting.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <h1 class="font-display">Tausche</h1>
    <p class="subtitle">
      Deine Tausch-Unterhaltungen. Neue Vorschläge entstehen über die
      <router-link to="/wishlist">Tauschpartner-Suche</router-link> oder den öffentlichen
      Tausch-Binder eines Nutzers - oder starte hier direkt einen Tausch per Nickname.
    </p>

    <div v-if="error" class="error-banner">{{ error }}</div>

    <form class="poke-panel start-trade" @submit.prevent="startTrade">
      <label>Neuen Tausch starten</label>
      <div class="start-trade-row">
        <input v-model="newPartnerUsername" type="text" placeholder="Nickname des Tauschpartners" />
        <button class="btn btn-accent" :disabled="starting" type="submit">
          {{ starting ? 'Starte...' : 'Starten' }}
        </button>
      </div>
    </form>

    <PokeballLoader v-if="loading" />

    <p v-else-if="trades.length === 0">Noch keine Tausch-Unterhaltungen.</p>

    <div v-else class="trades-list">
      <router-link v-for="trade in trades" :key="trade.id" :to="`/trades/${trade.id}`" class="poke-card trade-row">
        <div class="trade-row-main">
          <strong>{{ trade.partner_username }}</strong>
          <span class="trade-row-date">{{ formatDate(trade.updated_at) }}</span>
        </div>
        <span
          class="badge"
          :class="{
            'badge-trade': trade.status === 'open',
            'badge-vault': trade.status === 'completed',
          }"
          :style="trade.status === 'cancelled' ? 'background:#ccc;color:#555' : ''"
        >
          {{ statusLabels[trade.status] }}
        </span>
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.subtitle {
  color: var(--poke-gray);
  font-weight: 600;
  max-width: 620px;
  margin-top: -6px;
}

.start-trade {
  max-width: 480px;
  margin: 20px 0 28px;
}

.start-trade label {
  font-weight: 700;
  font-size: 14px;
  display: block;
  margin-bottom: 8px;
}

.start-trade-row {
  display: flex;
  gap: 10px;
}

.start-trade-row input {
  flex: 1;
  font-family: var(--font-body);
  font-size: 15px;
  padding: 10px 12px;
  border: var(--border-comic);
  border-radius: var(--radius-sm);
}

.trades-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: 560px;
}

.trade-row {
  padding: 14px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--poke-white);
  color: var(--poke-ink);
}

.trade-row:hover {
  transform: translateY(-2px);
}

.trade-row-main {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.trade-row-date {
  font-size: 12px;
  color: var(--poke-gray);
}
</style>
