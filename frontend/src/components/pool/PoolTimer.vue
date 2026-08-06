<template>
  <div class="flex items-center gap-2">
    <span
      class="font-mono text-sm"
      :class="{
        'text-danger font-bold': timeInMinutes >= 5 && estado === 'en_espera',
        'text-warning': timeInMinutes >= 4 && timeInMinutes < 5,
        'text-success': timeInMinutes < 4,
      }"
    >
      {{ formatTime(elapsedSeconds) }}
    </span>
    <span
      v-if="timeInMinutes >= 5 && estado === 'en_espera'"
      class="animate-pulse text-danger text-xs font-bold"
    >
      <Iconoir name="exclamation-triangle" class="w-3.5 h-3.5 inline text-warning" /> SLA
    </span>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import Iconoir from '../common/Iconoir.vue'

const props = defineProps({
  createdAt: { type: String, required: true },
  estado: { type: String, required: true },
  operacionId: { type: Number, required: true },
})

const elapsedSeconds = ref(0)
let interval = null

const timeInMinutes = computed(() => elapsedSeconds.value / 60)

const formatTime = (seconds) => {
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
}

const updateTimer = () => {
  const created = new Date(props.createdAt)
  const now = new Date()
  const diff = (now - created) / 1000
  elapsedSeconds.value = Math.max(0, diff)
}

const handleSlaExcedida = (event) => {
  if (event.detail.operacion_id === props.operacionId) {
    // Podría mostrar un popup o sonido adicional si se quiere
  }
}

onMounted(() => {
  updateTimer()
  interval = setInterval(updateTimer, 1000)
  window.addEventListener('sla-excedida', handleSlaExcedida)
})

onUnmounted(() => {
  clearInterval(interval)
  window.removeEventListener('sla-excedida', handleSlaExcedida)
})
</script>
