<template>
  <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="font-semibold text-gray-700">Transacciones propuestas</h3>
        <p class="text-xs text-gray-400">Opcional — crea las transacciones junto con la solicitud</p>
      </div>
      <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" v-model="activo" class="sr-only peer">
        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
      </label>
    </div>

    <template v-if="activo">
      <div v-if="cargandoCuentas" class="text-sm text-gray-400 py-4 text-center">Cargando cuentas…</div>

      <template v-else>
        <div v-if="!clienteId" class="bg-amber-50 border border-amber-200 text-amber-700 text-sm p-3 rounded-lg">
          Selecciona un cliente para poder elegir sus cuentas.
        </div>

        <div class="grid grid-cols-2 gap-3 p-4 bg-blue-50 rounded-xl">
          <div>
            <label class="block text-xs text-gray-500 mb-1">
              Cuenta {{ labelCliente }} para {{ monedaSimbolo }}
              <span class="text-gray-400">({{ esCompra ? 'origen' : 'destino' }})</span>
            </label>
            <select v-model="cuentaClienteDivisa" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none" :disabled="!clienteId">
              <option value="">Seleccionar</option>
              <option v-for="c in cuentasClienteDivisa" :key="c.id" :value="c.id">{{ labelCuenta(c) }}</option>
            </select>
            <p v-if="clienteId && !cuentasClienteDivisa.length" class="text-xs text-red-500 mt-1">El cliente no tiene cuentas en {{ monedaSimbolo }}</p>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">
              Cuenta Intermedius para {{ monedaSimbolo }}
              <span class="text-gray-400">({{ esCompra ? 'destino' : 'origen' }})</span>
            </label>
            <select v-model="cuentaIntermediusDivisa" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar</option>
              <option v-for="c in cuentasIntermediusDivisa" :key="c.id" :value="c.id">{{ labelCuenta(c) }}</option>
            </select>
            <p v-if="!cuentasIntermediusDivisa.length" class="text-xs text-red-500 mt-1">No hay cuentas de Intermedius en {{ monedaSimbolo }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 p-4 bg-green-50 rounded-xl">
          <div>
            <label class="block text-xs text-gray-500 mb-1">
              Cuenta {{ labelCliente }} para VES
              <span class="text-gray-400">({{ esCompra ? 'destino' : 'origen' }})</span>
            </label>
            <select v-model="cuentaClienteVes" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none" :disabled="!clienteId">
              <option value="">Seleccionar</option>
              <option v-for="c in cuentasClienteVes" :key="c.id" :value="c.id">{{ labelCuenta(c) }}</option>
            </select>
            <p v-if="clienteId && !cuentasClienteVes.length" class="text-xs text-red-500 mt-1">El cliente no tiene cuentas en VES</p>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">
              Cuenta Intermedius para VES
              <span class="text-gray-400">({{ esCompra ? 'origen' : 'destino' }})</span>
            </label>
            <select v-model="cuentaIntermediusVes" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar</option>
              <option v-for="c in cuentasIntermediusVes" :key="c.id" :value="c.id">{{ labelCuenta(c) }}</option>
            </select>
            <p v-if="!cuentasIntermediusVes.length" class="text-xs text-red-500 mt-1">No hay cuentas de Intermedius en VES</p>
          </div>
        </div>

        <div class="flex gap-3">
          <div class="flex-1">
            <label class="block text-xs text-gray-500 mb-1">Método de pago</label>
            <select v-model="metodoPago" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar</option>
              <option value="efectivo">Efectivo</option>
              <option value="pago_movil">Pago móvil</option>
              <option value="transferencia">Transferencia</option>
              <option value="zelle">Zelle</option>
              <option value="binance">Binance</option>
              <option value="otro">Otro</option>
            </select>
          </div>
          <div v-if="metodoPago && metodoPago !== 'efectivo'" class="flex-1">
            <label class="block text-xs text-gray-500 mb-1">Comprobante</label>
            <input v-model="comprobante" placeholder="N° de referencia" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
        </div>

        <div v-if="resumen" class="text-xs text-gray-500 bg-gray-50 rounded-lg p-3 space-y-1">
          <p v-if="esCompra"><strong>{{ monedaSimbolo }}:</strong> {{ labelCliente }} entrega → Intermedius recibe <span class="font-medium">{{ formatMoney(montoDivisa) }} {{ monedaSimbolo }}</span></p>
          <p v-else><strong>{{ monedaSimbolo }}:</strong> Intermedius entrega → {{ labelCliente }} recibe <span class="font-medium">{{ formatMoney(montoDivisa) }} {{ monedaSimbolo }}</span></p>
          <p v-if="esCompra"><strong>VES:</strong> Intermedius entrega → {{ labelCliente }} recibe <span class="font-medium">{{ formatMoney(parseFloat(montoVES)) }} VES</span></p>
          <p v-else><strong>VES:</strong> {{ labelCliente }} entrega → Intermedius recibe <span class="font-medium">{{ formatMoney(parseFloat(montoVES)) }} VES</span></p>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useFormatting } from '@/composables/useFormatting'
