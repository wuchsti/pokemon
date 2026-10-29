<script setup>
import { reactive, ref } from 'vue'

const props = defineProps({
  card: { type: Object, required: true }, // pokemontcg.io card object
  defaultStorageType: { type: String, default: 'vault' },
})
const emit = defineEmits(['close', 'saved'])

const conditions = ['Mint', 'Near Mint', 'Excellent', 'Good', 'Light Played', 'Played', 'Poor']
const gradingCompanies = ['PSA', 'BGS', 'CGC', 'AP Grading']
const languages = ['DE', 'EN', 'JP', 'FR', 'IT', 'ES']

const form = reactive({
  storage_type: props.defaultStorageType,
  card_condition: 'Near Mint',
  language: 'DE',
  is_graded: false,
  grading_company: 'PSA',
  grading_score: 9.0,
})

const saving = ref(false)
const error = ref('')

async function save() {
  error.value = ''
  saving.value = true
  try {
    const { api } = await import('../api/client')
    await api.addUserCard({
      card_api_id: props.card.id,
      set_id: props.card.set?.id ?? '',
      card_number: props.card.number ?? '',
      card_name: props.card.name,
      image_url: props.card.images?.small ?? props.card.images?.large ?? '',
      storage_type: form.storage_type,
      card_condition: form.card_condition,
      language: form.language,
      is_graded: form.is_graded,
      grading_company: form.is_graded ? form.grading_company : undefined,
      grading_score: form.is_graded ? form.grading_score : undefined,
    })
    emit('saved')
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="modal-backdrop" @click.self="emit('close')">
    <div class="modal poke-panel">
      <button class="modal-close" @click="emit('close')" aria-label="Schließen">✕</button>

      <div class="modal-body">
        <img :src="card.images?.small" :alt="card.name" class="modal-img" />

        <div class="modal-form">
          <h3 class="font-display">{{ card.name }}</h3>
          <p class="modal-subtitle">{{ card.set?.name }} · #{{ card.number }}</p>

          <div v-if="error" class="error-banner">{{ error }}</div>

          <div class="field">
            <label>Ordner</label>
            <select v-model="form.storage_type">
              <option value="vault">Sammlung (Vault)</option>
              <option value="trade_binder">Tausch-Binder</option>
            </select>
          </div>

          <div class="field">
            <label>Zustand</label>
            <select v-model="form.card_condition">
              <option v-for="c in conditions" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>

          <div class="field">
            <label>Sprache</label>
            <select v-model="form.language">
              <option v-for="l in languages" :key="l" :value="l">{{ l }}</option>
            </select>
          </div>

          <div class="field field-checkbox">
            <label>
              <input type="checkbox" v-model="form.is_graded" />
              Gegradet
            </label>
          </div>

          <template v-if="form.is_graded">
            <div class="field">
              <label>Grading-Unternehmen</label>
              <select v-model="form.grading_company">
                <option v-for="g in gradingCompanies" :key="g" :value="g">{{ g }}</option>
              </select>
            </div>
            <div class="field">
              <label>Grading-Score (1.0 - 10.0)</label>
              <input type="number" min="1" max="10" step="0.5" v-model.number="form.grading_score" />
            </div>
          </template>

          <button class="btn btn-primary" :disabled="saving" @click="save">
            {{ saving ? 'Speichern...' : 'Karte speichern' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(42, 42, 42, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  z-index: 50;
}

.modal {
  max-width: 560px;
  width: 100%;
  position: relative;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-close {
  position: absolute;
  top: 14px;
  right: 14px;
  border: var(--border-comic);
  background: var(--poke-white);
  border-radius: 50%;
  width: 32px;
  height: 32px;
  cursor: pointer;
  font-weight: 700;
}

.modal-body {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.modal-img {
  width: 160px;
  border-radius: var(--radius-sm);
  border: var(--border-comic);
  align-self: flex-start;
}

.modal-form {
  flex: 1;
  min-width: 220px;
}

.modal-subtitle {
  color: var(--poke-gray);
  font-weight: 700;
  margin-top: -4px;
}

.field-checkbox label {
  display: flex;
  align-items: center;
  gap: 8px;
}
</style>
