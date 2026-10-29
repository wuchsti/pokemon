<script setup>
import { useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth'
import Navbar from './components/Navbar.vue'
import PokeballLoader from './components/PokeballLoader.vue'

const auth = useAuthStore()
const route = useRoute()
</script>

<template>
  <PokeballLoader v-if="!auth.ready" />
  <template v-else>
    <Navbar v-if="!route.meta.hideNav" />
    <main :class="route.meta.hideNav ? 'print-content' : 'container main-content'">
      <router-view />
    </main>
  </template>
</template>

<style scoped>
.main-content {
  flex: 1;
  padding: 28px 20px 48px;
}

.print-content {
  flex: 1;
}
</style>
