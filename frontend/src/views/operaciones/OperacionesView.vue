<template>
  <div class="max-w-7xl mx-auto space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-xl font-bold text-heading">Operaciones</h2>
      <button @click="showFilter = true" class="self-start sm:self-auto px-3 py-2 bg-white dark:bg-surface-muted border border-edge-strong rounded-lg text-sm hover:bg-surface-soft flex items-center gap-1">
        <Iconoir name="magnifying-glass" class="w-4 h-4" /> Filtrar
        <span v-if="activeFilterCount" class="ml-1 bg-gold text-navy text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">{{ activeFilterCount }}</span>
      </button>
    </div>

    <div class="flex gap-2">
      <button @click="setTipoFiltro('compra')"
        class="px-4 py-2 rounded-lg text-sm font-medium transition"
        :class="operacionTipo === 'compra' ? 'bg-gold text-navy shadow-sm' : 'bg-white dark:bg-surface-muted border border-edge-strong text-ink hover:bg-surface-soft'">
        Compras
      </button>
      <button @click="setTipoFiltro('venta')"
        class="px-4 py-2 rounded-lg text-sm font-medium transition"
        :class="operacionTipo === 'venta' ? 'bg-gold text-navy shadow-sm' : 'bg-white dark:bg-surface-muted border border-edge-strong text-ink hover:bg-surface-soft'">
        Ventas
      </button>
      <button @click="setTipoFiltro(null)"
        class="px-4 py-2 rounded-lg text-sm font-medium transition"
        :class="operacionTipo === null ? 'bg-gold text-navy shadow-sm' : 'bg-white dark:bg-surface-muted border border-edge-strong text-ink hover:bg-surface-soft'">
        Todos
      </button>
    </div>

    <div v-if="ops.loading" class="text-center py-12">
      <div class="w-8 h-8 border-2 border-gold border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>
    <div v-else-if="ops.error" class="bg-danger-soft text-danger p-4 rounded-xl">
      {{ ops.error }}
      <button @click="reintentar" class="underline ml-2">Reintentar</button>
    </div>
    <div v-else-if="ops.list.length === 0" class="text-center py-16">
      <Iconoir name="document-text" class="w-12 h-12 block mb-4 mx-auto text-ink-muted" />
      <p class="text-ink-muted">No hay operaciones</p>
      <p class="text-sm text-ink-muted mt-1">Pulsa + para registrar una nueva</p>
    </div>
    <div v-else class="space-y-2">
      <router-link v-for="op in ops.list" :key="op.id" :to="`/operaciones/${op.id}`"
        class="block bg-surface border border-edge rounded-xl hover:shadow-md transition">
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2 p-4">
          <div class="min-w-0 space-y-1">
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold px-2 py-0.5 rounded-full" :class="tipoBadge(op).class">{{ tipoBadge(op).label }}</span>
              <span class="text-sm text-ink-muted">#{{ op.id }}</span>
              <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="estatusBadge(op).class">{{ estatusBadge(op).label }}</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
              <span v-if="op.cliente?.nombre" class="font-medium text-ink">{{ op.cliente.nombre }}</span>
              <span class="text-ink-muted">{{ formatDate(op.fecha) }}</span>
              <span class="text-ink-muted">·</span>
              <span class="text-ink-muted">{{ op.operador?.name || '—' }}</span>
            </div>
          </div>
          <div class="flex items-center gap-4 sm:text-right">
            <div>
              <p class="text-sm text-ink-muted">Monto</p>
              <p class="font-semibold text-sm text-heading">{{ formatMoney(montoUsd(op)) }} USD</p>
            </div>
            <div class="hidden sm:block w-px h-8 bg-surface-muted"></div>
            <div>
              <p class="text-sm text-ink-muted">Bs.</p>
              <p class="text-sm text-ink-muted">Bs. {{ formatMoney(bolivares(op)) }}</p>
            </div>
            <div class="hidden sm:block w-px h-8 bg-surface-muted"></div>
            <div>
              <p class="text-sm text-ink-muted">Tasa</p>
              <p class="text-sm text-ink-muted">{{ formatRate(op.tasa_aplicada) }}</p>
            </div>
            <div v-if="gananciaOp(op)" class="hidden sm:block w-px h-8 bg-surface-muted"></div>
            <div v-if="gananciaOp(op)">
              <p class="text-sm text-ink-muted">Ganancia</p>
              <p class="text-sm font-medium" :class="gananciaOp(op) >= 0 ? 'text-success' : 'text-danger'">
                ${{ formatRate(gananciaOp(op)) }}
              </p>
            </div>
          </div>
        </div>
      </router-link>
    </div>

    <AppFormModal v-model="showFilter" title="Filtrar operaciones">
      <div class="space-y-4">
        <!-- Tipo -->
        <div>
          <label class="block text-sm font-medium text-ink-muted mb-1">Tipo</label>
          <select v-model="filters.tipo_codigo" class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm bg-white dark:bg-surface-muted">
            <option value="">Todos</option>
            <option value="compra_usd">Compra de USD</option>
            <option value="venta_usd">Venta de USD</option>
            <option value="cambio">Cambio / Intermediada</option>
          </select>
        </div>
        <!-- Estatus (legacy) -->
        <div>
          <label class="block text-sm font-medium text-ink-muted mb-1">Estatus</label>
          <select v-model="filters.estatus" class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm bg-white dark:bg-surface-muted">
            <option value="">Todos</option>
            <option value="sin_verificar">Sin verificar</option>
            <option value="en_revision">En revisión</option>
            <option value="verificado">Verificado / Completa</option>
          </select>
        </div>
        <!-- Estado (flujo multi-paso) -->
        <div>
          <label class="block text-sm font-medium text-ink-muted mb-1">Estado</label>
          <select v-model="filters.estado" class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm bg-white dark:bg-surface-muted">
            <option value="">Todos</option>
            <option value="solicitud">Solicitud</option>
            <option value="en_progreso">En Progreso</option>
            <option value="cerrada">Cerrada</option>
            <option value="cancelada">Cancelada</option>
          </select>
        </div>
        <!-- Fechas -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-ink-muted mb-1">Desde</label>
            <input v-model="filters.fecha_desde" type="date" class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-ink-muted mb-1">Hasta</label>
            <input v-model="filters.fecha_hasta" type="date" class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm" />
          </div>
        </div>
        <!-- Moneda -->
        <div>
          <label class="block text-sm font-medium text-ink-muted mb-1">Moneda</label>
          <select v-model="filters.moneda" class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm bg-white dark:bg-surface-muted">
            <option value="">Todas</option>
            <option value="USD">USD</option>
            <option value="USDT">USDT</option>
            <option value="EUR">EUR</option>
            <option value="COP">COP</option>
          </select>
        </div>
        <!-- Cliente -->
        <div class="relative">
          <label class="block text-sm font-medium text-ink-muted mb-1">Cliente</label>
          <div v-if="filters.cliente_id" class="flex items-center justify-between bg-gold-soft rounded-lg px-3 py-2">
            <span class="text-sm text-gold-dark">{{ filters.cliente_nombre }}</span>
            <button @click="clearClienteFilter" class="text-xs text-gold-dark">Cambiar</button>
          </div>
          <template v-else>
            <input v-model="clienteSearch" @input="onClienteSearch" type="text" placeholder="Buscar cliente..."
              class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm" />
            <div v-if="clienteSearch && clienteResults.length"
              class="absolute z-20 left-0 right-0 mt-1 bg-surface border border-edge rounded-lg shadow-lg max-h-40 overflow-y-auto">
              <button v-for="c in clienteResults" :key="c.id" @click="selectClienteFilter(c)"
                class="w-full text-left px-3 py-2 text-sm hover:bg-surface-soft border-b border-edge last:border-0">
                {{ c.nombre }}
              </button>
            </div>
          </template>
        </div>
      </div>
      <template #footer>
        <div class="flex gap-3">
          <button @click="clearFilters" class="flex-1 py-2.5 text-sm text-ink-muted hover:bg-surface-muted rounded-xl transition">Limpiar</button>
          <button @click="applyFilters" class="flex-1 py-2.5 bg-gold text-navy text-sm font-medium rounded-xl hover:bg-gold-dark transition">Aplicar</button>
        </div>
      </template>
    </AppFormModal>
  </div>
