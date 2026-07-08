<template>
  <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h3 class="font-semibold text-gray-700">Comisión</h3>
        <p class="text-xs text-gray-400">Comisión bancaria sobre el monto en {{ simbolo }} que sale.</p>
      </div>
      <button type="button" @click="$emit('update:activa', !activa)"
        class="relative w-12 h-6 rounded-full transition shrink-0" :class="activa ? 'bg-blue-600' : 'bg-gray-300'">
        <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
          :class="activa ? 'translate-x-6' : ''"></span>
      </button>
    </div>

    <template v-if="activa">
      <div>
        <label class="block text-sm text-gray-600 mb-1">Tipo de comisión</label>
        <select :value="tipo" @change="$emit('update:tipo', $event.target.value)"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
          <option value="pago_movil">Pago móvil (0.3%)</option>
          <option value="otros_bancos">Transferencia otros bancos (0.3%)</option>
          <option value="mismo_banco">Transferencia mismo banco (0%)</option>
          <option value="manual">Manual (monto libre)</option>
        </select>
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1">Monto de comisión ({{ simbolo }})</label>
        <input :value="monto" @input="$emit('update:monto', $event.target.value)" type="number" step="0.01" placeholder="0.00"
          :disabled="tipo === 'mismo_banco'"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 disabled:text-gray-400" />
        <p v-if="['pago_movil', 'otros_bancos'].includes(tipo)" class="text-xs text-gray-400 mt-1">
          Calculado: 0.3% de {{ simbolo }} {{ montoCalculado }}. Puedes ajustarlo.
        </p>
        <p v-else-if="tipo === 'mismo_banco'" class="text-xs text-gray-400 mt-1">Sin comisión para el mismo banco.</p>
      </div>
    </template>
  </div>
</template>

<script setup>
defineProps({
  activa: Boolean,
  tipo: String,
  monto: [String, Number],
  simbolo: String,
  montoCalculado: [String, Number],
})
defineEmits(['update:activa', 'update:tipo', 'update:monto'])
</script>
