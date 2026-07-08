/**
 * Store de usuarios.
 * Gestiona el listado, creación, actualización y activación/desactivación de usuarios del sistema.
 */
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const useUsuariosStore = defineStore('usuarios', () => {
  /** @type {import('vue').Ref<Array>} Lista de usuarios */
  const list = ref([])
  /** @type {import('vue').Ref<boolean>} Indicador de carga */
  const loading = ref(false)
  /** @type {import('vue').Ref<string>} Mensaje de error de la última operación */
  const error = ref('')

  /**
   * Obtiene todos los usuarios del sistema.
   * @returns {Promise<void>}
   */
  async function fetchAll() {
    loading.value = true
    error.value = ''
    try {
      const { data } = await api.get('/usuarios')
      list.value = Array.isArray(data) ? data : (data.data || [])
    } catch (err) {
      error.value = err.response?.data?.message || err.message
    } finally {
      loading.value = false
    }
  }

  /**
   * Crea un nuevo usuario.
   * @param {Object} body - Datos del usuario
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function create(body) {
    const { data } = await api.post('/usuarios', body)
    list.value = []
    return data
  }

  /**
   * Actualiza un usuario existente.
   * @param {number|string} id - ID del usuario
   * @param {Object} body - Datos a actualizar
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function update(id, body) {
    const { data } = await api.put(`/usuarios/${id}`, body)
    list.value = []
    return data
  }

  /**
   * Activa o desactiva un usuario alternando su estado.
   * @param {Object} usuario - Objeto usuario con al menos { id, activo }
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function toggleActivo(usuario) {
    const { data } = await api.put(`/usuarios/${usuario.id}`, { activo: !usuario.activo })
    list.value = []
    return data
  }

  return { list, loading, error, fetchAll, create, update, toggleActivo }
})
