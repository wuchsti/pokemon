<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

function logout() {
  auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <header class="navbar">
    <div class="container navbar-inner">
      <router-link to="/dashboard" class="navbar-brand font-display">
        <span class="navbar-ball"></span>
        PokéVault
      </router-link>

      <nav v-if="auth.isAuthenticated" class="navbar-links">
        <router-link to="/dashboard">Dashboard</router-link>
        <router-link to="/collection/vault">Vault</router-link>
        <router-link to="/collection/trade_binder">Tauschbinder</router-link>
        <router-link to="/scan" class="navbar-scan">📷 Scannen</router-link>
        <router-link to="/search">Karten suchen</router-link>
        <router-link to="/wishlist">Wunschliste</router-link>
        <button class="btn btn-outline btn-sm" @click="logout">Abmelden</button>
      </nav>
    </div>
  </header>
</template>

<style scoped>
.navbar {
  background: var(--poke-white);
  border-bottom: var(--border-comic);
}

.navbar-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  flex-wrap: wrap;
  gap: 12px;
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 22px;
  color: var(--poke-ink);
}

.navbar-ball {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid var(--poke-ink);
  background: linear-gradient(to bottom, var(--poke-red) 0 50%, var(--poke-white) 50% 100%);
  position: relative;
}

.navbar-ball::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 8px;
  height: 8px;
  margin: -4px 0 0 -4px;
  background: var(--poke-white);
  border: 2px solid var(--poke-ink);
  border-radius: 50%;
}

.navbar-links {
  display: flex;
  align-items: center;
  gap: 18px;
  flex-wrap: wrap;
}

.navbar-links a {
  font-weight: 700;
  color: var(--poke-ink);
}

.navbar-links a.router-link-active {
  color: var(--poke-red);
}

.navbar-scan {
  background: var(--poke-yellow);
  padding: 6px 12px;
  border-radius: 999px;
  border: 2px solid var(--poke-ink);
}

.navbar-scan.router-link-active {
  color: var(--poke-ink);
}
</style>
