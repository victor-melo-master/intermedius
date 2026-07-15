import { ref } from 'vue'
import { useApi } from './useApi'
import api from '@/api/axios'

export function useBancos() {
  const { execute, loading, error } = useApi()
  const list = ref([])

  const fetchAll = async () => {
    const response = await execute((signal) => api.get('/bancos', { signal }))
    list.value = Array.isArray(response?.data) ? response.data : (response?.data?.data || [])
    return response
  }

  const create = async (body) => {
    const response = await execute((signal) => api.post('/bancos', body, { signal }))
    list.value = []
    return response
  }

  const update = async (id, body) => {
    const response = await execute((signal) => api.put(`/bancos/${id}`, body, { signal }))
    list.value = []
    return response
  }

  return { list, loading, error, fetchAll, create, update }
}
