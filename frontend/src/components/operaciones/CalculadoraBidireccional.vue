<template>
  <div :class="flat ? 'space-y-4' : 'bg-surface border border-edge rounded-xl p-5 space-y-4'">
    <h3 class="font-semibold text-ink">Monto y tasa</h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm text-ink-muted mb-1">Monto {{ moneda }} *</label>
        <div class="flex gap-2 items-start">
          <input
            :value="montoDisplay"
            @input="onMontoInput"
            type="text" inputmode="decimal" required
            placeholder="100.00"
            class="flex-1 px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-gold outline-none"
          />
          <button
            v-if="monto !== '' && monto != null"
            type="button"
            @click="limpiarMonto"
            class="shrink-0 px-2.5 h-[42px] bg-surface-muted hover:bg-danger-soft hover:text-danger text-ink-muted rounded-xl text-sm font-medium transition active:scale-[0.98] flex items-center gap-1"
          ><Iconoir name="x-mark" class="w-3.5 h-3.5" /> Limpiar</button>
        </div>
      </div>
      <div>
        <label class="block text-sm text-ink-muted mb-1">
          Tasa {{ tipo === 'venta' ? 'de venta' : 'de compra' }} ({{ parStr }}) *
        </label>
        <div class="flex gap-2 items-start">
          <input
            :value="tasaDisplay"
            @input="onTasaInput"
            type="text" inputmode="decimal" required
            :placeholder="tasaSugerida ? formatTasa(tasaSugerida) : '36.50'"
            class="flex-1 px-4 py-2.5 border rounded-xl focus:ring-2 outline-none"
            :class="desfavorable ? 'border-warning focus:ring-warning' : 'border-edge-strong focus:ring-gold'"
          />
          <button
            v-if="tasa !== '' && tasa != null"
            type="button"
            @click="limpiarTasa"
            class="shrink-0 px-2.5 h-[42px] bg-surface-muted hover:bg-danger-soft hover:text-danger text-ink-muted rounded-xl text-sm font-medium transition active:scale-[0.98] flex items-center gap-1"
          ><Iconoir name="x-mark" class="w-3.5 h-3.5" /> Limpiar</button>
        </div>
        <p v-if="tasaSugerida" class="text-sm text-ink-muted mt-1">
          Referencia ({{ fuenteLabel }}): <span class="font-medium text-ink-muted">{{ formatTasa(tasaSugerida) }}</span>
        </p>
        <p v-else class="text-xs text-warning mt-1">No hay tasa de referencia disponible.</p>
      </div>
    </div>

    <div v-if="desfavorable" class="bg-warning-soft border border-warning-edge text-warning-strong text-sm p-3 rounded-lg">
      <Iconoir name="exclamation-triangle" class="w-4 h-4 inline text-warning" /> La tasa es desfavorable para la casa.
    </div>

    <div>
      <label class="block text-sm text-ink-muted mb-1">{{ quoteNombre }} ({{ quoteCodigo }}) a recibir</label>
      <div class="flex gap-2 items-start">
        <div class="relative flex-1">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted text-sm">{{ quoteSimbolo }}</span>
          <input
            :value="bolivaresDisplay"
            @input="onBolivaresInput"
            type="text" inputmode="decimal" placeholder="0.00"
            class="w-full pl-10 pr-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-gold outline-none"
          />
        </div>
        <button
          v-if="bolivares !== '' && bolivares != null"
          type="button"
          @click="limpiarBolivares"
          class="shrink-0 px-2.5 h-[42px] bg-surface-muted hover:bg-danger-soft hover:text-danger text-ink-muted rounded-xl text-sm font-medium transition active:scale-[0.98] flex items-center gap-1"
        ><Iconoir name="x-mark" class="w-3.5 h-3.5" /> Limpiar</button>
      </div>
      <p class="text-sm text-ink-muted mt-1">
        Editá dos valores y el tercero se calcula automáticamente.
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import Iconoir from '../common/Iconoir.vue'

