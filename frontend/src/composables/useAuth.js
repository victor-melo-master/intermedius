import { ref, computed } from 'vue'
import { useApi } from './useApi'
import api from '@/api/axios'

export function useAuth() {
  const { execute, loading, error } = useApi()
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token') || null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const login = async (credentials) => {
    const response = await execute((signal) => api.post('/auth/login', credentials, { signal }))
    if (response?.data?.token) {
      token.value = response.data.token
      user.value = response.data.user
      localStorage.setItem('auth_token', token.value)
    }
    return response
  }

  const logout = async () => {
    try {
      await api.post('/auth/logout')
    } catch (e) {
      // Ignorar errores al cerrar sesión
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
    }
  }

  const fetchMe = async () => {
    const response = await execute((signal) => api.get('/auth/me', { signal }))
    if (response?.data) {
      user.value = response.data
    }
    return response
  }

  // Inicializar si hay token guardado
  if (token.value && !user.value) {
    fetchMe()
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    logout,
    fetchMe,
    loading,
    error,
  }
}
