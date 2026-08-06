<template>
  <div class="space-y-4">
    <h2 class="text-xl font-bold text-heading">Reportes</h2>

    <div class="bg-surface border border-edge rounded-xl p-5 space-y-4">
      <h3 class="font-semibold text-ink">Comisiones por operador</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm text-ink-muted mb-1">Desde</label>
          <input v-model="desde" type="date" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        </div>
        <div>
          <label class="block text-sm text-ink-muted mb-1">Hasta</label>
          <input v-model="hasta" type="date" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        </div>
      </div>
      <div class="flex gap-2">
        <button @click="buscar" :disabled="loading"
          class="flex-1 bg-gold hover:bg-gold-dark disabled:opacity-50 text-white font-semibold py-2.5 rounded-lg transition flex items-center justify-center gap-2">
          <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ loading ? 'Consultando...' : 'Consultar' }}
        </button>
        <button @click="exportar" :disabled="exporting"
          class="flex-1 border border-edge-strong hover:bg-surface-soft text-ink font-medium py-2.5 rounded-lg transition flex items-center justify-center gap-1">
          <span v-if="exporting" class="w-4 h-4 border-2 border-edge-strong/30 border-t-ink-soft rounded-full animate-spin"></span>
          {{ exporting ? 'Generando...' : '⬇ Exportar Excel' }}
        </button>
      </div>
    </div>

    <div v-if="error" class="bg-danger-soft text-danger p-4 rounded-xl">{{ error }}</div>

    <div v-if="loaded && data.length === 0" class="text-center py-12">
      <Iconoir name="arrow-trending-down" class="w-12 h-12 mx-auto mb-4 text-ink-muted" />
      <p class="text-ink-muted">Sin datos para el período seleccionado</p>
    </div>

    <div v-if="data.length" class="space-y-2">
      <div v-for="item in data" :key="item.operador || item.nombre" class="bg-surface border border-edge rounded-xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-gold-soft flex items-center justify-center text-gold-dark font-bold text-sm">
          {{ (item.operador || item.nombre || 'O').charAt(0).toUpperCase() }}
        </div>
        <div class="flex-1">
          <p class="font-semibold text-sm">{{ item.operador || item.nombre || 'Operador' }}</p>
          <p class="text-sm text-ink-muted">{{ item.cantidad_operaciones || item.total_operaciones || 0 }} operaciones</p>
        </div>
        <p class="text-lg font-bold text-gold-dark">${{ formatMoney(item.total_comisiones || 0) }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * ReportesView — Generación de reportes descargables.
 * Permite consultar y exportar comisiones por operador en un rango de fechas.
 * Los datos se muestran en tarjetas y se pueden exportar a Excel.
 */
import { ref } from 'vue'
import { useFormatting } from '@/composables/useFormatting'
import { useApiError } from '@/composables/useApiError'
import api from '../../api/axios.js'
import Iconoir from '../../components/common/Iconoir.vue'

const { formatMoney } = useFormatting()
const { parseError } = useApiError()

/** Fecha de inicio del reporte (primer día del mes actual) */
const desde = ref(new Date().toISOString().slice(0, 8) + '01')
/** Fecha de fin del reporte (hoy) */
const hasta = ref(new Date().toISOString().slice(0, 10))
/** Indica carga de la consulta */
const loading = ref(false)
/** Indica generación de exportación */
const exporting = ref(false)
/** Mensaje de error */
const error = ref('')
/** Datos del reporte consultado */
const data = ref([])
/** Indica si ya se realizó una consulta */
const loaded = ref(false)

/**
 * Consulta el reporte de comisiones por operador en el rango de fechas.
 * @returns {Promise<void>}
 */
async function buscar() {
  error.value = ''
  loading.value = true
  loaded.value = false
  try {
    const { data: res } = await api.get('/reportes/comisiones-operadores', {
      params: { desde: desde.value, hasta: hasta.value }
    })
    data.value = res.data || []
    loaded.value = true
  } catch (err) {
    error.value = parseError(err)
    loaded.value = true
  } finally {
    loading.value = false
  }
}

/**
 * Exporta el reporte a Excel.
 * @returns {Promise<void>}
 */
async function exportar() {
  error.value = ''
  exporting.value = true
  try {
    await api.post('/reportes/comisiones-operadores/exportar', {
      desde: desde.value,
      hasta: hasta.value,
      formato: 'excel'
    })
    alert('Reporte generado. Revisa tu email o el servidor.')
  } catch (err) {
    error.value = parseError(err)
  } finally {
    exporting.value = false
  }
}
</script>
