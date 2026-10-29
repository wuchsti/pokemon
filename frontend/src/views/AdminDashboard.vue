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

// --- "alle Kartendaten & Preise jetzt holen" ---
const warming = ref(false)
const warmError = ref('')
const warmResult = ref(null)
const warmDone = ref(false)

async function warmCache() {
  warming.value = true
  warmError.value = ''
  warmDone.value = false
  warmResult.value = null
  try {
    // Der Endpunkt ist zeitlich begrenzt (sicher innerhalb Webserver-
    // Timeouts) und meldet "done: false", solange noch was zu tun ist -
    // einfach wiederholt aufrufen, bis alles durch ist. Obergrenze als
    // Sicherheitsnetz, falls die externe API so instabil ist, dass pro
    // Aufruf kaum Fortschritt gemacht wird (z.B. viele Timeouts) - dann
    // einfach den Knopf erneut klicken, der Fortschritt bleibt erhalten
    // (bereits aktualisierte Sets/Preise werden beim nächsten Mal übersprungen).
    let done = false
    let iterations = 0
    while (!done && iterations < 30) {
      const result = await api.adminWarmCache()
      warmResult.value = result
      done = result.done
      iterations++
    }
    if (done) {
      warmDone.value = true
    } else {
      warmError.value = 'Noch nicht ganz fertig (die externe API ist gerade langsam) - bitte den Knopf noch einmal klicken, der bisherige Fortschritt bleibt erhalten.'
    }
  } catch (e) {
    warmError.value = e.message
  } finally {
    warming.value = false
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

    <div class="poke-panel warm-cache-panel">
      <h3 class="font-display">Kartendaten & Preise</h3>
      <p class="warm-cache-hint">
        Lädt Kartendaten für alle Sammlungen vorab in den Cache und synchronisiert Preise für alle
        erfassten Karten - danach kommt "Kartendaten nicht erreichbar" seltener vor, weil Nutzer aus
        dem Cache statt live von der (manchmal instabilen) externen API bedient werden. Kann bei
        vielen Sammlungen mehrere Durchläufe/Minuten brauchen.
      </p>
      <button class="btn btn-primary btn-sm" :disabled="warming" @click="warmCache">
        {{ warming ? 'Läuft...' : '🔄 Jetzt alle Kartendaten & Preise holen' }}
      </button>

      <div v-if="warmError" class="error-banner">{{ warmError }}</div>

      <div v-if="warmResult" class="warm-cache-result">
        <p>
          <strong>Sets:</strong>
          {{ warmResult.sets.updated }} aktualisiert · {{ warmResult.sets.skipped_fresh }} bereits aktuell ·
          {{ warmResult.sets.failed }} fehlgeschlagen
          <template v-if="!warmDone"> · {{ warmResult.sets.remaining }} folgen im nächsten Durchlauf...</template>
        </p>
        <p>
          <strong>Preise:</strong>
          {{ warmResult.prices.updated }} aktualisiert · {{ warmResult.prices.skipped }} ohne Cardmarket-Preis ·
          {{ warmResult.prices.failed }} fehlgeschlagen
          <template v-if="!warmDone"> · {{ warmResult.prices.remaining }} folgen im nächsten Durchlauf...</template>
        </p>
        <p v-if="warmDone" class="warm-cache-done">✓ Fertig.</p>
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

.warm-cache-panel {
  margin-top: 20px;
  padding: 16px;
}

.warm-cache-hint {
  font-size: 13px;
  color: var(--poke-gray);
  font-weight: 600;
  max-width: 640px;
  margin: 4px 0 12px;
}

.warm-cache-result {
  margin-top: 12px;
  font-size: 13px;
}

.warm-cache-result p {
  margin: 4px 0;
}

.warm-cache-done {
  font-weight: 700;
  color: #1d9a4a;
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
