<template>
  <div class="space-y-6">
    <div class="bg-gradient-to-br from-blue-600 to-blue-400 rounded-2xl p-6 text-white shadow-lg">
      <div class="flex items-center gap-2 mb-3">
        <span class="text-2xl">💱</span>
        <span class="font-bold text-lg">Intermedius</span>
      </div>
      <h2 class="text-xl font-bold">Bienvenido, {{ auth.user?.name }}</h2>
      <p class="text-blue-100 text-sm mt-1">{{ hoy }}</p>
    </div>

    <div>
      <h3 class="text-lg font-bold text-gray-800 mb-4">Tasas vigentes hoy</h3>
      <div v-if="tasas.loading" class="text-center py-12">
        <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
      </div>
      <div v-else-if="tasas.error" class="bg-red-50 text-red-600 p-4 rounded-xl">
        {{ tasas.error }}
        <button @click="tasas.fetchVigentes()" class="underline ml-2">Reintentar</button>
      </div>
      <div v-else-if="tasas.vigentes.length === 0" class="bg-red-50 border border-red-200 rounded-xl p-6 flex items-center gap-3">
        <span class="text-xl">⚠️</span>
        <p class="text-red-700 text-sm">No hay tasas publicadas para hoy. Contacta al administrador.</p>
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="t in tasas.vigentes" :key="t.id" class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <span class="bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1 rounded-full">{{ t.moneda_base?.codigo }}/{{ t.moneda_cotizada?.codigo }}</span>
            <span v-if="!t.vigente_hasta" class="bg-green-50 text-green-700 text-xs font-bold px-2 py-1 rounded-full flex items-center gap-1">
              <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Vigente
            </span>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-gray-500 mb-1">Compra</p>
              <p class="text-xl font-bold text-teal-600">{{ format(t.tasa_compra) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 mb-1">Venta</p>
              <p class="text-xl font-bold text-blue-600">{{ format(t.tasa_venta) }}</p>
            </div>
          </div>
          <p v-if="t.notas" class="text-xs text-gray-400 mt-3">{{ t.notas }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { useAuthStore } from '../stores/auth.js'
import { useTasasStore } from '../stores/tasas.js'

const auth = useAuthStore()
const tasas = useTasasStore()

const hoy = computed(() => new Date().toLocaleDateString('es-VE', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

function format(n) {
  return new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 4 }).format(n)
}

onMounted(() => tasas.fetchVigentes())
</script>
