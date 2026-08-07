<template>
  <div class="min-h-screen bg-surface-alt flex flex-col">
    <!-- Header -->
    <header class="bg-surface border-b border-edge sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <button class="lg:hidden p-2 -ml-2 text-ink-muted hover:bg-surface-muted rounded-lg" @click="drawer = !drawer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <router-link to="/dashboard" class="flex items-center gap-2">
            <img :src="logoPositivo" alt="Intermedius" class="h-8 w-auto dark:hidden" />
            <img :src="logoNegativo" alt="Intermedius" class="h-8 w-auto hidden dark:block" />
          </router-link>
        </div>
        <div class="flex items-center gap-2">
          <button @click="theme.toggle()" :title="theme.isDark ? 'Modo claro' : 'Modo oscuro'"
            class="p-2 rounded-lg text-ink-muted hover:bg-surface-muted hover:text-heading transition"
            aria-label="Cambiar tema">
            <Iconoir :name="theme.isDark ? 'sun' : 'moon'" class="w-5 h-5" />
          </button>
          <span class="text-sm text-ink-muted hidden sm:block">{{ auth.user?.name }}</span>
          <router-link to="/perfil" title="Mi perfil"
            class="flex items-center gap-2 px-1.5 py-1 rounded-lg hover:bg-surface-muted transition">
            <img v-if="avatarUrl(auth.user)" :src="avatarUrl(auth.user)" :alt="`Avatar de ${auth.user?.name}`"
              class="w-8 h-8 rounded-full object-cover border border-edge" />
            <span v-else
              class="w-8 h-8 rounded-full bg-gold-soft flex items-center justify-center text-gold-dark font-bold text-sm">
              {{ (auth.user?.name || '?').charAt(0).toUpperCase() }}
            </span>
          </router-link>
          <button @click="logout" class="text-sm text-danger hover:text-danger-strong px-3 py-1.5 rounded-lg hover:bg-danger-soft transition">
            Salir
          </button>
        </div>
      </div>
    </header>

    <div class="flex flex-1 w-full">
      <!-- Sidebar desktop -->
      <aside class="hidden lg:flex flex-col w-56 shrink-0 border-r border-white/10 bg-navy dark:bg-surface-muted h-[calc(100vh-3.5rem)] sticky top-14">
        <nav class="p-3 space-y-1 pt-4">
          <router-link v-for="item in nav" :key="item.path" :to="item.path"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-base font-medium transition active:scale-[0.98]"
            :class="$route.path.startsWith(item.path) ? 'bg-gold-soft text-white' : 'text-white/50 hover:bg-white/10 hover:text-white'">
                <Iconoir :name="item.icon" :class="$route.path.startsWith(item.path) ? 'text-white' : 'text-white/50'" />
            {{ item.label }}
          </router-link>
        </nav>
      </aside>

      <!-- Mobile drawer -->
      <Transition name="drawer">
        <div v-if="drawer" class="fixed inset-0 z-40 lg:hidden" @click="drawer = false">
          <div class="absolute inset-0 bg-black/40"></div>
          <aside class="absolute left-0 top-0 bottom-0 w-64 bg-navy dark:bg-surface-muted p-4" @click.stop>
            <div class="flex items-center justify-between mb-4">
              <button @click="drawer = false" class="ml-auto p-1 hover:bg-white/10 rounded"><Iconoir name="x-mark" class="text-slate-400" /></button>
            </div>
            <nav class="space-y-1">
              <router-link v-for="item in nav" :key="item.path" :to="item.path" @click="drawer = false"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-base font-medium transition active:scale-[0.98]"
                :class="$route.path.startsWith(item.path) ? 'bg-gold-soft text-white' : 'text-white/50 hover:bg-white/10 hover:text-white'">
            <Iconoir :name="item.icon" :class="$route.path.startsWith(item.path) ? 'text-white' : 'text-white/50'" />
                {{ item.label }}
              </router-link>
            </nav>
          </aside>
        </div>
      </Transition>

      <!-- Main -->
      <main class="flex-1 min-w-0 p-4 lg:p-6 overflow-auto">
        <div class="max-w-7xl mx-auto">
          <router-view v-slot="{ Component, route }">
            <Transition name="page" mode="out-in">
              <component :is="Component" :key="route.path" />
            </Transition>
          </router-view>
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
import { useThemeStore } from '../../stores/theme.js'
import { useInactivityTimer } from '../../composables/useInactivityTimer.js'
import PoolAlarm from '../pool/PoolAlarm.vue'
import echo from '../../plugins/echo'
import Iconoir from '../../components/common/Iconoir.vue'
import logoPositivo from '../../assets/logo-positivo.png'
import logoNegativo from '../../assets/logo-negativo.png'

