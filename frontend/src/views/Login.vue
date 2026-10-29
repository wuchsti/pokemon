<script setup>
import { reactive, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({ username: '', password: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form)
    router.push(route.query.redirect?.toString() || { name: 'dashboard' })
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
        PokéVault Login
      </h1>

      <div v-if="error" class="error-banner">{{ error }}</div>

      <div class="field">
        <label>Benutzername oder E-Mail</label>
        <input v-model="form.username" type="text" required autocomplete="username" />
      </div>

      <div class="field">
        <label>Passwort</label>
        <input v-model="form.password" type="password" required autocomplete="current-password" />
      </div>

      <button class="btn btn-primary auth-submit" :disabled="loading">
        {{ loading ? 'Anmelden...' : 'Anmelden' }}
      </button>

      <p class="auth-switch">
        Noch kein Konto? <router-link to="/register">Jetzt registrieren</router-link>
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
