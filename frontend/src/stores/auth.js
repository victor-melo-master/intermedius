import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/axios.js'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || '')
  const loading = ref(false)
  const error = ref('')
  const initialized = ref(false)

  const isAuthenticated = computed(() => !!user.value && !!token.value)
  const isAdmin = computed(() => user.value?.roles?.includes('admin') || user.value?.roles?.includes('super_admin'))
  const isSuperAdmin = computed(() => user.value?.roles?.includes('super_admin'))

  async function init() {
    if (!token.value) {
      initialized.value = true
      return
    }
    try {
      const { data } = await api.get('/auth/me')
      user.value = data.data || data
      initialized.value = true
    } catch {
      token.value = ''
      localStorage.removeItem('token')
      initialized.value = true
    }
  }

  async function login(email, password) {
    loading.value = true
    error.value = ''
    try {
      const { data } = await api.post('/auth/login', { email, password })
      token.value = data.token
      user.value = data.user
      localStorage.setItem('token', data.token)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Credenciales incorrectas'
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try { await api.post('/auth/logout') } catch {}
    user.value = null
    token.value = ''
    localStorage.removeItem('token')
  }

  return { user, token, loading, error, initialized, isAuthenticated, isAdmin, isSuperAdmin, init, login, logout }
})
