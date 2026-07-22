<template>
  <div>
    <div v-if="!transacciones.length" class="text-center py-8">
      <p class="text-gray-400 text-sm">No hay transacciones registradas</p>
    </div>
    <div v-else class="space-y-2">
      <div v-for="tx in transacciones" :key="tx.id"
        class="border border-gray-200 rounded-xl p-4 space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs text-gray-400">#{{ tx.orden }}</span>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-medium" :class="estadoBadge(tx).clase">
            {{ estadoBadge(tx).label }}
          </span>
        </div>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div>
            <p class="text-xs text-gray-400">Origen</p>
            <p class="font-medium text-gray-700">{{ tx.cuenta_origen?.alias || `Cuenta #${tx.cuenta_origen_id}` }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-400">Destino</p>
            <p class="font-medium text-gray-700">{{ tx.cuenta_destino?.alias || `Cuenta #${tx.cuenta_destino_id}` }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-400">Monto</p>
            <p class="font-semibold text-gray-800">{{ Number(tx.monto).toFixed(2) }} {{ tx.moneda?.codigo || '' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-400">Método de pago</p>
            <p class="text-gray-600">{{ tx.metodo_pago || '—' }}</p>
          </div>
        </div>
        <div v-if="tx.comprobante" class="text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-1">
          Comprobante: {{ tx.comprobante }}
        </div>
        <div v-if="tx.motivo_rechazo" class="text-xs text-red-500 bg-red-50 rounded-lg px-3 py-1">
          Motivo: {{ tx.motivo_rechazo }}
        </div>
        <div v-if="!['solicitud', 'cerrada', 'cancelada'].includes(estado)" class="flex gap-2 pt-1">
          <button v-if="tx.estado === 'pendiente'"
            @click="editarTx(tx)"
            class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
            Editar
          </button>
          <button v-if="tx.estado === 'pendiente'"
            @click="confirmarTx(tx)"
            class="text-xs px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition">
            Confirmar
          </button>
          <button v-if="tx.estado === 'pendiente'"
            @click="eliminarTx(tx)"
            class="text-xs px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition">
            Eliminar
          </button>
          <button v-if="tx.estado === 'confirmada'"
            @click="mostrarRevertir = tx.id"
            class="text-xs px-3 py-1.5 bg-orange-50 hover:bg-orange-100 text-orange-600 rounded-lg transition">
            Revertir
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Revertir -->
    <Teleport to="body">
      <AppFormModal v-model="mostrarRevertirSeleccionado" title="Revertir transacción">
        <form @submit.prevent="revertirTx" class="space-y-4">
          <p class="text-sm text-gray-500">¿Estás seguro de revertir esta transacción? Se ajustará el saldo de las cuentas.</p>
          <textarea v-model="motivoRevertir" rows="3" required
            placeholder="Motivo de la reversión..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none resize-none"></textarea>
          <div class="flex gap-3">
            <button type="button" @click="mostrarRevertir = null"
              class="flex-1 py-2.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">Volver</button>
            <button type="submit" :disabled="!motivoRevertir.trim() || revertiendo"
              class="flex-1 py-2.5 bg-orange-600 text-white text-sm font-medium rounded-xl hover:bg-orange-700 disabled:bg-orange-300 transition">
              {{ revertiendo ? 'Revirtiendo...' : 'Revertir transacción' }}
            </button>
          </div>
        </form>
      </AppFormModal>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useTransacciones } from '@/composables/useTransacciones'
import { useNotification } from '@/composables/useNotification'
import AppFormModal from '@/components/common/AppFormModal.vue'

const props = defineProps({
  transacciones: { type: Array, default: () => [] },
  operacionId: { type: [String, Number], required: true },
  estado: { type: String, default: '' },
})

const emit = defineEmits(['refrescar'])

const txService = useTransacciones()
const notifier = useNotification()

const mostrarRevertir = ref(null)
const motivoRevertir = ref('')
const revertiendo = ref(false)
const editandoTx = ref(null)

const mostrarRevertirSeleccionado = computed(() => mostrarRevertir.value !== null)

function estadoBadge(tx) {
  const map = {
    pendiente:  { label: 'Pendiente',  clase: 'bg-yellow-100 text-yellow-700' },
    confirmada: { label: 'Confirmada', clase: 'bg-green-100 text-green-700' },
    revertida:  { label: 'Revertida',  clase: 'bg-orange-100 text-orange-700' },
    cancelada:  { label: 'Cancelada',  clase: 'bg-red-100 text-red-700' },
  }
  return map[tx.estado] || { label: tx.estado, clase: 'bg-gray-100 text-gray-600' }
}

function editarTx(tx) {
  editandoTx.value = tx
}

async function confirmarTx(tx) {
  if (!confirm('¿Confirmar esta transacción?')) return
  try {
    await txService.confirmar(props.operacionId, tx.id)
    notifier.success('Transacción confirmada')
    emit('refrescar')
  } catch {
    notifier.error('Error al confirmar la transacción')
  }
}

async function eliminarTx(tx) {
  if (!confirm('¿Eliminar esta transacción?')) return
  try {
    await txService.eliminar(props.operacionId, tx.id)
    notifier.success('Transacción eliminada')
    emit('refrescar')
  } catch {
    notifier.error('Error al eliminar la transacción')
  }
}

async function revertirTx() {
  if (!mostrarRevertir.value || !motivoRevertir.value.trim()) return
  revertiendo.value = true
  try {
    await txService.revertir(props.operacionId, mostrarRevertir.value, motivoRevertir.value.trim())
    notifier.success('Transacción revertida')
    mostrarRevertir.value = null
    motivoRevertir.value = ''
    emit('refrescar')
  } catch {
    notifier.error('Error al revertir la transacción')
  }
  revertiendo.value = false
}
</script>
