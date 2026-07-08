<template>
  <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-2 text-sm">
    <h3 class="font-semibold text-gray-700 mb-2">Resumen</h3>
    <div v-for="item in items" :key="item.label" class="flex justify-between">
      <span class="text-gray-500">{{ item.label }}</span>
      <span class="font-medium">{{ item.value }}</span>
    </div>
  </div>
</template>

<script setup>
/**
 * Componente de resumen de operación.
 * Muestra una lista de pares label/valor antes de confirmar una operación.
 *
 * @component
 * @prop {Array<{label: string, value: string}>} items - Items del resumen
 */
defineProps({
  /** @type {Array<{label: string, value: string}>} - Items del resumen */
  items: { type: Array, required: true },
})
</script>
