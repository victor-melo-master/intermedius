<template>
  <div>
    <label class="block text-sm text-gray-600 mb-1">{{ label }} *</label>

    <!-- Filtro por banco (opcional) -->
    <select v-if="bancos && bancos.length" v-model="bancoFiltro" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white mb-2 text-sm">
      <option value="">Todos los bancos</option>
      <option v-for="b in bancos" :key="b.id" :value="b.id">{{ b.nombre }}</option>
    </select>

    <select :value="modelValue" @change="$emit('update:modelValue', $event.target.value)" required
      class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
      <option value="">{{ placeholder }}</option>
      <option v-for="c in cuentasFiltradas" :key="c.id" :value="c.id">
        {{ cuentaLabel(c) }} — Saldo: {{ formatSaldo(c.saldo_cache) }}
      </option>
    </select>
    <p v-if="cuentasFiltradas.length === 0" class="text-xs text-amber-500 mt-1">{{ emptyMessage }}</p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  modelValue: [String, Number],
  label: String,
  placeholder: String,
  cuentas: Array,
  emptyMessage: String,
  cuentaLabel: Function,
  bancos: { type: Array, default: () => [] },
})

defineEmits(['update:modelValue'])

const bancoFiltro = ref('')

const cuentasFiltradas = computed(() => {
  if (!bancoFiltro.value) return props.cuentas
  return props.cuentas.filter(c => c.banco_id == bancoFiltro.value)
})

function formatSaldo(saldo) {
  if (saldo === null || saldo === undefined) return 'N/D'
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(saldo))
}
</script>
