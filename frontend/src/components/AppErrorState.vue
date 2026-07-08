<template>
  <div class="bg-red-50 text-red-600 p-4 rounded-xl text-sm">
    {{ message }}
    <button v-if="retry" @click="$emit('retry')" class="underline ml-2">Reintentar</button>
  </div>
</template>

<script setup>
/**
 * Componente de estado de error.
 * Muestra un mensaje de error y un botón opcional para reintentar.
 *
 * @component
 * @prop {string} message - Mensaje de error a mostrar
 * @prop {boolean} retry - Si debe mostrarse el botón de reintentar
 * @emit {void} retry - Evento emitido al hacer clic en "Reintentar"
 */
defineProps({
  /** @type {string} - Mensaje de error a mostrar */
  message: { type: String, default: 'Ocurrió un error.' },
  /** @type {boolean} - Si debe mostrarse el botón de reintentar */
  retry: { type: Boolean, default: true },
})
defineEmits(['retry'])
</script>
