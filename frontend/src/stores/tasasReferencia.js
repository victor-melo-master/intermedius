/**
 * Store de tasas de referencia para el dashboard.
 * Contiene las tasas de referencia actuales utilizadas en la vista de dashboard.
 */
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const useTasasReferenciaStore = defineStore('tasasReferencia', () => {
  /** @type {import('vue').Ref<Object|null} Datos de tasas de referencia */
  const tasas = ref(null)
  /** @type {import('vue').Ref<boolean>} Indicador de carga */
  const loading = ref(false)
  /** @type {import('vue').Ref<string>} Mensaje de error de la última operación */
  const error = ref('')

  /**
   * Obtiene las tasas de referencia desde la API del dashboard.
   * @returns {Promise<void>}
   */
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
