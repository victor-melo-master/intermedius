<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-xl font-bold text-heading">Tasas del día</h2>
      <div class="flex gap-2">
        <button @click="tasas.fetchVigentes()" class="px-3 py-2 bg-white dark:bg-surface-muted border border-edge-strong rounded-lg text-sm hover:bg-surface-soft inline-flex items-center gap-1"><Iconoir name="arrow-path" class="w-4 h-4" /> Actualizar</button>
        <button v-if="auth.isAdmin" @click="openNew" class="px-4 py-2 bg-gold text-navy rounded-lg text-sm font-medium hover:bg-gold-dark">+ Publicar tasa</button>
      </div>
    </div>

    <div v-if="tasas.loading" class="text-center py-12">
      <div class="w-8 h-8 border-2 border-gold border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>
    <div v-else-if="tasas.error" class="bg-danger-soft text-danger p-4 rounded-xl">
      {{ tasas.error }}
      <button @click="tasas.fetchVigentes()" class="underline ml-2">Reintentar</button>
    </div>
    <template v-else>
      <!-- Alerta sin tasas hoy -->
      <div v-if="!hayTasasHoy" class="bg-warning-soft border border-warning-edge text-warning-strong p-4 rounded-xl text-sm flex items-center gap-1">
        <Iconoir name="exclamation-triangle" class="w-4 h-4 shrink-0 text-warning" /> No hay tasas publicadas para hoy
        <span v-if="auth.isAdmin"> — usa <strong>+ Publicar tasa</strong> para crearlas.</span>
      </div>

      <!-- Grupos por moneda base -->
      <div v-for="grupo in grupos" :key="grupo.baseId" class="space-y-2">
        <div class="flex items-center gap-2">
          <span class="text-lg">{{ iconoMoneda(grupo.baseCodigo) }}</span>
          <h3 class="font-semibold text-ink">{{ grupo.baseCodigo }}</h3>
          <span class="text-sm text-ink-muted">{{ grupo.items.length }} {{ grupo.items.length === 1 ? 'par' : 'pares' }}</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div v-for="t in grupo.items" :key="t.id" class="bg-surface border border-edge rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <span class="bg-info-soft text-info-strong text-xs font-bold px-3 py-1 rounded-full">{{ t.par }}</span>
              <button v-if="auth.isAdmin" @click="openEdit(t)" class="text-sm text-ink-muted hover:text-gold-dark inline-flex items-center gap-1" title="Editar"><Iconoir name="pencil-square" class="w-4 h-4" /> Editar</button>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-ink-muted mb-0.5">Compra sugerida</p>
                <p class="text-xl font-bold text-teal">{{ formatRate(t.tasa_compra) }}</p>
                <p v-if="t.tasa_compra_minima" class="text-sm text-ink-muted mt-0.5">Mín: {{ formatRate(t.tasa_compra_minima) }}</p>
              </div>
              <div>
                <p class="text-sm text-ink-muted mb-0.5">Venta sugerida</p>
                <p class="text-xl font-bold text-gold-dark">{{ formatRate(t.tasa_venta) }}</p>
                <p v-if="t.tasa_venta_minima" class="text-sm text-ink-muted mt-0.5">Mín: {{ formatRate(t.tasa_venta_minima) }}</p>
              </div>
            </div>
            <p class="text-sm text-ink-muted mt-3 pt-2 border-t border-edge">{{ publicada(t.vigente_desde) }}</p>
          </div>
        </div>
      </div>
    </template>

    <AppFormModal v-model="showForm" :title="isEdit ? 'Editar tasa' : 'Publicar tasa del día'">
      <!-- Paso 1: moneda base -->
      <p class="text-sm font-medium text-ink-muted mb-2">1. Moneda base</p>
      <div class="grid grid-cols-4 gap-2 mb-5">
        <button v-for="m in baseOptions" :key="m.id" type="button" @click="selectBase(m.id)"
          class="py-3 rounded-xl border-2 text-center transition"
          :class="selectedBaseId === m.id ? 'border-info bg-info-soft' : 'border-edge hover:border-edge-strong'">
          <span class="block text-xl">{{ iconoMoneda(m.codigo) }}</span>
          <span class="block text-xs font-semibold mt-0.5" :class="selectedBaseId === m.id ? 'text-info-strong' : 'text-ink-muted'">{{ m.codigo }}</span>
        </button>
      </div>

      <!-- Paso 2: pares -->
      <template v-if="selectedBaseId">
        <p class="text-sm font-medium text-ink-muted mb-2">2. Configurar pares</p>
        <div class="space-y-3">
          <div v-for="ref in referenceMonedas" :key="ref.id" class="border border-edge rounded-xl p-3"
            :class="pairs[ref.id]?.active ? 'bg-white dark:bg-surface-muted' : 'bg-surface-soft'">
            <label class="flex items-center justify-between cursor-pointer">
              <span class="font-semibold text-sm text-ink">{{ baseCodigo }} / {{ ref.codigo }}</span>
              <input type="checkbox" v-model="pairs[ref.id].active" class="accent-gold w-5 h-5" />
            </label>
            <div v-if="pairs[ref.id]?.active" class="grid grid-cols-2 gap-2 mt-3">
              <div>
                <label class="block text-sm text-ink-muted mb-1">Tasa compra *</label>
                <input v-model="pairs[ref.id].tasa_compra" type="number" step="0.01" inputmode="decimal" placeholder="0.00"
                  class="w-full px-3 py-2 text-sm border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
              </div>
              <div>
                <label class="block text-sm text-ink-muted mb-1">Mín. compra</label>
                <input v-model="pairs[ref.id].tasa_compra_minima" type="number" step="0.01" inputmode="decimal" placeholder="opcional"
                  class="w-full px-3 py-2 text-sm border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
              </div>
              <div>
                <label class="block text-sm text-ink-muted mb-1">Tasa venta *</label>
                <input v-model="pairs[ref.id].tasa_venta" type="number" step="0.01" inputmode="decimal" placeholder="0.00"
                  class="w-full px-3 py-2 text-sm border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
              </div>
              <div>
                <label class="block text-sm text-ink-muted mb-1">Mín. venta</label>
                <input v-model="pairs[ref.id].tasa_venta_minima" type="number" step="0.01" inputmode="decimal" placeholder="opcional"
                  class="w-full px-3 py-2 text-sm border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
              </div>
            </div>
          </div>
        </div>
      </template>

      <div v-if="formError" class="bg-danger-soft text-danger text-sm p-3 rounded-lg mt-4 whitespace-pre-line">{{ formError }}</div>

      <button type="button" @click="submit" :disabled="saving || !puedeGuardar"
        class="w-full mt-5 bg-gold text-navy font-semibold py-2.5 rounded-lg hover:bg-gold-dark disabled:opacity-50 transition active:scale-[0.98] flex items-center justify-center gap-2">
        <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
        {{ saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Publicar') }}
      </button>
    </AppFormModal>
  </div>
</template>

<script setup>
/**
 * TasasView — Gestión de tasas de mercado del día.
 * Muestra las tasas vigentes agrupadas por moneda base, con alerta si no hay tasas hoy.
 * Modal en 2 pasos: seleccionar moneda base y configurar pares con tasas compra/venta
 * (y mínimos opcionales). Solo administradores pueden publicar/editar tasas.
 */
import { ref, reactive, computed, onMounted } from 'vue'
import { useTasasStore } from '../../stores/tasas.js'
import { useAuthStore } from '../../stores/auth.js'
import { useFormatting } from '@/composables/useFormatting'
import { useApiError } from '@/composables/useApiError'
import AppFormModal from '@/components/common/AppFormModal.vue'
import Iconoir from '../../components/common/Iconoir.vue'

/** Store de tasas */
const tasas = useTasasStore()
/** Store de autenticación (permisos) */
const auth = useAuthStore()
const { formatRate } = useFormatting()
const { parseError } = useApiError()

/** Controla visibilidad del modal */
const showForm = ref(false)
/** Indica si es modo edición */
const isEdit = ref(false)
/** Indica guardado en curso */
const saving = ref(false)
/** Error del formulario */
const formError = ref('')
/** ID de moneda base seleccionada en el modal */
const selectedBaseId = ref('')
/** Pares de tasas configurados en el modal (key: moneda_cotizada_id) */
const pairs = reactive({})

/** Moneda local (cotizada) */
const LOCAL = 'VES'
/** Íconos para cada moneda */
const ICONOS = { USD: '💵', USDT: '₮', EUR: '€', COP: '$', VES: 'Bs' }
/**
 * Devuelve el ícono de una moneda.
 * @param {string} codigo - Código de moneda
 * @returns {string}
 */
function iconoMoneda(codigo) { return ICONOS[codigo] || '💱' }

/** Mapa de moneda ID a objeto de moneda */
const monedaById = computed(() => Object.fromEntries(tasas.monedas.map(m => [m.id, m])))

/** Opciones de moneda base (todas excepto VES) */
const baseOptions = computed(() => tasas.monedas.filter(m => m.codigo !== LOCAL))

/** Código de la moneda base seleccionada */
const baseCodigo = computed(() => monedaById.value[selectedBaseId.value]?.codigo || '')

/** Monedas cotizadas disponibles para la base seleccionada */
const referenceMonedas = computed(() =>
  tasas.monedas.filter(m => m.id !== selectedBaseId.value)
)

/**
 * Obtiene el código de la moneda base de una tasa.
 * @param {Object} t - Tasa vigente
 * @returns {string}
 */
function baseCodigoDe(t) {
  return monedaById.value[t.moneda_base_id]?.codigo || (t.par || '').split('/')[0] || '?'
}

/** Tasas vigentes agrupadas por moneda base */
const grupos = computed(() => {
  const map = {}
  for (const t of tasas.vigentes) {
    const baseId = t.moneda_base_id
    if (!map[baseId]) map[baseId] = { baseId, baseCodigo: baseCodigoDe(t), items: [] }
    map[baseId].items.push(t)
  }
  return Object.values(map)
})

/** Indica si hay al menos una tasa publicada hoy */
const hayTasasHoy = computed(() => tasas.vigentes.some(t => esHoy(t.vigente_desde)))

/** Indica si se puede guardar (base seleccionada y al menos un par activo) */
const puedeGuardar = computed(() =>
  selectedBaseId.value && Object.values(pairs).some(p => p?.active)
)

/**
 * Verifica si una fecha ISO corresponde al día de hoy.
 * @param {string} iso - Fecha ISO
 * @returns {boolean}
 */
function esHoy(iso) {
  if (!iso) return false
  const d = new Date(iso)
  const h = new Date()
  return d.toDateString() === h.toDateString()
}

/**
 * Genera texto descriptivo de cuándo fue publicada una tasa.
 * @param {string} iso - Fecha ISO
 * @returns {string}
 */
function publicada(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  const hora = d.toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' })
  if (esHoy(iso)) return `Publicada hoy ${hora}`
  return `Publicada ${d.toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit' })} ${hora}`
}

/** Crea un objeto de par vacío para el formulario */
function emptyPair() {
  return { active: false, tasa_compra: '', tasa_compra_minima: '', tasa_venta: '', tasa_venta_minima: '' }
}

/**
 * Construye los pares del formulario para una moneda base.
 * Si prefill es true, precarga con datos de tasas vigentes.
 * @param {number|string} baseId - ID de moneda base
 * @param {Object} [options] - Opciones
 * @param {boolean} [options.prefill=false] - Precargar con tasas existentes
 */
function buildPairs(baseId, { prefill = false } = {}) {
  Object.keys(pairs).forEach(k => delete pairs[k])
  for (const ref of tasas.monedas.filter(m => m.id !== baseId)) {
    pairs[ref.id] = emptyPair()
  }
  if (prefill) {
    for (const t of tasas.vigentes.filter(v => v.moneda_base_id === baseId)) {
      const p = pairs[t.moneda_cotizada_id]
      if (!p) continue
      p.active = true
      p.tasa_compra = t.tasa_compra ?? ''
      p.tasa_compra_minima = t.tasa_compra_minima ?? ''
      p.tasa_venta = t.tasa_venta ?? ''
      p.tasa_venta_minima = t.tasa_venta_minima ?? ''
    }
  }
}

/**
 * Selecciona una moneda base y construye los pares en el formulario.
 * @param {number|string} id - ID de moneda base
 */
function selectBase(id) {
  selectedBaseId.value = id
  buildPairs(id, { prefill: true })
}

/** Abre el modal en modo nueva publicación */
async function openNew() {
  await tasas.fetchMonedas()
  isEdit.value = false
  formError.value = ''
  selectedBaseId.value = ''
  Object.keys(pairs).forEach(k => delete pairs[k])
  showForm.value = true
}

/**
 * Abre el modal en modo edición para una tasa específica.
 * @param {Object} t - Tasa vigente a editar
 */
async function openEdit(t) {
  await tasas.fetchMonedas()
  isEdit.value = true
  formError.value = ''
  showForm.value = true
  selectBase(t.moneda_base_id)
}

/**
 * Envía las tasas configuradas a la API (una por par).
 * @returns {Promise<void>}
 */
async function submit() {
  formError.value = ''
  const activos = Object.entries(pairs).filter(([, p]) => p.active)

  if (!activos.length) {
    formError.value = 'Activa al menos un par para guardar.'
    return
  }
  for (const [cotId, p] of activos) {
    if (!p.tasa_compra || !p.tasa_venta) {
      formError.value = `Completa tasa compra y venta del par ${baseCodigo.value}/${monedaById.value[cotId]?.codigo}.`
      return
    }
  }

  saving.value = true
  const fecha = new Date().toISOString().split('T')[0]
  try {
    for (const [cotId, p] of activos) {
      const body = {
        fecha,
        moneda_base_id: Number(selectedBaseId.value),
        moneda_cotizada_id: Number(cotId),
        tasa_compra: parseFloat(p.tasa_compra),
        tasa_venta: parseFloat(p.tasa_venta),
        notas: 'Tasa del día',
      }
      if (p.tasa_compra_minima !== '' && p.tasa_compra_minima != null) body.tasa_compra_minima = parseFloat(p.tasa_compra_minima)
      if (p.tasa_venta_minima !== '' && p.tasa_venta_minima != null) body.tasa_venta_minima = parseFloat(p.tasa_venta_minima)
      await tasas.publicar(body)
    }
    showForm.value = false
    await tasas.fetchVigentes()
  } catch (err) {
    formError.value = parseError(err)
  } finally {
    saving.value = false
  }
}

/** Carga tasas vigentes y monedas al montar */
onMounted(() => {
  tasas.fetchVigentes()
  tasas.fetchMonedas()
})
</script>
