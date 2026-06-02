import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const useTitularesStore = defineStore('titulares', () => {
  const list = ref([])
  const loading = ref(false)
  const error = ref('')

  async function fetchAll() {
    loading.value = true
    error.value = ''
    try {
      const { data } = await api.get('/titulares')
      list.value = Array.isArray(data) ? data : (data.data || [])
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error al cargar titulares:', err)
    } finally {
      loading.value = false
    }
  }

  async function create(body) {
    const { data } = await api.post('/titulares', body)
    list.value = []
    return data
  }

  async function update(id, body) {
    const { data } = await api.put(`/titulares/${id}`, body)
    list.value = []
    return data
  }

  return { list, loading, error, fetchAll, create, update }
})
