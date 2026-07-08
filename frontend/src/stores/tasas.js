/**
 * Store de tasas de mercado.
 * Gestiona tasas vigentes, historial de tasas diarias y monedas disponibles.
 */
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const useTasasStore = defineStore('tasas', () => {
  /** @type {import('vue').Ref<Array>} Tasas vigentes actuales */
  const vigentes = ref([])
  /** @type {import('vue').Ref<Array>} Historial de tasas diarias */
  const historial = ref([])
  /** @type {import('vue').Ref<Array>} Lista de monedas disponibles */
  const monedas = ref([])
  /** @type {import('vue').Ref<boolean>} Indicador de carga */
  const loading = ref(false)
  /** @type {import('vue').Ref<string>} Mensaje de error de la última operación */
  const error = ref('')

  /**
   * Obtiene las tasas vigentes desde la API.
   * @returns {Promise<void>}
   */
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

  /**
   * Obtiene el historial de tasas diarias.
   * @returns {Promise<void>}
   */
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

  /**
   * Obtiene la lista de monedas disponibles.
   * @returns {Promise<void>}
   */
  async function fetchMonedas() {
    try {
      const { data } = await api.get('/monedas')
      monedas.value = Array.isArray(data) ? data : (data.data || [])
      console.log('Monedas cargadas:', monedas.value)
    } catch (err) {
      console.error('Error al cargar monedas:', err)
      error.value = err.response?.data?.message || err.message
    }
  }

  /**
   * Publica una nueva tasa diaria.
   * @param {Object} body - Datos de la tasa a publicar
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function publicar(body) {
    const { data } = await api.post('/configuracion/tasas-diarias', body)
    return data
  }

  return { vigentes, historial, monedas, loading, error, fetchVigentes, fetchHistorial, fetchMonedas, publicar }
})
