<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
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

const emailForm = reactive({ new_email: '', current_password: '' })
const emailSaving = ref(false)
const emailError = ref('')
const emailSuccess = ref('')

async function changeEmail() {
  emailSaving.value = true
  emailError.value = ''
  emailSuccess.value = ''
  try {
    const result = await api.changeEmail(emailForm)
    user.value.email = result.email
    if (auth.user) auth.user.email = result.email
    emailSuccess.value = 'E-Mail-Adresse geändert ✓'
    emailForm.new_email = ''
    emailForm.current_password = ''
  } catch (e) {
    emailError.value = e.message
  } finally {
    emailSaving.value = false
  }
}

const passwordForm = reactive({ current_password: '', new_password: '', new_password_confirm: '' })
const passwordSaving = ref(false)
const passwordError = ref('')
const passwordSuccess = ref('')

async function changePassword() {
  passwordSaving.value = true
  passwordError.value = ''
  passwordSuccess.value = ''
  try {
    await api.changePassword(passwordForm)
    passwordSuccess.value = 'Passwort geändert ✓'
    passwordForm.current_password = ''
    passwordForm.new_password = ''
    passwordForm.new_password_confirm = ''
  } catch (e) {
    passwordError.value = e.message
  } finally {
    passwordSaving.value = false
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
          <label>Aktuelle E-Mail</label>
          <input :value="user.email" disabled />
        </div>
      </section>

      <section class="poke-panel settings-panel">
        <h2 class="font-display panel-title">E-Mail-Adresse ändern</h2>

        <div v-if="emailError" class="error-banner">{{ emailError }}</div>
        <p v-if="emailSuccess" class="success-note">{{ emailSuccess }}</p>

        <form @submit.prevent="changeEmail">
          <div class="field">
            <label>Neue E-Mail-Adresse</label>
            <input v-model="emailForm.new_email" type="email" required autocomplete="email" />
          </div>
          <div class="field">
            <label>Aktuelles Passwort (zur Bestätigung)</label>
            <input v-model="emailForm.current_password" type="password" required autocomplete="current-password" />
          </div>
          <button class="btn btn-primary" :disabled="emailSaving" type="submit">
            {{ emailSaving ? 'Speichert...' : 'E-Mail-Adresse ändern' }}
          </button>
        </form>
      </section>

      <section class="poke-panel settings-panel">
        <h2 class="font-display panel-title">Passwort ändern</h2>

        <div v-if="passwordError" class="error-banner">{{ passwordError }}</div>
        <p v-if="passwordSuccess" class="success-note">{{ passwordSuccess }}</p>

        <form @submit.prevent="changePassword">
          <div class="field">
            <label>Aktuelles Passwort</label>
            <input v-model="passwordForm.current_password" type="password" required autocomplete="current-password" />
          </div>
          <div class="field">
            <label>Neues Passwort (min. 8 Zeichen)</label>
            <input v-model="passwordForm.new_password" type="password" required minlength="8" autocomplete="new-password" />
          </div>
          <div class="field">
            <label>Neues Passwort bestätigen</label>
            <input v-model="passwordForm.new_password_confirm" type="password" required minlength="8" autocomplete="new-password" />
          </div>
          <button class="btn btn-primary" :disabled="passwordSaving" type="submit">
            {{ passwordSaving ? 'Speichert...' : 'Passwort ändern' }}
          </button>
        </form>
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

.success-note {
  font-weight: 700;
  color: #1d9a4a;
}
</style>
