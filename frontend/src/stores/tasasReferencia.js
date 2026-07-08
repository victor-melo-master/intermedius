import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const useTasasReferenciaStore = defineStore('tasasReferencia', () => {
  const tasas = ref(null)
  const loading = ref(false)
  const error = ref('')

  async function fetch() {
    loading.value = true
    error.value = ''
    try {
      const { data } = await api.get('/dashboard/tasas-referencia')
      tasas.value = data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
    } finally {
      loading.value = false
    }
  }

  return { tasas, loading, error, fetch }
})
