// src/components/AppPageHeader.vue
<template>
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <h2 class="text-xl font-bold text-gray-800">{{ title }}</h2>
    <button
      v-if="actionLabel"
      @click="$emit('action')"
      class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center gap-1 self-start sm:self-auto"
    >
      <span>+</span> {{ actionLabel }}
    </button>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  actionLabel: { type: String, default: '' },
})
defineEmits(['action'])
</script>
