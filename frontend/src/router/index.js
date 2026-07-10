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
  { path: '/login', name: 'Login', component: () => import('../views/LoginView.vue') },
  /** Ruta padre: layout con AppShell que envuelve todas las secciones protegidas. */
  {
    path: '/',
    component: () => import('../components/AppShell.vue'),
    meta: { requiresAuth: true },
    children: [
      /** Redirección por defecto al dashboard. */
      { path: '', redirect: '/dashboard' },
      /** Panel principal con resumen de datos. */
      { path: 'dashboard', name: 'Dashboard', component: () => import('../views/DashboardView.vue') },
      /** Listado de operaciones registradas. */
      { path: 'operaciones', name: 'Operaciones', component: () => import('../views/OperacionesView.vue') },
      /** Selección de moneda para nueva operación. */
      { path: 'operaciones/nueva', name: 'OperacionMoneda', component: () => import('../views/OperacionMonedaView.vue') },
      /** Formulario de nueva operación para una moneda específica. */
      { path: 'operaciones/nueva/:moneda', name: 'OperacionNueva', component: () => import('../views/OperacionFormView.vue') },
      /** Formulario de operación intermediada. */
      { path: 'operaciones/intermediada/nueva', name: 'OperacionIntermediadaNueva', component: () => import('../views/OperacionIntermediadaForm.vue') },
      /** Detalle de una operación existente. */
      { path: 'operaciones/:id', name: 'OperacionDetail', component: () => import('../views/OperacionDetailView.vue') },
      /** Edición de una operación existente. */
      { path: 'operaciones/:id/editar', name: 'OperacionEdit', component: () => import('../views/OperacionFormView.vue') },
      /** Vista del pool de operaciones. */
      { path: 'pool', name: 'Pool', component: () => import('../views/PoolView.vue') },
      /** Gestión de tasas de cambio. */
      { path: 'tasas', name: 'Tasas', component: () => import('../views/TasasView.vue') },
      /** Gestión de titulares de cuentas. */
      { path: 'titulares', name: 'Titulares', component: () => import('../views/TitularesView.vue') },
      /** Gestión de bancos. */
      { path: 'bancos', name: 'Bancos', component: () => import('../views/BancosView.vue') },
      /** Gestión de clientes. */
      { path: 'clientes', name: 'Clientes', component: () => import('../views/ClientesView.vue') },
      /** Gestión de cuentas bancarias. */
      { path: 'cuentas', name: 'Cuentas', component: () => import('../views/CuentasView.vue') },
      /** Visualización de reportes y estadísticas. */
      { path: 'reportes', name: 'Reportes', component: () => import('../views/ReportesView.vue') },
      /** Administración de usuarios del sistema. */
      { path: 'usuarios', name: 'Usuarios', component: () => import('../views/UsuariosView.vue') },
      /** Gestión de comisiones. */
      { path: 'comisiones', name: 'Comisiones', component: () => import('../views/ComisionesView.vue') },
    ]
  }
]

/** Instancia del enrutador con historial HTML5. */
const router = createRouter({
  history: createWebHistory(),
  routes,
})

/** Guard de navegación: verifica autenticación y redirige según el estado. */
router.beforeEach(async (to) => {
  const auth = useAuthStore()
  
  // Iniciar verificación en paralelo, sin esperar
  if (!auth.initialized) auth.init()
  
  // No bloquear: verificar solo si hay token
  if (to.meta.requiresAuth && !auth.token) return '/login'
  if (to.path === '/login' && auth.token) return '/dashboard'
})
export default router
