<script setup>
import { onMounted, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { api } from '../api/client'
import AdminNav from '../components/AdminNav.vue'
import PokeballLoader from '../components/PokeballLoader.vue'

const auth = useAuthStore()
const users = ref([])
const loading = ref(true)
const error = ref('')
const actingId = ref(null)

function formatDate(value) {
  return new Date(value.replace(' ', 'T')).toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const result = await api.adminListUsers()
    users.value = result.users
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function block(user) {
  actingId.value = user.id
  error.value = ''
  try {
    await api.adminBlockUser(user.id)
    user.is_blocked = 1
  } catch (e) {
    error.value = e.message
  } finally {
    actingId.value = null
  }
}

async function unblock(user) {
  actingId.value = user.id
  error.value = ''
  try {
    await api.adminUnblockUser(user.id)
    user.is_blocked = 0
  } catch (e) {
    error.value = e.message
  } finally {
    actingId.value = null
  }
}

async function remove(user) {
  if (!confirm(`"${user.username}" wirklich endgültig löschen? Das entfernt auch alle seine Karten, Tausche und Wunschlisten-Einträge.`)) {
    return
  }
  actingId.value = user.id
  error.value = ''
  try {
    await api.adminDeleteUser(user.id)
    users.value = users.value.filter((u) => u.id !== user.id)
  } catch (e) {
    error.value = e.message
  } finally {
    actingId.value = null
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

    <div v-else class="table-wrap poke-panel">
      <table class="users-table">
        <thead>
          <tr>
            <th>Nutzer</th>
            <th>E-Mail</th>
            <th>Seit</th>
            <th>Karten</th>
            <th>Tausche</th>
            <th>Scans</th>
            <th>Status</th>
            <th>Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td>
              {{ user.username }}
              <span v-if="user.is_admin" class="badge badge-vault admin-badge">Admin</span>
            </td>
            <td>{{ user.email }}</td>
            <td>{{ formatDate(user.created_at) }}</td>
            <td>{{ user.card_count }}</td>
            <td>{{ user.trade_count }}</td>
            <td>{{ user.scan_count }}</td>
            <td>
              <span class="badge" :class="user.is_blocked ? 'badge-blocked' : 'badge-active'">
                {{ user.is_blocked ? 'Gesperrt' : 'Aktiv' }}
              </span>
            </td>
            <td class="actions-cell">
              <template v-if="user.id !== auth.user?.id">
                <button
                  v-if="user.is_blocked"
                  class="btn btn-accent btn-sm"
                  :disabled="actingId === user.id"
                  @click="unblock(user)"
                >
                  Freischalten
                </button>
                <button v-else class="btn btn-outline btn-sm" :disabled="actingId === user.id" @click="block(user)">
                  Sperren
                </button>
                <button class="btn btn-primary btn-sm" :disabled="actingId === user.id" @click="remove(user)">
                  Löschen
                </button>
              </template>
              <span v-else class="self-note">(du)</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.table-wrap {
  overflow-x: auto;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
  white-space: nowrap;
}

.users-table th,
.users-table td {
  padding: 8px 12px;
  text-align: left;
  border-bottom: 1px solid var(--poke-bg);
}

.admin-badge {
  margin-left: 6px;
  font-size: 10px;
  padding: 2px 8px;
}

.badge-active {
  background: #eafcef;
  color: #1d9a4a;
}

.badge-blocked {
  background: #ffeeee;
  color: var(--poke-red-dark);
}

.actions-cell {
  display: flex;
  gap: 6px;
}

.self-note {
  color: var(--poke-gray);
  font-weight: 600;
}
</style>
