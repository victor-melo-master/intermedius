import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const usePoolStore = defineStore('pool', () => {
  const pool = ref([])
  const misOrdenes = ref([])
  const loadingPool = ref(false)
  const loadingMias = ref(false)
  const error = ref('')

  function normalize(data) {
    return Array.isArray(data) ? data : (data.data || [])
  }

  async function fetchPool() {
    loadingPool.value = true
    error.value = ''
    try {
      const { data } = await api.get('/pool')
      pool.value = normalize(data)
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error al cargar el pool:', err)
    } finally {
      loadingPool.value = false
    }
  }

  async function fetchMisOrdenes() {
    loadingMias.value = true
    error.value = ''
    try {
      const { data } = await api.get('/pool/mis-ordenes')
      misOrdenes.value = normalize(data)
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error al cargar mis órdenes:', err)
    } finally {
      loadingMias.value = false
    }
  }

  async function tomar(id) {
    const { data } = await api.post(`/pool/${id}/tomar`)
    return data
  }

  async function soltar(id) {
    const { data } = await api.post(`/pool/${id}/soltar`)
    return data
  }

  async function pagar(id) {
    const { data } = await api.post(`/pool/${id}/pagar`)
    return data
  }

  async function cancelar(id, motivo) {
    const { data } = await api.post(`/pool/${id}/cancelar`, { motivo_cancelacion: motivo })
    return data
  }

  return {
    pool,
    misOrdenes,
    loadingPool,
    loadingMias,
    error,
    fetchPool,
    fetchMisOrdenes,
    tomar,
    soltar,
    pagar,
    cancelar,
  }
})
