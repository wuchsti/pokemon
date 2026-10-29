<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { api } from '../api/client'
import PokeballLoader from '../components/PokeballLoader.vue'
import CardModal from '../components/CardModal.vue'

const props = defineProps({
  storageType: { type: String, required: true },
})

const title = computed(() => (props.storageType === 'vault' ? 'Sammlung (Vault)' : 'Tausch-Binder'))

const allSets = ref([])
const ownedCards = ref([])
const loading = ref(true)
const error = ref('')
const savedMessage = ref('')
const searchQuery = ref('')
const allWishlistIds = ref(new Set())

// --- enlarged card preview (click a card image to read it properly) ---
const zoomedImage = ref(null)

function zoomImage(src, alt) {
  if (src) zoomedImage.value = { src, alt }
}

function closeZoom() {
  zoomedImage.value = null
}

function handleZoomKeydown(e) {
  if (e.key === 'Escape') closeZoom()
}

onMounted(() => window.addEventListener('keydown', handleZoomKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleZoomKeydown))

const ownedBySetId = computed(() => {
  const map = {}
  for (const card of ownedCards.value) {
    map[card.set_id] ??= []
    map[card.set_id].push(card)
  }
  return map
})

const setsWithProgress = computed(() => {
  return allSets.value.map((set) => {
    const owned = ownedBySetId.value[set.id] ?? []
    const ownedNumbers = new Set(owned.map((c) => c.card_number))
    const total = set.printedTotal ?? set.total ?? 0
    return {
      ...set,
      ownedCount: ownedNumbers.size,
      total,
      percent: total ? Math.min(100, Math.round((ownedNumbers.size / total) * 100)) : 0,
    }
  })
})

const showEmptySets = ref(false)

const filteredSets = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  let list = setsWithProgress.value
  if (!showEmptySets.value) {
    list = list.filter((s) => s.ownedCount > 0)
  }
  if (q) {
    list = list.filter((s) => s.name.toLowerCase().includes(q))
  }
  return list
})

