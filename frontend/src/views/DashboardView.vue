<template>
  <div class="space-y-5">
    <!-- ── Sección 1: Tasas de referencia (discreta) ───────────────────── -->
    <div class="rounded-lg px-3 py-2 text-xs flex flex-wrap items-center gap-x-3 gap-y-1"
      :class="refStale ? 'bg-orange-50 text-orange-700 border border-orange-200' : 'bg-gray-100 text-gray-500'">
      <template v-if="hayReferencia">
        <span v-if="refTasas?.bcv"><span class="font-semibold">BCV:</span> {{ formatVes(refTasas.bcv.tasa) }}</span>
        <span v-if="refTasas?.binance_p2p" class="text-gray-300">•</span>
        <span v-if="refTasas?.binance_p2p"><span class="font-semibold">Binance USDT:</span> {{ formatVes(refTasas.binance_p2p.tasa) }}</span>
        <span class="opacity-70">
          <template v-if="refStale">⚠️ Datos desactualizados</template>
          <template v-else>(actualizado {{ refRelativo }})</template>
        </span>
      </template>
      <span v-else>Tasas de referencia no disponibles</span>
    </div>

    <!-- ── Alertas ─────────────────────────────────────────────────────── -->
    <div v-if="alertas.operaciones_sin_tasa_referencia_hoy || alertas.pares_sin_tasa_vigente?.length" class="flex flex-wrap gap-2">
      <span v-if="alertas.operaciones_sin_tasa_referencia_hoy" class="bg-red-50 text-red-700 border border-red-200 px-3 py-1 rounded-full text-xs font-medium">
        ⚠️ {{ alertas.operaciones_sin_tasa_referencia_hoy }} op. sin tasa de referencia hoy
      </span>
      <span v-if="alertas.pares_sin_tasa_vigente?.length" class="bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1 rounded-full text-xs font-medium">
        📋 Falta publicar tasa: {{ alertas.pares_sin_tasa_vigente.join(', ') }}
      </span>
    </div>

    <!-- Encabezado -->
    <div class="flex items-start justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Resumen operativo</h2>
        <p class="text-sm text-gray-500">Hola, {{ auth.user?.name }} · {{ hoy }}</p>
      </div>
      <router-link to="/bancos" class="flex items-center gap-2 bg-white border border-gray-200 hover:border-blue-300 text-gray-700 hover:text-blue-700 text-sm font-medium px-4 py-2 rounded-xl shadow-sm transition">
        <span>🏛️</span>
        Gestionar bancos
      </router-link>
    </div>

    <!-- ── Filtros ──────────────────────────────────────────────────────── -->
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 items-end">
        <div>
          <label class="block text-[11px] text-gray-500 mb-1">Desde</label>
          <input v-model="filtros.fecha_desde" type="date"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
        </div>
        <div>
          <label class="block text-[11px] text-gray-500 mb-1">Hasta</label>
          <input v-model="filtros.fecha_hasta" type="date"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
        </div>
        <div>
          <label class="block text-[11px] text-gray-500 mb-1">Moneda</label>
          <select v-model="filtros.moneda"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="">Todas</option>
            <option value="USD">USD</option>
            <option value="USDT">USDT</option>
            <option value="EUR">EUR</option>
            <option value="COP">COP</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] text-gray-500 mb-1">Operador</label>
          <select v-model="filtros.operador_id"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="">Todos</option>
            <option v-for="o in operadores" :key="o.id" :value="o.id">{{ o.name }}</option>
          </select>
        </div>
        <button @click="aplicarFiltros" :disabled="loadingResumen"
          class="bg-blue-600 text-white text-sm font-semibold py-2 rounded-lg hover:bg-blue-700 disabled:bg-blue-300 transition col-span-2 lg:col-span-1">
          Aplicar filtros
        </button>
      </div>
    </div>

    <!-- ── Loading (skeleton) ───────────────────────────────────────────── -->
    <div v-if="loadingResumen" class="space-y-4">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div v-for="n in 4" :key="n" class="bg-white border border-gray-200 rounded-xl p-4 h-28 animate-pulse">
          <div class="h-3 w-20 bg-gray-200 rounded mb-3"></div>
          <div class="h-6 w-16 bg-gray-200 rounded"></div>
        </div>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-4 h-32 animate-pulse"></div>
    </div>

    <!-- ── Sin operaciones ──────────────────────────────────────────────── -->
    <div v-else-if="resumen && resumen.operaciones && resumen.operaciones.total === 0"
      class="bg-white border border-gray-200 rounded-xl p-10 text-center">
      <span class="text-4xl block mb-3">📊</span>
      <p class="text-gray-500">Sin operaciones para este período</p>
    </div>

    <!-- ── Resumen ──────────────────────────────────────────────────────── -->
    <template v-else-if="resumen && resumen.operaciones">
      <!-- Tarjetas -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
          <p class="text-xs text-gray-500">Total operaciones</p>
          <p class="text-3xl font-bold text-gray-800 mt-1">{{ resumen.operaciones.total ?? 0 }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm space-y-1">
          <p class="text-xs text-gray-500 mb-1">Desglose</p>
          <p class="text-sm flex justify-between"><span class="text-gray-500">Compras</span><span class="font-semibold text-teal-600">{{ resumen.operaciones.compras ?? 0 }}</span></p>
          <p class="text-sm flex justify-between"><span class="text-gray-500">Ventas</span><span class="font-semibold text-blue-600">{{ resumen.operaciones.ventas ?? 0 }}</span></p>
          <p class="text-sm flex justify-between"><span class="text-gray-500">Intermediadas</span><span class="font-semibold text-purple-600">{{ resumen.operaciones.intermediadas ?? 0 }}</span></p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
          <p class="text-xs text-gray-500">Ganancia bruta</p>
          <p class="text-xl font-bold text-green-600">{{ formatUsd(resumen.ganancias?.bruta_usd) }}</p>
          <p class="text-xs text-gray-500 mt-2">Ganancia neta</p>
          <p class="text-lg font-bold text-green-700">{{ formatUsd(resumen.ganancias?.neta_usd) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
          <p class="text-xs text-gray-500">Efectivo pendiente</p>
          <p class="text-3xl font-bold text-amber-600 mt-1">{{ resumen.efectivo_pendiente?.count ?? 0 }}</p>
          <p class="text-sm font-semibold text-amber-700 mt-1">{{ formatUsd(resumen.efectivo_pendiente?.monto_usd) }}</p>
        </div>
      </div>

      <!-- Tablas -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Volúmenes por moneda -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
          <h3 class="font-semibold text-gray-700 text-sm mb-3">Volúmenes por moneda</h3>
          <div v-if="!resumen.volumenes || resumen.volumenes.length === 0" class="text-xs text-gray-400 py-2">Sin volúmenes</div>
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
                <th class="py-2 font-medium">Moneda</th>
                <th class="py-2 font-medium text-right">Comprado</th>
                <th class="py-2 font-medium text-right">Vendido</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="v in resumen.volumenes" :key="v.moneda" class="border-b border-gray-50 last:border-0">
                <td class="py-2 font-semibold text-gray-700">{{ v.moneda }}</td>
                <td class="py-2 text-right text-teal-600">{{ formatUsd(v.comprado) }}</td>
                <td class="py-2 text-right text-blue-600">{{ formatUsd(v.vendido) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Actividad por operador -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
          <h3 class="font-semibold text-gray-700 text-sm mb-3">Actividad por operador</h3>
          <div v-if="!resumen.por_operador || resumen.por_operador.length === 0" class="text-xs text-gray-400 py-2">Sin actividad</div>
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
                <th class="py-2 font-medium">Operador</th>
                <th class="py-2 font-medium text-right">Operaciones</th>
                <th class="py-2 font-medium text-right">Volumen USD</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="o in resumen.por_operador" :key="o.operador" class="border-b border-gray-50 last:border-0">
                <td class="py-2 font-semibold text-gray-700">{{ o.operador }}</td>
                <td class="py-2 text-right text-gray-600">{{ o.total_operaciones ?? 0 }}</td>
                <td class="py-2 text-right font-semibold text-gray-800">{{ formatUsd(o.volumen_usd) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <div v-else-if="resumenError" class="bg-red-50 text-red-600 p-4 rounded-xl text-sm">
      {{ resumenError }}
      <button @click="fetchResumen" class="underline ml-2">Reintentar</button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../stores/auth.js'
import api from '../api/axios.js'

const auth = useAuthStore()

const hoy = computed(() => new Date().toLocaleDateString('es-VE', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

const refTasas = ref(null)
const resumen = ref(null)
const resumenError = ref('')
const loadingResumen = ref(false)
const operadores = ref([])
const ahora = ref(Date.now())
const alertas = ref({})

function hoyStr() {
  return new Date().toLocaleDateString('en-CA')
}

const filtros = reactive({
  fecha_desde: hoyStr(),
  fecha_hasta: hoyStr(),
  moneda: '',
  operador_id: '',
})

function formatUsd(n) {
  return '$' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(n) || 0)
}
function formatVes(n) {
  return 'Bs. ' + new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(n) || 0)
}

const hayReferencia = computed(() => !!(refTasas.value?.bcv || refTasas.value?.binance_p2p))

const refUltimoTs = computed(() => {
  const fechas = [refTasas.value?.bcv?.capturado_en, refTasas.value?.binance_p2p?.capturado_en]
    .filter(Boolean)
    .map(f => new Date(f).getTime())
  return fechas.length ? Math.max(...fechas) : null
})

const refStale = computed(() => {
  if (!refUltimoTs.value) return false
  return (ahora.value - refUltimoTs.value) > 2 * 60 * 1000
})

const refRelativo = computed(() => {
  if (!refUltimoTs.value) return ''
  const diff = Math.max(0, Math.floor((ahora.value - refUltimoTs.value) / 1000))
  if (diff < 60) return 'hace unos segundos'
  const min = Math.floor(diff / 60)
  if (min < 60) return `hace ${min} min`
  const h = Math.floor(min / 60)
  if (h < 24) return `hace ${h} h`
  return `hace ${Math.floor(h / 24)} d`
})

async function fetchTasasReferencia() {
  try {
    const { data } = await api.get('/dashboard/tasas-referencia')
    refTasas.value = data
    ahora.value = Date.now()
  } catch {
    refTasas.value = null
  }
}

async function fetchResumen() {
  loadingResumen.value = true
  resumenError.value = ''
  try {
    const params = {
      fecha_desde: filtros.fecha_desde,
      fecha_hasta: filtros.fecha_hasta,
    }
    if (filtros.moneda) params.moneda = filtros.moneda
    if (filtros.operador_id) params.operador_id = filtros.operador_id
    const { data } = await api.get('/dashboard/resumen', { params })
    resumen.value = data
  } catch (err) {
    resumenError.value = err.response?.data?.message || err.message
    resumen.value = null
  } finally {
    loadingResumen.value = false
  }
}

async function fetchOperadores() {
  try {
    const { data } = await api.get('/usuarios')
    const lista = Array.isArray(data) ? data : (data.data || [])
    operadores.value = lista.filter(u => u.roles?.includes('operador'))
  } catch {
    operadores.value = []
  }
}

async function fetchAlertas() {
  try {
    const { data } = await api.get('/dashboard/general')
    alertas.value = data.alertas || {}
  } catch {
    alertas.value = {}
  }
}

function aplicarFiltros() {
  fetchResumen()
}

let refTimer = null

function scheduleRefresh() {
  refTimer = setTimeout(async () => {
    await fetchTasasReferencia()
    scheduleRefresh()
  }, 1 * 60 * 1000)
}

onMounted(async () => {
  await Promise.all([
    fetchTasasReferencia(),
    fetchResumen(),
    fetchOperadores(),
    fetchAlertas(),
  ])
  scheduleRefresh()
})

onUnmounted(() => {
  if (refTimer) clearTimeout(refTimer)
})
</script>