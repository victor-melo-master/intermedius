<template>
  <div class="flex items-center justify-center gap-1 py-4">
    <div v-for="(paso, i) in pasos" :key="paso.key"
      class="flex items-center">
      <div class="flex flex-col items-center">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition"
          :class="estadoClase(paso.key)">
          <Iconoir v-if="estadoIndex > i && paso.key !== 'cancelada' && paso.key !== 'revertida'" name="check" class="w-4 h-4 text-success" />
          <Iconoir v-else-if="paso.key === 'cancelada'" name="x-mark" class="w-4 h-4 text-danger" />
          <Iconoir v-else-if="paso.key === 'revertida'" name="arrow-uturn-left" class="w-4 h-4" />
          <span v-else>{{ i + 1 }}</span>
        </div>
        <span class="text-xs mt-1 font-medium"
          :class="estadoIndex >= i ? 'text-ink' : 'text-ink-muted'">
          {{ paso.label }}
        </span>
      </div>
      <div v-if="i < pasos.length - 1 && pasos[i+1].key !== 'cancelada' && pasos[i+1].key !== 'revertida'"
        class="w-12 sm:w-20 h-0.5 mx-1 rounded transition"
        :class="estadoIndex > i ? 'bg-gold' : 'bg-surface-muted'">
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Iconoir from '../common/Iconoir.vue'

const props = defineProps({
  estado: { type: String, default: 'solicitud' },
  revertida: { type: Boolean, default: false },
})

const pasos = computed(() => {
  if (props.estado === 'cancelada') {
    return [
      { key: 'solicitud', label: 'Solicitud' },
      { key: 'cancelada', label: 'Cancelada' },
    ]
  }

  if (props.revertida || props.estado === 'revertida') {
    return [
      { key: 'solicitud', label: 'Solicitud' },
      { key: 'en_progreso', label: 'En Progreso' },
      { key: 'cerrada', label: 'Cerrada' },
      { key: 'revertida', label: 'Revertida' },
    ]
  }

  return [
    { key: 'solicitud', label: 'Solicitud' },
    { key: 'en_progreso', label: 'En Progreso' },
    { key: 'cerrada', label: 'Cerrada' },
  ]
})

const estadoIndex = computed(() => {
  const idx = pasos.value.findIndex(p => p.key === props.estado)
  return idx >= 0 ? idx : 0
})

function estadoClase(key) {
  const idx = pasos.value.findIndex(p => p.key === key)
  if (key === 'cancelada') return 'bg-danger-soft0 text-white'
  if (key === 'revertida') return 'bg-warning-soft0 text-white'
  if (estadoIndex.value > idx) return 'bg-success-soft0 text-white'
  if (estadoIndex.value === idx) return 'bg-gold text-white ring-2 ring-gold-soft'
  return 'bg-surface-muted text-ink-muted'
}
</script>
