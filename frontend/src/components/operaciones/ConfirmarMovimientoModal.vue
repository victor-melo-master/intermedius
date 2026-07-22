<template>
  <Teleport to="body">
    <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-black/40" @click="$emit('cancel')"></div>
      <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
        <h3 class="text-lg font-bold text-gray-800">Confirmar movimiento</h3>

        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-500">Origen</span>
            <span class="font-medium text-gray-700">{{ transaccion?.cuenta_origen?.alias || '—' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Destino</span>
            <span class="font-medium text-gray-700">{{ transaccion?.cuenta_destino?.alias || '—' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Monto</span>
            <span class="font-semibold text-gray-800">{{ Number(transaccion?.monto || 0).toFixed(2) }} {{ transaccion?.moneda?.codigo || '' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Método de pago</span>
            <span class="text-gray-700">{{ transaccion?.metodo_pago || '—' }}</span>
          </div>
        </div>

        <div v-if="transaccion?.metodo_pago && transaccion?.metodo_pago !== 'efectivo'" class="space-y-2">
          <label class="block text-sm text-gray-600 font-medium">Comprobante <span class="text-red-400">*</span></label>
          <input v-model="comprobante" required placeholder="N° de referencia, voucher..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
        </div>

        <div v-if="error" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">{{ error }}</div>

        <div class="flex gap-3 pt-2">
          <button @click="$emit('cancel')"
            class="flex-1 py-2.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
            Cancelar
          </button>
          <button @click="confirmar" :disabled="confirmando || !comprobanteValido"
            class="flex-1 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 disabled:bg-blue-300 transition flex items-center justify-center gap-2">
            <span v-if="confirmando" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ confirmando ? 'Confirmando...' : 'Confirmar movimiento' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useMovimientos } from '@/composables/useMovimientos'
import { useNotification } from '@/composables/useNotification'

const props = defineProps({
  visible: { type: Boolean, default: false },
  transaccion: { type: Object, default: null },
  operacionId: { type: [String, Number], required: true },
})

const emit = defineEmits(['confirmado', 'cancel'])

const txService = useMovimientos()
const notifier = useNotification()

const comprobante = ref('')
const confirmando = ref(false)
const error = ref('')

const comprobanteValido = computed(() => {
  if (!props.transaccion?.metodo_pago || props.transaccion?.metodo_pago === 'efectivo') return true
  return comprobante.value.trim().length > 0
})

async function confirmar() {
  error.value = ''
  confirmando.value = true
  try {
    await txService.confirmar(props.operacionId, props.transaccion.id)
    notifier.success('Movimiento confirmado exitosamente')
    emit('confirmado')
  } catch (err) {
    error.value = err.response?.data?.message || err.message
  }
  confirmando.value = false
}
</script>
