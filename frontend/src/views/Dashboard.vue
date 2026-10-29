<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '../api/client'
import { useAuthStore } from '../stores/auth'
import PokeballLoader from '../components/PokeballLoader.vue'
import SetProgress from '../components/SetProgress.vue'
import ValueChart from '../components/ValueChart.vue'

const auth = useAuthStore()
const cards = ref([])
const setTotals = ref({})
const stats = ref(null)
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

function formatEuro(value) {
  if (value === null || value === undefined) return '–'
  return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(value)
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [cardsResult, statsResult] = await Promise.all([api.listUserCards(), api.dashboardStats()])
    cards.value = cardsResult.cards
    stats.value = statsResult

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
          <span class="stat-value">{{ formatEuro(stats?.totals?.vault_value) }}</span>
          <span class="stat-label">Wert im Vault ({{ vaultCount }} Karten)</span>
        </router-link>
        <router-link to="/collection/trade_binder" class="poke-panel stat-card">
          <span class="stat-value">{{ formatEuro(stats?.totals?.trade_binder_value) }}</span>
          <span class="stat-label">Wert im Tausch-Binder ({{ tradeCount }} Karten)</span>
        </router-link>
        <router-link to="/search" class="poke-panel stat-card stat-card-accent">
          <span class="stat-value">+</span>
          <span class="stat-label">Neue Karte hinzufügen</span>
        </router-link>
      </div>

      <div class="dashboard-grid">
        <section class="poke-panel">
          <h2 class="font-display panel-title">Wertentwicklung (30 Tage)</h2>
          <ValueChart :series="stats?.value_series ?? []" />
        </section>

        <section class="poke-panel movers-panel">
          <h2 class="font-display panel-title">Tagesgewinner &amp; -verlierer</h2>

          <div v-if="!stats?.movers?.gainer && !stats?.movers?.loser" class="movers-empty">
            Noch nicht genug Preis-Historie für einen Vergleich. Sobald der tägliche
            Preis-Sync mindestens zwei Tage gelaufen ist, erscheinen hier die größten
            Kursbewegungen deiner Sammlung.
          </div>

          <div v-else class="movers-list">
            <div v-if="stats.movers.gainer" class="mover-row mover-gainer">
              <img :src="stats.movers.gainer.image_url" :alt="stats.movers.gainer.card_name" />
              <div class="mover-info">
                <strong>{{ stats.movers.gainer.card_name }}</strong>
                <span>{{ formatEuro(stats.movers.gainer.old_price) }} → {{ formatEuro(stats.movers.gainer.new_price) }}</span>
              </div>
              <span class="mover-delta mover-delta-up">▲ {{ stats.movers.gainer.delta_pct }}%</span>
            </div>

            <div v-if="stats.movers.loser" class="mover-row mover-loser">
              <img :src="stats.movers.loser.image_url" :alt="stats.movers.loser.card_name" />
              <div class="mover-info">
                <strong>{{ stats.movers.loser.card_name }}</strong>
                <span>{{ formatEuro(stats.movers.loser.old_price) }} → {{ formatEuro(stats.movers.loser.new_price) }}</span>
              </div>
              <span class="mover-delta mover-delta-down">▼ {{ stats.movers.loser.delta_pct }}%</span>
            </div>
          </div>
        </section>
      </div>

      <h2 class="font-display">Top 5 Schätze</h2>
      <p v-if="!stats?.top_treasures?.length">
        Noch keine Preise synchronisiert. Sobald der tägliche Preis-Sync läuft, erscheinen
        hier deine wertvollsten Karten.
      </p>
      <div v-else class="treasures-row">
        <div v-for="card in stats.top_treasures" :key="card.id" class="poke-card treasure-card">
          <span v-if="card.is_graded" class="badge badge-graded treasure-grade">
            {{ card.grading_company }} {{ card.grading_score }}
          </span>
          <img :src="card.image_url" :alt="card.card_name" />
          <div class="treasure-info">
            <strong>{{ card.card_name }}</strong>
            <span class="treasure-price">{{ formatEuro(card.latest_price) }}</span>
          </div>
        </div>
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
  margin: 20px 0 24px;
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
  font-size: 28px;
  font-weight: 700;
}

.stat-label {
  font-weight: 700;
  font-size: 13px;
  color: var(--poke-gray);
}

.dashboard-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
  margin-bottom: 32px;
}

@media (max-width: 760px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}

.panel-title {
  margin-top: 0;
  font-size: 18px;
}

.movers-empty {
  color: var(--poke-gray);
  font-weight: 600;
  font-size: 14px;
}

.movers-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.mover-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px;
  border-radius: var(--radius-sm);
  border: 2px solid var(--poke-ink);
}

.mover-row img {
  width: 44px;
  border-radius: 6px;
}

.mover-gainer {
  background: #eafcef;
}

.mover-loser {
  background: #ffeeee;
}

.mover-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  font-size: 13px;
  min-width: 0;
}

.mover-info span {
  color: var(--poke-gray);
}

.mover-delta {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 14px;
  white-space: nowrap;
}

.mover-delta-up {
  color: #1d9a4a;
}

.mover-delta-down {
  color: var(--poke-red-dark);
}

.treasures-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 16px;
  margin: 16px 0 32px;
}

.treasure-card {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: var(--poke-white);
  position: relative;
}

.treasure-grade {
  position: absolute;
  top: 8px;
  right: 8px;
  font-size: 10px;
  padding: 2px 8px;
}

.treasure-card img {
  width: 100%;
  border-radius: var(--radius-sm);
}

.treasure-info {
  display: flex;
  flex-direction: column;
  font-size: 13px;
}

.treasure-price {
  font-family: var(--font-display);
  font-weight: 700;
  color: var(--poke-blue);
}
</style>
