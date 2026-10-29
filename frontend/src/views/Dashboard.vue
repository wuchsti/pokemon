<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '../api/client'
import { useAuthStore } from '../stores/auth'
import PokeballLoader from '../components/PokeballLoader.vue'
import SetProgress from '../components/SetProgress.vue'

const auth = useAuthStore()
const cards = ref([])
const setTotals = ref({})
const loading = ref(true)
const error = ref('')

const vaultCount = computed(() => cards.value.filter((c) => c.storage_type === 'vault').length)
const tradeCount = computed(() => cards.value.filter((c) => c.storage_type === 'trade_binder').length)

const setSummaries = computed(() => {
  const groups = {}
  for (const card of cards.value) {
    if (!groups[card.set_id]) groups[card.set_id] = []
    groups[card.set_id].push(card)
  }
  return Object.entries(groups)
    .map(([setId, setCards]) => ({
      setId,
      owned: setCards.length,
      name: setTotals.value[setId]?.name ?? setId,
      total: setTotals.value[setId]?.total ?? setCards.length,
    }))
    .sort((a, b) => b.owned / b.total - a.owned / a.total)
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const result = await api.listUserCards()
    cards.value = result.cards
    const uniqueSetIds = [...new Set(cards.value.map((c) => c.set_id))]
    const totals = {}
    await Promise.all(
      uniqueSetIds.map(async (setId) => {
        try {
          const { set } = await api.getSet(setId)
          totals[setId] = { name: set?.name ?? setId, total: set?.printedTotal ?? set?.total ?? 0 }
        } catch {
          totals[setId] = { name: setId, total: 0 }
        }
      })
    )
    setTotals.value = totals
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
    <h1 class="font-display">Willkommen zurück, {{ auth.user?.username }}!</h1>

    <div v-if="error" class="error-banner">{{ error }}</div>

    <PokeballLoader v-if="loading" />

    <template v-else>
      <div class="stats-row">
        <router-link to="/collection/vault" class="poke-panel stat-card">
          <span class="stat-value">{{ vaultCount }}</span>
          <span class="stat-label">Karten im Vault</span>
        </router-link>
        <router-link to="/collection/trade_binder" class="poke-panel stat-card">
          <span class="stat-value">{{ tradeCount }}</span>
          <span class="stat-label">Karten im Tausch-Binder</span>
        </router-link>
        <router-link to="/search" class="poke-panel stat-card stat-card-accent">
          <span class="stat-value">+</span>
          <span class="stat-label">Neue Karte hinzufügen</span>
        </router-link>
      </div>

      <h2 class="font-display">Set-Fortschritt</h2>
      <p v-if="setSummaries.length === 0">
        Noch keine Sets begonnen. Füge Karten über die Kartensuche hinzu.
      </p>
      <div v-else class="poke-panel">
        <SetProgress
          v-for="summary in setSummaries"
          :key="summary.setId"
          :set-name="summary.name"
          :owned="summary.owned"
          :total="summary.total"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin: 20px 0 32px;
}

.stat-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  gap: 6px;
  text-align: center;
  color: var(--poke-ink);
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-card-accent {
  background: var(--poke-yellow);
}

.stat-value {
  font-family: var(--font-display);
  font-size: 32px;
  font-weight: 700;
}

.stat-label {
  font-weight: 700;
  font-size: 13px;
  color: var(--poke-gray);
}
</style>