const router = useRouter()
const auth = useAuthStore()
const theme = useThemeStore()
useInactivityTimer()

/**
 * URL autenticada del avatar de un usuario (imagen WebP servida por la API).
 * @param {Object|null} u - Usuario con { id, avatar_path }
 * @returns {string|null}
 */
function avatarUrl(u) {
  if (!u?.avatar_path) return null
  const token = localStorage.getItem('token')
  return `${import.meta.env.VITE_API_URL}/usuarios/${u.id}/avatar?token=${token}`
}

/** @type {import('vue').Ref<boolean>} - Controla la apertura del drawer móvil */
const drawer = ref(false)

/** @type {Array<{path: string, label: string, icon: string}>} - Items fijos de navegación */
const baseNav = [
  { path: '/dashboard', label: 'Dashboard', icon: 'chart-bar' },
  { path: '/operaciones', label: 'Operaciones', icon: 'document-text' },

  { path: '/clientes', label: 'Clientes', icon: 'users' },
  { path: '/cuentas', label: 'Cuentas', icon: 'credit-card' },
  { path: '/bancos', label: 'Bancos', icon: 'building-library' },
]

/** @type {import('vue').ComputedRef<boolean>} - Indica si el usuario puede acceder al pool de pagos */
const canPool = computed(() => auth.canPool)

/** @type {import('vue').ComputedRef<boolean>} - Indica si el usuario puede crear operaciones directas */
const canCreateVenta = computed(() => auth.canWrite)

/** @type {import('vue').ComputedRef<Array<{path: string, label: string, icon: string}>>} - Items de navegación según rol */
const nav = computed(() => {
  const items = [...baseNav]

  if (canCreateVenta.value) {
    const opIdx = items.findIndex(i => i.path === '/operaciones')
    items.splice(opIdx + 1, 0,
      { path: '/operaciones/venta/nueva', label: 'Nueva Venta', icon: 'currency-dollar' },
      { path: '/operaciones/nueva', label: 'Nueva Compra', icon: 'shopping-cart' })
  }

  if (canPool.value) {
    items.push({ path: '/pool', label: 'Pool de pagos', icon: 'banknotes' })
  }

  if (auth.canReports) {
    items.push({ path: '/reportes', label: 'Reportes', icon: 'arrow-trending-down' })
    items.push({ path: '/comisiones', label: 'Comisiones', icon: 'receipt-percent' })
  }

  if (auth.canConfig) {
    items.push({ path: '/tasas', label: 'Tasas', icon: 'arrow-trending-up' })
    items.push({ path: '/usuarios', label: 'Usuarios', icon: 'key' })
  }

  items.push({ path: '/perfil', label: 'Mi perfil', icon: 'identification' })

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

<style scoped>
.drawer-enter-active,
.drawer-leave-active {
  transition: opacity 0.25s ease;
}
.drawer-enter-active aside,
.drawer-leave-active aside {
  transition: transform 0.25s ease;
}
.drawer-enter-from,
.drawer-leave-to {
  opacity: 0;
}
.drawer-enter-from aside,
.drawer-leave-to aside {
  transform: translateX(-100%);
}
</style>

<style>
.page-enter-active,
.page-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.page-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
.page-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
