import { ref } from 'vue'
import { useApiError } from './useApiError'
import api from '@/api/axios'

export function useVerificacion() {
  const { parseError } = useApiError()
  const loading = ref(false)
  const error = ref(null)

  const operacion = ref(null)
  const transacciones = ref([])
  const saldos = ref({})
  const totalTransacciones = ref(0)
  const transaccionesValidadas = ref(0)
  const todasValidadas = ref(false)

  const fetchVerificacion = async (id) => {
    console.log('🔍 fetchVerificacion iniciado, ID:', id)
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/operaciones/${id}/verificacion`)
      console.log('✅ Respuesta API:', response)
      const raw = response?.data || {}
      console.log('📦 Datos crudos (raw):', raw)
      const data = raw.data || raw
      console.log('📦 Datos procesados (data):', data)

      operacion.value = data.operacion || null
      transacciones.value = data.transacciones || []
      saldos.value = data.saldos || {}
      totalTransacciones.value = data.total_transacciones || 0
      transaccionesValidadas.value = data.transacciones_validadas || 0
      todasValidadas.value =
        transaccionesValidadas.value > 0 &&
        transaccionesValidadas.value === totalTransacciones.value

      console.log('📋 operacion asignada:', operacion.value)
      console.log('📋 transacciones asignadas:', transacciones.value)
    } catch (err) {
      console.error('❌ Error en fetchVerificacion:', err)
      error.value = parseError(err)
    } finally {
      loading.value = false
      console.log('🔚 fetchVerificacion finalizado, loading:', loading.value)
    }
  }

  const agregarTransaccion = async (operacionId, payload) => {
    const response = await api.post(`/operaciones/${operacionId}/transacciones`, payload)
    return response
  }

  const editarTransaccion = async (operacionId, transaccionId, payload) => {
    const response = await api.put(`/operaciones/${operacionId}/transacciones/${transaccionId}`, payload)
    return response
  }

  const validarTransaccion = async (operacionId, transaccionId) => {
    const response = await api.patch(`/operaciones/${operacionId}/transacciones/${transaccionId}/validar`)
    return response
  }

  const eliminarTransaccion = async (operacionId, transaccionId) => {
    const response = await api.delete(`/operaciones/${operacionId}/transacciones/${transaccionId}`)
    return response
  }

  const cerrarVerificacion = async (operacionId) => {
    const response = await api.patch(`/operaciones/${operacionId}/verificar`)
    return response
  }

  return {
    loading,
    error,
    operacion,
    transacciones,
    saldos,
    totalTransacciones,
    transaccionesValidadas,
    todasValidadas,
    fetchVerificacion,
    agregarTransaccion,
    editarTransaccion,
    validarTransaccion,
    eliminarTransaccion,
    cerrarVerificacion,
  }
}
