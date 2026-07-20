<template>
  <form @submit.prevent="guardar" class="space-y-4">
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs text-gray-500 mb-1">Cuenta origen</label>
        <select v-model="form.cuenta_origen_id" required
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
          <option value="">Seleccionar</option>
          <option v-for="c in cuentas" :key="c.id" :value="c.id">
            {{ labelCuenta(c) }}
          </option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Cuenta destino</label>
        <select v-model="form.cuenta_destino_id" required
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
          <option value="">Seleccionar</option>
          <option v-for="c in cuentas" :key="c.id" :value="c.id">
            {{ labelCuenta(c) }}
          </option>
        </select>
      </div>
    </div>

    <div>
      <label class="block text-xs text-gray-500 mb-1">Moneda</label>
      <select v-model="form.moneda_id" required
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
        <option value="">Seleccionar</option>
        <option v-for="m in monedas" :key="m.id" :value="m.id">{{ m.codigo }} — {{ m.nombre }}</option>
      </select>
    </div>

    <div>
      <label class="block text-xs text-gray-500 mb-1">Monto</label>
      <input v-model="form.monto" type="number" step="0.01" min="0" required placeholder="0.00"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
    </div>

    <div>
      <label class="block text-xs text-gray-500 mb-1">Tasa aplicada <span class="text-gray-400">(opcional)</span></label>
      <input v-model="form.tasa_aplicada" type="number" step="0.01" min="0" placeholder="Dejar vacío para usar la de la operación"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
    </div>

    <div>
      <label class="block text-xs text-gray-500 mb-1">Método de pago</label>
      <select v-model="form.metodo_pago"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
        <option value="">Seleccionar</option>
        <option value="efectivo">Efectivo</option>
        <option value="pago_movil">Pago móvil</option>
        <option value="transferencia">Transferencia</option>
        <option value="zelle">Zelle</option>
        <option value="binance">Binance</option>
        <option value="otro">Otro</option>
      </select>
    </div>

    <div v-if="form.metodo_pago && form.metodo_pago !== 'efectivo'">
      <label class="block text-xs text-gray-500 mb-1">Comprobante <span class="text-red-400">*</span></label>
      <input v-model="form.comprobante" required
        placeholder="N° de referencia, voucher, hash..."
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
    </div>

    <div v-if="error" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">{{ error }}</div>

    <div class="flex gap-3 pt-2">
      <button type="button" @click="$emit('cancel')"
        class="flex-1 py-2.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
        Cancelar
      </button>
      <button type="submit" :disabled="saving || !valido"
        class="flex-1 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 disabled:bg-blue-300 transition flex items-center justify-center gap-2">
        <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
        {{ saving ? 'Guardando...' : 'Guardar transacción' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useTransacciones } from '@/composables/useTransacciones'
import { useNotification } from '@/composables/useNotification'
import { useCuentas } from '@/composables/useCuentas'
import api from '@/api/axios'

const props = defineProps({
  operacionId: { type: [String, Number], required: true },
})

const emit = defineEmits(['saved', 'cancel'])

const txService = useTransacciones()
const cuentasComposable = useCuentas()
const notifier = useNotification()

const cuentas = ref([])
const monedas = ref([])
const saving = ref(false)
const error = ref('')

const form = reactive({
  cuenta_origen_id: '',
  cuenta_destino_id: '',
  moneda_id: '',
  monto: '',
  tasa_aplicada: '',
  metodo_pago: '',
  comprobante: '',
})

const valido = computed(() =>
  form.cuenta_origen_id && form.cuenta_destino_id && form.moneda_id && parseFloat(form.monto) > 0
)

function labelCuenta(c) {
  const tipo = c.banco?.nombre || c.tipo || 'cuenta'
  return `${c.alias} · ${tipo} (${c.moneda?.codigo})`
}

async function guardar() {
  error.value = ''
  saving.value = true
  try {
    const payload = {
      cuenta_origen_id: Number(form.cuenta_origen_id),
      cuenta_destino_id: Number(form.cuenta_destino_id),
      moneda_id: Number(form.moneda_id),
      monto: parseFloat(form.monto),
    }
    if (form.tasa_aplicada) payload.tasa_aplicada = parseFloat(form.tasa_aplicada)
    if (form.metodo_pago) payload.metodo_pago = form.metodo_pago
    if (form.comprobante) payload.comprobante = form.comprobante

    await txService.agregar(props.operacionId, payload)
    notifier.success('Transacción agregada')
    emit('saved')
  } catch (err) {
    error.value = err.response?.data?.message || err.message
  }
  saving.value = false
}

onMounted(async () => {
  await cuentasComposable.fetchAll()
  cuentas.value = cuentasComposable.cuentas.value || []
  try {
    const { data } = await api.get('/monedas')
    monedas.value = Array.isArray(data) ? data : (data.data || [])
  } catch { monedas.value = [] }
})
</script>
