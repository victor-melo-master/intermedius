<template>
  <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-semibold text-gray-700">Monto y tasa</h3>
      <div class="flex gap-1 bg-gray-100 rounded-lg p-0.5">
        <button
          v-for="m in modos"
          :key="m.value"
          type="button"
          @click="modoActual = m.value"
          class="px-2.5 py-1 text-xs rounded-md transition"
          :class="modoActual === m.value ? 'bg-white text-blue-700 shadow-sm font-medium' : 'text-gray-500 hover:text-gray-700'"
        >
          {{ m.label }}
        </button>
      </div>
    </div>

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
          <template v-if="modoActual === 'calcular_tasa'">Tasa calculada</template>
          <template v-else>Tasa {{ tipo === 'venta' ? 'de venta' : 'de compra' }} ({{ parStr }}) *</template>
        </label>
        <div class="flex gap-2 items-start">
          <input
            :value="fmt(tasa)"
            @input="onTasaInput"
            type="text" inputmode="decimal" required
            :readonly="modoActual === 'calcular_tasa'"
            :placeholder="tasaSugerida && modoActual !== 'calcular_tasa' ? formatTasa(tasaSugerida) : '36.50'"
            class="flex-1 px-4 py-2.5 border rounded-xl focus:ring-2 outline-none"
            :class="[
              modoActual === 'calcular_tasa'
                ? 'bg-blue-50 border-blue-300 font-bold text-blue-700 cursor-default'
                : desfavorable
                  ? 'border-amber-400 focus:ring-amber-400'
                  : 'border-gray-300 focus:ring-blue-500'
            ]"
          />
          <button
            v-if="tasa !== '' && tasa != null && modoActual !== 'calcular_tasa'"
            type="button"
            @click="limpiarTasa"
            class="shrink-0 px-2.5 h-[42px] bg-gray-100 hover:bg-red-100 hover:text-red-600 text-gray-500 rounded-xl text-xs font-medium transition flex items-center gap-1"
          >✕ Limpiar</button>
        </div>
        <p v-if="modoActual === 'calcular_tasa'" class="text-xs text-blue-500 mt-1">Calculado automáticamente</p>
        <p v-else-if="tasaSugerida" class="text-xs text-gray-400 mt-1">Sugerida del día: <span class="font-medium text-gray-600">{{ fmt(tasaSugerida) }}</span></p>
        <p v-else class="text-xs text-amber-500 mt-1">No hay tasa {{ parStr }} publicada hoy.</p>
      </div>
    </div>

    <div v-if="desfavorable && modoActual !== 'calcular_tasa'" class="bg-amber-50 border border-amber-200 text-amber-700 text-sm p-3 rounded-lg">
      ⚠️ La tasa es desfavorable para la casa.
    </div>

    <div>
      <label class="block text-sm text-gray-600 mb-1">
        <template v-if="modoActual === 'calcular_tasa'">{{ quoteNombre }} ({{ quoteCodigo }})</template>
        <template v-else>{{ quoteNombre }} ({{ quoteCodigo }}) a recibir</template>
      </label>
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
        <template v-if="modoActual === 'calcular_tasa'">Ingresa ambos montos para calcular la tasa.</template>
        <template v-else>Edita el monto o los {{ quoteNombre.toLowerCase() }}; la tasa es el valor de referencia.</template>
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

const modos = [
  { value: 'divisa_ves', label: 'Divisa ↔ VES' },
  { value: 'calcular_tasa', label: 'Calcular Tasa' },
]

const modoActual = ref('divisa_ves')

function fmt(v) {
  if (v === '' || v == null) return ''
  const s = String(v).replace(/,/g, '')
  const n = parseFloat(s)
  if (isNaN(n)) return s
  const parts = s.split('.')
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  return parts.join('.')
}

function raw(v) { return String(v).replace(/,/g, '') }

function formatTasa(val) {
  const n = parseFloat(val)
  if (isNaN(n)) return ''
  return n.toFixed(2)
}

function round2(n) {
  return (Math.round((n + Number.EPSILON) * 100) / 100).toString()
}

function parse(v) { return parseFloat(String(v).replace(/,/g, '')) || 0 }

function limpiarMonto() {
  emit('update:monto', '')
  const t = parse(props.tasa)
  if (t > 0) emit('update:bolivares', '')
}

function limpiarTasa() {
  if (modoActual.value === 'calcular_tasa') return
  emit('update:tasa', '')
  emit('update:bolivares', '')
  emit('update:monto', '')
}

function onMontoInput(e) {
  const val = raw(e.target.value)
  const m = parse(val)
  const t = parse(props.tasa)
  emit('update:monto', val)
  if (modoActual.value === 'divisa_ves' && m > 0 && t > 0) {
    emit('update:bolivares', round2(m * t))
  } else if (modoActual.value === 'calcular_tasa' && m > 0) {
    const b = parse(props.bolivares)
    if (b > 0) emit('update:tasa', round2(b / m))
  }
}

function onBolivaresInput(e) {
  const val = raw(e.target.value)
  const b = parse(val)
  const t = parse(props.tasa)
  emit('update:bolivares', val)
  if (modoActual.value === 'divisa_ves' && b > 0 && t > 0) {
    emit('update:monto', round2(b / t))
  } else if (modoActual.value === 'calcular_tasa' && b > 0) {
    const m = parse(props.monto)
    if (m > 0) emit('update:tasa', round2(b / m))
  }
}

function onTasaInput(e) {
  if (modoActual.value === 'calcular_tasa') return
  const val = raw(e.target.value)
  emit('update:tasa', val)
  const t = parse(val)
  const m = parse(props.monto)
  if (m > 0 && t > 0) emit('update:bolivares', round2(m * t))
}
</script>
