<template>
  <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
    <h3 class="font-semibold text-gray-700">Monto y tasa</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm text-gray-600 mb-1">Monto {{ moneda }} *</label>
        <input :value="monto" @input="$emit('update:monto', $event.target.value)" type="number" step="0.01" required placeholder="100.00"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1">Tasa {{ tipo === 'venta' ? 'de venta' : 'de compra' }} ({{ parStr }}) *</label>
        <input :value="tasa" @input="$emit('update:tasa', $event.target.value)" type="number" step="0.01" required :placeholder="tasaSugerida ? formatTasa(tasaSugerida) : '36.50'"
          class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 outline-none"
          :class="desfavorable ? 'border-amber-400 focus:ring-amber-400' : 'border-gray-300 focus:ring-blue-500'" />
        <p v-if="tasaSugerida" class="text-xs text-gray-400 mt-1">Sugerida del día: <span class="font-medium text-gray-600">{{ formatTasa(tasaSugerida) }}</span></p>
        <p v-else class="text-xs text-amber-500 mt-1">No hay tasa {{ parStr }} publicada hoy.</p>
      </div>
    </div>
    <div v-if="desfavorable" class="bg-amber-50 border border-amber-200 text-amber-700 text-sm p-3 rounded-lg">
      ⚠️ La tasa es desfavorable para la casa.
    </div>
    <div>
      <label class="block text-sm text-gray-600 mb-1">{{ quoteNombre }} ({{ quoteCodigo }}) {{ tipo === 'venta' ? 'a recibir' : 'a pagar' }}</label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">{{ quoteSimbolo }}</span>
        <input :value="bolivares" @input="$emit('update:bolivares', $event.target.value)" type="number" step="0.01" placeholder="0.00"
          class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
      </div>
      <p class="text-xs text-gray-400 mt-1">Edita el monto o los {{ quoteNombre.toLowerCase() }}; la tasa es el valor de referencia.</p>
    </div>
  </div>
</template>

<script setup>
function formatTasa(val) {
  const n = parseFloat(val)
  if (isNaN(n)) return ''
  return n.toFixed(2)
}

defineProps({
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
defineEmits(['update:monto', 'update:bolivares', 'update:tasa'])
</script>
