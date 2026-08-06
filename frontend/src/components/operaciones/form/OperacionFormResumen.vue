<template>
  <div v-if="items.length" class="bg-white border border-edge rounded-xl p-5 space-y-3">
    <h3 class="font-semibold text-ink">Resumen</h3>
    <div
      v-for="item in items"
      :key="item.label"
      class="flex items-center justify-between text-sm"
    >
      <span class="text-ink-soft">{{ item.label }}</span>
      <span class="font-medium text-heading">{{ item.value }}</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  items: { type: Array, default: () => [] },
})
</script>
