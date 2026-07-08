<template>
  <div>
    <label class="block text-sm text-gray-600 mb-1">{{ label }} *</label>
    <select :value="modelValue" @change="$emit('update:modelValue', $event.target.value)" required
      class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
      <option value="">{{ placeholder }}</option>
      <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ cuentaLabel(c) }}</option>
    </select>
    <p v-if="cuentas.length === 0" class="text-xs text-amber-500 mt-1">{{ emptyMessage }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: [String, Number],
  label: String,
  placeholder: String,
  cuentas: Array,
  emptyMessage: String,
  cuentaLabel: Function,
})
defineEmits(['update:modelValue'])
</script>
