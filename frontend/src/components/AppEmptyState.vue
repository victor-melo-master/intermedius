// src/components/AppEmptyState.vue
<template>
  <div class="text-center py-16">
    <span class="text-5xl block mb-4">{{ icon }}</span>
    <p class="text-gray-500">{{ message }}</p>
    <p v-if="subtitle" class="text-sm text-gray-400 mt-1">{{ subtitle }}</p>
  </div>
</template>

<script setup>
defineProps({
  icon: { type: String, default: '📄' },
  message: { type: String, default: 'No hay datos' },
  subtitle: { type: String, default: '' },
})
</script>
