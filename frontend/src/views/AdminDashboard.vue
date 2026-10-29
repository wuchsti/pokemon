<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../api/client'
import AdminNav from '../components/AdminNav.vue'
import PokeballLoader from '../components/PokeballLoader.vue'

const stats = ref(null)
const loading = ref(true)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    stats.value = await api.adminStats()
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

    <div v-if="error" class="error-banner">{{ error }}</div>
    <PokeballLoader v-if="loading" />

    <div v-else-if="stats" class="stats-grid">
      <div class="poke-panel stat-card">
        <span class="stat-value">{{ stats.users.total }}</span>
        <span class="stat-label">Nutzer gesamt</span>
        <span class="stat-detail">{{ stats.users.active }} aktiv · {{ stats.users.blocked }} gesperrt · {{ stats.users.admins }} Admin(s)</span>
      </div>

      <div class="poke-panel stat-card">
        <span class="stat-value">{{ stats.cards.total }}</span>
        <span class="stat-label">Karten erfasst</span>
        <span class="stat-detail">{{ stats.cards.vault }} im Vault · {{ stats.cards.trade_binder }} im Tausch-Binder</span>
      </div>

      <div class="poke-panel stat-card">
        <span class="stat-value">{{ stats.trades.completed }}</span>
        <span class="stat-label">Abgeschlossene Tausche</span>
        <span class="stat-detail">{{ stats.trades.open }} offen · {{ stats.trades.cancelled }} abgebrochen · {{ stats.trades.total }} insgesamt</span>
      </div>

      <div class="poke-panel stat-card">
        <span class="stat-value">{{ stats.scans.total }}</span>
        <span class="stat-label">Gescannte Karten</span>
        <span class="stat-detail">{{ stats.scans.matched }} davon automatisch zugeordnet</span>
      </div>

      <div class="poke-panel stat-card">
        <span class="stat-value">{{ stats.wishlist_items_total }}</span>
        <span class="stat-label">Wunschlisten-Einträge</span>
      </div>

      <div class="poke-panel stat-card">
        <span class="stat-value">{{ stats.public_binders_total }}</span>
        <span class="stat-label">Öffentliche Tausch-Binder</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}

.stat-card {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-value {
  font-family: var(--font-display);
  font-size: 30px;
  font-weight: 700;
  color: var(--poke-blue);
}

.stat-label {
  font-weight: 700;
  font-size: 14px;
}

.stat-detail {
  font-size: 12px;
  color: var(--poke-gray);
}
</style>
