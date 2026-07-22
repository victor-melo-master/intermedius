<template>
  <form @submit.prevent="guardar" class="space-y-4">
    <!-- Moneda primero para forzar dirección -->
    <div>
      <label class="block text-xs text-gray-500 mb-1">Moneda</label>
      <select v-model="form.moneda_id" required
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
        <option value="">Seleccionar</option>
        <option v-for="m in monedasFiltradas" :key="m.id" :value="m.id">{{ m.codigo }} — {{ m.nombre }}</option>
      </select>
      <p v-if="monedasFiltradas.length === 1" class="text-xs text-gray-400 mt-1">Moneda fijada por la operación</p>
    </div>

    <div v-if="!form.moneda_id" class="bg-amber-50 border border-amber-200 text-amber-700 text-sm p-3 rounded-lg">
      Selecciona la moneda primero para ver las cuentas disponibles.
    </div>

    <template v-else>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs text-gray-500 mb-1">
            Cuenta origen
            <span class="text-gray-400">({{ labelOrigen }})</span>
          </label>
          <select v-model="form.cuenta_origen_id" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="">Seleccionar</option>
            <option v-for="c in cuentasOrigen" :key="c.id" :value="c.id">
              {{ labelCuenta(c) }}
            </option>
          </select>
          <p v-if="!cuentasOrigen.length" class="text-xs text-red-500 mt-1">No hay cuentas disponibles</p>
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">
            Cuenta destino
            <span class="text-gray-400">({{ labelDestino }})</span>
          </label>
          <select v-model="form.cuenta_destino_id" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="">Seleccionar</option>
            <option v-for="c in cuentasDestino" :key="c.id" :value="c.id">
              {{ labelCuenta(c) }}
            </option>
          </select>
          <p v-if="!cuentasDestino.length" class="text-xs text-red-500 mt-1">No hay cuentas disponibles</p>
        </div>
      </div>

      <div class="bg-blue-50 border border-blue-200 text-blue-700 text-sm p-3 rounded-lg flex items-start gap-2">
        <span class="mt-0.5">ℹ️</span>
        <span>{{ textoFlujo }}</span>
      </div>
    </template>

    <div>
      <label class="block text-xs text-gray-500 mb-1">Monto</label>
      <input v-model="form.monto" type="number" step="0.01" min="0" required placeholder="0.00"
        class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 outline-none"
        :class="excedeLimite ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500'" />
      <p v-if="disponible !== null && form.moneda_id" class="text-xs mt-1"
        :class="excedeLimite ? 'text-red-500 font-medium' : 'text-gray-400'">
        {{ excedeLimite ? 'Excede el límite. ' : '' }}Disponible: {{ monedaSel?.simbolo || '' }}{{ formatMoney(disponible) }} de {{ monedaSel?.simbolo || '' }}{{ formatMoney(limiteMoneda) }}
      </p>
    </div>

    <div>
      <label class="block text-xs text-gray-500 mb-1">Tasa aplicada</label>
      <input v-model="form.tasa_aplicada" type="number" step="0.01" min="0" required
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
    </div>

    <div>
      <label class="block text-xs text-gray-500 mb-1">Método de pago <span class="text-red-400">*</span></label>
      <select v-model="form.metodo_pago" required
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
import { reactive, ref, computed, onMounted, watch } from 'vue'
import { useTransacciones } from '@/composables/useTransacciones'
import { useNotification } from '@/composables/useNotification'
import { useFormatting } from '@/composables/useFormatting'
import api from '@/api/axios'

const props = defineProps({
  operacionId: { type: [String, Number], required: true },
  clienteId: { type: [String, Number, null], default: null },
  clienteNombre: { type: String, default: '' },
  intermediusTitularId: { type: [String, Number, null], default: null },
  monedasPermitidas: { type: Array, default: () => [] },
  esCompra: { type: Boolean, default: true },
  tasaOperacion: { type: [String, Number, null], default: null },
  montoSolicitado: { type: [String, Number, null], default: null },
  transaccionesExistentes: { type: Array, default: () => [] },
})

const emit = defineEmits(['saved', 'cancel'])

const txService = useTransacciones()
const notifier = useNotification()
const { formatMoney } = useFormatting()

const cuentasIntermedius = ref([])
const cuentasCliente = ref([])
const monedas = ref([])
const saving = ref(false)
const error = ref('')

const form = reactive({
  cuenta_origen_id: '',
  cuenta_destino_id: '',
  moneda_id: '',
  monto: '',
  tasa_aplicada: props.tasaOperacion ? parseFloat(props.tasaOperacion).toFixed(2) : '',
  metodo_pago: '',
  comprobante: '',
})

const monedasFiltradas = computed(() => {
  if (!props.monedasPermitidas.length) return monedas.value
  return monedas.value.filter(m => props.monedasPermitidas.includes(m.codigo))
})

const monedaSel = computed(() =>
  monedasFiltradas.value.find(m => m.id == form.moneda_id) || null
)

const esDivisa = computed(() => monedaSel.value?.codigo !== 'VES')

const valido = computed(() =>
  form.cuenta_origen_id && form.cuenta_destino_id && form.moneda_id && parseFloat(form.monto) > 0 && form.metodo_pago && !excedeLimite.value
)

const cuentasOrigen = computed(() => {
  if (!form.moneda_id) return []
  const deIntermedius = cuentasIntermedius.value.filter(c => c.moneda_id == form.moneda_id)
  const delCliente = cuentasCliente.value.filter(c => c.moneda_id == form.moneda_id)
  if (props.esCompra) {
    return esDivisa.value ? delCliente : deIntermedius
  }
  return esDivisa.value ? deIntermedius : delCliente
})

