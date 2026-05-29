<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-xl font-bold text-gray-800">Operaciones</h2>
      <div class="flex gap-2">
        <button @click="showFilter = true" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 flex items-center gap-1">
          <span>🔍</span> Filtrar
        </button>
        <router-link to="/operaciones/nueva" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center gap-1">
          <span>+</span> Nueva
        </router-link>
      </div>
    </div>

    <div v-if="ops.loading" class="text-center py-12">
      <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>
    <div v-else-if="ops.error" class="bg-red-50 text-red-600 p-4 rounded-xl">
      {{ ops.error }}
      <button @click="ops.fetchAll(params)" class="underline ml-2">Reintentar</button>
    </div>
    <div v-else-if="ops.list.length === 0" class="text-center py-16">
      <span class="text-5xl block mb-4">📄</span>
      <p class="text-gray-500">No hay operaciones</p>
      <p class="text-sm text-gray-400 mt-1">Pulsa + para registrar una nueva</p>
    </div>
    <div v-else class="space-y-2">
      <router-link v-for="op in ops.list" :key="op.id" :to="`/operaciones/${op.id}`"
        class="block bg-white border border-gray-200 rounded-xl p-4 hover:shadow-md transition">
        <div class="flex items-start justify-between">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg"
              :class="op.estatus === 'verificado' ? 'bg-green-50' : op.estatus === 'en_revision' ? 'bg-orange-50' : 'bg-gray-50'">
              {{ op.estatus === 'verificado' ? '✅' : op.estatus === 'en_revision' ? '⏳' : '◯' }}
            </div>
            <div>
              <p class="font-semibold text-gray-800 text-sm">{{ op.tipo_operacion?.nombre || 'Operación' }} #{{ op.id }}</p>
              <p v-if="op.cliente?.nombre" class="text-xs text-gray-500">{{ op.cliente.nombre }}</p>
              <p class="text-xs text-gray-400">{{ op.fecha }} · {{ op.operador?.name || op.operador_nombre }}</p>
            </div>
          </div>
          <div class="text-right">
            <p class="font-bold text-sm" :class="op.ganancia_neta_usd >= 0 ? 'text-green-600' : 'text-red-600'">
              ${{ formatMoney(op.ganancia_neta_usd) }}
            </p>
            <span v-if="op.sin_tasa_referencia" class="text-xs text-red-500">⚠️ Sin tasa ref.</span>
          </div>
        </div>
      </router-link>
    </div>

    <!-- Filter modal -->
    <div v-if="showFilter" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" @click.self="showFilter = false">
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-md p-6 relative z-10">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-lg">Filtrar por estatus</h3>
          <button @click="showFilter = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="space-y-2">
          <label v-for="s in estatusOptions" :key="s.value" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer border"
            :class="filterEstatus === s.value ? 'border-blue-200 bg-blue-50' : 'border-transparent'">
            <input type="radio" name="estatus" :value="s.value" v-model="filterEstatus" class="accent-blue-600">
            <span class="text-sm">{{ s.label }}</span>
          </label>
        </div>
        <div class="flex gap-3 mt-6">
          <button v-if="filterEstatus" @click="clearFilter" class="flex-1 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl transition">Limpiar</button>
          <button @click="applyFilter" class="flex-1 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition">Aplicar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useOperacionesStore } from '../stores/operaciones.js'

const ops = useOperacionesStore()
const showFilter = ref(false)
const filterEstatus = ref('')
const params = reactive({})

const estatusOptions = [
  { value: 'sin_verificar', label: 'Sin verificar' },
  { value: 'en_revision', label: 'En revisión' },
  { value: 'verificado', label: 'Verificado' },
]

function applyFilter() {
  if (filterEstatus.value) params.estatus = filterEstatus.value
  else delete params.estatus
  ops.fetchAll(params)
  showFilter.value = false
}

function clearFilter() {
  filterEstatus.value = ''
  delete params.estatus
  ops.fetchAll(params)
  showFilter.value = false
}

function formatMoney(n) {
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0)
}

onMounted(() => ops.fetchAll())
</script>
