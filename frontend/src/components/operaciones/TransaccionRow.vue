<template>
  <div class="border border-gray-200 rounded-xl p-4 space-y-3">
    <div class="flex items-center justify-between">
      <span class="text-sm font-medium text-gray-500">Transacción {{ index + 1 }}</span>
      <button
        type="button"
        @click="$emit('remove')"
        class="text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg p-1.5 text-sm transition"
      >✕ Eliminar</button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div>
        <label class="block text-xs text-gray-500 mb-1">Moneda</label>
        <select
          :value="monedaId"
          @change="$emit('update:monedaId', $event.target.value)"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white"
        >
          <option value="">Seleccionar</option>
          <option v-for="m in monedasDisponibles" :key="m.id" :value="m.id">{{ m.codigo }} — {{ m.nombre }}</option>
        </select>
      </div>

      <div>
        <label class="block text-xs text-gray-500 mb-1">
          <span class="inline-block w-2 h-2 bg-orange-400 rounded-full mr-1"></span>
          Salida (entrega) <span class="text-orange-600 font-medium">{{ salidaLabel }}</span>
        </label>
        <select
          :value="cuentaOrigenId"
          @change="$emit('update:cuentaOrigenId', $event.target.value)"
          class="w-full px-3 py-2 border border-orange-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 outline-none bg-white"
        >
          <option value="">Seleccionar</option>
          <option v-for="c in cuentasSalida" :key="c.id" :value="c.id">
            {{ labelCuenta(c) }} — Saldo: {{ saldo(c) }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs text-gray-500 mb-1">
          <span class="inline-block w-2 h-2 bg-emerald-400 rounded-full mr-1"></span>
          Entrada (recibe) <span class="text-emerald-600 font-medium">{{ entradaLabel }}</span>
        </label>
        <select
          :value="cuentaDestinoId"
          @change="$emit('update:cuentaDestinoId', $event.target.value)"
          class="w-full px-3 py-2 border border-emerald-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-400 outline-none bg-white"
        >
          <option value="">Seleccionar</option>
          <option v-for="c in cuentasEntrada" :key="c.id" :value="c.id">
            {{ labelCuenta(c) }} — Saldo: {{ saldo(c) }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs text-gray-500 mb-1">Monto</label>
        <input
          :value="monto"
          @input="$emit('update:monto', $event.target.value)"
          type="number" step="0.01" min="0" placeholder="0.00"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none"
        />
      </div>
    </div>

    <!-- Comisión -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2 border-t border-gray-100">
      <div class="col-span-2">
        <label class="block text-xs text-gray-500 mb-1">
          <span class="inline-block w-2 h-2 bg-amber-400 rounded-full mr-1"></span>
          Tipo de comisión
        </label>
        <select
          :value="comisionTipo"
          @change="onTipoChange"
          class="w-full px-3 py-2 border border-amber-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-400 outline-none bg-white"
        >
          <option value="sin_comision">Sin comisión</option>
          <option value="manual">Manual (monto libre)</option>
          <option value="pago_movil">Pago móvil (0.3%)</option>
          <option value="otros_bancos">Transferencia otros bancos (0.3%)</option>
          <option value="mismo_banco">Mismo banco (0%)</option>
        </select>
      </div>
      <div class="col-span-2">
        <label class="block text-xs text-gray-500 mb-1">Monto de comisión</label>
        <input
          :value="comisionMonto"
          @input="$emit('update:comisionMonto', $event.target.value)"
          type="number" step="0.01" min="0" placeholder="0.00"
          :disabled="comisionTipo === 'sin_comision' || comisionTipo === 'mismo_banco'"
          class="w-full px-3 py-2 border border-amber-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-400 outline-none disabled:bg-gray-100 disabled:text-gray-400"
        />
        <p v-if="comisionTipo === 'sin_comision' || comisionTipo === 'mismo_banco'" class="text-xs text-gray-400 mt-0.5">Sin costo</p>
        <p v-else-if="sugerido" class="text-xs text-gray-400 mt-0.5">Sugerido: {{ sugerido }}</p>
      </div>
    </div>

    <p v-if="advertenciaSaldo" class="text-xs text-red-500">⚠️ El monto supera el saldo disponible en la cuenta de salida.</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  index: Number,
  monedas: { type: Array, default: () => [] },
  cuentas: { type: Array, default: () => [] },
  cuentaOrigenId: [String, Number, null],
  cuentaDestinoId: [String, Number, null],
  monedaId: [String, Number, null],
  monto: [String, Number],
  comisionTipo: { type: String, default: 'manual' },
  comisionMonto: { type: [String, Number], default: '' },
  clienteId: { type: [Number, String, null], default: null },
  monedaForeignId: { type: [Number, String, null], default: null },
  monedaQuoteId: { type: [Number, String, null], default: null },
})

