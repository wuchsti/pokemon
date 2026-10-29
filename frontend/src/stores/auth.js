import { defineStore } from 'pinia'
import { api, getToken, setToken } from '../api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: getToken(),
    ready: false,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
  },
  actions: {
    async init() {
      if (this.token && !this.user) {
        try {
          const { user } = await api.me()
          this.user = user
        } catch {
          this.logout()
        }
      }
      this.ready = true
    },
    async login(credentials) {
      const { token, user } = await api.login(credentials)
      this.token = token
      this.user = user
      setToken(token)
    },
    async register(payload) {
      const { token, user } = await api.register(payload)
      this.token = token
      this.user = user
      setToken(token)
    },
    logout() {
      this.token = null
      this.user = null
      setToken(null)
    },
  },
})
