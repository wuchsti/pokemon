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
</style>
