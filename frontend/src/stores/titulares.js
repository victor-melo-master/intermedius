/**
 * Store de titulares.
 * Gestiona el listado, creación y actualización de titulares.
 */
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const useTitularesStore = defineStore('titulares', () => {
  /** @type {import('vue').Ref<Array>} Lista de titulares */
  const list = ref([])
  /** @type {import('vue').Ref<boolean>} Indicador de carga */
  const loading = ref(false)
  /** @type {import('vue').Ref<string>} Mensaje de error de la última operación */
  const error = ref('')

  /**
   * Obtiene todos los titulares desde la API.
   * @returns {Promise<void>}
   */
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

  /**
   * Crea un nuevo titular.
   * @param {Object} body - Datos del titular
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function create(body) {
    const { data } = await api.post('/titulares', body)
    list.value = []
    return data
  }

  /**
   * Actualiza un titular existente.
   * @param {number|string} id - ID del titular
   * @param {Object} body - Datos a actualizar
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function update(id, body) {
    const { data } = await api.put(`/titulares/${id}`, body)
    list.value = []
    return data
  }

  return { list, loading, error, fetchAll, create, update }
})
