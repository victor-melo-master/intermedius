<template>
  <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
    <h3 class="font-semibold text-gray-700">Monto y tasa</h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm text-gray-600 mb-1">Monto {{ moneda }} *</label>
        <div class="flex gap-2 items-start">
          <input
            :value="fmt(monto)"
            @input="onMontoInput"
            type="text" inputmode="decimal" required
            placeholder="100.00"
            class="flex-1 px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"
          />
          <button
            v-if="monto !== '' && monto != null"
            type="button"
            @click="limpiarMonto"
            class="shrink-0 px-2.5 h-[42px] bg-gray-100 hover:bg-red-100 hover:text-red-600 text-gray-500 rounded-xl text-xs font-medium transition flex items-center gap-1"
          >✕ Limpiar</button>
        </div>
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1">
          Tasa {{ tipo === 'venta' ? 'de venta' : 'de compra' }} ({{ parStr }}) *
        </label>
        <div class="flex gap-2 items-start">
          <input
            :value="fmtTasa(tasa)"
            @input="onTasaInput"
            type="text" inputmode="decimal" required
            :placeholder="tasaSugerida ? formatTasa(tasaSugerida) : '36.50'"
            class="flex-1 px-4 py-2.5 border rounded-xl focus:ring-2 outline-none"
            :class="desfavorable ? 'border-amber-400 focus:ring-amber-400' : 'border-gray-300 focus:ring-blue-500'"
          />
          <button
            v-if="tasa !== '' && tasa != null"
            type="button"
            @click="limpiarTasa"
            class="shrink-0 px-2.5 h-[42px] bg-gray-100 hover:bg-red-100 hover:text-red-600 text-gray-500 rounded-xl text-xs font-medium transition flex items-center gap-1"
          >✕ Limpiar</button>
        </div>
        <p v-if="tasaSugerida" class="text-xs text-gray-400 mt-1">
          Referencia ({{ fuenteLabel }}): <span class="font-medium text-gray-600">{{ formatTasa(tasaSugerida) }}</span>
        </p>
        <p v-else class="text-xs text-amber-500 mt-1">No hay tasa de referencia disponible.</p>
      </div>
    </div>

    <div v-if="desfavorable" class="bg-amber-50 border border-amber-200 text-amber-700 text-sm p-3 rounded-lg">
      ⚠️ La tasa es desfavorable para la casa.
    </div>

    <div>
      <label class="block text-sm text-gray-600 mb-1">{{ quoteNombre }} ({{ quoteCodigo }}) a recibir</label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">{{ quoteSimbolo }}</span>
        <input
          :value="fmt(bolivares)"
          @input="onBolivaresInput"
          type="text" inputmode="decimal" placeholder="0.00"
          class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"
        />
      </div>
      <p class="text-xs text-gray-400 mt-1">
        Editá dos valores y el tercero se calcula automáticamente.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

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
})

const emit = defineEmits(['update:monto', 'update:bolivares', 'update:tasa'])

const fuenteLabel = computed(() => {
  const map = { USD: 'BCV', EUR: 'BCV', USDT: 'Binance', COP: 'BCV' }
  return map[props.moneda] || props.moneda || 'BCV'
})

function parse(v) { return parseFloat(String(v).replace(/,/g, '')) || 0 }

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

function limpiarMonto() {
  emit('update:monto', '')
}

function limpiarTasa() {
  emit('update:tasa', '')
  emit('update:bolivares', '')
  emit('update:monto', '')
}

/**
 * Tres campos, dos de entrada + uno calculado:
 *   bolivares = monto × tasa
 *   monto     = bolivares / tasa
 *   tasa      = bolivares / monto
 *
 * Al editar cualquiera, se calcula el tercero usando los otros dos.
 */

function onMontoInput(e) {
  const val = String(e.target.value).replace(/,/g, '')
  emit('update:monto', val)
  const m = parse(val)
  const t = parse(props.tasa)
  const b = parse(props.bolivares)

  if (m > 0 && t > 0) {
    emit('update:bolivares', round2(m * t))
  } else if (m > 0 && b > 0 && t === 0) {
    emit('update:tasa', round2(b / m))
  }
}

function onTasaInput(e) {
  const val = String(e.target.value).replace(/,/g, '')
  emit('update:tasa', val)
  const t = parse(val)
  const m = parse(props.monto)
  const b = parse(props.bolivares)

  if (m > 0 && t > 0) {
    emit('update:bolivares', round2(m * t))
  } else if (b > 0 && t > 0 && m === 0) {
    emit('update:monto', round2(b / t))
  }
}

function onBolivaresInput(e) {
  let val = String(e.target.value).replace(/,/g, '')
  const b = parse(val)
  const m = parse(props.monto)
  const t = parse(props.tasa)

  if (m > 0 && t > 0) {
    const max = Math.round(m * t * 100) / 100
    if (b > max) {
      val = round2(max)
      emit('update:bolivares', val)
      return
    }
  }

  emit('update:bolivares', val)

  if (b > 0 && m > 0) {
    emit('update:tasa', round2(b / m))
  } else if (b > 0 && t > 0 && m === 0) {
    emit('update:monto', round2(b / t))
  }
}
</script>
