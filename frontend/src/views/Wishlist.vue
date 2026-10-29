<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '../api/client'
import PokeballLoader from '../components/PokeballLoader.vue'

const items = ref([])
const loading = ref(true)
const error = ref('')
const removingId = ref(null)

const groupedBySet = computed(() => {
  const groups = {}
  for (const item of items.value) {
    if (!groups[item.set_id]) groups[item.set_id] = []
    groups[item.set_id].push(item)
  }
  return Object.entries(groups).sort((a, b) => a[0].localeCompare(b[0]))
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const result = await api.listWishlist()
    items.value = result.items
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function remove(item) {
  removingId.value = item.id
  try {
    await api.removeWishlistItem({ id: item.id })
    items.value = items.value.filter((i) => i.id !== item.id)
  } catch (e) {
    error.value = e.message
  } finally {
    removingId.value = null
  }
}

const traders = ref(null)
const loadingTraders = ref(false)
const tradersError = ref('')

async function searchTraders() {
  loadingTraders.value = true
  tradersError.value = ''
  try {
    const result = await api.findTraders()
    traders.value = result.traders
  } catch (e) {
    tradersError.value = e.message
  } finally {
    loadingTraders.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <h1 class="font-display">Wunschliste</h1>
    <p class="subtitle">
      Karten, die du dir aus der Set-Fortschrittsanzeige gemerkt hast. Praktisch für den
      Abgleich beim nächsten Tausch oder Kartenladen-Besuch.
    </p>

    <div v-if="error" class="error-banner">{{ error }}</div>

    <PokeballLoader v-if="loading" />

    <p v-else-if="items.length === 0">
      Deine Wunschliste ist leer. Öffne "Fehlende Karten anzeigen" bei einem Set in deiner
      Sammlung, um Karten hier zu sammeln.
    </p>

    <div v-else>
      <section v-for="[setId, setItems] in groupedBySet" :key="setId" class="wishlist-group">
        <h2 class="font-display set-heading">{{ setId }} <span>({{ setItems.length }})</span></h2>
        <div class="wishlist-grid">
          <div v-for="item in setItems" :key="item.id" class="wishlist-card poke-card">
            <img :src="item.image_url" :alt="item.card_name" />
            <div class="wishlist-info">
              <strong>{{ item.card_name }}</strong>
              <span>#{{ item.card_number }}</span>
            </div>
            <button
              class="btn btn-outline btn-sm"
              :disabled="removingId === item.id"
              @click="remove(item)"
            >
              Entfernen
            </button>
          </div>
        </div>
      </section>
    </div>

    <section v-if="items.length > 0" class="matchmaking poke-panel">
      <h2 class="font-display panel-title">Tauschpartner finden</h2>
      <p class="settings-hint">
        Vergleicht deine Wunschliste mit den öffentlichen Tausch-Bindern anderer
        PokéVault-Nutzer.
      </p>

      <button class="btn btn-accent" :disabled="loadingTraders" @click="searchTraders">
        {{ loadingTraders ? 'Suche läuft...' : '🔍 Tauschpartner suchen' }}
      </button>

      <div v-if="tradersError" class="error-banner">{{ tradersError }}</div>
      <PokeballLoader v-if="loadingTraders" />

      <p v-else-if="traders && traders.length === 0" class="no-traders">
        Noch niemand mit öffentlichem Tausch-Binder hat eine deiner gesuchten Karten.
      </p>

      <div v-else-if="traders" class="traders-list">
        <div v-for="trader in traders" :key="trader.username" class="trader-card poke-card">
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
          <router-link :to="`/user/${trader.username}/binder`" class="btn btn-outline btn-sm" target="_blank">
            Tausch-Binder ansehen
          </router-link>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.subtitle {
  color: var(--poke-gray);
  font-weight: 600;
  max-width: 560px;
  margin-top: -6px;
}

.wishlist-group {
  margin-bottom: 32px;
}

.set-heading {
  font-size: 18px;
}

.set-heading span {
  color: var(--poke-gray);
  font-size: 14px;
}

.wishlist-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 16px;
}

.wishlist-card {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: var(--poke-white);
}

.wishlist-card img {
  width: 100%;
  border-radius: var(--radius-sm);
}

.wishlist-info {
  display: flex;
  flex-direction: column;
  font-size: 13px;
}

.wishlist-info span {
  color: var(--poke-gray);
}

.matchmaking {
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
</style>
