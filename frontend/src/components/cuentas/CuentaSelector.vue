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
/**
 * Componente selector de cuentas bancarias.
 * Muestra un filtro por banco y un select de cuentas con saldo.
 *
 * @component
 * @prop {string|number} modelValue - ID de la cuenta seleccionada
 * @prop {string} label - Etiqueta del campo
 * @prop {string} placeholder - Texto placeholder del select
 * @prop {Array<Object>} cuentas - Lista de cuentas disponibles
 * @prop {string} emptyMessage - Mensaje cuando no hay cuentas
 * @prop {Function} cuentaLabel - Función para formatear la etiqueta de cada cuenta
 * @prop {Array<Object>} bancos - Lista de bancos para el filtro
 * @emit {string|number} update:modelValue - Actualiza la cuenta seleccionada
 */
import { ref, computed } from 'vue'

const props = defineProps({
  /** @type {string|number} - ID de la cuenta seleccionada */
  modelValue: [String, Number],
  /** @type {string} - Etiqueta del campo */
  label: String,
  /** @type {string} - Texto placeholder */
  placeholder: String,
  /** @type {Array<Object>} - Lista de cuentas */
  cuentas: Array,
  /** @type {string} - Mensaje cuando no hay cuentas */
  emptyMessage: String,
  /** @type {Function} - Función para formatear etiqueta de cuenta */
  cuentaLabel: Function,
  /** @type {Array<Object>} - Lista de bancos para filtro */
  bancos: { type: Array, default: () => [] },
})

defineEmits(['update:modelValue'])

/** @type {import('vue').Ref<string>} - ID del banco seleccionado en el filtro */
const bancoFiltro = ref('')

/** @type {import('vue').ComputedRef<Array<Object>>} - Cuentas filtradas por banco */
const cuentasFiltradas = computed(() => {
  if (!bancoFiltro.value) return props.cuentas
  return props.cuentas.filter(c => c.banco_id == bancoFiltro.value)
})

/**
 * Formatea un saldo numérico con 2 decimales.
 * @param {string|number|null} saldo - Saldo a formatear
 * @returns {string} Saldo formateado o 'N/D' si es nulo
 */
function formatSaldo(saldo) {
  if (saldo === null || saldo === undefined) return 'N/D'
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(saldo))
}
</script>