import api from '@/api/axios'

const props = defineProps({
  clienteId: { type: [String, Number, null], default: null },
  clienteNombre: { type: String, default: '' },
  intermediusTitularId: { type: [String, Number, null], default: null },
  esCompra: { type: Boolean, default: true },
  monedaCodigo: { type: String, default: 'USD' },
  tasa: { type: [String, Number, null], default: null },
  montoUsd: { type: [String, Number, null], default: null },
  monedaId: { type: [String, Number, null], default: null },
  vesId: { type: [String, Number, null], default: null },
})

const emit = defineEmits(['update:transacciones'])

const { formatMoney } = useFormatting()

const activo = ref(false)
const cuentaClienteDivisa = ref('')
const cuentaIntermediusDivisa = ref('')
const cuentaClienteVes = ref('')
const cuentaIntermediusVes = ref('')
const metodoPago = ref('')
const comprobante = ref('')

const cuentasIntermedius = ref([])
const cuentasCliente = ref([])
const cargandoCuentas = ref(false)

const monedaSimbolo = computed(() => ({ USD: 'USD', USDT: 'USDT', EUR: 'EUR', COP: 'COP' })[props.monedaCodigo] || props.monedaCodigo)
const labelCliente = computed(() => props.clienteNombre || 'el cliente')
const montoDivisa = computed(() => parseFloat(props.montoUsd || 0))
const montoVES = computed(() => montoDivisa.value * parseFloat(props.tasa || 0))

const cuentasClienteDivisa = computed(() =>
  cuentasCliente.value.filter(c => c.moneda_id == props.monedaId)
)
const cuentasIntermediusDivisa = computed(() =>
  cuentasIntermedius.value.filter(c => c.moneda_id == props.monedaId)
)
const cuentasClienteVes = computed(() =>
  cuentasCliente.value.filter(c => c.moneda_id == props.vesId)
)
const cuentasIntermediusVes = computed(() =>
  cuentasIntermedius.value.filter(c => c.moneda_id == props.vesId)
)

const resumen = computed(() => {
  if (!activo.value || !cuentaClienteDivisa.value || !cuentaIntermediusDivisa.value) return null
  return true
})

function labelCuenta(c) {
  const tipo = c.banco?.nombre || c.tipo || 'cuenta'
  return `${c.alias} · ${tipo}`
}

function construirTransacciones() {
  if (!activo.value || !metodoPago.value) return []

  const txs = []

  if (cuentaClienteDivisa.value && cuentaIntermediusDivisa.value && montoDivisa.value > 0) {
    txs.push({
      cuenta_origen_id: props.esCompra ? Number(cuentaClienteDivisa.value) : Number(cuentaIntermediusDivisa.value),
      cuenta_destino_id: props.esCompra ? Number(cuentaIntermediusDivisa.value) : Number(cuentaClienteDivisa.value),
      moneda_id: Number(props.monedaId),
      monto: montoDivisa.value,
      tasa_aplicada: parseFloat(props.tasa),
      metodo_pago: metodoPago.value,
      comprobante: comprobante.value || null,
    })
  }

  if (cuentaClienteVes.value && cuentaIntermediusVes.value && montoVES.value > 0) {
    txs.push({
      cuenta_origen_id: props.esCompra ? Number(cuentaIntermediusVes.value) : Number(cuentaClienteVes.value),
      cuenta_destino_id: props.esCompra ? Number(cuentaClienteVes.value) : Number(cuentaIntermediusVes.value),
      moneda_id: Number(props.vesId),
      monto: montoVES.value,
      tasa_aplicada: parseFloat(props.tasa),
      metodo_pago: metodoPago.value,
      comprobante: comprobante.value || null,
    })
  }

  return txs
}

watch([activo, cuentaClienteDivisa, cuentaIntermediusDivisa, cuentaClienteVes, cuentaIntermediusVes, metodoPago], () => {
  emit('update:transacciones', construirTransacciones())
})

async function cargarCuentas() {
  cargandoCuentas.value = true
  try {
    if (props.intermediusTitularId) {
      const { data } = await api.get(`/cuentas?titular_id=${props.intermediusTitularId}`)
      cuentasIntermedius.value = Array.isArray(data) ? data : (data.data || [])
    }
    if (props.clienteId) {
      const { data } = await api.get(`/cuentas?cliente_id=${props.clienteId}`)
      cuentasCliente.value = Array.isArray(data) ? data : (data.data || [])
    }
  } catch {
    cuentasIntermedius.value = []
    cuentasCliente.value = []
  }
  cargandoCuentas.value = false
}

watch(() => [props.clienteId, props.intermediusTitularId], cargarCuentas, { immediate: true })
</script>
