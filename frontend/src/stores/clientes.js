/**
 * Store de clientes.
 * Gestiona el listado (activos e inactivos), creación, actualización y restauración de clientes.
 */
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const useClientesStore = defineStore('clientes', () => {
  /** @type {import('vue').Ref<Array>} Lista de clientes */
  const list = ref([])
  /** @type {import('vue').Ref<boolean>} Indicador de carga */
  const loading = ref(false)
  /** @type {import('vue').Ref<string>} Mensaje de error de la última operación */
  const error = ref('')

  /**
   * Obtiene clientes activos, con búsqueda opcional.
   * @param {string} [search=''] - Término de búsqueda
   * @returns {Promise<void>}
   */
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

  /**
   * Obtiene clientes inactivos (soft-deleted), con búsqueda opcional.
   * @param {string} [search=''] - Término de búsqueda
   * @returns {Promise<void>}
   */
  async function fetchTrashed(search = '') {
    loading.value = true
    try {
      const params = { inactivos: true }
      if (search) params.q = search
      const { data } = await api.get('/clientes', { params })
      list.value = Array.isArray(data) ? data : (data.data || [])
    } catch (err) {
      error.value = err.response?.data?.message || err.message
    } finally {
      loading.value = false
    }
  }

  /**
   * Crea un nuevo cliente.
   * @param {Object} body - Datos del cliente
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function create(body) {
    const { data } = await api.post('/clientes', body)
    return data
  }

  /**
   * Actualiza un cliente existente.
   * @param {number|string} id - ID del cliente
   * @param {Object} body - Datos a actualizar
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function update(id, body) {
    const { data } = await api.put(`/clientes/${id}`, body)
    return data
  }

  /**
   * Restaura un cliente eliminado (soft-delete).
   * @param {number|string} id - ID del cliente
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function restore(id) {
    const { data } = await api.post(`/clientes/${id}/restaurar`)
    return data
  }

  return { list, loading, error, fetchAll, fetchTrashed, create, update, restore }
})
