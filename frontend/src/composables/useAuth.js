import { ref, computed } from 'vue'
import { useApi } from './useApi'
import api from '@/api/axios'

export function useAuth() {
  const { execute, loading, error } = useApi()
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const hasRole = (role) => {
    if (!user.value?.roles) return false
    return user.value.roles.includes(role)
  }

  const hasAnyRole = (roles) => {
    if (!user.value?.roles) return false
    return roles.some(role => user.value.roles.includes(role))
  }

  const isAdmin = computed(() => hasAnyRole(['admin', 'super_admin']))
  const isPagador = computed(() => hasRole('pagador'))
  const isOperador = computed(() => hasRole('operador'))
  const isContador = computed(() => hasRole('contador'))
  const isLectura = computed(() => hasRole('lectura'))

  const login = async (credentials) => {
    const response = await execute((signal) => api.post('/auth/login', credentials, { signal }))
    if (response?.data?.token) {
      token.value = response.data.token
      user.value = response.data.user
      localStorage.setItem('token', token.value)
    }
    return response
  }

  const logout = async () => {
    try {
      await api.post('/auth/logout')
    } catch (e) {
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
    }
  }

  const fetchMe = async () => {
    const response = await execute((signal) => api.get('/auth/me', { signal }))
    if (response?.data) {
      user.value = response.data.data || response.data
    }
    return response
  }

  if (token.value && !user.value) {
    fetchMe()
  }

  return {
    user,
    token,
    isAuthenticated,
    hasRole,
    hasAnyRole,
    isAdmin,
    isPagador,
    isOperador,
    isContador,
    isLectura,
    login,
    logout,
    fetchMe,
    loading,
    error,
  }
}