function formatPrice(value) {
  return value === null || value === undefined ? '–' : `${Number(value).toFixed(2)} €`
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [setsResult, cardsResult, wishlistResult] = await Promise.all([
      api.listSets(),
      api.listUserCards({ storage_type: props.storageType }),
      api.listWishlist(),
    ])
    allSets.value = setsResult.sets
    ownedCards.value = cardsResult.cards
    allWishlistIds.value = new Set(wishlistResult.items.map((i) => i.card_api_id))
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function reloadOwnedCards() {
  try {
    const result = await api.listUserCards({ storage_type: props.storageType })
    ownedCards.value = result.cards
  } catch (e) {
    error.value = e.message
  }
}

// --- expand a set into its full card grid (lazy-loaded) ---
const expandedSetIds = reactive(new Set())
const setCardsCache = reactive({})
const wishlistCache = reactive({})
const loadingSetId = ref(null)

async function toggleExpand(set) {
  if (expandedSetIds.has(set.id)) {
    expandedSetIds.delete(set.id)
    return
  }
  expandedSetIds.add(set.id)
  if (setCardsCache[set.id]) return

  loadingSetId.value = set.id
  error.value = ''
  try {
    const [cardsResult, wishlistResult] = await Promise.all([
      api.getSetCards(set.id),
      api.listWishlist({ set_id: set.id }),
    ])
    setCardsCache[set.id] = cardsResult.cards
    wishlistCache[set.id] = new Set(wishlistResult.items.map((i) => i.card_api_id))
  } catch (e) {
    error.value = e.message
    expandedSetIds.delete(set.id)
  } finally {
    loadingSetId.value = null
  }
}

function tilesForSet(setId) {
  const official = setCardsCache[setId] ?? []
  const owned = ownedBySetId.value[setId] ?? []

  const ownedByNumber = {}
  for (const card of owned) {
    ownedByNumber[card.card_number] ??= []
    ownedByNumber[card.card_number].push(card)
  }

  const seenNumbers = new Set()
  const tiles = []
  for (const oc of official) {
    seenNumbers.add(oc.number)
    const instances = ownedByNumber[oc.number]
    if (instances?.length) {
      for (const instance of instances) {
        tiles.push({ kind: 'owned', price_eur: oc.price_eur, image_large: oc.image_large, ...instance })
      }
    } else {
      tiles.push({ kind: 'missing', ...oc })
    }
  }
  // Owned cards the official set list didn't include (rare API edge case) -
  // never hide a user's own card, just append it.
  for (const card of owned) {
    if (!seenNumbers.has(card.card_number)) {
      tiles.push({ kind: 'owned', ...card })
    }
  }
  return tiles
}

// --- owned card actions ---
async function removeCard(card) {
  if (!confirm(`"${card.card_name}" wirklich entfernen?`)) return
  try {
    await api.deleteUserCard(card.id)
    ownedCards.value = ownedCards.value.filter((c) => c.id !== card.id)
  } catch (e) {
    error.value = e.message
  }
}

async function toggleStorage(card) {
  const newType = card.storage_type === 'vault' ? 'trade_binder' : 'vault'
  try {
    await api.updateUserCard({ id: card.id, storage_type: newType })
    ownedCards.value = ownedCards.value.filter((c) => c.id !== card.id)
  } catch (e) {
    error.value = e.message
  }
}

// --- missing card actions ---
const selectedCard = ref(null)

function openAddModal(tile, set) {
  selectedCard.value = {
    id: tile.id,
    name: tile.name,
    number: tile.number,
    images: { small: tile.image_small },
    set: { id: set.id, name: set.name },
  }
}

async function onSaved() {
  savedMessage.value = `${selectedCard.value.name} wurde hinzugefügt!`
  selectedCard.value = null
  await reloadOwnedCards()
  setTimeout(() => (savedMessage.value = ''), 3000)
}

const addingWishlistId = ref(null)

async function addToWishlist(tile, set) {
  addingWishlistId.value = tile.id
  error.value = ''
  try {
    await api.addWishlistItem({
      card_api_id: tile.id,
      set_id: set.id,
      card_number: tile.number,
      card_name: tile.name,
      image_url: tile.image_small,
    })
    wishlistCache[set.id] = new Set([...(wishlistCache[set.id] ?? []), tile.id])
    allWishlistIds.value = new Set([...allWishlistIds.value, tile.id])
  } catch (e) {
    error.value = e.message
  } finally {
    addingWishlistId.value = null
  }
}

async function removeFromWishlist(tile, set) {
  addingWishlistId.value = tile.id
  error.value = ''
  try {
    await api.removeWishlistItem({ card_api_id: tile.id })
    const updated = new Set(wishlistCache[set.id] ?? [])
    updated.delete(tile.id)
    wishlistCache[set.id] = updated
    const updatedAll = new Set(allWishlistIds.value)
    updatedAll.delete(tile.id)
    allWishlistIds.value = updatedAll
  } catch (e) {
    error.value = e.message
  } finally {
    addingWishlistId.value = null
  }
}

// --- global card search (by name or collector number, across all sets) ---
const cardQuery = ref('')
const cardSearchLoading = ref(false)
const cardSearchError = ref('')
const cardSearchResults = ref([])
const cardSearchDone = ref(false)

async function searchCards() {
  const q = cardQuery.value.trim()
  if (!q) return
  cardSearchLoading.value = true
  cardSearchError.value = ''
  cardSearchDone.value = true
  try {
    const result = await api.searchCards({ q, pageSize: 24 })
    cardSearchResults.value = result.cards
  } catch (e) {
    cardSearchError.value = e.message
  } finally {
    cardSearchLoading.value = false
  }
}

function clearCardSearch() {
  cardQuery.value = ''
  cardSearchResults.value = []
  cardSearchDone.value = false
  cardSearchError.value = ''
}

function openAddModalFromSearch(card) {
  selectedCard.value = card
}

async function addToWishlistFromSearch(card) {
  addingWishlistId.value = card.id
  error.value = ''
  try {
    await api.addWishlistItem({
      card_api_id: card.id,
      set_id: card.set?.id ?? '',
      card_number: card.number,
      card_name: card.name,
      image_url: card.images?.small ?? '',
    })
    allWishlistIds.value = new Set([...allWishlistIds.value, card.id])
  } catch (e) {
    error.value = e.message
  } finally {
    addingWishlistId.value = null
  }
}

async function removeFromWishlistFromSearch(card) {
  addingWishlistId.value = card.id
  error.value = ''
  try {
    await api.removeWishlistItem({ card_api_id: card.id })
    const updated = new Set(allWishlistIds.value)
    updated.delete(card.id)
    allWishlistIds.value = updated
  } catch (e) {
    error.value = e.message
  } finally {
    addingWishlistId.value = null
  }
}

onMounted(load)
watch(() => props.storageType, load)
</script>

<template>
  <div>
    <h1 class="font-display">{{ title }}</h1>

    <div v-if="error" class="error-banner">{{ error }}</div>
    <div v-if="savedMessage" class="badge badge-trade saved-toast">{{ savedMessage }}</div>

    <div class="poke-panel card-search-panel">
      <h3 class="font-display card-search-title">Karte suchen</h3>
      <p class="card-search-hint">Nach Name oder Kartennummer (z.B. "Prinplup" oder "28/94") - über alle Sammlungen hinweg.</p>
      <form class="card-search-row" @submit.prevent="searchCards">
        <input v-model="cardQuery" type="text" placeholder="Name oder Nummer..." class="set-search" />
        <button type="submit" class="btn btn-primary btn-sm" :disabled="cardSearchLoading">
          {{ cardSearchLoading ? 'Suche...' : '🔍 Suchen' }}
        </button>
        <button v-if="cardSearchDone" type="button" class="btn btn-outline btn-sm" @click="clearCardSearch">
          Zurücksetzen
        </button>
      </form>

      <div v-if="cardSearchError" class="error-banner">{{ cardSearchError }}</div>

      <template v-if="cardSearchDone && !cardSearchLoading && !cardSearchError">
        <p v-if="cardSearchResults.length === 0" class="no-results">Keine Karten gefunden.</p>
        <div v-else class="card-grid card-search-grid">
          <div v-for="card in cardSearchResults" :key="card.id" class="card-tile poke-card">
            <img
              :src="card.images?.small"
              :alt="card.name"
              class="zoomable"
              @click="zoomImage(card.images?.large ?? card.images?.small, card.name)"
            />
            <div class="card-tile-info">
              <strong>{{ card.name }}</strong>
              <span>#{{ card.number }} · {{ card.set?.name }}</span>
              <span class="card-tile-price">{{ formatPrice(card.price_eur) }}</span>
              <div class="card-tile-actions">
                <button class="btn btn-accent btn-sm" @click="openAddModalFromSearch(card)">+ Hinzufügen</button>
                <button
                  class="btn btn-outline btn-sm"
                  :disabled="addingWishlistId === card.id"
                  @click="allWishlistIds.has(card.id) ? removeFromWishlistFromSearch(card) : addToWishlistFromSearch(card)"
                >
                  {{ allWishlistIds.has(card.id) ? '★ Entfernen' : '♡ Merken' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <div class="set-filter-row">
      <input v-model="searchQuery" type="text" placeholder="Sammlung filtern (nach Titel)..." class="set-search" />
      <label class="empty-sets-toggle">
        <input type="checkbox" v-model="showEmptySets" />
        Leere Sammlungen einblenden
      </label>
    </div>

    <PokeballLoader v-if="loading" />

    <div v-else class="sets-list">
      <div v-for="set in filteredSets" :key="set.id" class="poke-card set-row">
        <button type="button" class="set-row-head" @click="toggleExpand(set)">
          <img v-if="set.images?.symbol" :src="set.images.symbol" class="set-symbol" alt="" />
          <div class="set-row-info">
            <strong>{{ set.name }}</strong>
            <span class="set-row-meta">{{ set.series }} · {{ set.releaseDate }}</span>
          </div>
          <div class="set-row-progress">
            <div class="set-progress-track">
              <div class="set-progress-fill" :style="{ width: set.percent + '%' }"></div>
            </div>
            <span class="set-row-count">{{ set.ownedCount }} / {{ set.total }}</span>
          </div>
          <span class="set-row-chevron">{{ expandedSetIds.has(set.id) ? '▲' : '▼' }}</span>
        </button>

        <div v-if="expandedSetIds.has(set.id)" class="set-row-body">
          <PokeballLoader v-if="loadingSetId === set.id" />

          <div v-else class="card-grid">
            <div
              v-for="tile in tilesForSet(set.id)"
              :key="tile.kind + '-' + tile.id"
              class="card-tile poke-card"
              :class="{ 'card-tile-missing': tile.kind === 'missing' }"
            >
              <img
                :src="tile.kind === 'owned' ? tile.image_url : tile.image_small"
                :alt="tile.kind === 'owned' ? tile.card_name : tile.name"
                class="zoomable"
                @click="
                  zoomImage(
                    tile.kind === 'owned' ? (tile.image_large ?? tile.image_url) : (tile.image_large ?? tile.image_small),
                    tile.kind === 'owned' ? tile.card_name : tile.name
                  )
                "
              />
              <div class="card-tile-info">
                <strong>{{ tile.kind === 'owned' ? tile.card_name : tile.name }}</strong>
                <span>
                  #{{ tile.kind === 'owned' ? tile.card_number : tile.number }}
                  <template v-if="tile.kind === 'owned'"> · {{ tile.language }}</template>
                </span>
                <span class="card-tile-price">{{ formatPrice(tile.price_eur) }}</span>

                <template v-if="tile.kind === 'owned'">
                  <div class="card-tile-badges">
                    <span class="badge" :class="props.storageType === 'vault' ? 'badge-vault' : 'badge-trade'">
                      {{ tile.card_condition }}
                    </span>
                    <span v-if="tile.is_graded" class="badge badge-graded">
                      {{ tile.grading_company }} {{ tile.grading_score }}
                    </span>
                  </div>
                  <div class="card-tile-actions">
                    <button class="btn btn-outline btn-sm" @click="toggleStorage(tile)">
                      {{ tile.storage_type === 'vault' ? '→ Tausch' : '→ Vault' }}
                    </button>
                    <button class="btn btn-primary btn-sm" @click="removeCard(tile)">Entfernen</button>
                  </div>
                </template>

                <template v-else>
                  <div class="card-tile-actions">
                    <button class="btn btn-accent btn-sm" @click="openAddModal(tile, set)">+ Hinzufügen</button>
                    <button
                      class="btn btn-outline btn-sm"
                      :disabled="addingWishlistId === tile.id"
                      @click="wishlistCache[set.id]?.has(tile.id) ? removeFromWishlist(tile, set) : addToWishlist(tile, set)"
                    >
                      {{ wishlistCache[set.id]?.has(tile.id) ? '★ Entfernen' : '♡ Merken' }}
                    </button>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <CardModal
      v-if="selectedCard"
      :card="selectedCard"
      :default-storage-type="storageType"
      @close="selectedCard = null"
      @saved="onSaved"
    />

    <div v-if="zoomedImage" class="zoom-backdrop" @click.self="closeZoom">
      <button type="button" class="zoom-close" aria-label="Schließen" @click="closeZoom">✕</button>
      <img :src="zoomedImage.src" :alt="zoomedImage.alt" class="zoom-image" />
    </div>
  </div>
</template>

<style scoped>
.set-search {
  width: 100%;
  max-width: 360px;
  font-family: var(--font-body);
  font-size: 14px;
  padding: 10px 12px;
  border: var(--border-comic);
  border-radius: var(--radius-sm);
  margin: 16px 0 20px;
}

.card-search-panel {
  margin: 16px 0 20px;
  padding: 16px;
}

.card-search-title {
  margin: 0 0 4px;
}

.card-search-hint {
  margin: 0 0 12px;
  font-size: 13px;
  color: var(--poke-gray);
  font-weight: 600;
}

.card-search-row {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.card-search-row .set-search {
  margin: 0;
  flex: 1;
  min-width: 200px;
}

.card-search-grid {
  margin-top: 16px;
}

.no-results {
  margin-top: 12px;
  font-weight: 600;
  color: var(--poke-gray);
}

.card-tile-price {
  font-weight: 700;
  color: var(--poke-blue);
}

.set-filter-row {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  margin: 0 0 20px;
}

.set-filter-row .set-search {
  margin: 0;
}

.empty-sets-toggle {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}

.saved-toast {
  display: block;
  width: fit-content;
  margin-bottom: 16px;
  padding: 8px 16px;
  font-size: 14px;
}

.sets-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.set-row {
  padding: 0;
  overflow: hidden;
}

.set-row-head {
  width: 100%;
  background: var(--poke-white);
  border: none;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  font-family: var(--font-body);
  color: var(--poke-ink);
  text-align: left;
}

.set-symbol {
  width: 24px;
  height: 24px;
  object-fit: contain;
  flex-shrink: 0;
}

.set-row-info {
  display: flex;
  flex-direction: column;
  min-width: 160px;
  flex: 1;
}

.set-row-meta {
  font-size: 12px;
  color: var(--poke-gray);
  font-weight: 600;
}

.set-row-progress {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 160px;
}

.set-progress-track {
  width: 100px;
  height: 10px;
  background: #e9ecf3;
  border: 2px solid var(--poke-ink);
  border-radius: 999px;
  overflow: hidden;
}

.set-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--poke-yellow), var(--poke-red));
}

.set-row-count {
  font-size: 12px;
  font-weight: 700;
  color: var(--poke-blue);
  white-space: nowrap;
}

.set-row-chevron {
  color: var(--poke-gray);
  font-size: 12px;
}

.set-row-body {
  border-top: 2px solid var(--poke-ink);
  padding: 16px;
  background: var(--poke-bg);
}

.card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 16px;
}

.card-tile {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: var(--poke-white);
}

.card-tile img {
  width: 100%;
  border-radius: var(--radius-sm);
}

.zoomable {
  cursor: zoom-in;
}

.zoom-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  z-index: 1000;
}

.zoom-image {
  max-width: min(94vw, 720px);
  max-height: 92vh;
  border-radius: var(--radius-md);
  border: var(--border-comic);
  box-shadow: 6px 6px 0 rgba(0, 0, 0, 0.5);
}

.zoom-close {
  position: absolute;
  top: 20px;
  right: 24px;
  background: var(--poke-white);
  border: var(--border-comic);
  border-radius: 999px;
  width: 36px;
  height: 36px;
  font-size: 16px;
  cursor: pointer;
}

.card-tile-missing img {
  opacity: 0.55;
  filter: grayscale(60%);
}

.card-tile-missing {
  background: #f3f3f3;
}

.card-tile-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 13px;
}

.card-tile-info span {
  color: var(--poke-gray);
}

.card-tile-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.card-tile-actions {
  display: flex;
  gap: 6px;
  margin-top: 4px;
  flex-wrap: wrap;
}
</style>
