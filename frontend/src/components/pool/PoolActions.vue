<template>
  <div class="flex items-center gap-2 flex-wrap">
    <button
      v-if="operacion.estado === 'en_espera'"
      @click="$emit('tomar')"
      class="px-3 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded"
    >
      Tomar
    </button>
    <button
      v-if="operacion.estado === 'en_proceso' && puedeSoltar"
      @click="handleSoltar"
      class="px-3 py-1 text-xs bg-yellow-600 hover:bg-yellow-700 text-white rounded"
    >
      Soltar
    </button>
    <button
      v-if="operacion.estado === 'en_proceso' && puedePagar"
      @click="handlePagar"
      class="px-3 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded"
    >
      Pagar
    </button>
    <button
      v-if="puedeCancelar"
      @click="handleCancelar"
      class="px-3 py-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded"
    >
      Cancelar
    </button>
    <router-link
      :to="`/operaciones/${operacion.id}`"
      class="px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded"
    >
      Ver
    </router-link>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  operacion: { type: Object, required: true },
})

const emit = defineEmits(['tomar', 'soltar', 'pagar', 'cancelar'])

const authStore = useAuthStore()
const user = computed(() => authStore.user)

const puedeSoltar = computed(() => {
  return props.operacion.pagador_id === user.value?.id
})

const puedePagar = computed(() => {
  return props.operacion.pagador_id === user.value?.id
})

const puedeCancelar = computed(() => {
  return user.value?.roles?.some(r => ['admin', 'super_admin'].includes(r.name))
})

const handleSoltar = () => {
  if (confirm('¿Seguro que quieres soltar esta operación?')) {
    emit('soltar')
  }
}

const handlePagar = () => {
  if (confirm('¿Confirmas que todas las transacciones están pagadas?')) {
    emit('pagar')
  }
}

const handleCancelar = () => {
  const motivo = prompt('Motivo de cancelación:')
  if (motivo !== null && motivo.trim() !== '') {
    emit('cancelar', motivo)
  } else if (motivo !== null) {
    alert('Debes ingresar un motivo para cancelar.')
  }
}
</script>
