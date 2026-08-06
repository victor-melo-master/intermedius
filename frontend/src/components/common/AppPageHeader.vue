// src/components/AppPageHeader.vue
<template>
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <h2 class="text-xl font-bold text-heading">{{ title }}</h2>
    <button
      v-if="actionLabel"
      @click="$emit('action')"
      class="px-4 py-2 bg-gold text-navy rounded-lg text-sm font-medium hover:bg-gold-dark flex items-center gap-1 self-start sm:self-auto"
    >
      <span>+</span> {{ actionLabel }}
    </button>
  </div>
</template>

<script setup>
/**
 * Componente de encabezado de página.
 * Muestra un título y un botón de acción lateral.
 *
 * @component
 * @prop {string} title - Título de la página
 * @prop {string} actionLabel - Etiqueta del botón de acción (vacio = oculto)
 * @emit {void} action - Evento emitido al hacer clic en el botón de acción
 */
defineProps({
  /** @type {string} - Título de la página */
  title: { type: String, required: true },
  /** @type {string} - Etiqueta del botón de acción */
  actionLabel: { type: String, default: '' },
})
defineEmits(['action'])
</script>
