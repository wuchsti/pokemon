<script setup>
import { onMounted, reactive, ref } from 'vue'
import { api } from '../api/client'
import AdminNav from '../components/AdminNav.vue'
import PokeballLoader from '../components/PokeballLoader.vue'
import LegalContent from '../components/LegalContent.vue'

const loading = ref(true)
const error = ref('')
const saving = reactive({ impressum: false, datenschutz: false })
const savedMessage = reactive({ impressum: '', datenschutz: '' })
const text = reactive({ impressum: '', datenschutz: '' })
const previewing = reactive({ impressum: false, datenschutz: false })

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [impressum, datenschutz] = await Promise.all([
      api.getContent('impressum'),
      api.getContent('datenschutz'),
    ])
    text.impressum = impressum.content.content_value
    text.datenschutz = datenschutz.content.content_value
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function save(key) {
  saving[key] = true
  savedMessage[key] = ''
  error.value = ''
  try {
    await api.adminUpdateContent(key, text[key])
    savedMessage[key] = 'Gespeichert ✓'
    setTimeout(() => (savedMessage[key] = ''), 3000)
  } catch (e) {
    error.value = e.message
  } finally {
    saving[key] = false
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

    <template v-else>
      <div class="hint-banner">
        ⚠️ Impressum und Datenschutzerklärung sind rechtlich relevant: Trage
        hier die echten Angaben ein (Kontaktdaten, Vision-API-Anbieter etc.)
        und lass die Texte vor dem Live-Gang von einer fachkundigen Person
        prüfen. Formatierung: eine Zeile mit "## Titel" wird zur Überschrift,
        eine Leerzeile trennt Absätze.
      </div>

      <section v-for="key in ['impressum', 'datenschutz']" :key="key" class="poke-panel content-editor">
        <div class="editor-head">
          <h2 class="font-display">{{ key === 'impressum' ? 'Impressum' : 'Datenschutzerklärung' }}</h2>
          <button class="btn btn-outline btn-sm" @click="previewing[key] = !previewing[key]">
            {{ previewing[key] ? 'Zurück zum Bearbeiten' : 'Vorschau' }}
          </button>
        </div>

        <LegalContent v-if="previewing[key]" :text="text[key]" />
        <textarea v-else v-model="text[key]" rows="16" class="editor-textarea"></textarea>

        <div class="editor-actions">
          <button class="btn btn-primary" :disabled="saving[key]" @click="save(key)">
            {{ saving[key] ? 'Speichert...' : 'Speichern' }}
          </button>
          <span v-if="savedMessage[key]" class="saved-note">{{ savedMessage[key] }}</span>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.hint-banner {
  background: #fff8e1;
  border: var(--border-comic);
  border-radius: var(--radius-sm);
  padding: 12px 16px;
  font-weight: 700;
  font-size: 13px;
  margin-bottom: 20px;
}

.content-editor {
  margin-bottom: 24px;
}

.editor-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.editor-head h2 {
  margin: 0;
  font-size: 18px;
}

.editor-textarea {
  width: 100%;
  font-family: 'Nunito', sans-serif;
  font-size: 13px;
  padding: 12px;
  border: var(--border-comic);
  border-radius: var(--radius-sm);
  resize: vertical;
}

.editor-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 10px;
}

.saved-note {
  font-weight: 700;
  color: #1d9a4a;
}
</style>
