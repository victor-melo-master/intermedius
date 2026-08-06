<template>
  <div class="space-y-5">
    <TasasReferencia />

    <!-- ── Alertas ─────────────────────────────────────────────────────── -->
    <div v-if="alertas.operaciones_sin_tasa_referencia_hoy || alertas.pares_sin_tasa_vigente?.length" class="flex flex-wrap gap-2">
      <span v-if="alertas.operaciones_sin_tasa_referencia_hoy" class="bg-danger-soft text-danger-strong border border-danger-edge px-3 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1">
        <Iconoir name="exclamation-triangle" class="w-4 h-4 text-warning" /> {{ alertas.operaciones_sin_tasa_referencia_hoy }} op. sin tasa de referencia hoy
      </span>
      <span v-if="alertas.pares_sin_tasa_vigente?.length" class="bg-warning-soft text-warning-strong border border-warning-edge px-3 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1">
        <Iconoir name="clipboard" class="w-4 h-4 text-warning" /> Falta publicar tasa: {{ alertas.pares_sin_tasa_vigente.join(', ') }}
      </span>
    </div>

    <!-- Encabezado -->
    <div class="flex items-start justify-between">
      <div>
        <h2 class="text-xl font-bold text-heading">Resumen operativo</h2>
        <p class="text-sm text-ink-soft">Hola, {{ auth.user?.name }} · {{ hoy }}</p>
      </div>
      <router-link to="/bancos" class="flex items-center gap-2 bg-white dark:bg-surface-muted border border-edge hover:border-gold text-ink hover:text-gold-dark text-sm font-medium px-4 py-2 rounded-xl shadow-sm transition">
        <Iconoir name="building-library" class="w-5 h-5 text-ink-soft" />
        Gestionar bancos
      </router-link>
    </div>

    <!-- ── Filtros ──────────────────────────────────────────────────────── -->
    <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 items-end">
        <div>
          <label class="block text-[11px] text-ink-soft mb-1">Desde</label>
          <input v-model="filtros.fecha_desde" type="date"
            class="w-full px-3 py-2 text-sm border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        </div>
        <div>
          <label class="block text-[11px] text-ink-soft mb-1">Hasta</label>
          <input v-model="filtros.fecha_hasta" type="date"
            class="w-full px-3 py-2 text-sm border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        </div>
        <div>
          <label class="block text-[11px] text-ink-soft mb-1">Moneda</label>
          <select v-model="filtros.moneda"
            class="w-full px-3 py-2 text-sm border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none">
            <option value="">Todas</option>
            <option value="USD">USD</option>
            <option value="USDT">USDT</option>
            <option value="EUR">EUR</option>
            <option value="COP">COP</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] text-ink-soft mb-1">Operador</label>
          <select v-model="filtros.operador_id"
            class="w-full px-3 py-2 text-sm border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none">
            <option value="">Todos</option>
            <option v-for="o in operadores" :key="o.id" :value="o.id">{{ o.name }}</option>
          </select>
        </div>
        <button @click="aplicarFiltros" :disabled="loadingResumen"
          class="bg-gold text-navy text-sm font-semibold py-2 rounded-lg hover:bg-gold-dark disabled:opacity-50 transition active:scale-[0.98] col-span-2 lg:col-span-1">
          Aplicar filtros
        </button>
      </div>
    </div>

    <!-- ── Loading (skeleton) ───────────────────────────────────────────── -->
    <div v-if="loadingResumen" class="space-y-4">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div v-for="n in 4" :key="n" class="bg-surface border border-edge rounded-xl p-4 h-28 animate-pulse">
          <div class="h-3 w-20 bg-surface-muted rounded mb-3"></div>
          <div class="h-6 w-16 bg-surface-muted rounded"></div>
        </div>
      </div>
      <div class="bg-surface border border-edge rounded-xl p-4 h-32 animate-pulse"></div>
    </div>

    <!-- ── Sin operaciones ──────────────────────────────────────────────── -->
    <div v-else-if="resumen && resumen.operaciones && resumen.operaciones.total === 0"
      class="bg-surface border border-edge rounded-xl p-10 text-center">
      <Iconoir name="chart-bar" class="w-10 h-10 mx-auto mb-3 text-ink-faint" />
      <p class="text-ink-soft">Sin operaciones para este período</p>
    </div>

    <!-- ── Resumen ──────────────────────────────────────────────────────── -->
    <template v-else-if="resumen && resumen.operaciones">
      <!-- Tarjetas -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
          <p class="text-xs text-ink-soft">Total operaciones</p>
          <p class="text-3xl font-bold text-heading mt-1">{{ resumen.operaciones.total ?? 0 }}</p>
        </div>
        <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm space-y-1">
          <p class="text-xs text-ink-soft mb-1">Desglose</p>
          <p class="text-sm flex justify-between"><span class="text-ink-soft">Compras</span><span class="font-semibold text-teal">{{ resumen.operaciones.compras ?? 0 }}</span></p>
          <p class="text-sm flex justify-between"><span class="text-ink-soft">Ventas</span><span class="font-semibold text-gold-dark">{{ resumen.operaciones.ventas ?? 0 }}</span></p>
          <p class="text-sm flex justify-between"><span class="text-ink-soft">Intermediadas</span><span class="font-semibold text-violet">{{ resumen.operaciones.intermediadas ?? 0 }}</span></p>
        </div>
        <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
          <p class="text-xs text-ink-soft">Ganancia bruta</p>
          <p class="text-xl font-bold text-success">{{ formatUsd(resumen.ganancias?.bruta_usd) }}</p>
          <p class="text-xs text-ink-soft mt-2">Ganancia neta</p>
          <p class="text-lg font-bold text-success-strong">{{ formatUsd(resumen.ganancias?.neta_usd) }}</p>
        </div>
        <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
          <p class="text-xs text-ink-soft">Efectivo pendiente</p>
          <p class="text-3xl font-bold text-warning mt-1">{{ resumen.efectivo_pendiente?.count ?? 0 }}</p>
          <p class="text-sm font-semibold text-warning-strong mt-1">{{ formatUsd(resumen.efectivo_pendiente?.monto_usd) }}</p>
        </div>
      </div>

      <!-- Tablas -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Volúmenes por moneda -->
        <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
          <h3 class="font-semibold text-ink text-sm mb-3">Volúmenes por moneda</h3>
          <div v-if="!resumen.volumenes || resumen.volumenes.length === 0" class="text-xs text-ink-faint py-2">Sin volúmenes</div>
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs text-ink-faint border-b border-edge">
                <th class="py-2 font-medium">Moneda</th>
                <th class="py-2 font-medium text-right">Comprado</th>
                <th class="py-2 font-medium text-right">Vendido</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="v in resumen.volumenes" :key="v.moneda" class="border-b border-edge last:border-0">
                <td class="py-2 font-semibold text-ink">{{ v.moneda }}</td>
                <td class="py-2 text-right text-teal">{{ formatUsd(v.comprado) }}</td>
                <td class="py-2 text-right text-gold-dark">{{ formatUsd(v.vendido) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Actividad por operador -->
        <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
          <h3 class="font-semibold text-ink text-sm mb-3">Actividad por operador</h3>
          <div v-if="!resumen.por_operador || resumen.por_operador.length === 0" class="text-xs text-ink-faint py-2">Sin actividad</div>
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs text-ink-faint border-b border-edge">
                <th class="py-2 font-medium">Operador</th>
                <th class="py-2 font-medium text-right">Operaciones</th>
                <th class="py-2 font-medium text-right">Volumen USD</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="o in resumen.por_operador" :key="o.operador" class="border-b border-edge last:border-0">
                <td class="py-2 font-semibold text-ink">{{ o.operador }}</td>
                <td class="py-2 text-right text-ink-muted">{{ o.total_operaciones ?? 0 }}</td>
                <td class="py-2 text-right font-semibold text-heading">{{ formatUsd(o.volumen_usd) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <div v-else-if="resumenError" class="bg-danger-soft text-danger p-4 rounded-xl text-sm">
      {{ resumenError }}
      <button @click="fetchResumen" class="underline ml-2">Reintentar</button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth.js'
import { useFormatting } from '@/composables/useFormatting'
import { useApiError } from '@/composables/useApiError'
import api from '../../api/axios.js'
import TasasReferencia from '@/components/common/TasasReferencia.vue'
import Iconoir from '../../components/common/Iconoir.vue'

const auth = useAuthStore()
const { formatUsd } = useFormatting()
const { parseError } = useApiError()

const hoy = computed(() => new Date().toLocaleDateString('es-VE', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

const resumen = ref(null)
const resumenError = ref('')
const loadingResumen = ref(false)
const operadores = ref([])
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
    resumenError.value = parseError(err)
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

onMounted(async () => {
  await Promise.all([
    fetchResumen(),
    fetchOperadores(),
    fetchAlertas(),
  ])
})
</script>