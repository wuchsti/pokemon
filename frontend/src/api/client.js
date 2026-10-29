const TOKEN_KEY = 'pokevault_token'

export function getToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token) {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token)
  } else {
    localStorage.removeItem(TOKEN_KEY)
  }
}

async function request(path, { method = 'GET', body, auth = true } = {}) {
  const headers = { 'Content-Type': 'application/json' }
  if (auth) {
    const token = getToken()
    if (token) {
      headers.Authorization = `Bearer ${token}`
    }
  }

  const response = await fetch(`/api/${path}`, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  })

  const isJson = response.headers.get('content-type')?.includes('application/json')
  const data = isJson ? await response.json() : null

  if (!response.ok) {
    const error = new Error(data?.error || `Anfrage fehlgeschlagen (${response.status})`)
    error.status = response.status
    throw error
  }

  return data
}

export const api = {
  register: (payload) => request('auth/register.php', { method: 'POST', body: payload, auth: false }),
  login: (payload) => request('auth/login.php', { method: 'POST', body: payload, auth: false }),
  me: () => request('auth/me.php'),

  searchCards: (params) => request(`tcg/search.php?${new URLSearchParams(params)}`),
  listSets: () => request('tcg/sets.php'),
  getSet: (id) => request(`tcg/sets.php?${new URLSearchParams({ id })}`),

  listUserCards: (params = {}) => request(`cards/list.php?${new URLSearchParams(params)}`),
  addUserCard: (payload) => request('cards/add.php', { method: 'POST', body: payload }),
  updateUserCard: (payload) => request('cards/update.php', { method: 'PUT', body: payload }),
  deleteUserCard: (id) => request('cards/delete.php', { method: 'DELETE', body: { id } }),

  dashboardStats: () => request('stats/dashboard.php'),
  getSetCards: (setId) => request(`tcg/set_cards.php?${new URLSearchParams({ setId })}`),

  listWishlist: (params = {}) => request(`wishlist/list.php?${new URLSearchParams(params)}`),
  addWishlistItem: (payload) => request('wishlist/add.php', { method: 'POST', body: payload }),
  removeWishlistItem: (payload) => request('wishlist/remove.php', { method: 'DELETE', body: payload }),

  scanCard: (imageDataUri) => request('scan/recognize.php', { method: 'POST', body: { image: imageDataUri } }),

  updateSettings: (payload) => request('auth/settings.php', { method: 'PUT', body: payload }),
  publicBinder: (username) => request(`public/binder.php?${new URLSearchParams({ username })}`, { auth: false }),
  findTraders: () => request('matchmaking/find_traders.php'),
  exportCardsJson: () => request('export/cards.php'),

  async exportCsv() {
    const token = getToken()
    const response = await fetch('/api/export/csv.php', {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    })
    if (!response.ok) {
      throw new Error('CSV-Export fehlgeschlagen.')
    }
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `pokevault-sammlung-${new Date().toISOString().slice(0, 10)}.csv`
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  },

  createTrade: (payload) => request('trades/create.php', { method: 'POST', body: payload }),
  listTrades: () => request('trades/list.php'),
  getTrade: (id) => request(`trades/get.php?${new URLSearchParams({ id })}`),
  sendTradeMessage: (payload) => request('trades/message.php', { method: 'POST', body: payload }),
  confirmTrade: (tradeId) => request('trades/confirm.php', { method: 'POST', body: { trade_id: tradeId } }),
  cancelTrade: (tradeId) => request('trades/cancel.php', { method: 'POST', body: { trade_id: tradeId } }),
  searchTradeCards: (q) => request(`matchmaking/search.php?${new URLSearchParams({ q })}`),

  changeEmail: (payload) => request('auth/change_email.php', { method: 'PUT', body: payload }),
  changePassword: (payload) => request('auth/change_password.php', { method: 'PUT', body: payload }),

  getContent: (key) => request(`content/get.php?${new URLSearchParams({ key })}`, { auth: false }),

  adminListUsers: () => request('admin/users/list.php'),
  adminBlockUser: (id) => request('admin/users/block.php', { method: 'POST', body: { id } }),
  adminUnblockUser: (id) => request('admin/users/unblock.php', { method: 'POST', body: { id } }),
  adminDeleteUser: (id) => request('admin/users/delete.php', { method: 'POST', body: { id } }),
  adminStats: () => request('admin/stats.php'),
  adminUpdateContent: (key, value) => request('admin/content/update.php', { method: 'POST', body: { key, value } }),
  adminLogs: (params = {}) => request(`admin/logs.php?${new URLSearchParams(params)}`),
}
