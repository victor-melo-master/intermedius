import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

const routes = [
  { path: '/login', name: 'Login', component: () => import('../views/LoginView.vue') },
  {
    path: '/',
    component: () => import('../components/AppShell.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      { path: 'dashboard', name: 'Dashboard', component: () => import('../views/DashboardView.vue') },
      { path: 'operaciones', name: 'Operaciones', component: () => import('../views/OperacionesView.vue') },
      { path: 'operaciones/nueva', name: 'OperacionNueva', component: () => import('../views/OperacionFormView.vue') },
      { path: 'operaciones/:id', name: 'OperacionDetalle', component: () => import('../views/OperacionDetailView.vue'), props: true },
      { path: 'tasas', name: 'Tasas', component: () => import('../views/TasasView.vue') },
      { path: 'clientes', name: 'Clientes', component: () => import('../views/ClientesView.vue') },
      { path: 'cuentas', name: 'Cuentas', component: () => import('../views/CuentasView.vue') },
      { path: 'reportes', name: 'Reportes', component: () => import('../views/ReportesView.vue') },
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (!auth.initialized) await auth.init()

  if (to.meta.requiresAuth && !auth.isAuthenticated) return '/login'
  if (to.path === '/login' && auth.isAuthenticated) return '/dashboard'
})

export default router
