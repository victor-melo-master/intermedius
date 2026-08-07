<template>
  <div class="space-y-4">
    <!-- ── Encabezado ──────────────────────────────────────────────────── -->
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-navy dark:bg-surface-muted flex items-center justify-center text-white shrink-0">
        <IntermediusSymbol :size="28" />
      </div>
      <div>
        <h2 class="text-xl font-bold text-heading">Reportes</h2>
        <p class="text-sm text-ink-muted">Resumen operativo, comisiones por operador y archivos exportados.</p>
      </div>
    </div>

    <!-- ── Tabs ────────────────────────────────────────────────────────── -->
    <div class="flex gap-2">
      <button @click="cambiarTab('resumen')"
        class="text-sm px-3 py-1.5 rounded-lg transition active:scale-[0.98] inline-flex items-center gap-1.5"
        :class="tab === 'resumen' ? 'bg-gold text-white' : 'bg-surface text-ink-muted hover:bg-surface-soft'">
        <Iconoir name="chart-bar" class="w-4 h-4" /> Resumen del período
      </button>
      <button @click="cambiarTab('comisiones')"
        class="text-sm px-3 py-1.5 rounded-lg transition active:scale-[0.98] inline-flex items-center gap-1.5"
        :class="tab === 'comisiones' ? 'bg-gold text-white' : 'bg-surface text-ink-muted hover:bg-surface-soft'">
        <Iconoir name="receipt-percent" class="w-4 h-4" /> Comisiones por operador
      </button>
    </div>

    <!-- ── Filtros ─────────────────────────────────────────────────────── -->
    <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm space-y-3">
      <div class="flex flex-wrap gap-2">
        <button v-for="p in presets" :key="p.id" @click="aplicarPreset(p.id)"
          class="text-xs px-3 py-1.5 rounded-full border transition active:scale-[0.98]"
          :class="presetActivo === p.id
            ? 'bg-gold text-white border-gold'
            : 'bg-white dark:bg-surface-muted text-ink-muted border-edge-strong hover:border-gold hover:text-gold-dark'">
          {{ p.label }}
        </button>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 items-end">
        <div>
          <label class="block text-sm text-ink-muted mb-1">Desde</label>
          <input v-model="filtros.fecha_desde" type="date" @change="presetActivo = ''"
            class="w-full px-3 py-2 text-sm border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        </div>
        <div>
          <label class="block text-sm text-ink-muted mb-1">Hasta</label>
          <input v-model="filtros.fecha_hasta" type="date" @change="presetActivo = ''"
            class="w-full px-3 py-2 text-sm border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        </div>
        <template v-if="tab === 'resumen'">
          <div>
            <label class="block text-sm text-ink-muted mb-1">Moneda</label>
            <select v-model="filtros.moneda" @change="presetActivo = ''"
              class="w-full px-3 py-2 text-sm border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none">
              <option value="">Todas</option>
              <option value="USD">USD</option>
              <option value="USDT">USDT</option>
              <option value="EUR">EUR</option>
              <option value="COP">COP</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-ink-muted mb-1">Operador</label>
            <select v-model="filtros.operador_id" @change="presetActivo = ''"
              class="w-full px-3 py-2 text-sm border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none">
              <option value="">Todos</option>
              <option v-for="o in operadores" :key="o.id" :value="o.id">{{ o.name }}</option>
            </select>
          </div>
        </template>
      </div>

      <div class="flex flex-wrap gap-2 pt-1">
        <button @click="aplicarFiltros" :disabled="loading"
          class="bg-gold text-white text-sm font-semibold py-2 px-4 rounded-lg hover:bg-gold-dark disabled:opacity-50 transition active:scale-[0.98] inline-flex items-center gap-2">
          <Iconoir name="arrows-right-left" class="w-4 h-4" /> Aplicar filtros
        </button>
        <button @click="exportar('pdf')" :disabled="exporting"
          class="bg-danger text-white text-sm font-semibold py-2 px-4 rounded-lg hover:bg-danger-strong disabled:opacity-50 transition active:scale-[0.98] inline-flex items-center gap-2">
          <span v-if="exporting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          <Iconoir name="document-text" class="w-4 h-4" /> {{ exporting ? 'Generando PDF...' : 'Exportar PDF' }}
        </button>
        <button @click="exportar('excel')" :disabled="exporting"
          class="border border-edge-strong hover:bg-surface-soft text-ink text-sm font-medium py-2 px-4 rounded-lg disabled:opacity-50 transition active:scale-[0.98] inline-flex items-center gap-2">
          <Iconoir name="credit-card" class="w-4 h-4" /> Exportar Excel
        </button>
      </div>
    </div>

    <!-- ── Error general ───────────────────────────────────────────────── -->
    <div v-if="tabError" class="bg-danger-soft text-danger-strong border border-danger-edge p-4 rounded-xl">{{ tabError }}</div>

    <AppLoadingSpinner v-if="loading" />

    <!-- ── Contenido: Resumen del período ──────────────────────────────── -->
    <template v-else-if="tab === 'resumen'">
      <div v-if="resumen && resumen.operaciones && resumen.operaciones.total === 0"
        class="bg-surface border border-edge rounded-xl p-10 text-center">
        <Iconoir name="chart-bar" class="w-10 h-10 mx-auto mb-3 text-ink-muted" />
        <p class="text-ink-muted">Sin operaciones para este período</p>
      </div>

      <template v-else-if="resumen && resumen.operaciones">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
            <p class="text-sm text-ink-muted">Total operaciones</p>
            <p class="text-3xl font-bold text-heading mt-1">{{ resumen.operaciones.total ?? 0 }}</p>
          </div>
          <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm space-y-1">
            <p class="text-sm text-ink-muted mb-1">Desglose</p>
            <p class="text-sm flex justify-between"><span class="text-ink-muted">Compras</span><span class="font-semibold text-teal">{{ resumen.operaciones.compras ?? 0 }}</span></p>
            <p class="text-sm flex justify-between"><span class="text-ink-muted">Ventas</span><span class="font-semibold text-gold-dark">{{ resumen.operaciones.ventas ?? 0 }}</span></p>
            <p class="text-sm flex justify-between"><span class="text-ink-muted">Intermediadas</span><span class="font-semibold text-violet">{{ resumen.operaciones.intermediadas ?? 0 }}</span></p>
          </div>
          <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
            <p class="text-sm text-ink-muted">Ganancia bruta</p>
            <p class="text-xl font-bold text-success">{{ formatUsd(resumen.ganancias?.bruta_usd) }}</p>
            <p class="text-sm text-ink-muted mt-2">Ganancia neta</p>
            <p class="text-lg font-bold text-success-strong">{{ formatUsd(resumen.ganancias?.neta_usd) }}</p>
          </div>
          <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
            <p class="text-sm text-ink-muted">Efectivo pendiente</p>
            <p class="text-3xl font-bold text-warning mt-1">{{ resumen.efectivo_pendiente?.count ?? 0 }}</p>
            <p class="text-sm font-semibold text-warning-strong mt-1">{{ formatUsd(resumen.efectivo_pendiente?.monto_usd) }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
            <h3 class="font-semibold text-ink text-sm mb-3">Volúmenes por moneda</h3>
            <table v-if="resumen.volumenes?.length" class="w-full text-sm">
              <thead class="text-white">
                <tr>
                  <th class="text-left font-semibold bg-gold rounded-l-lg px-3 py-2">Moneda</th>
                  <th class="text-right font-semibold bg-gold px-3 py-2">Comprado</th>
                  <th class="text-right font-semibold bg-gold rounded-r-lg px-3 py-2">Vendido</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(v, i) in resumen.volumenes" :key="v.moneda" :class="i % 2 ? 'bg-surface-alt' : ''">
                  <td class="px-3 py-2 font-semibold text-gold-dark">{{ v.moneda }}</td>
                  <td class="px-3 py-2 text-right">{{ formatUsd(v.comprado) }}</td>
                  <td class="px-3 py-2 text-right">{{ formatUsd(v.vendido) }}</td>
                </tr>
              </tbody>
            </table>
            <p v-else class="text-sm text-ink-muted py-4 text-center">Sin volumen de divisas en el período</p>
          </div>

          <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
            <h3 class="font-semibold text-ink text-sm mb-3">Actividad por operador</h3>
            <table v-if="resumen.por_operador?.length" class="w-full text-sm">
              <thead class="text-white">
                <tr>
                  <th class="text-left font-semibold bg-gold rounded-l-lg px-3 py-2">Operador</th>
                  <th class="text-right font-semibold bg-gold px-3 py-2">Operaciones</th>
                  <th class="text-right font-semibold bg-gold rounded-r-lg px-3 py-2">Volumen USD</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(o, i) in resumen.por_operador" :key="o.operador" :class="i % 2 ? 'bg-surface-alt' : ''">
                  <td class="px-3 py-2 font-semibold">{{ o.operador }}</td>
                  <td class="px-3 py-2 text-right">{{ o.total_operaciones }}</td>
                  <td class="px-3 py-2 text-right">{{ formatUsd(o.volumen_usd) }}</td>
                </tr>
              </tbody>
            </table>
            <p v-else class="text-sm text-ink-muted py-4 text-center">Sin actividad de operadores en el período</p>
          </div>
        </div>
      </template>
    </template>

    <!-- ── Contenido: Comisiones por operador ──────────────────────────── -->
    <template v-else>
      <div v-if="comisiones.length === 0" class="bg-surface border border-edge rounded-xl p-10 text-center">
        <Iconoir name="receipt-percent" class="w-10 h-10 mx-auto mb-3 text-ink-muted" />
        <p class="text-ink-muted">Sin comisiones para el período seleccionado</p>
      </div>

      <div v-else class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
        <table class="w-full text-sm">
          <thead class="text-white">
            <tr>
              <th class="text-left font-semibold bg-gold rounded-l-lg px-3 py-2">Operador</th>
              <th class="text-right font-semibold bg-gold px-3 py-2">Operaciones</th>
              <th class="text-right font-semibold bg-gold rounded-r-lg px-3 py-2">Comisiones USD</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(c, i) in comisiones" :key="c.titular_id || c.titular" :class="i % 2 ? 'bg-surface-alt' : ''">
              <td class="px-3 py-2 font-semibold">{{ c.titular }}</td>
              <td class="px-3 py-2 text-right">{{ c.total_operaciones }}</td>
              <td class="px-3 py-2 text-right font-semibold text-gold-dark">{{ formatUsd(c.total_comisiones_usd) }}</td>
            </tr>
            <tr class="bg-navy text-white">
              <td class="px-3 py-2 font-semibold">Total</td>
              <td class="px-3 py-2 text-right font-semibold">{{ totalComisionesOperaciones }}</td>
              <td class="px-3 py-2 text-right font-bold">{{ formatUsd(totalComisionesUsd) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- ── Histórico de reportes ───────────────────────────────────────── -->
    <div class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-ink text-sm">Histórico de reportes</h3>
        <button @click="fetchHistorico" class="text-xs text-gold-dark hover:text-gold-dark inline-flex items-center gap-1">
          <Iconoir name="arrow-path" class="w-3.5 h-3.5" /> Actualizar
        </button>
      </div>

      <p v-if="historico.length === 0" class="text-sm text-ink-muted py-4 text-center">Sin reportes exportados todavía</p>

      <table v-else class="w-full text-sm">
        <thead class="text-white">
          <tr>
            <th class="text-left font-semibold bg-navy rounded-l-lg px-3 py-2">Reporte</th>
            <th class="text-left font-semibold bg-navy px-3 py-2">Tipo</th>
            <th class="text-left font-semibold bg-navy px-3 py-2">Formato</th>
            <th class="text-left font-semibold bg-navy px-3 py-2">Generado</th>
            <th class="text-right font-semibold bg-navy px-3 py-2">Tamaño</th>
            <th class="text-right font-semibold bg-navy rounded-r-lg px-3 py-2">Acción</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(r, i) in historico" :key="r.path" :class="i % 2 ? 'bg-surface-alt' : ''">
            <td class="px-3 py-2 font-medium">{{ r.nombre }}</td>
            <td class="px-3 py-2">
              <span class="text-[10px] px-2 py-0.5 rounded-full font-medium"
                :class="r.tipo === 'resumen' ? 'bg-info-soft text-info' : 'bg-violet-soft text-violet'">
                {{ r.tipo === 'resumen' ? 'Resumen' : 'Comisiones' }}
              </span>
            </td>
            <td class="px-3 py-2">
              <span class="text-[10px] px-2 py-0.5 rounded-full font-medium uppercase"
                :class="r.formato === 'pdf' ? 'bg-danger-soft text-danger' : 'bg-success-soft text-success'">
                {{ r.formato }}
              </span>
            </td>
            <td class="px-3 py-2 text-ink-muted">{{ formatDateTime(r.modificado_en) }}</td>
            <td class="px-3 py-2 text-right text-ink-muted">{{ formatTamano(r.tamano_bytes) }}</td>
            <td class="px-3 py-2 text-right">
              <button @click="descargarReporte(r.path)"
                class="text-xs text-gold-dark hover:text-gold-dark underline inline-flex items-center gap-1">
                <Iconoir name="arrow-down" class="w-3.5 h-3.5" /> Descargar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
/**
 * ReportesView — Hub de reportes operativos.
 * - Resumen del período: agregados de operaciones con filtros por fechas,
 *   moneda y operador; vista previa y exportación a PDF/Excel.
 * - Comisiones por operador: consulta y exportación en rango de fechas.
 * - Histórico: archivos exportados (comisiones + resumen) descargables.
 * Los PDFs se generan en el backend con estética corporativa Intermedius.
 */
import { computed, onMounted, reactive, ref } from 'vue'
import { useFormatting } from '@/composables/useFormatting'
import { useApiError } from '@/composables/useApiError'
import { useNotification } from '@/composables/useNotification'
import api from '../../api/axios.js'
import Iconoir from '../../components/common/Iconoir.vue'
import AppLoadingSpinner from '../../components/common/AppLoadingSpinner.vue'
import IntermediusSymbol from '../../components/common/IntermediusSymbol.vue'

const { formatUsd, formatDateTime, formatTamano } = useFormatting()
const { parseError } = useApiError()
const notify = useNotification()

/** Tab activa: 'resumen' | 'comisiones' */
const tab = ref('resumen')
/** Filtros del período compartidos entre tabs */
const filtros = reactive({
  fecha_desde: '',
  fecha_hasta: '',
  moneda: '',
  operador_id: '',
})
/** Preset de período activo */
const presetActivo = ref('mes')
/** Operadores disponibles para el filtro */
const operadores = ref([])
/** Datos del resumen operativo */
const resumen = ref(null)
/** Datos de comisiones por operador */
const comisiones = ref([])
/** Histórico de reportes exportados */
const historico = ref([])
/** Indica carga de datos */
const loading = ref(false)
/** Indica generación de exportación */
const exporting = ref(false)
/** Error del tab activo */
const tabError = ref('')

const presets = [
  { id: 'hoy', label: 'Hoy' },
  { id: 'semana', label: 'Esta semana' },
  { id: 'mes', label: 'Este mes' },
  { id: 'anio', label: 'Este año' },
]

/** Convierte una fecha Date a formato ISO local (YYYY-MM-DD). */
function toISODate(d) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

/** Aplica un preset de período sobre los filtros. */
function aplicarPreset(id) {
  const hoy = new Date()
  let desde = new Date(hoy)
  const hasta = new Date(hoy)

  if (id === 'semana') {
    const diff = (hoy.getDay() + 6) % 7 // lunes = 0
    desde.setDate(hoy.getDate() - diff)
  } else if (id === 'mes') {
    desde = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
  } else if (id === 'anio') {
    desde = new Date(hoy.getFullYear(), 0, 1)
  }

  filtros.fecha_desde = toISODate(desde)
  filtros.fecha_hasta = toISODate(hasta)
  presetActivo.value = id
  aplicarFiltros()
}

/** Cambia de tab y carga los datos correspondientes. */
function cambiarTab(t) {
  tab.value = t
  presetActivo.value = ''
  aplicarFiltros()
}

/** Valida que las fechas estén presentes y en orden. */
function validarFechas() {
  if (!filtros.fecha_desde || !filtros.fecha_hasta) {
    notify.warning('Selecciona un período (desde y hasta).')
    return false
  }
  if (filtros.fecha_desde > filtros.fecha_hasta) {
    notify.warning('La fecha "hasta" no puede ser anterior a "desde".')
    return false
  }
  return true
}

/** Consulta el resumen operativo del período. */
async function fetchResumen() {
  loading.value = true
  tabError.value = ''
  try {
    const params = {
      fecha_desde: filtros.fecha_desde,
      fecha_hasta: filtros.fecha_hasta,
    }
    if (filtros.moneda) params.moneda = filtros.moneda
    if (filtros.operador_id) params.operador_id = filtros.operador_id
    const { data } = await api.get('/reportes/resumen', { params })
    resumen.value = data
  } catch (err) {
    resumen.value = null
    tabError.value = parseError(err)
  } finally {
    loading.value = false
  }
}

/** Consulta el reporte de comisiones por operador del período. */
async function fetchComisiones() {
  loading.value = true
  tabError.value = ''
  try {
    const { data } = await api.get('/reportes/comisiones-operadores', {
      params: { desde: filtros.fecha_desde, hasta: filtros.fecha_hasta }
    })
    comisiones.value = Array.isArray(data.data) ? data.data : []
  } catch (err) {
    comisiones.value = []
    tabError.value = parseError(err)
  } finally {
    loading.value = false
  }
}

/** Carga el histórico de reportes exportados. */
async function fetchHistorico() {
  try {
    const { data } = await api.get('/reportes/historico')
    historico.value = Array.isArray(data.data) ? data.data : []
  } catch {
    historico.value = []
  }
}

/** Aplica los filtros cargando los datos del tab activo. */
function aplicarFiltros() {
  if (!validarFechas()) return
  if (tab.value === 'resumen') {
    fetchResumen()
  } else {
    fetchComisiones()
  }
}

/** Carga los operadores para el filtro (usuarios con rol operador). */
async function fetchOperadores() {
  try {
    const { data } = await api.get('/usuarios')
    const lista = Array.isArray(data) ? data : (data.data || [])
    operadores.value = lista.filter(u => u.roles?.includes('operador'))
  } catch {
    operadores.value = []
  }
}

/**
 * Descarga un reporte del backend mediante el endpoint protegido
 * (el disco es s3, por lo que no se puede usar una URL pública).
 * @param {string} path Ruta relativa del archivo en storage
 * @returns {Promise<void>}
 */
async function descargarReporte(path) {
  try {
    const res = await api.get('/reportes/descargar', { params: { path }, responseType: 'blob' })
    const nombre = path.split('/').pop() || 'reporte'
    const url = window.URL.createObjectURL(res.data)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', nombre)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    notify.success(`Reporte descargado: ${nombre}`)
  } catch (err) {
    let msg = parseError(err)
    if (err.response?.data instanceof Blob) {
      try {
        const text = await err.response.data.text()
        const parsed = JSON.parse(text)
        if (parsed.message) msg = parsed.message
      } catch {
        /* mantiene el mensaje por defecto */
      }
    }
    notify.error(msg)
  }
}

/**
 * Exporta el reporte del tab activo al formato indicado y lo descarga.
 * @param {'pdf'|'excel'} formato Formato de salida
 * @returns {Promise<void>}
 */
async function exportar(formato) {
  if (exporting.value || !validarFechas()) return
  exporting.value = true
  try {
    const body = { formato }

    if (tab.value === 'resumen') {
      body.fecha_desde = filtros.fecha_desde
      body.fecha_hasta = filtros.fecha_hasta
      if (filtros.moneda) body.moneda = filtros.moneda
      if (filtros.operador_id) body.operador_id = filtros.operador_id
      const { data } = await api.post('/reportes/resumen/exportar', body)
      await descargarReporte(data.data.path)
    } else {
      body.desde = filtros.fecha_desde
      body.hasta = filtros.fecha_hasta
      const { data } = await api.post('/reportes/comisiones-operadores/exportar', body)
      await descargarReporte(data.data.path)
    }

    fetchHistorico()
  } catch (err) {
    notify.error(parseError(err))
  } finally {
    exporting.value = false
  }
}

/** Total de operaciones en la tabla de comisiones. */
const totalComisionesOperaciones = computed(() =>
  comisiones.value.reduce((sum, c) => sum + (Number(c.total_operaciones) || 0), 0)
)

/** Total de comisiones en USD. */
const totalComisionesUsd = computed(() =>
  comisiones.value.reduce((sum, c) => sum + (Number(c.total_comisiones_usd) || 0), 0)
)

onMounted(async () => {
  aplicarPreset('mes')
  fetchOperadores()
  fetchHistorico()
})
</script>
