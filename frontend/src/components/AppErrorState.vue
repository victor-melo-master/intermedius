<template>
  <div class="bg-red-50 text-red-600 p-4 rounded-xl text-sm">
    {{ message }}
    <button v-if="retry" @click="$emit('retry')" class="underline ml-2">Reintentar</button>
  </div>
</template>

<script setup>
defineProps({
  message: { type: String, default: 'Ocurrió un error.' },
  retry: { type: Boolean, default: true },
})
defineEmits(['retry'])
</script>
