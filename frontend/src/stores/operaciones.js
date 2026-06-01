import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const useOperacionesStore = defineStore('operaciones', () => {
  const list = ref([])
  const detail = ref(null)
  const loading = ref(false)
  const error = ref('')

  async function fetchAll(params = {}) {
    loading.value = true
    try {
      const { data } = await api.get('/operaciones', { params })
      list.value = data.data || []
    } catch (err) {
      error.value = err.response?.data?.message || err.message
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id) {
    loading.value = true
    try {
      const { data } = await api.get(`/operaciones/${id}`)
      detail.value = data.data || data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
    } finally {
      loading.value = false
    }
  }

  async function create(body) {
    const { data } = await api.post('/operaciones', body)
    list.value = []
    return data
  }

  async function verificar(id) {
    const { data } = await api.patch(`/operaciones/${id}/verificar`)
    return data
  }

  return { list, detail, loading, error, fetchAll, fetchOne, create, verificar }
})