const props = defineProps({
  monto: [String, Number],
  bolivares: [String, Number],
  tasa: [String, Number],
  tipo: String,
  moneda: String,
  quoteCodigo: String,
  quoteSimbolo: String,
  quoteNombre: String,
  parStr: String,
  tasaSugerida: [String, Number, null],
  desfavorable: Boolean,
  flat: Boolean,
})

const emit = defineEmits(['update:monto', 'update:bolivares', 'update:tasa'])

// Orden de edición: índice 0 = más reciente. Los dos primeros son los
// campos "conocidos"; el último (índice 2) es el que se recalcula.
const editOrder = ref(['monto', 'tasa', 'bolivares'])

function marcarEditado(campo) {
  const idx = editOrder.value.indexOf(campo)
  if (idx !== -1) editOrder.value.splice(idx, 1)
  editOrder.value.unshift(campo)
}

const fuenteLabel = computed(() => {
  const map = { USD: 'BCV', EUR: 'BCV', USDT: 'Binance', COP: 'BCV' }
  return map[props.moneda] || props.moneda || 'BCV'
})

function parse(v) {
  if (v === '' || v == null) return 0
  return parseFloat(String(v).replace(/,/g, '')) || 0
}

function fmt(v) {
  if (v === '' || v == null) return ''
  const s = String(v).replace(/,/g, '')
  const n = parseFloat(s)
  if (isNaN(n)) return s
  const parts = s.split('.')
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  return parts.join('.')
}

function fmtTasa(v) {
  if (v === '' || v == null) return ''
  return String(v)
}

function formatTasa(val) {
  const n = parseFloat(val)
  if (isNaN(n)) return ''
  return n.toFixed(2)
}

function round2(n) {
  return (Math.round((n + Number.EPSILON) * 100) / 100).toString()
}

const montoDisplay = computed(() => fmt(props.monto))
const tasaDisplay = computed(() => fmtTasa(props.tasa))
const bolivaresDisplay = computed(() => fmt(props.bolivares))

function limpiarMonto() {
  emit('update:monto', '')
}

function limpiarTasa() {
  emit('update:tasa', '')
  emit('update:bolivares', '')
}

function limpiarBolivares() {
  emit('update:bolivares', '')
}

/**
 * Handler único: emite el campo editado, lo marca como el más reciente
 * y recalcula el tercer campo a partir de los dos más recientemente
 * tocados (sin pisar el que el usuario acaba de escribir).
 */
function handleInput(campo, rawValue) {
  emit(`update:${campo}`, rawValue)
  marcarEditado(campo)

  const valores = {
    monto: campo === 'monto' ? parse(rawValue) : parse(props.monto),
    tasa: campo === 'tasa' ? parse(rawValue) : parse(props.tasa),
    bolivares: campo === 'bolivares' ? parse(rawValue) : parse(props.bolivares),
  }

  const target = editOrder.value[2]

  if (target === 'bolivares') {
    if (valores.monto > 0 && valores.tasa > 0) {
      emit('update:bolivares', round2(valores.monto * valores.tasa))
    }
  } else if (target === 'tasa') {
    if (valores.monto > 0 && valores.bolivares > 0) {
      emit('update:tasa', round2(valores.bolivares / valores.monto))
    }
  } else if (target === 'monto') {
    if (valores.tasa > 0 && valores.bolivares > 0) {
      emit('update:monto', round2(valores.bolivares / valores.tasa))
    }
  }
}

function onMontoInput(e) {
  handleInput('monto', String(e.target.value).replace(/,/g, ''))
}

function onTasaInput(e) {
  handleInput('tasa', String(e.target.value).replace(/,/g, ''))
}

function onBolivaresInput(e) {
  handleInput('bolivares', String(e.target.value).replace(/,/g, ''))
}
</script>
