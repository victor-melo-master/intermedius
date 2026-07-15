import { ref } from 'vue'
import { useApi } from './useApi'
import api from '@/api/axios'

export function useTitulares() {
  const { execute, loading, error } = useApi()
  const list = ref([])

  const fetchAll = async () => {
    try {
      const response = await execute((signal) => api.get('/titulares', { signal }))
      list.value = Array.isArray(response?.data) ? response.data : (response?.data?.data || [])
    } catch (e) {
      console.warn('Error cargando titulares:', e)
      list.value = []
    }
  }

  const getIntermedius = () => {
    return list.value.find(t => t.nombre === 'Intermedius') || null
  }

  return { list, fetchAll, getIntermedius, loading, error }
}
