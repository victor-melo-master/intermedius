<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-xl font-bold text-gray-800">Operaciones</h2>
      <div class="flex gap-2">
        <button @click="showFilter = true" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 flex items-center gap-1">
          <span>🔍</span> Filtrar
          <span v-if="activeFilterCount" class="ml-1 bg-blue-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">{{ activeFilterCount }}</span>
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
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="tipoBadge(op).class">{{ tipoBadge(op).label }}</span>
              <span class="text-xs text-gray-400">#{{ op.id }}</span>
              <span class="text-[11px] font-medium px-2 py-0.5 rounded-full" :class="estatusBadge(op).class">{{ estatusBadge(op).label }}</span>
            </div>
            <p v-if="op.cliente?.nombre" class="text-sm font-medium text-gray-700 mt-1">{{ op.cliente.nombre }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(op.fecha) }} · {{ op.operador?.name || '—' }}</p>
          </div>
          <div class="text-right shrink-0">
            <p class="font-bold text-sm text-gray-800">$ {{ formatMoney(montoUsd(op)) }}</p>
            <p class="text-xs text-gray-500">Bs. {{ formatMoney(bolivares(op)) }}</p>
            <p class="text-[11px] text-gray-400">Tasa {{ formatRate(op.tasa_aplicada) }}</p>
          </div>
        </div>
      </router-link>
    </div>

    <!-- Filter modal -->
    <div v-if="showFilter" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" @click.self="showFilter = false">
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-md p-6 relative z-10 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-lg">Filtrar operaciones</h3>
          <button @click="showFilter = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="space-y-4">
          <!-- Tipo -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Tipo</label>
            <select v-model="filters.tipo_codigo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
              <option value="">Todos</option>
              <option value="compra_usd">Compra de USD</option>
              <option value="venta_usd">Venta de USD</option>
              <option value="cambio">Cambio / Intermediada</option>
            </select>
          </div>
          <!-- Estatus -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Estatus</label>
            <select v-model="filters.estatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
              <option value="">Todos</option>
              <option value="sin_verificar">Sin verificar</option>
              <option value="en_revision">En revisión</option>
              <option value="verificado">Verificado / Completa</option>
            </select>
          </div>
          <!-- Fechas -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Desde</label>
              <input v-model="filters.fecha_desde" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Hasta</label>
              <input v-model="filters.fecha_hasta" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
            </div>
          </div>
          <!-- Moneda -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Moneda</label>
            <select v-model="filters.moneda" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
              <option value="">Todas</option>
              <option value="USD">USD</option>
              <option value="USDT">USDT</option>
              <option value="EUR">EUR</option>
              <option value="COP">COP</option>
            </select>
          </div>
          <!-- Cliente -->
          <div class="relative">
            <label class="block text-sm font-medium text-gray-600 mb-1">Cliente</label>
            <div v-if="filters.cliente_id" class="flex items-center justify-between bg-blue-50 rounded-lg px-3 py-2">
              <span class="text-sm text-blue-700">{{ filters.cliente_nombre }}</span>
              <button @click="clearClienteFilter" class="text-xs text-blue-500">Cambiar</button>
            </div>
            <template v-else>
              <input v-model="clienteSearch" @input="onClienteSearch" type="text" placeholder="Buscar cliente..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
              <div v-if="clienteSearch && clienteResults.length"
                class="absolute z-20 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                <button v-for="c in clienteResults" :key="c.id" @click="selectClienteFilter(c)"
                  class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 border-b border-gray-100 last:border-0">
                  {{ c.nombre }}
                </button>
              </div>
            </template>
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <button @click="clearFilters" class="flex-1 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl transition">Limpiar</button>
          <button @click="applyFilters" class="flex-1 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition">Aplicar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useOperacionesStore } from '../stores/operaciones.js'
import api from '../api/axios.js'

const ops = useOperacionesStore()
const showFilter = ref(false)

const filters = reactive({
  tipo_codigo: '',
  estatus: '',
  fecha_desde: '',
  fecha_hasta: '',
  cliente_id: '',
  cliente_nombre: '',
  moneda: '',
})

const params = reactive({})

