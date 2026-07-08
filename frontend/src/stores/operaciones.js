/**
 * Store de operaciones.
 * Gestiona el listado (con filtros), detalle, creación, actualización y verificación de operaciones.
 */
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const useOperacionesStore = defineStore('operaciones', () => {
  /** @type {import('vue').Ref<Array>} Lista de operaciones */
  const list = ref([])
  /** @type {import('vue').Ref<Object|null>} Detalle de una operación específica */
  const detail = ref(null)
  /** @type {import('vue').Ref<boolean>} Indicador de carga */
  const loading = ref(false)
  /** @type {import('vue').Ref<string>} Mensaje de error de la última operación */
  const error = ref('')

  /**
   * Obtiene listado de operaciones con filtros opcionales.
   * @param {Object} [params={}] - Parámetros de consulta (filtros, paginación, etc.)
   * @returns {Promise<void>}
   */
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

  /**
   * Obtiene el detalle de una operación por ID.
   * @param {number|string} id - ID de la operación
   * @returns {Promise<void>}
   */
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

  /**
   * Crea una nueva operación.
   * @param {Object} body - Datos de la operación
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function create(body) {
    const { data } = await api.post('/operaciones', body)
    list.value = []
    return data
  }

  /**
   * Actualiza una operación existente.
   * @param {number|string} id - ID de la operación
   * @param {Object} body - Datos a actualizar
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function update(id, body) {
    const { data } = await api.put(`/operaciones/${id}`, body)
    detail.value = data.data || data
    return data
  }

  /**
   * Marca una operación como verificada.
   * @param {number|string} id - ID de la operación
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function verificar(id) {
    const { data } = await api.patch(`/operaciones/${id}/verificar`)
    return data
  }

  return { list, detail, loading, error, fetchAll, fetchOne, create, update, verificar }
})