const emit = defineEmits(['remove', 'update:cuentaOrigenId', 'update:cuentaDestinoId', 'update:monedaId', 'update:monto', 'update:comisionTipo', 'update:comisionMonto'])

function onTipoChange(e) {
  const tipo = e.target.value
  emit('update:comisionTipo', tipo)
  if (tipo === 'sin_comision' || tipo === 'mismo_banco') {
    emit('update:comisionMonto', '0')
  } else if (tipo === 'pago_movil' || tipo === 'otros_bancos') {
    const monto = parseFloat(props.monto)
    if (monto > 0) emit('update:comisionMonto', (monto * 0.003).toFixed(2))
  }
}

const sugerido = computed(() => {
  const monto = parseFloat(props.monto)
  if (!monto || !['pago_movil', 'otros_bancos'].includes(props.comisionTipo)) return null
  return (monto * 0.003).toFixed(2)
})

const monedasDisponibles = computed(() => {
  return props.monedas.filter(m => m.id == props.monedaForeignId || m.id == props.monedaQuoteId)
})

const esForeign = computed(() => props.monedaId && props.monedaId == props.monedaForeignId)
const esQuote = computed(() => props.monedaId && props.monedaId == props.monedaQuoteId)

function titularAccounts(monedaId) {
  return props.cuentas.filter(c => c.titular_id != null && c.moneda_id == monedaId)
}
function clientAccounts(monedaId) {
  if (!props.clienteId) return []
  return props.cuentas.filter(c => c.cliente_id == props.clienteId && c.moneda_id == monedaId)
}

const cuentasSalida = computed(() => {
  if (!props.monedaId) return []
  if (esForeign.value) return titularAccounts(props.monedaId)
  if (esQuote.value) return clientAccounts(props.monedaId)
  return []
})

const cuentasEntrada = computed(() => {
  if (!props.monedaId) return []
  if (esForeign.value) return clientAccounts(props.monedaId)
  if (esQuote.value) return titularAccounts(props.monedaId)
  return []
})

const salidaLabel = computed(() => {
  if (esForeign.value) return '(Intermedius)'
  if (esQuote.value) return '(Cliente)'
  return ''
})

const entradaLabel = computed(() => {
  if (esForeign.value) return '(Cliente)'
  if (esQuote.value) return '(Intermedius)'
  return ''
})

const cuentaOrigenObj = computed(() => props.cuentas.find(c => c.id == props.cuentaOrigenId))
const advertenciaSaldo = computed(() => {
  if (!cuentaOrigenObj.value || !props.monto) return false
  const saldo = parseFloat(cuentaOrigenObj.value.saldo_cache)
  const monto = parseFloat(props.monto)
  if (isNaN(saldo) || isNaN(monto)) return false
  return monto > saldo
})

function labelCuenta(c) {
  const tipo = c.banco?.nombre || c.tipo || 'cuenta'
  const quien = c.titular_id ? 'Intermedius' : (c.cliente_id ? 'Cliente' : '')
  return `${c.alias} · ${tipo} (${c.moneda?.codigo}) ${quien ? `— ${quien}` : ''}`
}

function saldo(c) {
  if (c.saldo_cache === null || c.saldo_cache === undefined) return 'N/D'
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(c.saldo_cache))
}
</script>