</template>

<script setup>
/**
 * OperacionesView — Listado de operaciones con filtros.
 * Muestra todas las operaciones con badges de tipo y estatus,
 * montos desde movimientos, y un modal de filtros por tipo, estatus,
 * fechas, moneda y cliente (con autocomplete).
 */
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useOperacionesStore } from '../../stores/operaciones.js'
import { useFormatting } from '@/composables/useFormatting'
import api from '../../api/axios.js'
import Iconoir from '@/components/common/Iconoir.vue'
import AppFormModal from '@/components/common/AppFormModal.vue'

/** Store de operaciones */
const ops = useOperacionesStore()
const { formatMoney, formatRate, formatDate } = useFormatting()
/** Controla visibilidad del modal de filtros */
const showFilter = ref(false)

/** Filtro rápido de tipo: 'compra' | 'venta' | null (todos) */
const operacionTipo = ref('compra')

function setTipoFiltro(tipo) {
  operacionTipo.value = tipo
}

watch(operacionTipo, () => {
  applyTipoFilter()
})

function paramsConTipo() {
  const p = { ...params }
  if (operacionTipo.value) {
    p.operacion_tipo = operacionTipo.value
  } else {
    delete p.operacion_tipo
  }
  return p
}

function applyTipoFilter() {
  ops.fetchAll(paramsConTipo())
}

