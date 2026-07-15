import { ref, onUnmounted } from 'vue'
import { useApi } from './useApi'
import api from '@/api/axios'

export function useClientSearch(delay = 300, customSearchFn = null) {
  const { execute, loading, error } = useApi()
  const query = ref('')
  const results = ref([])
  const selected = ref(null)
  let timeout = null

  const search = async (term) => {
    if (!term || term.length < 2) {
      results.value = []
      return
    }

    try {
      const response = await execute((signal) => {
        if (customSearchFn) {
          return customSearchFn(term, signal)
        }
        return api.get('/clientes', {
          params: { search: term },
          signal,
        })
      })
      const data = response?.data?.data || response?.data || []
      results.value = Array.isArray(data) ? data : []
    } catch (e) {
      results.value = []
    }
  }

  const onSearch = (term) => {
    query.value = term
    if (timeout) clearTimeout(timeout)
    timeout = setTimeout(() => search(term), delay)
  }

  const clearSearch = () => {
    query.value = ''
    results.value = []
    if (timeout) {
      clearTimeout(timeout)
      timeout = null
    }
  }

  const select = (client) => {
    selected.value = client
    query.value = client?.nombre || ''
    results.value = []
    return client
  }

  onUnmounted(() => {
    if (timeout) clearTimeout(timeout)
  })

  return {
    query,
    results,
    selected,
    loading,
    error,
    onSearch,
    clearSearch,
    search,
    select,
  }
}
