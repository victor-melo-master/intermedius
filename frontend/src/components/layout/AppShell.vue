<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <button class="lg:hidden p-2 -ml-2 text-gray-600 hover:bg-gray-100 rounded-lg" @click="drawer = !drawer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold">I</div>
            <span class="font-bold text-lg text-gray-800 hidden sm:block">Intermedius</span>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-500 hidden sm:block">{{ auth.user?.name }}</span>
          <button @click="logout" class="text-sm text-red-600 hover:text-red-700 px-3 py-1.5 rounded-lg hover:bg-red-50 transition">
            Salir
          </button>
        </div>
      </div>
    </header>

    <div class="flex flex-1 w-full">
      <!-- Sidebar desktop -->
      <aside class="hidden lg:block w-56 shrink-0 border-r border-gray-200 bg-white h-[calc(100vh-3.5rem)] sticky top-14">
        <nav class="p-3 space-y-1">
          <router-link v-for="item in nav" :key="item.path" :to="item.path"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition"
             :class="$route.path.startsWith(item.path) ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50'">
            <Iconoir :name="item.icon" :class="$route.path.startsWith(item.path) ? 'text-blue-700' : item.color" />
            {{ item.label }}
          </router-link>
        </nav>
      </aside>

      <!-- Mobile drawer -->
      <div v-if="drawer" class="fixed inset-0 z-40 lg:hidden" @click="drawer = false">
        <div class="absolute inset-0 bg-black/40"></div>
        <aside class="absolute left-0 top-0 bottom-0 w-64 bg-white p-4" @click.stop>
          <div class="flex items-center justify-between mb-6">
            <span class="font-bold text-lg">Intermedius</span>
            <button @click="drawer = false" class="p-1 hover:bg-gray-100 rounded"><Iconoir name="x-mark" class="text-gray-400" /></button>
          </div>
          <nav class="space-y-1">
            <router-link v-for="item in nav" :key="item.path" :to="item.path" @click="drawer = false"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition"
              :class="$route.path.startsWith(item.path) ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50'">
              <Iconoir :name="item.icon" :class="$route.path.startsWith(item.path) ? 'text-blue-700' : item.color" />
              {{ item.label }}
            </router-link>
          </nav>
        </aside>
      </div>

      <!-- Main -->
      <main class="flex-1 min-w-0 p-4 lg:p-6 overflow-auto">
        <div class="max-w-7xl mx-auto">
          <router-view />
        </div>
      </main>
    </div>
    <PoolAlarm />
  </div>
</template>

<script setup>
/**
 * Componente de layout principal.
 * Renderiza la estructura global con header, sidebar y router-view para el contenido.
 *
 * @component
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth.js'
import { useInactivityTimer } from '../../composables/useInactivityTimer.js'
import PoolAlarm from '../pool/PoolAlarm.vue'
import echo from '../../plugins/echo'
import Iconoir from '../../components/common/Iconoir.vue'

const router = useRouter()
const auth = useAuthStore()
useInactivityTimer()

/** @type {import('vue').Ref<boolean>} - Controla la apertura del drawer móvil */
const drawer = ref(false)

/** @type {Array<{path: string, label: string, icon: string, color: string}>} - Items fijos de navegación */
const baseNav = [
  { path: '/dashboard', label: 'Dashboard', icon: 'chart-bar', color: 'text-sky-600' },
  { path: '/operaciones', label: 'Operaciones', icon: 'document-text', color: 'text-indigo-600' },

  { path: '/clientes', label: 'Clientes', icon: 'users', color: 'text-purple-600' },
  { path: '/cuentas', label: 'Cuentas', icon: 'building-library', color: 'text-amber-600' },
  { path: '/bancos', label: 'Bancos', icon: 'building-library', color: 'text-orange-600' },
  { path: '/reportes', label: 'Reportes', icon: 'arrow-trending-down', color: 'text-red-600' },
  { path: '/comisiones', label: 'Comisiones', icon: 'currency-dollar', color: 'text-emerald-600' },
]

/** @type {import('vue').ComputedRef<boolean>} - Indica si el usuario puede acceder al pool de pagos */
const canPool = computed(() => auth.isAdmin || auth.isPagador)

/** @type {import('vue').ComputedRef<boolean>} - Indica si el usuario puede crear ventas directas */
const canCreateVenta = computed(() => auth.isAdmin || auth.isSuperAdmin || auth.isOperador)

/** @type {import('vue').ComputedRef<Array<{path: string, label: string, icon: string}>>} - Items de navegación dinámicos según rol */
const nav = computed(() => {
  const items = [...baseNav]
  if (canPool.value) {
    items.push({ path: '/pool', label: 'Pool de pagos', icon: 'banknotes', color: 'text-green-600' })
  }
  if (canCreateVenta.value) {
    const opIdx = items.findIndex(i => i.path === '/operaciones')
    items.splice(opIdx + 1, 0, { path: '/operaciones/venta/nueva', label: 'Nueva Venta', icon: 'currency-dollar', color: 'text-emerald-600' })
    items.splice(opIdx + 2, 0, { path: '/operaciones/nueva', label: 'Nueva Compra', icon: 'shopping-cart', color: 'text-cyan-600' })
  }
  if (auth.isSuperAdmin) {
    items.push({ path: '/usuarios', label: 'Usuarios', icon: 'key', color: 'text-slate-600' })
  }
  return items
})

/**
 * Cierra la sesión del usuario y redirige al login.
 * @returns {void}
 */
function logout() {
  auth.logout()
  router.push('/login')
}

onMounted(() => {
  echo.channel('pool').listen('.sla.excedida', (data) => {
    window.dispatchEvent(new CustomEvent('sla-excedida', { detail: data }))
  })
})

onUnmounted(() => {
  echo.leave('pool')
})
</script>
