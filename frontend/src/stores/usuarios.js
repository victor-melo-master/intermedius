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
  /** @type {import('vue').Ref<Object>} Mapa id → last_active_at de usuarios en línea */
  const enLinea = ref({})
  /** @type {import('vue').Ref<number>} Total de usuarios en línea */
  const enLineaTotal = ref(0)

  /**
   * Obtiene todos los usuarios del sistema con filtros opcionales.
   * @param {Object} [params] - Filtros: q (búsqueda), rol, activo
   * @returns {Promise<void>}
   */
  async function fetchAll(params = {}) {
    loading.value = true
    error.value = ''
    try {
      const { data } = await api.get('/usuarios', { params })
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
   * Actualiza el usuario en la lista en el lugar (sin recargar el listado completo).
   * @param {Object} usuario - Objeto usuario con al menos { id, activo }
   * @returns {Promise<Object>} Respuesta de la API
   */
  async function toggleActivo(usuario) {
    const { data } = await api.put(`/usuarios/${usuario.id}`, { activo: !usuario.activo })
    const idx = list.value.findIndex((u) => u.id === usuario.id)
    if (idx !== -1) {
      list.value[idx] = { ...list.value[idx], ...data, activo: !usuario.activo }
    }
    return data
  }

  /**
   * Obtiene los usuarios en línea (con actividad reciente) y guarda un mapa id → last_active_at.
   * @returns {Promise<void>}
   */
  async function fetchEnLinea() {
    try {
      const { data } = await api.get('/usuarios/en-linea')
      const mapa = {}
      for (const u of data.usuarios) {
        mapa[u.id] = u.last_active_at
      }
      enLinea.value = mapa
      enLineaTotal.value = data.total
    } catch {
      // Silencioso: el listado principal sigue funcionando sin el estado en línea.
    }
  }

  /**
   * Indica si un usuario está en línea.
   * @param {number|string} id - ID del usuario
   * @returns {boolean}
   */
  function estaEnLinea(id) {
    return id in enLinea.value
  }

  return { list, loading, error, enLinea, enLineaTotal, fetchAll, create, update, toggleActivo, fetchEnLinea, estaEnLinea }
})
