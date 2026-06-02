import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const useClientesStore = defineStore('clientes', () => {
  const list = ref([])
  const loading = ref(false)
  const error = ref('')

  async function fetchAll(search = '') {
    loading.value = true
    try {
      const params = search ? { q: search } : {}
      const { data } = await api.get('/clientes', { params })
      list.value = Array.isArray(data) ? data : (data.data || [])
    } catch (err) {
      error.value = err.response?.data?.message || err.message
    } finally {
      loading.value = false
    }
  }

  async function create(body) {
    const { data } = await api.post('/clientes', body)
    return data
  }

  async function update(id, body) {
    const { data } = await api.put(`/clientes/${id}`, body)
    return data
  }

  return { list, loading, error, fetchAll, create, update }
})
