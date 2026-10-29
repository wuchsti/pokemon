<script setup>
import { computed, nextTick, onBeforeUnmount, ref } from 'vue'
import { api } from '../api/client'
import { drawToJpegDataUri, fileToJpegDataUri } from '../utils/image'
import PokeballLoader from './PokeballLoader.vue'
import CardModal from './CardModal.vue'

const cameraSupported = typeof navigator !== 'undefined' && !!navigator.mediaDevices?.getUserMedia

const videoEl = ref(null)
const fileInput = ref(null)
let stream = null

const cameraActive = ref(false)
const cameraError = ref('')
const capturedImage = ref(null)
const loading = ref(false)
const error = ref('')
const extracted = ref(null)
const candidates = ref([])
const selectedCard = ref(null)
const savedMessage = ref('')

const lowConfidence = computed(() => extracted.value && extracted.value.confidence < 0.5)

async function startCamera() {
  cameraError.value = ''
  reset({ keepCamera: true })
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' } },
      audio: false,
    })
    cameraActive.value = true
    await nextTick()
    if (videoEl.value) videoEl.value.srcObject = stream
  } catch (e) {
    if (e.name === 'NotAllowedError') {
      cameraError.value = 'Kamera-Zugriff wurde verweigert. Bitte erlauben oder stattdessen ein Foto hochladen.'
    } else if (e.name === 'NotFoundError') {
      cameraError.value = 'Keine Kamera gefunden. Bitte lade stattdessen ein Foto hoch.'
    } else {
      cameraError.value = `Kamera konnte nicht gestartet werden: ${e.message}`
    }
  }
}

function stopCamera() {
  stream?.getTracks().forEach((track) => track.stop())
  stream = null
  cameraActive.value = false
}

function capturePhoto() {
  const video = videoEl.value
  if (!video || !video.videoWidth) return
  const dataUri = drawToJpegDataUri(video, video.videoWidth, video.videoHeight)
  stopCamera()
  capturedImage.value = dataUri
  runRecognition(dataUri)
}

async function onFileChange(event) {
  const file = event.target.files?.[0]
  if (!file) return
  reset({ keepCamera: false })
  const dataUri = await fileToJpegDataUri(file)
  capturedImage.value = dataUri
  runRecognition(dataUri)
  event.target.value = ''
}

async function runRecognition(dataUri) {
  loading.value = true
  error.value = ''
  try {
    const result = await api.scanCard(dataUri)
    extracted.value = result.extracted
    candidates.value = result.candidates
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function reset({ keepCamera = false } = {}) {
  if (!keepCamera) stopCamera()
  capturedImage.value = null
  extracted.value = null
  candidates.value = []
  error.value = ''
  savedMessage.value = ''
}

function onSaved() {
  savedMessage.value = `${selectedCard.value.name} wurde deiner Sammlung hinzugefügt!`
  selectedCard.value = null
  reset()
}

onBeforeUnmount(stopCamera)
</script>

<template>
  <div class="scanner">
    <div v-if="savedMessage" class="badge badge-trade saved-toast">{{ savedMessage }}</div>
    <div v-if="error" class="error-banner">{{ error }}</div>

    <!-- Idle: choose camera or upload -->
    <div v-if="!cameraActive && !capturedImage" class="scanner-start poke-panel">
      <p class="scanner-hint">
        Fotografiere eine Karte oder lade ein Foto hoch – PokéVault liest Name und
        Kartennummer automatisch aus und schlägt passende Karten vor.
      </p>
      <div v-if="cameraError" class="error-banner">{{ cameraError }}</div>
      <div class="scanner-actions">
        <button v-if="cameraSupported" class="btn btn-primary" @click="startCamera">📷 Kamera starten</button>
        <button class="btn btn-outline" @click="fileInput.click()">🖼️ Foto hochladen</button>
        <input ref="fileInput" type="file" accept="image/*" capture="environment" hidden @change="onFileChange" />
      </div>
    </div>

    <!-- Camera live preview -->
    <div v-else-if="cameraActive" class="scanner-camera poke-panel">
      <video ref="videoEl" autoplay playsinline muted class="scanner-video"></video>
      <div class="scanner-actions">
        <button class="btn btn-primary" @click="capturePhoto">📸 Foto aufnehmen</button>
        <button class="btn btn-outline" @click="stopCamera">Abbrechen</button>
      </div>
    </div>

    <!-- Captured image + recognition results -->
    <div v-else class="scanner-result">
      <div class="scanner-result-head poke-panel">
        <img :src="capturedImage" alt="Aufgenommenes Foto" class="scanner-thumb" />

        <div class="scanner-result-info">
          <PokeballLoader v-if="loading" />
          <template v-else-if="extracted">
            <p class="extracted-line">
              <strong>Erkannt:</strong>
              {{ extracted.card_name ?? '?' }}
              <span v-if="extracted.card_number">
                · #{{ extracted.card_number }}<span v-if="extracted.set_total">/{{ extracted.set_total }}</span>
              </span>
            </p>
            <p class="confidence-line" :class="{ low: lowConfidence }">
              Konfidenz: {{ Math.round((extracted.confidence ?? 0) * 100) }}%
              <span v-if="lowConfidence">– bitte Treffer unten genau prüfen oder manuell suchen.</span>
            </p>
          </template>
          <button class="btn btn-outline btn-sm" @click="reset">🔁 Erneut aufnehmen</button>
        </div>
      </div>

      <template v-if="!loading">
        <p v-if="candidates.length === 0" class="no-match">
          Keine automatische Übereinstimmung gefunden.
          <router-link to="/search">Nutze die manuelle Kartensuche</router-link>.
        </p>

        <div v-else class="card-grid">
          <button v-for="card in candidates" :key="card.id" class="card-tile poke-card" @click="selectedCard = card">
            <img :src="card.images?.small" :alt="card.name" />
            <div class="card-tile-info">
              <strong>{{ card.name }}</strong>
              <span>{{ card.set?.name }} · #{{ card.number }}</span>
            </div>
          </button>
        </div>
      </template>
    </div>

    <CardModal v-if="selectedCard" :card="selectedCard" @close="selectedCard = null" @saved="onSaved" />
  </div>
</template>

<style scoped>
.scanner-hint {
  margin-top: 0;
  font-weight: 600;
  color: var(--poke-gray);
}

.scanner-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.scanner-video {
  width: 100%;
  max-height: 420px;
  border-radius: var(--radius-sm);
  border: var(--border-comic);
  background: #000;
  margin-bottom: 14px;
}

.scanner-result-head {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.scanner-thumb {
  width: 140px;
  border-radius: var(--radius-sm);
  border: var(--border-comic);
}

.scanner-result-info {
  flex: 1;
  min-width: 220px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.extracted-line {
  margin: 0;
  font-size: 15px;
}

.confidence-line {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: var(--poke-gray);
}

.confidence-line.low {
  color: var(--poke-red-dark);
}

.saved-toast {
  display: block;
  width: fit-content;
  margin-bottom: 16px;
  padding: 8px 16px;
  font-size: 14px;
}

.no-match {
  font-weight: 600;
}

.card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 16px;
}

.card-tile {
  padding: 12px;
  text-align: left;
  cursor: pointer;
  background: var(--poke-white);
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.card-tile:hover {
  transform: translateY(-2px);
}

.card-tile img {
  width: 100%;
  border-radius: var(--radius-sm);
}

.card-tile-info {
  display: flex;
  flex-direction: column;
  font-size: 13px;
}

.card-tile-info span {
  color: var(--poke-gray);
}
</style>