function reintentar() {
  ops.fetchAll(paramsConTipo())
}

/** Valores actuales de filtros en el modal */
const filters = reactive({
  tipo_codigo: '',
  estatus: '',
  estado: '',
  fecha_desde: '',
  fecha_hasta: '',
  cliente_id: '',
  cliente_nombre: '',
  moneda: '',
})

/** Parámetros activos enviados a la API (sincronizado al aplicar filtros) */
const params = reactive({})

/** Cantidad de filtros activos (para el badge del botón) */
const activeFilterCount = computed(() =>
  ['tipo_codigo', 'estatus', 'estado', 'fecha_desde', 'fecha_hasta', 'cliente_id', 'moneda'].filter(k => filters[k]).length
)

/** Término de búsqueda para autocomplete de cliente en filtro */
const clienteSearch = ref('')
/** Resultados de búsqueda de cliente */
const clienteResults = ref([])
/** Timeout para debounce del autocomplete */
let clienteDebounce = null

/**
 * Ejecuta búsqueda de cliente con debounce de 300ms para el autocomplete.
 */
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

/**
 * Selecciona un cliente del autocomplete.
 * @param {Object} c - Cliente seleccionado
 */
function selectClienteFilter(c) {
  filters.cliente_id = c.id
  filters.cliente_nombre = c.nombre
  clienteSearch.value = ''
  clienteResults.value = []
}

/** Limpia el filtro de cliente */
function clearClienteFilter() {
  filters.cliente_id = ''
  filters.cliente_nombre = ''
}

/**
 * Genera el badge visual del tipo de operación.
 * @param {Object} op - Operación
 * @returns {{ label: string, class: string }}
 */
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
    compra_usd: { label, class: 'bg-info-soft text-info-strong' },
    venta_usd:  { label, class: 'bg-success-soft text-success-strong' },
    cambio:     { label, class: 'bg-warning-soft text-warning-strong' },
  }
  return map[codigo] || { label, class: 'bg-surface-muted text-ink-muted' }
}

/**
 * Extrae la moneda (no VES) de los movimientos de la operación.
 * @param {Object} op - Operación
 * @returns {string}
 */
function monedaDeOperacion(op) {
  const mov = (op.movimientos || []).find(m => m.moneda?.codigo !== 'VES')
  return mov?.moneda?.codigo || ''
}

/**
 * Genera el badge visual del estatus de la operación.
 * @param {Object} op - Operación
 * @returns {{ label: string, class: string }}
 */
