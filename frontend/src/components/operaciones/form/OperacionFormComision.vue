<template>
  <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
    <div class="flex items-center gap-3">
      <input
        :checked="activa"
        type="checkbox"
        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
        @change="$emit('update:activa', $event.target.checked)"
      />
      <h3 class="font-semibold text-gray-700">Comisión</h3>
    </div>

    <template v-if="activa">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm text-gray-600 mb-1">Tipo de comisión</label>
          <select
            :value="tipo"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white"
            @change="$emit('update:tipo', $event.target.value)"
          >
            <option value="pago_movil">Pago móvil</option>
            <option value="transferencia">Transferencia</option>
            <option value="efectivo">Efectivo</option>
            <option value="otro">Otro</option>
          </select>
        </div>
        <div>
          <label class="block text-sm text-gray-600 mb-1">Monto {{ simbolo }}</label>
          <input
            :value="monto"
            type="number"
            step="0.01"
            min="0"
            placeholder="0.00"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"
            @input="$emit('update:monto', $event.target.value)"
          />
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
defineProps({
  activa: Boolean,
  tipo: String,
  monto: [String, Number],
  simbolo: { type: String, default: 'Bs.' },
})

defineEmits(['update:activa', 'update:tipo', 'update:monto'])
</script>
