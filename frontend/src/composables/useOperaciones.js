import { ref } from 'vue'
import { useApi } from './useApi'
import api from '@/api/axios'

export function useOperaciones() {
  const { execute, loading, error } = useApi()
  const list = ref([])
  const detail = ref(null)

  const fetchAll = async (params = {}) => {
    const response = await execute((signal) => api.get('/operaciones', { params, signal }))
    list.value = response?.data?.data || response?.data || []
    return response
  }

  const fetchOne = async (id) => {
    const response = await execute((signal) => api.get(`/operaciones/${id}`, { signal }))
    detail.value = response?.data?.data || response?.data
    return response
  }

  const create = async (payload) => {
    const response = await execute((signal) => api.post('/operaciones', payload, { signal }))
    return response
  }

  const update = async (id, payload) => {
    const response = await execute((signal) => api.put(`/operaciones/${id}`, payload, { signal }))
    detail.value = response?.data?.data || response?.data
    return response
  }

  const verificar = async (id) => {
    const response = await execute((signal) => api.patch(`/operaciones/${id}/verificar`, null, { signal }))
    return response
  }

  const destroy = async (id) => {
    const response = await execute((signal) => api.delete(`/operaciones/${id}`, { signal }))
    return response
  }

  const solicitar = async (payload) => {
    const response = await execute((signal) => api.post('/operaciones/solicitud', payload, { signal }))
    return response
  }

  const iniciar = async (id) => {
    const response = await execute((signal) => api.post(`/operaciones/${id}/iniciar`, null, { signal }))
    return response
  }

  const cerrar = async (id) => {
    const response = await execute((signal) => api.post(`/operaciones/${id}/cerrar`, null, { signal }))
    return response
  }

  const cancelar = async (id, motivo) => {
    const response = await execute((signal) => api.post(`/operaciones/${id}/cancelar`, { motivo }, { signal }))
    return response
  }

  return {
    list,
    detail,
    fetchAll,
    fetchOne,
    create,
    update,
    verificar,
    destroy,
    solicitar,
    iniciar,
    cerrar,
    cancelar,
    loading,
    error,
  }
}
