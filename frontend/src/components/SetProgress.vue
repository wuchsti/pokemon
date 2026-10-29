<script setup>
import { computed, ref } from 'vue'
import { api } from '../api/client'
import PokeballLoader from './PokeballLoader.vue'

const props = defineProps({
  setId: { type: String, default: null },
  setName: { type: String, required: true },
  owned: { type: Number, required: true },
  total: { type: Number, required: true },
  ownedNumbers: { type: Array, default: null }, // card_number strings the user already owns in this set
})

const percent = computed(() => {
  if (!props.total) return 0
  return Math.min(100, Math.round((props.owned / props.total) * 100))
})

const canShowMissing = computed(() => !!props.setId && Array.isArray(props.ownedNumbers) && props.total > 0)

const expanded = ref(false)
const loadingMissing = ref(false)
const error = ref('')
const allSetCards = ref([])
const wishlistedIds = ref(new Set())
const addingId = ref(null)

const missingCards = computed(() => {
  const owned = new Set(props.ownedNumbers ?? [])
  return allSetCards.value.filter((card) => !owned.has(card.number))
})

async function toggleMissing() {
  expanded.value = !expanded.value
  if (expanded.value && allSetCards.value.length === 0) {
    await loadMissing()
  }
}

async function loadMissing() {
  loadingMissing.value = true
  error.value = ''
  try {
    const [setCardsResult, wishlistResult] = await Promise.all([
      api.getSetCards(props.setId),
      api.listWishlist({ set_id: props.setId }),
    ])
    allSetCards.value = setCardsResult.cards
    wishlistedIds.value = new Set(wishlistResult.items.map((item) => item.card_api_id))
  } catch (e) {
    error.value = e.message
  } finally {
    loadingMissing.value = false
  }
}

async function addToWishlist(card) {
  addingId.value = card.id
  error.value = ''
  try {
    await api.addWishlistItem({
      card_api_id: card.id,
      set_id: props.setId,
      card_number: card.number,
      card_name: card.name,
      image_url: card.image_small,
    })
    wishlistedIds.value = new Set([...wishlistedIds.value, card.id])
  } catch (e) {
    error.value = e.message
  } finally {
    addingId.value = null
  }
}
</script>

<template>
  <div class="set-progress">
    <div class="set-progress-head">
      <span class="font-display set-progress-name">{{ setName }}</span>
      <span class="set-progress-count">{{ owned }} / {{ total }} Karten</span>
    </div>
    <div class="set-progress-track">
      <div class="set-progress-fill" :style="{ width: percent + '%' }">
        <span class="set-progress-ball" v-if="percent > 0 && percent < 100"></span>
      </div>
    </div>
    <div class="set-progress-foot">
      <button v-if="canShowMissing && percent < 100" class="missing-toggle" @click="toggleMissing">
        {{ expanded ? 'Fehlende Karten ausblenden' : 'Fehlende Karten anzeigen' }}
      </button>
      <span v-else></span>
      <span class="set-progress-percent">{{ percent }}%</span>
    </div>

    <div v-if="expanded" class="missing-panel">
      <div v-if="error" class="error-banner">{{ error }}</div>
      <PokeballLoader v-if="loadingMissing" />
      <p v-else-if="missingCards.length === 0" class="missing-complete">Alle Karten dieses Sets vorhanden! 🎉</p>
      <div v-else class="missing-grid">
        <div v-for="card in missingCards" :key="card.id" class="missing-card">
          <img :src="card.image_small" :alt="card.name" loading="lazy" />
          <span class="missing-card-name">#{{ card.number }} {{ card.name }}</span>
          <button
            class="btn btn-sm"
            :class="wishlistedIds.has(card.id) ? 'btn-outline' : 'btn-accent'"
            :disabled="wishlistedIds.has(card.id) || addingId === card.id"
            @click="addToWishlist(card)"
          >
            {{ wishlistedIds.has(card.id) ? '★ Auf Wunschliste' : '+ Wunschliste' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.set-progress {
  margin-bottom: 18px;
}

.set-progress-head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 6px;
}

.set-progress-name {
  font-size: 16px;
}

.set-progress-count {
  font-size: 13px;
  font-weight: 700;
  color: var(--poke-gray);
}

.set-progress-track {
  height: 16px;
  background: #e9ecf3;
  border: 2px solid var(--poke-ink);
  border-radius: 999px;
  overflow: hidden;
  position: relative;
}

.set-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--poke-yellow), var(--poke-red));
  border-radius: 999px;
  position: relative;
  transition: width 0.4s ease;
}

.set-progress-ball {
  position: absolute;
  right: -2px;
  top: 50%;
  width: 14px;
  height: 14px;
  transform: translateY(-50%);
  background: var(--poke-white);
  border: 2px solid var(--poke-ink);
  border-radius: 50%;
}

.set-progress-foot {
  margin-top: 4px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.missing-toggle {
  background: none;
  border: none;
  color: var(--poke-blue);
  font-weight: 700;
  font-size: 12px;
  cursor: pointer;
  padding: 0;
}

.set-progress-percent {
  font-size: 12px;
  font-weight: 700;
  color: var(--poke-blue);
}

.missing-panel {
  margin-top: 12px;
  padding: 12px;
  background: var(--poke-bg);
  border: 2px dashed var(--poke-gray);
  border-radius: var(--radius-sm);
}

.missing-complete {
  font-weight: 700;
  color: #1d9a4a;
  margin: 4px 0;
}

.missing-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 12px;
}

.missing-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  text-align: center;
  background: var(--poke-white);
  border: 2px solid var(--poke-ink);
  border-radius: var(--radius-sm);
  padding: 8px;
}

.missing-card img {
  width: 100%;
  border-radius: 6px;
  opacity: 0.6;
  filter: grayscale(40%);
}

.missing-card-name {
  font-size: 11px;
  font-weight: 700;
  line-height: 1.3;
}
</style>
