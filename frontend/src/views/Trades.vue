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

const cardQuery = ref('')
const cardResults = ref(null)
const cardSearching = ref(false)
const cardSearchError = ref('')
const startingTradeWith = ref(null)

async function searchCards() {
  if (cardQuery.value.trim().length < 2) {
    cardSearchError.value = 'Bitte mindestens 2 Zeichen eingeben.'
    return
  }
  cardSearching.value = true
  cardSearchError.value = ''
  try {
    const result = await api.searchTradeCards(cardQuery.value.trim())
    cardResults.value = result.traders
  } catch (e) {
    cardSearchError.value = e.message
  } finally {
    cardSearching.value = false
  }
}

async function proposeTrade(username) {
  startingTradeWith.value = username
  cardSearchError.value = ''
  try {
    const { trade } = await api.createTrade({ partner_username: username })
    router.push(`/trades/${trade.id}`)
  } catch (e) {
    cardSearchError.value = e.message
  } finally {
    startingTradeWith.value = null
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

    <section class="poke-panel card-search-section">
      <h2 class="font-display panel-title">Karte suchen</h2>
      <p class="settings-hint">
        Suche nach einer bestimmten Karte in allen öffentlichen Tausch-Bindern - unabhängig von deiner Wunschliste.
      </p>

      <form class="card-search-row" @submit.prevent="searchCards">
        <input v-model="cardQuery" type="text" placeholder="z. B. Glurak, Pikachu..." />
        <button class="btn btn-accent" :disabled="cardSearching" type="submit">
          {{ cardSearching ? 'Suche läuft...' : '🔍 Suchen' }}
        </button>
      </form>

      <div v-if="cardSearchError" class="error-banner">{{ cardSearchError }}</div>
      <PokeballLoader v-if="cardSearching" />

      <p v-else-if="cardResults && cardResults.length === 0" class="no-traders">
        Niemand mit öffentlichem Tausch-Binder hat gerade eine passende Karte.
      </p>

      <div v-else-if="cardResults" class="traders-list">
        <div v-for="trader in cardResults" :key="trader.username" class="trader-card poke-card">
          <div class="trader-head">
            <strong>{{ trader.username }}</strong>
            <span class="badge badge-trade">{{ trader.matches.length }} Treffer</span>
          </div>
          <div class="trader-matches">
            <img
              v-for="match in trader.matches"
              :key="match.card_api_id"
              :src="match.image_url"
              :alt="match.card_name"
              :title="`${match.card_name} (#${match.card_number}, ${match.card_condition})`"
            />
          </div>
          <div class="trader-actions">
            <router-link :to="`/user/${trader.username}/binder`" class="btn btn-outline btn-sm" target="_blank">
              Tausch-Binder ansehen
            </router-link>
            <button
              class="btn btn-primary btn-sm"
              :disabled="startingTradeWith === trader.username"
              @click="proposeTrade(trader.username)"
            >
              💬 Tausch vorschlagen
            </button>
          </div>
        </div>
      </div>
    </section>
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

.card-search-section {
  margin-top: 36px;
  max-width: 720px;
}

.panel-title {
  margin-top: 0;
  font-size: 18px;
}

.settings-hint {
  color: var(--poke-gray);
  font-weight: 600;
  font-size: 14px;
}

.card-search-row {
  display: flex;
  gap: 10px;
  max-width: 480px;
  margin: 12px 0 16px;
}

.card-search-row input {
  flex: 1;
  font-family: var(--font-body);
  font-size: 15px;
  padding: 10px 12px;
  border: var(--border-comic);
  border-radius: var(--radius-sm);
}

.no-traders {
  margin-top: 14px;
  font-weight: 600;
  color: var(--poke-gray);
}

.traders-list {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.trader-card {
  padding: 14px;
  background: var(--poke-white);
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.trader-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.trader-matches {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.trader-matches img {
  width: 48px;
  border-radius: 6px;
  border: 2px solid var(--poke-ink);
}

.trader-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
</style>
