/**
 * Store de autenticación.
 * Maneja inicio/cierre de sesión, token JWT, y datos del usuario autenticado.
 * También expone computados para verificar roles (admin, super_admin).
 */
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/axios.js'

export const useAuthStore = defineStore('auth', () => {
  /** @type {import('vue').Ref<Object|null>} Datos del usuario autenticado */
  const user = ref(null)
  /** @type {import('vue').Ref<string>} Token de acceso Bearer (persistido en localStorage) */
  const token = ref(localStorage.getItem('token') || '')
  /** @type {import('vue').Ref<boolean>} Indicador de carga en operaciones de auth */
  const loading = ref(false)
  /** @type {import('vue').Ref<string>} Mensaje de error de la última operación */
  const error = ref('')
  /** @type {import('vue').Ref<boolean>} Indica si ya se verificó la sesión al cargar la app */
  const initialized = ref(false)

  /** @type {import('vue').ComputedRef<boolean>} Verdadero si hay usuario y token */
  const isAuthenticated = computed(() => !!user.value && !!token.value)
  /** @type {import('vue').ComputedRef<boolean>} Verdadero si el usuario tiene rol admin o super_admin */
  const isAdmin = computed(() => user.value?.roles?.includes('admin') || user.value?.roles?.includes('super_admin'))
  /** @type {import('vue').ComputedRef<boolean>} Verdadero si el usuario tiene rol super_admin */
  const isSuperAdmin = computed(() => user.value?.roles?.includes('super_admin'))
  /** @type {import('vue').ComputedRef<boolean>} Verdadero si el usuario tiene rol pagador */
  const isPagador = computed(() => user.value?.roles?.includes('pagador'))
  /** @type {import('vue').ComputedRef<boolean>} Verdadero si el usuario tiene rol operador */
  const isOperador = computed(() => user.value?.roles?.includes('operador'))
  /** @type {import('vue').ComputedRef<boolean>} Verdadero si el usuario tiene rol contador */
  const isContador = computed(() => user.value?.roles?.includes('contador'))
  /** @type {import('vue').ComputedRef<boolean>} Verdadero si el usuario tiene rol lectura (solo lectura) */
  const isLectura = computed(() => user.value?.roles?.includes('lectura'))

  /**
   * Verifica si el usuario tiene un rol específico.
   * @param {string} role - Nombre del rol (admin, super_admin, operador, pagador, contador, lectura)
   * @returns {boolean}
   */
  const hasRole = (role) => user.value?.roles?.includes(role) ?? false

  /**
   * Verifica si el usuario tiene al menos uno de los roles dados.
   * @param {string[]} roles - Lista de roles permitidos
   * @returns {boolean}
   */
  const hasAnyRole = (roles) => (user.value?.roles ?? []).some((r) => roles.includes(r))

  /** @type {import('vue').ComputedRef<boolean>} Puede crear/editar catálogos y gestionar operaciones (admin, super_admin, operador) */
  const canWrite = computed(() => hasAnyRole(['admin', 'super_admin', 'operador']))
  /** @type {import('vue').ComputedRef<boolean>} Puede operar el pool de pagos (admin, super_admin, pagador) */
  const canPool = computed(() => hasAnyRole(['admin', 'super_admin', 'pagador']))
  /** @type {import('vue').ComputedRef<boolean>} Puede ver reportes y comisiones (admin, super_admin, contador) */
  const canReports = computed(() => hasAnyRole(['admin', 'super_admin', 'contador']))
  /** @type {import('vue').ComputedRef<boolean>} Puede gestionar configuración y usuarios (admin, super_admin) */
  const canConfig = computed(() => hasAnyRole(['admin', 'super_admin']))

  /**
   * Verifica la sesión actual consultando /auth/me.
   * Si no hay token, marca como inicializado sin hacer nada.
   * Si el token es inválido, lo remueve de memoria y localStorage.
   * @returns {Promise<void>}
   */
  async function init() {
    if (!token.value) {
      initialized.value = true
      return
    }
    try {
      const { data } = await api.get('/auth/me')
      user.value = data.data || data
      initialized.value = true
    } catch {
      token.value = ''
      localStorage.removeItem('token')
      initialized.value = true
    }
  }

  /**
   * Inicia sesión con email/username y contraseña.
   * @param {string} login - Correo electrónico o nombre de usuario
   * @param {string} password - Contraseña
   * @returns {Promise<boolean>} true si el login fue exitoso, false en caso contrario
   */
  async function login(login, password) {
    loading.value = true
    error.value = ''
    try {
      const { data } = await api.post('/auth/login', { login, password })
      token.value = data.token || data.data?.token || data
localStorage.setItem('token', token.value)
      user.value = data.user
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Credenciales incorrectas'
      return false
    } finally {
      loading.value = false
    }
  }

  /**
   * Cierra la sesión actual.
   * Envía POST /auth/logout y limpia token/usuario de memoria y localStorage.
   * @returns {Promise<void>}
   */
  async function logout() {
    try { await api.post('/auth/logout') } catch {}
    user.value = null
    token.value = ''
    localStorage.removeItem('token')
  }

  /**
   * Reemplaza los datos del usuario autenticado (tras actualizar el perfil).
   * @param {Object} u - Usuario actualizado (formato del backend)
   * @returns {void}
   */
  const actualizarUsuario = (u) => {
    user.value = u
  }

  return { user, token, loading, error, initialized, isAuthenticated, isAdmin, isSuperAdmin, isPagador, isOperador, isContador, isLectura, hasRole, hasAnyRole, canWrite, canPool, canReports, canConfig, init, login, logout, actualizarUsuario }
})
