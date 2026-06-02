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

    <div class="flex flex-1 max-w-7xl mx-auto w-full">
      <!-- Sidebar desktop -->
      <aside class="hidden lg:block w-56 border-r border-gray-200 bg-white h-[calc(100vh-3.5rem)] sticky top-14">
        <nav class="p-3 space-y-1">
          <router-link v-for="item in nav" :key="item.path" :to="item.path"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition"
            :class="$route.path.startsWith(item.path) ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50'">
            <span class="text-lg">{{ item.icon }}</span>
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
            <button @click="drawer = false" class="p-1 hover:bg-gray-100 rounded">✕</button>
          </div>
          <nav class="space-y-1">
            <router-link v-for="item in nav" :key="item.path" :to="item.path" @click="drawer = false"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition"
              :class="$route.path.startsWith(item.path) ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50'">
              <span class="text-lg">{{ item.icon }}</span>
              {{ item.label }}
            </router-link>
          </nav>
        </aside>
      </div>

      <!-- Main -->
      <main class="flex-1 p-4 lg:p-6 overflow-auto">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

const router = useRouter()
const auth = useAuthStore()
const drawer = ref(false)

const nav = [
  { path: '/dashboard', label: 'Dashboard', icon: '📊' },
  { path: '/operaciones', label: 'Operaciones', icon: '📄' },
  { path: '/tasas', label: 'Tasas', icon: '📈' },
  { path: '/clientes', label: 'Clientes', icon: '👥' },
  { path: '/cuentas', label: 'Cuentas', icon: '🏦' },
  { path: '/reportes', label: 'Reportes', icon: '📉' },
]

function logout() {
  auth.logout()
  router.push('/login')
}
</script>
