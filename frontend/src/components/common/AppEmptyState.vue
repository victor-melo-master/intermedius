// src/components/AppEmptyState.vue
<template>
  <div class="text-center py-16">
    <Iconoir :name="icon" class="text-5xl block mb-4" />
    <p class="text-gray-500">{{ message }}</p>
    <p v-if="subtitle" class="text-sm text-gray-400 mt-1">{{ subtitle }}</p>
  </div>
</template>

<script setup>
/**
 * Componente de estado vacío.
 * Muestra un icono y un mensaje cuando no hay datos que listar.
 *
 * @component
 * @prop {string} icon - Icono a mostrar (emoji)
 * @prop {string} message - Mensaje descriptivo
 * @prop {string} subtitle - Subtítulo opcional
 */
import Iconoir from './Iconoir.vue'

defineProps({
  /** @type {string} - Icono a mostrar (emoji) */
  icon: { type: String, default: 'document-text' },
  /** @type {string} - Mensaje descriptivo */
  message: { type: String, default: 'No hay datos' },
  /** @type {string} - Subtítulo opcional */
  subtitle: { type: String, default: '' },
})
</script>
