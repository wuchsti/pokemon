<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { api } from '../api/client'
import PokeballLoader from '../components/PokeballLoader.vue'
import SetProgress from '../components/SetProgress.vue'

const props = defineProps({
  storageType: { type: String, required: true },
})

const cards = ref([])
const setTotals = ref({})
const loading = ref(true)
const error = ref('')

const title = computed(() => (props.storageType === 'vault' ? 'Sammlung (Vault)' : 'Tausch-Binder'))

const groupedBySet = computed(() => {
  const groups = {}
  for (const card of cards.value) {
    if (!groups[card.set_id]) {
      groups[card.set_id] = { setId: card.set_id, cards: [] }
    }
    groups[card.set_id].cards.push(card)
  }
  return Object.values(groups).sort((a, b) => a.setId.localeCompare(b.setId))
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const result = await api.listUserCards({ storage_type: props.storageType })
    cards.value = result.cards
    await loadSetTotals()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function loadSetTotals() {
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
}

async function removeCard(card) {
  if (!confirm(`"${card.card_name}" wirklich entfernen?`)) return
  try {
    await api.deleteUserCard(card.id)
    cards.value = cards.value.filter((c) => c.id !== card.id)
  } catch (e) {
    error.value = e.message
  }
}

async function toggleStorage(card) {
  const newType = card.storage_type === 'vault' ? 'trade_binder' : 'vault'
  try {
    await api.updateUserCard({ id: card.id, storage_type: newType })
    cards.value = cards.value.filter((c) => c.id !== card.id)
  } catch (e) {
    error.value = e.message
  }
}

onMounted(load)
watch(() => props.storageType, load)
</script>

<template>
  <div>
    <h1 class="font-display">{{ title }}</h1>

    <div v-if="error" class="error-banner">{{ error }}</div>

    <PokeballLoader v-if="loading" />

    <p v-else-if="cards.length === 0">Noch keine Karten hier. Nutze die Kartensuche, um welche hinzuzufügen!</p>

    <div v-else>
      <section v-for="group in groupedBySet" :key="group.setId" class="set-group">
        <SetProgress
          :set-id="group.setId"
          :set-name="setTotals[group.setId]?.name ?? group.setId"
          :owned="group.cards.length"
          :total="setTotals[group.setId]?.total ?? group.cards.length"
          :owned-numbers="group.cards.map((c) => c.card_number)"
        />

        <div class="card-grid">
          <div v-for="card in group.cards" :key="card.id" class="card-tile poke-card">
            <img :src="card.image_url" :alt="card.card_name" />
            <div class="card-tile-info">
              <strong>{{ card.card_name }}</strong>
              <span>#{{ card.card_number }} · {{ card.language }}</span>
              <div class="card-tile-badges">
                <span class="badge" :class="props.storageType === 'vault' ? 'badge-vault' : 'badge-trade'">
                  {{ card.card_condition }}
                </span>
                <span v-if="card.is_graded" class="badge badge-graded">
                  {{ card.grading_company }} {{ card.grading_score }}
                </span>
              </div>
              <div class="card-tile-actions">
                <button class="btn btn-outline btn-sm" @click="toggleStorage(card)">
                  {{ card.storage_type === 'vault' ? '→ Tausch' : '→ Vault' }}
                </button>
                <button class="btn btn-primary btn-sm" @click="removeCard(card)">Entfernen</button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.set-group {
  margin-bottom: 36px;
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
}
</style>
