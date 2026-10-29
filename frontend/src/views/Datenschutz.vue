<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../api/client'
import PokeballLoader from '../components/PokeballLoader.vue'
import LegalContent from '../components/LegalContent.vue'

const text = ref('')
const loading = ref(true)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { content } = await api.getContent('datenschutz')
    text.value = content.content_value
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="legal-page">
    <h1 class="font-display">Datenschutzerklärung</h1>

    <div v-if="error" class="error-banner">{{ error }}</div>
    <PokeballLoader v-if="loading" />

    <section v-else class="poke-panel">
      <LegalContent :text="text" />
    </section>
  </div>
</template>

<style scoped>
.legal-page {
  max-width: 720px;
}
</style>
