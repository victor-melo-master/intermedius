/**
 * Configuración del enrutador Vue Router.
 * Define todas las rutas públicas y protegidas (requieren autenticación),
 * el guard de navegación beforeEach para control de acceso,
 * y el historial en modo HTML5 (sin hash).
 */
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

/** Definición de todas las rutas de la aplicación. */
const routes = [
  /** Ruta pública: pantalla de inicio de sesión. */
  { path: '/login', name: 'Login', component: () => import('../views/auth/LoginView.vue') },
  { path: '/email/verify', name: 'EmailVerify', component: () => import('../views/auth/EmailVerifyView.vue') },
  /** Ruta padre: layout con AppShell que envuelve todas las secciones protegidas. */
  {
    path: '/',
    component: () => import('../components/layout/AppShell.vue'),
    meta: { requiresAuth: true },
    children: [
      /** Redirección por defecto al dashboard. */
      { path: '', redirect: '/dashboard' },
      /** Panel principal con resumen de datos. */
      { path: 'dashboard', name: 'Dashboard', component: () => import('../views/dashboard/DashboardView.vue') },
      /** Listado de operaciones registradas. */
      { path: 'operaciones', name: 'Operaciones', component: () => import('../views/operaciones/OperacionesView.vue') },
      /** Formulario de nueva compra de divisa (flujo progresivo). */
      { path: 'operaciones/venta/nueva', name: 'VentaNueva', component: () => import('../views/operaciones/VentaFormView.vue'), meta: { roles: ['admin', 'super_admin', 'operador'] } },
      { path: 'operaciones/nueva', name: 'CompraNueva', component: () => import('../views/operaciones/CompraFormView.vue'), meta: { roles: ['admin', 'super_admin', 'operador'] } },
      { path: 'operaciones/nueva/:moneda', name: 'CompraNuevaConMoneda', component: () => import('../views/operaciones/CompraFormView.vue'), meta: { roles: ['admin', 'super_admin', 'operador'] } },
      /** Formulario de operación intermediada. */
      { path: 'operaciones/intermediada/nueva', name: 'OperacionIntermediadaNueva', component: () => import('../views/operaciones/OperacionIntermediadaForm.vue'), meta: { roles: ['admin', 'super_admin', 'operador'] } },
      /** Detalle de una operación existente. */
      { path: 'operaciones/:id', name: 'OperacionDetail', component: () => import('../views/operaciones/OperacionDetailView.vue') },
      /** Verificación de una operación. */
      { path: 'operaciones/:id/verificar', name: 'Verificacion', component: () => import('../views/operaciones/VerificacionView.vue'), meta: { roles: ['admin', 'super_admin'] } },
      /** Gestión multi-paso de una operación (solicitud → en_progreso → cerrada). */
      { path: 'operaciones/:id/gestionar', name: 'GestionarOperacion', component: () => import('../views/operaciones/GestionarOperacionView.vue'), meta: { roles: ['admin', 'super_admin', 'operador'] } },
      /** Edición de una operación existente. */
      { path: 'operaciones/:id/editar', name: 'OperacionEdit', component: () => import('../views/operaciones/OperacionFormView.vue'), meta: { roles: ['admin', 'super_admin', 'operador'] } },
      /** Vista del pool de operaciones. */
      { path: 'pool', name: 'Pool', component: () => import('../views/pool/PoolView.vue'), meta: { roles: ['admin', 'super_admin', 'pagador'] } },
      /** Gestión de tasas de cambio. */
      { path: 'tasas', name: 'Tasas', component: () => import('../views/configuracion/TasasView.vue') },
      /** Gestión de titulares de cuentas. */
      { path: 'titulares', name: 'Titulares', component: () => import('../views/catalogos/TitularesView.vue') },
      /** Gestión de bancos. */
      { path: 'bancos', name: 'Bancos', component: () => import('../views/catalogos/BancosView.vue') },
      /** Gestión de clientes. */
      { path: 'clientes', name: 'Clientes', component: () => import('../views/catalogos/ClientesView.vue') },
      /** Gestión de cuentas bancarias. */
      { path: 'cuentas', name: 'Cuentas', component: () => import('../views/catalogos/CuentasView.vue') },
      /** Visualización de reportes y estadísticas. */
      { path: 'reportes', name: 'Reportes', component: () => import('../views/reportes/ReportesView.vue'), meta: { roles: ['admin', 'super_admin', 'contador'] } },
      /** Administración de usuarios del sistema. */
      { path: 'usuarios', name: 'Usuarios', component: () => import('../views/catalogos/UsuariosView.vue'), meta: { roles: ['admin', 'super_admin'] } },
      /** Gestión de comisiones. */
      { path: 'comisiones', name: 'Comisiones', component: () => import('../views/configuracion/ComisionesView.vue'), meta: { roles: ['admin', 'super_admin', 'contador'] } },
      { path: '/:pathMatch(.*)*', name: 'NotFound', component: () => import('../views/NotFoundView.vue') }
    ]
  }
]

/** Instancia del enrutador con historial HTML5. */
const router = createRouter({
  history: createWebHistory(),
  routes,
})

/** Guard de navegación: verifica autenticación y permisos por rol. */
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Cargar la sesión (y los roles del usuario) antes de evaluar permisos
  if (auth.token && !auth.initialized) await auth.init()

  // No bloquear: verificar solo si hay token
  if (to.meta.requiresAuth && !auth.token) return '/login'
  if (to.path === '/login' && auth.token) return '/dashboard'

  // Verificar roles permitidos para la ruta
  if (to.meta.roles && !auth.hasAnyRole(to.meta.roles)) return '/dashboard'
})
export default router
