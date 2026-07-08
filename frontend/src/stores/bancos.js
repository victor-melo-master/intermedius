/**
 * Store de bancos.
 * Gestiona el listado, creación y actualización de bancos.
 */
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const useBancosStore = defineStore('bancos', () => {
  /** @type {import('vue').Ref<Array>} Lista de bancos */
  const list = ref([])
  /** @type {import('vue').Ref<boolean>} Indicador de carga */
  const loading = ref(false)
  /** @type {import('vue').Ref<string>} Mensaje de error de la última operación */
  const error = ref('')

  /**
   * Obtiene todos los bancos desde la API.
   * @returns {Promise<void>}
   */
  async function fetchAll() {
    loading.value = true
    error.value = ''
    try {
      const { data } = await api.get('/bancos')
      list.value = Array.isArray(data) ? data : (data.data || [])
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error al cargar bancos:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Crea un nuevo banco.
   * @param {Object} body - Datos del banco a crear
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function create(body) {
    const { data } = await api.post('/bancos', body)
    list.value = []
    return data
  }

  /**
   * Actualiza un banco existente.
   * @param {number|string} id - ID del banco
   * @param {Object} body - Datos a actualizar
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function update(id, body) {
    const { data } = await api.put(`/bancos/${id}`, body)
    list.value = []
    return data
  }

  return { list, loading, error, fetchAll, create, update }
})
