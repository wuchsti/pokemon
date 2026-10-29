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
}
