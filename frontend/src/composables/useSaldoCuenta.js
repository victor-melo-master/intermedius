import { ref } from 'vue'
import api from '@/api/axios'

const cache = new Map()
const CACHE_TTL = 30000

export function useSaldoCuenta() {
  const loading = ref(false)
  const error = ref(null)

  const getSaldo = async (cuentaId) => {
    if (!cuentaId) return 0

    const cached = cache.get(cuentaId)
    if (cached && Date.now() - cached.at < CACHE_TTL) {
      return cached.saldo
    }

    loading.value = true
    error.value = null

    try {
      const { data } = await api.get(`/cuentas/${cuentaId}`)
      const cuenta = data.data || data
      const saldo = parseFloat(cuenta.saldo_cache || 0)

      cache.set(cuentaId, { saldo, at: Date.now() })
      return saldo
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      return 0
    } finally {
      loading.value = false
    }
  }

  const invalidateCache = (cuentaId) => {
    if (cuentaId) {
      cache.delete(cuentaId)
    } else {
      cache.clear()
    }
  }

  return { getSaldo, invalidateCache, loading, error }
}
