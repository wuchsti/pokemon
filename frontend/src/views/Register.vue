<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = reactive({ username: '', email: '', password: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.register(form)
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-screen">
    <form class="poke-panel auth-form" @submit.prevent="submit">
      <h1 class="font-display auth-title">
        <span class="auth-ball"></span>
        Konto erstellen
      </h1>

      <div v-if="error" class="error-banner">{{ error }}</div>

      <div class="field">
        <label>Benutzername</label>
        <input v-model="form.username" type="text" required minlength="3" maxlength="50" autocomplete="username" />
      </div>

      <div class="field">
        <label>E-Mail</label>
        <input v-model="form.email" type="email" required autocomplete="email" />
      </div>

      <div class="field">
        <label>Passwort (min. 8 Zeichen)</label>
        <input v-model="form.password" type="password" required minlength="8" autocomplete="new-password" />
      </div>

      <button class="btn btn-primary auth-submit" :disabled="loading">
        {{ loading ? 'Registrieren...' : 'Registrieren' }}
      </button>

      <p class="auth-switch">
        Schon ein Konto? <router-link to="/login">Anmelden</router-link>
      </p>
    </form>
  </div>
</template>

<style scoped>
.auth-screen {
  display: flex;
  justify-content: center;
  padding-top: 40px;
}

.auth-form {
  width: 100%;
  max-width: 380px;
}

.auth-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 24px;
  margin-top: 0;
}

.auth-ball {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  border: 2px solid var(--poke-ink);
  background: linear-gradient(to bottom, var(--poke-red) 0 50%, var(--poke-white) 50% 100%);
}

.auth-submit {
  width: 100%;
  margin-top: 8px;
}

.auth-switch {
  text-align: center;
  margin-top: 16px;
  font-weight: 600;
}
</style>