function estatusBadge(op) {
  if (op.estado && op.estado !== 'en_espera') {
    const map = {
      cerrada:     { label: 'Cerrada',     class: 'bg-success-soft text-success-strong' },
      en_progreso: { label: 'En Progreso', class: 'bg-info-soft text-info-strong' },
      solicitud:   { label: 'Solicitud',   class: 'bg-warning-soft text-warning-strong' },
      cancelada:   { label: 'Cancelada',   class: 'bg-danger-soft text-danger-strong' },
    }
    if (map[op.estado]) return map[op.estado]
  }
  if (/pendiente/i.test(op.descripcion || '')) {
    return { label: 'Efectivo pendiente', class: 'bg-danger-soft text-danger-strong' }
  }
  const map = {
    verificado:    { label: 'Completa', class: 'bg-success-soft text-success-strong' },
    en_revision:   { label: 'En revisión', class: 'bg-warning-soft text-warning-strong' },
    sin_verificar: { label: 'Sin verificar', class: 'bg-surface-muted text-ink-muted' },
  }
  return map[op.estatus] || { label: op.estatus, class: 'bg-surface-muted text-ink-muted' }
}

/**
 * Obtiene el monto en USD de una operación desde sus movimientos.
 * @param {Object} op - Operación
 * @returns {number}
 */
function montoUsd(op) {
  const mov = (op.movimientos || []).find(m => ['USD', 'USDT'].includes(m.moneda?.codigo))
  if (mov) return Math.abs(parseFloat(mov.monto))
  const tx = (op.transacciones || []).find(t => ['USD', 'USDT'].includes(t.moneda?.codigo))
  if (tx) return Math.abs(parseFloat(tx.monto))
  return op.monto_solicitado ? Math.abs(parseFloat(op.monto_solicitado)) : 0
}

/**
 * Obtiene el monto en VES de una operación desde sus movimientos o transacciones.
 * @param {Object} op - Operación
 * @returns {number}
 */
function bolivares(op) {
  const mov = (op.movimientos || []).find(m => m.moneda?.codigo === 'VES')
  if (mov) return Math.abs(parseFloat(mov.monto))
  const tx = (op.transacciones || []).find(t => t.moneda?.codigo === 'VES')
  if (tx) return Math.abs(parseFloat(tx.monto))
  const usd = montoUsd(op)
  const tasa = parseFloat(op.tasa_aplicada)
  return usd && tasa ? usd * tasa : 0
}

/**
 * Obtiene la ganancia neta en USD de una operación.
 * @param {Object} op - Operación
 * @returns {number|null}
 */
function gananciaOp(op) {
  const ganancia = op.ganancia?.neta_usd ?? op.ganancia_neta_usd
  if (ganancia === null || ganancia === undefined) return null
  const val = parseFloat(ganancia)
  return val !== 0 ? val : null
}

/** Aplica los filtros y recarga la lista de operaciones */
function applyFilters() {
  Object.keys(params).forEach(k => delete params[k])
  if (filters.tipo_codigo) params.tipo_codigo = filters.tipo_codigo
  if (filters.estatus) params.estatus = filters.estatus
  if (filters.fecha_desde) params.fecha_desde = filters.fecha_desde
  if (filters.fecha_hasta) params.fecha_hasta = filters.fecha_hasta
  if (filters.cliente_id) params.cliente_id = Number(filters.cliente_id)
  if (filters.moneda) params.moneda = filters.moneda
  if (filters.estado) params.estado = filters.estado
  applyTipoFilter()
  showFilter.value = false
}

/** Limpia todos los filtros y recarga la lista */
function clearFilters() {
  Object.assign(filters, { tipo_codigo: '', estatus: '', estado: '', fecha_desde: '', fecha_hasta: '', cliente_id: '', cliente_nombre: '', moneda: '' })
  Object.keys(params).forEach(k => delete params[k])
  applyTipoFilter()
  showFilter.value = false
}

/** Carga la lista de operaciones al montar (default compras) */
onMounted(() => {
  ops.fetchAll({ operacion_tipo: 'compra' })
})
</script>
