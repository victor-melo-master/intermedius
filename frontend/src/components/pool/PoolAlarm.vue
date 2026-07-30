<template>
  <Teleport to="body">
    <div
      v-if="showAlarm"
      class="fixed inset-0 z-50 flex items-center justify-center"
    >
      <div class="absolute inset-0 bg-black bg-opacity-50"></div>
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 mx-4 animate-bounce">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
            <Iconoir name="bell" class="w-6 h-6 text-red-500" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-red-600">¡SLA Excedido!</h3>
            <p class="text-sm text-gray-600">
              Operación #{{ alarmData?.operacion_id }} lleva
              <strong>{{ alarmData?.minutos_espera }} minutos</strong> en espera.
            </p>
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <button
            @click="dismissAlarm"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded"
          >
            Cerrar
          </button>
          <button
            @click="dismissAlarm"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded"
          >
            Tomar operación
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import Iconoir from '../common/Iconoir.vue'

const showAlarm = ref(false)
const alarmData = ref(null)
let audioContext = null

const playSound = () => {
  try {
    if (!audioContext) {
      audioContext = new (window.AudioContext || window.webkitAudioContext)()
    }
    const oscillator = audioContext.createOscillator()
    const gain = audioContext.createGain()
    oscillator.connect(gain)
    gain.connect(audioContext.destination)
    oscillator.frequency.value = 800
    oscillator.type = 'square'
    gain.gain.value = 0.3
    oscillator.start()
    setTimeout(() => oscillator.stop(), 500)
  } catch (e) {
    console.warn('No se pudo reproducir sonido:', e)
  }
}

const handleSlaExcedida = (event) => {
  alarmData.value = event.detail
  showAlarm.value = true
  playSound()
  if ('Notification' in window && Notification.permission === 'granted') {
    new Notification('SLA Excedido', {
      body: `Operación #${event.detail.operacion_id} lleva ${event.detail.minutos_espera} minutos en espera.`,
      icon: '/favicon.ico',
    })
  }
}

const dismissAlarm = () => {
  showAlarm.value = false
  alarmData.value = null
}

onMounted(() => {
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission()
  }
  window.addEventListener('sla-excedida', handleSlaExcedida)
})

onUnmounted(() => {
  window.removeEventListener('sla-excedida', handleSlaExcedida)
})
</script>