const activeFilterCount = computed(() =>
  ['tipo_codigo', 'estatus', 'fecha_desde', 'fecha_hasta', 'cliente_id', 'moneda'].filter(k => filters[k]).length
)

// ── Cliente autocomplete en filtro ──
const clienteSearch = ref('')
const clienteResults = ref([])
let clienteDebounce = null

function onClienteSearch() {
  clearTimeout(clienteDebounce)
  clienteDebounce = setTimeout(async () => {
    const q = clienteSearch.value.trim()
    if (!q) { clienteResults.value = []; return }
    try {
      const { data } = await api.get('/clientes', { params: { q } })
      clienteResults.value = Array.isArray(data) ? data : (data.data || [])
    } catch {
      clienteResults.value = []
    }
  }, 300)
}

function selectClienteFilter(c) {
  filters.cliente_id = c.id
  filters.cliente_nombre = c.nombre
  clienteSearch.value = ''
  clienteResults.value = []
}

function clearClienteFilter() {
  filters.cliente_id = ''
  filters.cliente_nombre = ''
}

// ── Badges ──
function tipoBadge(op) {
  const codigo = op.tipo_operacion?.codigo
  const moneda = monedaDeOperacion(op)
  const baseLabel = {
    compra_usd: 'Compra',
    venta_usd:  'Venta',
    cambio:     'Intermediada',
  }[codigo] || (op.tipo_operacion?.nombre || 'Operación')
  const label = moneda ? `${baseLabel} ${moneda}` : baseLabel
  const map = {
    compra_usd: { label, class: 'bg-blue-100 text-blue-700' },
    venta_usd:  { label, class: 'bg-green-100 text-green-700' },
    cambio:     { label, class: 'bg-orange-100 text-orange-700' },
  }
  return map[codigo] || { label, class: 'bg-gray-100 text-gray-600' }
}

function monedaDeOperacion(op) {
  const mov = (op.movimientos || []).find(m => m.moneda?.codigo !== 'VES')
  return mov?.moneda?.codigo || ''
}

function estatusBadge(op) {
  // Efectivo pendiente se infiere de la descripción (Fase 1, sin columna dedicada)
  if (/pendiente/i.test(op.descripcion || '')) {
    return { label: 'Efectivo pendiente', class: 'bg-red-100 text-red-700' }
  }
  const map = {
    verificado:    { label: 'Completa', class: 'bg-green-100 text-green-700' },
    en_revision:   { label: 'En revisión', class: 'bg-yellow-100 text-yellow-700' },
    sin_verificar: { label: 'Sin verificar', class: 'bg-gray-100 text-gray-600' },
  }
  return map[op.estatus] || { label: op.estatus, class: 'bg-gray-100 text-gray-600' }
}

// ── Montos desde movimientos ──
function montoUsd(op) {
  const mov = (op.movimientos || []).find(m => ['USD', 'USDT'].includes(m.moneda?.codigo))
  return mov ? Math.abs(parseFloat(mov.monto)) : 0
}

function bolivares(op) {
  const mov = (op.movimientos || []).find(m => m.moneda?.codigo === 'VES')
  return mov ? Math.abs(parseFloat(mov.monto)) : 0
}

function formatMoney(n) {
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(n) || 0)
}

function formatRate(n) {
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 4 }).format(parseFloat(n) || 0)
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

// ── Filtros ──
function applyFilters() {
  Object.keys(params).forEach(k => delete params[k])
  if (filters.tipo_codigo) params.tipo_codigo = filters.tipo_codigo
  if (filters.estatus) params.estatus = filters.estatus
  if (filters.fecha_desde) params.fecha_desde = filters.fecha_desde
  if (filters.fecha_hasta) params.fecha_hasta = filters.fecha_hasta
  if (filters.cliente_id) params.cliente_id = Number(filters.cliente_id)
  if (filters.moneda) params.moneda = filters.moneda
  ops.fetchAll(params)
  showFilter.value = false
}

function clearFilters() {
  Object.assign(filters, { tipo_codigo: '', estatus: '', fecha_desde: '', fecha_hasta: '', cliente_id: '', cliente_nombre: '', moneda: '' })
  Object.keys(params).forEach(k => delete params[k])
  ops.fetchAll(params)
  showFilter.value = false
}

onMounted(() => ops.fetchAll())
</script>
