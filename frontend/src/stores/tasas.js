import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const useTasasStore = defineStore('tasas', () => {
  const vigentes = ref([])
  const historial = ref([])
  const monedas = ref([])
  const loading = ref(false)
  const error = ref('')

  async function fetchVigentes() {
    loading.value = true
    try {
      const { data } = await api.get('/configuracion/tasas-vigentes')
      vigentes.value = data.data || []
    } catch (err) {
      error.value = err.response?.data?.message || err.message
    } finally {
      loading.value = false
    }
  }

  async function fetchHistorial() {
    loading.value = true
    try {
      const { data } = await api.get('/configuracion/tasas-diarias')
      historial.value = data.data || []
    } catch (err) {
      error.value = err.response?.data?.message || err.message
    } finally {
      loading.value = false
    }
  }

  async function fetchMonedas() {
    try {
      const { data } = await api.get('/monedas')
      monedas.value = data.data || []
    } catch {}
  }

  async function publicar(body) {
    const { data } = await api.post('/configuracion/tasas-diarias', body)
    return data
  }

  return { vigentes, historial, monedas, loading, error, fetchVigentes, fetchHistorial, fetchMonedas, publicar }
})
