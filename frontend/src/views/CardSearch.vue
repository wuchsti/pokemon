<script setup>
import { ref } from 'vue'
import { api } from '../api/client'
import PokeballLoader from '../components/PokeballLoader.vue'
import CardModal from '../components/CardModal.vue'

const query = ref('')
const cards = ref([])
const loading = ref(false)
const error = ref('')
const searched = ref(false)
const selectedCard = ref(null)
const savedMessage = ref('')

async function search() {
  if (!query.value.trim()) return
  error.value = ''
  savedMessage.value = ''
  loading.value = true
  searched.value = true
  try {
    const result = await api.searchCards({ q: query.value.trim(), pageSize: 24 })
    cards.value = result.cards
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function onSaved() {
  savedMessage.value = `${selectedCard.value.name} wurde deiner Sammlung hinzugefügt!`
  selectedCard.value = null
}
</script>

<template>
  <div>
    <h1 class="font-display">Karten suchen</h1>

    <form class="search-bar" @submit.prevent="search">
      <input
        v-model="query"
        type="text"
        placeholder="z. B. Pikachu, Charizard..."
        class="search-input"
      />
      <button class="btn btn-primary" type="submit" :disabled="loading">Suchen</button>
    </form>

    <div v-if="savedMessage" class="badge badge-trade saved-toast">{{ savedMessage }}</div>
    <div v-if="error" class="error-banner">{{ error }}</div>

    <PokeballLoader v-if="loading" />

    <p v-else-if="searched && cards.length === 0">Keine Karten gefunden.</p>

    <div v-else class="card-grid">
      <button v-for="card in cards" :key="card.id" class="card-tile poke-card" @click="selectedCard = card">
        <img :src="card.images?.small" :alt="card.name" />
        <div class="card-tile-info">
          <strong>{{ card.name }}</strong>
          <span>{{ card.set?.name }} · #{{ card.number }}</span>
        </div>
      </button>
    </div>

    <CardModal v-if="selectedCard" :card="selectedCard" @close="selectedCard = null" @saved="onSaved" />
  </div>
</template>

<style scoped>
.search-bar {
  display: flex;
  gap: 10px;
  margin: 20px 0 24px;
  max-width: 480px;
}

.search-input {
  flex: 1;
  font-family: var(--font-body);
  font-size: 15px;
  padding: 10px 14px;
  border: var(--border-comic);
  border-radius: var(--radius-sm);
}

.saved-toast {
  display: block;
  width: fit-content;
  margin-bottom: 16px;
  padding: 8px 16px;
  font-size: 14px;
}

.card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 16px;
}

.card-tile {
  padding: 12px;
  text-align: left;
  cursor: pointer;
  background: var(--poke-white);
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.card-tile:hover {
  transform: translateY(-2px);
}

.card-tile img {
  width: 100%;
  border-radius: var(--radius-sm);
}

.card-tile-info {
  display: flex;
  flex-direction: column;
  font-size: 13px;
}

.card-tile-info span {
  color: var(--poke-gray);
}
</style>
