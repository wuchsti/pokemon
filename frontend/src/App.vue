<script setup>
import { useAuthStore } from './stores/auth'
import Navbar from './components/Navbar.vue'
import PokeballLoader from './components/PokeballLoader.vue'

const auth = useAuthStore()
</script>

<template>
  <PokeballLoader v-if="!auth.ready" />
  <template v-else>
    <Navbar />
    <main class="container main-content">
      <router-view />
    </main>
  </template>
</template>

<style scoped>
.main-content {
  flex: 1;
  padding: 28px 20px 48px;
}
</style>
