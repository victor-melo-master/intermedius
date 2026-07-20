<template>
  <div class="flex items-center justify-center gap-1 py-4">
    <div v-for="(paso, i) in pasos" :key="paso.key"
      class="flex items-center">
      <div class="flex flex-col items-center">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition"
          :class="estadoClase(paso.key)">
          <span v-if="estadoIndex > i">✓</span>
          <span v-else>{{ i + 1 }}</span>
        </div>
        <span class="text-[10px] mt-1 font-medium"
          :class="estadoIndex >= i ? 'text-gray-700' : 'text-gray-400'">
          {{ paso.label }}
        </span>
      </div>
      <div v-if="i < pasos.length - 1" class="w-12 sm:w-20 h-0.5 mx-1 rounded transition"
        :class="estadoIndex > i ? 'bg-blue-500' : 'bg-gray-200'">
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  estado: { type: String, default: 'solicitud' },
})

const pasos = [
  { key: 'solicitud', label: 'Solicitud' },
  { key: 'en_progreso', label: 'En Progreso' },
  { key: 'cerrada', label: 'Cerrada' },
]

const estadoIndex = computed(() => {
  const idx = pasos.findIndex(p => p.key === props.estado)
  return idx >= 0 ? idx : 0
})

function estadoClase(key) {
  const idx = pasos.findIndex(p => p.key === key)
  if (estadoIndex.value > idx) return 'bg-green-500 text-white'
  if (estadoIndex.value === idx) return 'bg-blue-600 text-white ring-2 ring-blue-200'
  return 'bg-gray-200 text-gray-500'
}
</script>
