import { ref } from 'vue'
import { useApi } from './useApi'
import api from '@/api/axios'

export function useClientes() {
  const { execute, loading, error } = useApi()
  const list = ref([])
  const detail = ref(null)

  const fetchAll = async (params = {}) => {
    const response = await execute((signal) => api.get('/clientes', { params, signal }))
    list.value = response?.data?.data || response?.data || []
    return response
  }

  const fetchOne = async (id) => {
    const response = await execute((signal) => api.get(`/clientes/${id}`, { signal }))
    detail.value = response?.data?.data || response?.data
    return response
  }

  const create = async (payload) => {
    const response = await execute((signal) => api.post('/clientes', payload, { signal }))
    return response
  }

  const update = async (id, payload) => {
    const response = await execute((signal) => api.put(`/clientes/${id}`, payload, { signal }))
    detail.value = response?.data?.data || response?.data
    return response
  }

  const destroy = async (id) => {
    const response = await execute((signal) => api.delete(`/clientes/${id}`, { signal }))
    return response
  }

  const restore = async (id) => {
    const response = await execute((signal) => api.patch(`/clientes/${id}/restore`, null, { signal }))
    return response
  }

  const search = async (query) => {
    const response = await execute((signal) => api.get('/clientes', { params: { search: query }, signal }))
    return response?.data?.data || response?.data || []
  }

  return {
    list,
    detail,
    fetchAll,
    fetchOne,
    create,
    update,
    destroy,
    restore,
    search,
    loading,
    error,
  }
}
