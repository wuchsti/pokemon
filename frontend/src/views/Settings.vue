<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '../api/client'
import { useAuthStore } from '../stores/auth'
import PokeballLoader from '../components/PokeballLoader.vue'

const auth = useAuthStore()
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const publicBinderEnabled = ref(false)
const copied = ref(false)
const user = ref(null)

const binderLink = computed(() => {
  if (!user.value) return ''
  return `${window.location.origin}/user/${user.value.username}/binder`
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const result = await api.me()
    user.value = result.user
    publicBinderEnabled.value = !!result.user.public_binder_enabled
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function toggleBinder() {
  const next = !publicBinderEnabled.value
  saving.value = true
  error.value = ''
  try {
    const result = await api.updateSettings({ public_binder_enabled: next })
    publicBinderEnabled.value = !!result.user.public_binder_enabled
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

async function copyLink() {
  try {
    await navigator.clipboard.writeText(binderLink.value)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  } catch {
    error.value = 'Link konnte nicht automatisch kopiert werden. Bitte manuell markieren.'
  }
}

onMounted(load)
</script>

<template>
  <div>
    <h1 class="font-display">Einstellungen</h1>

    <div v-if="error" class="error-banner">{{ error }}</div>
    <PokeballLoader v-if="loading" />

    <template v-else>
      <section class="poke-panel settings-panel">
        <h2 class="font-display panel-title">Konto</h2>
        <div class="field">
          <label>Benutzername</label>
          <input :value="user.username" disabled />
        </div>
        <div class="field">
          <label>E-Mail</label>
          <input :value="user.email" disabled />
        </div>
      </section>

      <section class="poke-panel settings-panel">
        <h2 class="font-display panel-title">Öffentlicher Tausch-Binder-Link</h2>
        <p class="settings-hint">
          Wenn aktiviert, kann jeder mit dem Link unten deinen Tausch-Binder
          einsehen (Kartenname, Nummer, Zustand, Sprache, Grading). Deine
          E-Mail-Adresse, dein Vault und Kartenwerte bleiben dabei immer privat.
        </p>

        <label class="toggle-row">
          <input type="checkbox" :checked="publicBinderEnabled" :disabled="saving" @change="toggleBinder" />
          <span>Tausch-Binder öffentlich teilen</span>
        </label>

        <div v-if="publicBinderEnabled" class="binder-link-box">
          <code class="binder-link">{{ binderLink }}</code>
          <button class="btn btn-accent btn-sm" @click="copyLink">{{ copied ? '✓ Kopiert' : 'Kopieren' }}</button>
          <router-link :to="`/user/${user.username}/binder`" class="btn btn-outline btn-sm" target="_blank">
            Ansehen
          </router-link>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.settings-panel {
  max-width: 560px;
  margin-bottom: 20px;
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

.toggle-row {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  cursor: pointer;
  margin: 12px 0;
}

.toggle-row input {
  width: 20px;
  height: 20px;
}

.binder-link-box {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  background: var(--poke-bg);
  border: 2px dashed var(--poke-gray);
  border-radius: var(--radius-sm);
  padding: 10px;
  margin-top: 8px;
}

.binder-link {
  flex: 1;
  min-width: 180px;
  font-size: 13px;
  word-break: break-all;
}
</style>
