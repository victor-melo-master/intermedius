<template>
  <div v-if="items.length" class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
    <h3 class="font-semibold text-gray-700">Resumen</h3>
    <div
      v-for="item in items"
      :key="item.label"
      class="flex items-center justify-between text-sm"
    >
      <span class="text-gray-500">{{ item.label }}</span>
      <span class="font-medium text-gray-800">{{ item.value }}</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  items: { type: Array, default: () => [] },
})
</script>
