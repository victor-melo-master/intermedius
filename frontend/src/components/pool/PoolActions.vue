<template>
  <div class="flex items-center gap-2 flex-wrap">
    <button
      v-if="operacion.estado === 'en_espera'"
      @click="$emit('tomar')"
      class="px-3 py-1 text-xs bg-gold hover:bg-gold-dark text-white rounded"
    >
      Tomar
    </button>
    <button
      v-if="operacion.estado === 'en_proceso' && puedeSoltar"
      @click="handleSoltar"
      class="px-3 py-1 text-xs bg-warning hover:bg-warning-strong text-white dark:text-navy rounded"
    >
      Soltar
    </button>
    <button
      v-if="operacion.estado === 'en_proceso' && puedePagar"
      @click="handlePagar"
      class="px-3 py-1 text-xs bg-success hover:bg-success-strong text-white dark:text-navy rounded"
    >
      Pagar
    </button>
    <button
      v-if="puedeCancelar"
      @click="handleCancelar"
      class="px-3 py-1 text-xs bg-danger hover:bg-danger-strong text-white dark:text-navy rounded"
    >
      Cancelar
    </button>
    <router-link
      :to="`/operaciones/${operacion.id}`"
      class="px-3 py-1 text-xs bg-surface-muted hover:bg-surface-muted rounded"
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
  return authStore.hasAnyRole(['admin', 'super_admin'])
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
