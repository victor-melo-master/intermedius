import { ref, computed } from 'vue'
import { useApi } from './useApi'
import api from '@/api/axios'

export function usePool() {
  const { execute, loading, error } = useApi()
  const operaciones = ref([])

  const enEspera = computed(() => operaciones.value.filter(op => op.estado === 'en_espera'))
  const enProceso = computed(() => operaciones.value.filter(op => op.estado === 'en_proceso'))
  const concluidas = computed(() => operaciones.value.filter(op => op.estado === 'concluida'))

  const fetchAll = async () => {
    const response = await execute((signal) => api.get('/pool', { signal }))
    operaciones.value = response?.data?.data || response?.data || []
    return response
  }

  const tomar = async (operacionId) => {
    const response = await execute((signal) => api.post(`/pool/${operacionId}/tomar`, null, { signal }))
    await fetchAll()
    return response
  }

  const soltar = async (operacionId) => {
    const response = await execute((signal) => api.post(`/pool/${operacionId}/soltar`, null, { signal }))
    await fetchAll()
    return response
  }

  const pagar = async (operacionId) => {
    const response = await execute((signal) => api.post(`/pool/${operacionId}/pagar`, null, { signal }))
    await fetchAll()
    return response
  }

  const cancelar = async (operacionId, motivo) => {
    const response = await execute((signal) => api.post(`/pool/${operacionId}/cancelar`, { motivo }, { signal }))
    await fetchAll()
    return response
  }

  return {
    operaciones,
    enEspera,
    enProceso,
    concluidas,
    fetchAll,
    tomar,
    soltar,
    pagar,
    cancelar,
    loading,
    error,
  }
}
