/**
 * Store del pool de pagadores.
 * Gestiona las operaciones disponibles en el pool, las órdenes del usuario,
 * y las acciones: tomar, soltar, pagar y cancelar.
 */
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const usePoolStore = defineStore('pool', () => {
  /** @type {import('vue').Ref<Array>} Lista de operaciones en el pool */
  const pool = ref([])
  /** @type {import('vue').Ref<Array>} Órdenes tomadas por el usuario actual */
  const misOrdenes = ref([])
  /** @type {import('vue').Ref<boolean>} Indicador de carga del pool */
  const loadingPool = ref(false)
  /** @type {import('vue').Ref<boolean>} Indicador de carga de mis órdenes */
  const loadingMias = ref(false)
  /** @type {import('vue').Ref<string>} Mensaje de error de la última operación */
  const error = ref('')

  /**
   * Normaliza la respuesta de la API a un array.
   * @param {Object|Array} data - Respuesta de la API
   * @returns {Array} Array normalizado
   */
  function normalize(data) {
    return Array.isArray(data) ? data : (data.data || [])
  }

  /**
   * Obtiene el listado del pool de pagadores.
   * @returns {Promise<void>}
   */
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

  /**
   * Obtiene las órdenes tomadas por el usuario actual.
   * @returns {Promise<void>}
   */
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

  /**
   * Toma una operación del pool para gestionarla.
   * @param {number|string} id - ID de la operación en el pool
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function tomar(id) {
    const { data } = await api.post(`/pool/${id}/tomar`)
    return data
  }

  /**
   * Suelta (libera) una operación previamente tomada.
   * @param {number|string} id - ID de la operación
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function soltar(id) {
    const { data } = await api.post(`/pool/${id}/soltar`)
    return data
  }

  /**
   * Marca una operación como pagada.
   * @param {number|string} id - ID de la operación
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function pagar(id) {
    const { data } = await api.post(`/pool/${id}/pagar`)
    return data
  }

  /**
   * Cancela una operación con un motivo.
   * @param {number|string} id - ID de la operación
   * @param {string} motivo - Razón de la cancelación
   * @returns {Promise<Object>} Respuesta de la API
   */
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
