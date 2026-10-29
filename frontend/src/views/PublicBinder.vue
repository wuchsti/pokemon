<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api/client'
import { useAuthStore } from '../stores/auth'
import PokeballLoader from '../components/PokeballLoader.vue'

const props = defineProps({
  username: { type: String, required: true },
})

const router = useRouter()
const auth = useAuthStore()

const cards = ref([])
const loading = ref(true)
const notFound = ref(false)
const error = ref('')
const startingTrade = ref(false)

const canProposeTrade = computed(() => auth.isAuthenticated && auth.user?.username !== props.username)

const groupedBySet = computed(() => {
  const groups = {}
  for (const card of cards.value) {
    if (!groups[card.set_id]) groups[card.set_id] = []
    groups[card.set_id].push(card)
  }
  return Object.entries(groups).sort((a, b) => a[0].localeCompare(b[0]))
})

async function load() {
  loading.value = true
  notFound.value = false
  try {
    const result = await api.publicBinder(props.username)
    cards.value = result.cards
  } catch {
    notFound.value = true
  } finally {
    loading.value = false
  }
}

async function proposeTrade() {
  startingTrade.value = true
  error.value = ''
  try {
    const { trade } = await api.createTrade({ partner_username: props.username })
    router.push(`/trades/${trade.id}`)
  } catch (e) {
    error.value = e.message
  } finally {
    startingTrade.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <PokeballLoader v-if="loading" />

    <div v-else-if="notFound" class="poke-panel not-found">
      <h1 class="font-display">Nicht gefunden</h1>
      <p>Dieser Tausch-Binder ist nicht öffentlich oder existiert nicht.</p>
    </div>

    <template v-else>
      <h1 class="font-display">Tausch-Binder von {{ username }}</h1>
      <p class="subtitle">
        Interessiert an einem Tausch? Diese Karten stehen zum Tausch bereit.
      </p>

      <div v-if="error" class="error-banner">{{ error }}</div>

      <button v-if="canProposeTrade" class="btn btn-primary propose-btn" :disabled="startingTrade" @click="proposeTrade">
        💬 Tausch mit {{ username }} vorschlagen
      </button>
      <p v-else-if="!auth.isAuthenticated" class="login-hint">
        <router-link to="/login">Melde dich an</router-link>, um {{ username }} einen Tausch vorzuschlagen.
      </p>

      <p v-if="cards.length === 0">Dieser Tausch-Binder ist aktuell leer.</p>

      <div v-else>
        <section v-for="[setId, setCards] in groupedBySet" :key="setId" class="set-group">
          <h2 class="font-display set-heading">{{ setId }} <span>({{ setCards.length }})</span></h2>
          <div class="card-grid">
            <div v-for="card in setCards" :key="card.card_api_id" class="card-tile poke-card">
              <img :src="card.image_url" :alt="card.card_name" />
              <div class="card-tile-info">
                <strong>{{ card.card_name }}</strong>
                <span>#{{ card.card_number }} · {{ card.language }}</span>
                <div class="card-tile-badges">
                  <span class="badge badge-trade">{{ card.card_condition }}</span>
                  <span v-if="card.is_graded" class="badge badge-graded">
                    {{ card.grading_company }} {{ card.grading_score }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <footer class="public-footer">
        Verwalte deine eigene Sammlung mit <router-link to="/register">PokéVault</router-link>.
      </footer>
    </template>
  </div>
</template>

<style scoped>
.not-found {
  max-width: 420px;
  margin-top: 40px;
}

.subtitle {
  color: var(--poke-gray);
  font-weight: 600;
  margin-top: -6px;
}

.propose-btn {
  margin-bottom: 20px;
}

.login-hint {
  font-weight: 600;
  margin-bottom: 20px;
}

.set-group {
  margin-bottom: 32px;
}

.set-heading {
  font-size: 18px;
}

.set-heading span {
  color: var(--poke-gray);
  font-size: 14px;
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

.public-footer {
  margin-top: 32px;
  text-align: center;
  font-weight: 700;
  color: var(--poke-gray);
}
</style>
