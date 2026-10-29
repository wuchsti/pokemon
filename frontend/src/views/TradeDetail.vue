<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { api } from '../api/client'
import PokeballLoader from '../components/PokeballLoader.vue'

const props = defineProps({
  id: { type: String, required: true },
})

const trade = ref(null)
const messages = ref([])
const loading = ref(true)
const error = ref('')
const sending = ref(false)
const confirming = ref(false)
const cancelling = ref(false)
const draft = ref('')
const messagesEl = ref(null)

let pollHandle = null

function formatTime(value) {
  return new Date(value.replace(' ', 'T')).toLocaleString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function scrollToBottom() {
  await nextTick()
  if (messagesEl.value) {
    messagesEl.value.scrollTop = messagesEl.value.scrollHeight
  }
}

async function load({ silent = false } = {}) {
  if (!silent) loading.value = true
  const previousCount = messages.value.length
  try {
    const result = await api.getTrade(props.id)
    trade.value = result.trade
    messages.value = result.messages
    if (!silent || messages.value.length > previousCount) {
      await scrollToBottom()
    }
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function send() {
  const text = draft.value.trim()
  if (!text) return
  sending.value = true
  error.value = ''
  try {
    const { message } = await api.sendTradeMessage({ trade_id: props.id, body: text })
    messages.value.push(message)
    draft.value = ''
    await scrollToBottom()
  } catch (e) {
    error.value = e.message
  } finally {
    sending.value = false
  }
}

function onKeydown(event) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    send()
  }
}

async function confirmSuccess() {
  confirming.value = true
  error.value = ''
  try {
    const result = await api.confirmTrade(props.id)
    trade.value = result.trade
  } catch (e) {
    error.value = e.message
  } finally {
    confirming.value = false
  }
}

async function cancelTrade() {
  if (!confirm('Diesen Tausch wirklich abbrechen?')) return
  cancelling.value = true
  error.value = ''
  try {
    const result = await api.cancelTrade(props.id)
    trade.value = result.trade
  } catch (e) {
    error.value = e.message
  } finally {
    cancelling.value = false
  }
}

onMounted(() => {
  load()
  pollHandle = setInterval(() => load({ silent: true }), 6000)
})

onBeforeUnmount(() => {
  if (pollHandle) clearInterval(pollHandle)
})
</script>

<template>
  <div class="trade-detail">
    <PokeballLoader v-if="loading" />

    <template v-else-if="trade">
      <h1 class="font-display">Tausch mit {{ trade.partner_username }}</h1>

      <div v-if="error" class="error-banner">{{ error }}</div>

      <div v-if="trade.status === 'completed'" class="status-banner status-completed">
        🎉 Dieser Tausch wurde von beiden Seiten als erfolgreich bestätigt.
      </div>
      <div v-else-if="trade.status === 'cancelled'" class="status-banner status-cancelled">
        ❌ Dieser Tausch wurde abgebrochen.
      </div>
      <div v-else class="status-banner status-open">
        <span>Du: {{ trade.my_confirmed ? '✓ bestätigt' : 'noch offen' }}</span>
        <span>{{ trade.partner_username }}: {{ trade.partner_confirmed ? '✓ bestätigt' : 'noch offen' }}</span>
      </div>

      <div ref="messagesEl" class="messages poke-panel">
        <p v-if="messages.length === 0" class="no-messages">
          Noch keine Nachrichten. Schreib {{ trade.partner_username }}, um Ablauf und Versand zu klären.
        </p>
        <div
          v-for="message in messages"
          :key="message.id"
          class="message"
          :class="message.from_me ? 'message-mine' : 'message-theirs'"
        >
          <div class="message-bubble">{{ message.body }}</div>
          <span class="message-meta">{{ message.from_me ? 'Du' : message.sender_username }} · {{ formatTime(message.created_at) }}</span>
        </div>
      </div>

      <form v-if="trade.status !== 'cancelled'" class="message-form" @submit.prevent="send">
        <textarea
          v-model="draft"
          rows="2"
          placeholder="Nachricht schreiben... (Enter zum Senden, Shift+Enter für neue Zeile)"
          @keydown="onKeydown"
        ></textarea>
        <button class="btn btn-primary" :disabled="sending || !draft.trim()" type="submit">Senden</button>
      </form>

      <div v-if="trade.status === 'open'" class="trade-actions">
        <button
          v-if="!trade.my_confirmed"
          class="btn btn-accent"
          :disabled="confirming"
          @click="confirmSuccess"
        >
          ✅ Tausch als erfolgreich bestätigen
        </button>
        <span v-else class="confirmed-note">✓ Du hast bestätigt. Warte auf {{ trade.partner_username }}...</span>

        <button class="btn btn-outline" :disabled="cancelling" @click="cancelTrade">Tausch abbrechen</button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.trade-detail {
  max-width: 640px;
}

.status-banner {
  border: var(--border-comic);
  border-radius: var(--radius-sm);
  padding: 10px 14px;
  margin-bottom: 16px;
  font-weight: 700;
}

.status-open {
  background: var(--poke-bg);
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
}

.status-completed {
  background: #eafcef;
  color: #1d9a4a;
}

.status-cancelled {
  background: #f1f1f1;
  color: #666;
}

.messages {
  height: 360px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 14px;
}

.no-messages {
  color: var(--poke-gray);
  font-weight: 600;
  text-align: center;
  margin-top: 40px;
}

.message {
  display: flex;
  flex-direction: column;
  max-width: 75%;
}

.message-mine {
  align-self: flex-end;
  align-items: flex-end;
}

.message-theirs {
  align-self: flex-start;
  align-items: flex-start;
}

.message-bubble {
  padding: 8px 12px;
  border-radius: var(--radius-sm);
  border: 2px solid var(--poke-ink);
  font-size: 14px;
  word-break: break-word;
}

.message-mine .message-bubble {
  background: var(--poke-blue);
  color: var(--poke-white);
}

.message-theirs .message-bubble {
  background: var(--poke-white);
}

.message-meta {
  font-size: 11px;
  color: var(--poke-gray);
  margin-top: 2px;
}

.message-form {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.message-form textarea {
  flex: 1;
  font-family: var(--font-body);
  font-size: 14px;
  padding: 10px 12px;
  border: var(--border-comic);
  border-radius: var(--radius-sm);
  resize: vertical;
}

.trade-actions {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}

.confirmed-note {
  font-weight: 700;
  color: #1d9a4a;
}
</style>