const cuentasDestino = computed(() => {
  if (!form.moneda_id) return []
  const deIntermedius = cuentasIntermedius.value.filter(c => c.moneda_id == form.moneda_id)
  const delCliente = cuentasCliente.value.filter(c => c.moneda_id == form.moneda_id)
  if (props.esCompra) {
    return esDivisa.value ? deIntermedius : delCliente
  }
  return esDivisa.value ? delCliente : deIntermedius
})

const labelOrigen = computed(() => {
  if (!cuentasOrigen.value.length) return ''
  return cuentasOrigen.value[0]?.titular_id ? 'Intermedius' : (props.clienteNombre || 'Cliente')
})

const labelDestino = computed(() => {
  if (!cuentasDestino.value.length) return ''
  return cuentasDestino.value[0]?.titular_id ? 'Intermedius' : (props.clienteNombre || 'Cliente')
})

const textoFlujo = computed(() => {
  if (!monedaSel.value) return ''
  const moneda = monedaSel.value.codigo
  if (props.esCompra) {
    return esDivisa.value
      ? `Compra: ${props.clienteNombre || 'El cliente'} entrega ${moneda} a Intermedius`
      : `Compra: Intermedius entrega ${moneda} al cliente → ${props.clienteNombre || 'el cliente'}`
  }
  return esDivisa.value
    ? `Venta: Intermedius entrega ${moneda} al cliente → ${props.clienteNombre || 'el cliente'}`
    : `Venta: ${props.clienteNombre || 'El cliente'} entrega ${moneda} a Intermedius`
})

const limiteMoneda = computed(() => {
  if (!props.montoSolicitado || !monedaSel.value) return null
  const monto = parseFloat(props.montoSolicitado)
  const tasa = parseFloat(props.tasaOperacion) || 0
  return esDivisa.value ? monto : monto * tasa
})

const totalExistente = computed(() => {
  if (!form.moneda_id || !props.transaccionesExistentes.length) return 0
  return props.transaccionesExistentes
    .filter(t => (t.moneda?.id == form.moneda_id || t.moneda_id == form.moneda_id)
      && ['pendiente', 'confirmada'].includes(t.estado))
    .reduce((sum, t) => sum + Math.abs(parseFloat(t.monto)), 0)
})

const disponible = computed(() => {
  if (limiteMoneda.value === null) return null
  return Math.max(0, limiteMoneda.value - totalExistente.value)
})

const excedeLimite = computed(() => {
  if (disponible.value === null || !form.monto) return false
  return parseFloat(form.monto) > disponible.value
})

function labelCuenta(c) {
  const tipo = c.banco?.nombre || c.tipo || 'cuenta'
  let saldo = ''
  if (c.titular_id && c.saldo_cache != null) {
    saldo = ` · Saldo: ${c.moneda?.simbolo || ''}${parseFloat(c.saldo_cache).toLocaleString('es-VE', { minimumFractionDigits: 2 })}`
  }
  return `${c.alias} · ${tipo}${saldo}`
}

function filtrarPorMoneda(lista) {
  if (!props.monedasPermitidas.length) return lista
  return lista.filter(c => props.monedasPermitidas.includes(c.moneda?.codigo))
}

async function cargarCuentas() {
  const params = []
  if (props.intermediusTitularId) params.push(`titular_id=${props.intermediusTitularId}`)
  if (props.clienteId) params.push(`cliente_id=${props.clienteId}`)

  if (params.length === 0) {
    const { data } = await api.get('/cuentas')
    const all = Array.isArray(data) ? data : (data.data || [])
    cuentasIntermedius.value = filtrarPorMoneda(all.filter(c => c.titular_id))
    cuentasCliente.value = filtrarPorMoneda(all.filter(c => c.cliente_id))
    return
  }

  if (props.intermediusTitularId) {
    try {
      const { data } = await api.get(`/cuentas?titular_id=${props.intermediusTitularId}`)
      cuentasIntermedius.value = filtrarPorMoneda(Array.isArray(data) ? data : (data.data || []))
    } catch { cuentasIntermedius.value = [] }
  }

  if (props.clienteId) {
    try {
      const { data } = await api.get(`/cuentas?cliente_id=${props.clienteId}`)
      cuentasCliente.value = filtrarPorMoneda(Array.isArray(data) ? data : (data.data || []))
    } catch { cuentasCliente.value = [] }
  }
}

async function cargarMonedas() {
  try {
    const { data } = await api.get('/monedas')
    monedas.value = Array.isArray(data) ? data : (data.data || [])
  } catch { monedas.value = [] }
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

watch(() => [props.clienteId, props.intermediusTitularId, props.monedasPermitidas], cargarCuentas)

watch(() => props.tasaOperacion, (val) => {
  if (val && !form.tasa_aplicada) {
    form.tasa_aplicada = parseFloat(val).toFixed(2)
  }
}, { immediate: true })

watch(monedasFiltradas, (list) => {
  if (list.length === 1 && !form.moneda_id) {
    form.moneda_id = list[0].id
  }
})

watch(() => form.moneda_id, () => {
  form.cuenta_origen_id = ''
  form.cuenta_destino_id = ''
})

onMounted(() => {
  cargarCuentas()
  cargarMonedas()
  if (props.tasaOperacion && !form.tasa_aplicada) {
    form.tasa_aplicada = parseFloat(props.tasaOperacion).toFixed(2)
  }
})
</script>
