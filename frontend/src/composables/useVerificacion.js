import { ref } from 'vue'
import { useApiError } from './useApiError'
import api from '@/api/axios'

export function useVerificacion() {
  const { parseError } = useApiError()
  const loading = ref(false)
  const error = ref(null)

  const operacion = ref(null)
  const movimientos = ref([])
  const saldos = ref({})
  const totalMovimientos = ref(0)
  const movimientosValidados = ref(0)
  const todasValidados = ref(false)

  const fetchVerificacion = async (id) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/operaciones/${id}/verificacion`)
      const raw = response?.data || {}
      const data = raw.data || raw

      operacion.value = data.operacion || null
      movimientos.value = data.movimientos || data.operacion?.movimientos || []
      saldos.value = (data.saldos && typeof data.saldos === 'object' && !Array.isArray(data.saldos))
        ? data.saldos : {}
      totalMovimientos.value = data.total_movimientos || movimientos.value.length || 0
      movimientosValidados.value = data.movimientos_validados || 0
      todasValidados.value =
        movimientosValidados.value > 0 &&
        movimientosValidados.value === totalMovimientos.value
    } catch (err) {
      error.value = parseError(err)
    } finally {
      loading.value = false
    }
  }

  const validarMovimiento = async (operacionId, movimientoId) => {
    const response = await api.patch(`/operaciones/${operacionId}/movimientos/${movimientoId}/validar`)
    return response
  }

  const rechazarMovimiento = async (operacionId, movimientoId, motivo) => {
    const response = await api.patch(`/operaciones/${operacionId}/movimientos/${movimientoId}/rechazar`, {
      motivo_rechazo: motivo,
    })
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
    movimientos,
    saldos,
    totalMovimientos,
    movimientosValidados,
    todasValidados,
    fetchVerificacion,
    validarMovimiento,
    rechazarMovimiento,
    cerrarVerificacion,
  }
}
