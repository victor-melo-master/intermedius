<template>
  <div class="border border-edge rounded-xl p-4 space-y-3">
    <div class="flex items-center justify-between">
      <span class="text-sm font-medium text-ink-soft">Movimiento {{ index + 1 }}</span>
      <button
        type="button"
        @click="$emit('remove')"
        class="text-ink-faint hover:text-danger hover:bg-danger-soft rounded-lg p-1.5 text-sm transition"
      ><Iconoir name="x-mark" class="w-3.5 h-3.5 text-danger" /> Eliminar</button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div>
        <label class="block text-xs text-ink-soft mb-1">Moneda</label>
        <select
          :value="monedaId"
          @change="$emit('update:monedaId', $event.target.value)"
          class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm focus:ring-2 focus:ring-gold outline-none bg-white dark:bg-surface-muted"
        >
          <option value="">Seleccionar</option>
          <option v-for="m in monedas" :key="m.id" :value="m.id">{{ m.codigo }} — {{ m.nombre }}</option>
        </select>
      </div>

      <div>
        <label class="block text-xs text-ink-soft mb-1">
          <span class="inline-block w-2 h-2 bg-warning rounded-full mr-1"></span>
          Salida (entrega)
        </label>
        <select
          :value="cuentaOrigenId"
          @change="$emit('update:cuentaOrigenId', $event.target.value)"
          class="w-full px-3 py-2 border border-warning-edge rounded-lg text-sm focus:ring-2 focus:ring-warning outline-none bg-white dark:bg-surface-muted"
        >
          <option value="">Seleccionar</option>
          <option v-for="c in cuentasOrigen" :key="c.id" :value="c.id">
            {{ labelCuenta(c) }}<template v-if="c.titular_id"> — Saldo: {{ saldo(c) }}</template>
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs text-ink-soft mb-1">
          <span class="inline-block w-2 h-2 bg-success rounded-full mr-1"></span>
          Entrada (recibe)
        </label>
        <select
          :value="cuentaDestinoId"
          @change="$emit('update:cuentaDestinoId', $event.target.value)"
          class="w-full px-3 py-2 border border-success-edge rounded-lg text-sm focus:ring-2 focus:ring-success outline-none bg-white dark:bg-surface-muted"
        >
          <option value="">Seleccionar</option>
          <option v-for="c in cuentasDestino" :key="c.id" :value="c.id">
            {{ labelCuenta(c) }}<template v-if="c.titular_id"> — Saldo: {{ saldo(c) }}</template>
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs text-ink-soft mb-1">Monto</label>
        <input
          :value="monto"
          @input="$emit('update:monto', $event.target.value)"
          type="number" step="0.01" min="0" placeholder="0.00"
          class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm focus:ring-2 focus:ring-gold outline-none"
        />
      </div>
    </div>

    <p v-if="advertenciaSaldo" class="text-xs text-danger">
      <Iconoir name="exclamation-triangle" class="w-3.5 h-3.5 inline text-warning" /> El monto supera el saldo disponible en la cuenta de salida.
    </p>

    <div class="border-t border-edge pt-2">
      <button type="button" @click="comisionAbierta = !comisionAbierta"
        class="text-xs text-ink-faint hover:text-ink-muted transition flex items-center gap-1">
        <span class="text-[10px]" :class="comisionAbierta ? 'rotate-90' : ''">▶</span>
        {{ comisionTipo !== 'sin_comision' && parseFloat(comisionMonto) > 0 ? 'Comisión activa' : 'Comisión' }}
      </button>
      <template v-if="comisionAbierta">
        <div class="mt-2 flex items-center gap-3 flex-wrap">
          <select
            :value="comisionTipo"
            @change="$emit('update:comisionTipo', $event.target.value)"
            class="text-xs px-2 py-1 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none bg-white dark:bg-surface-muted"
          >
            <option value="sin_comision">Sin comisión</option>
            <option value="manual">Manual</option>
            <option value="pago_movil">Pago móvil (0.3%)</option>
            <option value="otros_bancos">Otros bancos (0.3%)</option>
            <option value="mismo_banco">Mismo banco (0%)</option>
          </select>
          <input
            v-if="comisionTipo !== 'sin_comision' && comisionTipo !== 'mismo_banco'"
            :value="comisionMonto"
            @input="$emit('update:comisionMonto', $event.target.value)"
            type="number" step="0.01" min="0" placeholder="0.00"
            class="w-24 text-xs px-2 py-1 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none"
          />
          <span v-if="comisionTipo === 'mismo_banco'" class="text-xs text-ink-faint">Sin costo</span>
          <span v-if="comisionTipo === 'sin_comision'" class="text-xs text-ink-faint">Sin comisión</span>
          <span v-if="['pago_movil', 'otros_bancos'].includes(comisionTipo) && monto" class="text-xs text-ink-faint">
            (sugerido: {{ sugeridoComision }})
          </span>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import Iconoir from '../common/Iconoir.vue'

const props = defineProps({
  index: Number,
  monedas: { type: Array, default: () => [] },
  cuentas: { type: Array, default: () => [] },
  cuentaOrigenId: [String, Number, null],
  cuentaDestinoId: [String, Number, null],
  monedaId: [String, Number, null],
  monto: [String, Number],
  comisionTipo: { type: String, default: 'sin_comision' },
  comisionMonto: [String, Number],
  tipoOperacion: { type: String, default: 'compra' },
  clienteId: { type: [String, Number], default: null },
  intermediusTitularId: { type: [String, Number], default: null },
  monedaForeignId: { type: [String, Number], default: null },
  monedaQuoteId: { type: [String, Number], default: null },
})

defineEmits([
  'remove',
  'update:cuentaOrigenId',
  'update:cuentaDestinoId',
  'update:monedaId',
  'update:monto',
  'update:comisionTipo',
  'update:comisionMonto',
])

const comisionAbierta = ref(false)

const cuentasPorMoneda = computed(() => {
  if (!props.monedaId) return []
  return props.cuentas.filter(c => c.moneda_id == props.monedaId)
})

const cuentasOrigen = computed(() => {
  if (!props.monedaId || !props.cuentas.length) return []
  const esVenta = props.tipoOperacion === 'venta'
  const esCompra = props.tipoOperacion === 'compra'
  const esForeign = props.monedaId == props.monedaForeignId
  const esQuote = props.monedaId == props.monedaQuoteId

  if (!props.clienteId || !props.intermediusTitularId) return cuentasPorMoneda.value

  if (esVenta) {
    if (esForeign) return cuentasPorMoneda.value.filter(c => c.titular_id == props.intermediusTitularId)
    if (esQuote) return cuentasPorMoneda.value.filter(c => c.cliente_id == props.clienteId)
  }
  if (esCompra) {
    if (esForeign) return cuentasPorMoneda.value.filter(c => c.cliente_id == props.clienteId)
    if (esQuote) return cuentasPorMoneda.value.filter(c => c.titular_id == props.intermediusTitularId)
  }
  return cuentasPorMoneda.value
})

const cuentasDestino = computed(() => {
  if (!props.monedaId || !props.cuentas.length) return []
  const esVenta = props.tipoOperacion === 'venta'
  const esCompra = props.tipoOperacion === 'compra'
  const esForeign = props.monedaId == props.monedaForeignId
  const esQuote = props.monedaId == props.monedaQuoteId

  if (!props.clienteId || !props.intermediusTitularId) return cuentasPorMoneda.value

  if (esVenta) {
    if (esForeign) return cuentasPorMoneda.value.filter(c => c.cliente_id == props.clienteId)
    if (esQuote) return cuentasPorMoneda.value.filter(c => c.titular_id == props.intermediusTitularId)
  }
  if (esCompra) {
    if (esForeign) return cuentasPorMoneda.value.filter(c => c.titular_id == props.intermediusTitularId)
    if (esQuote) return cuentasPorMoneda.value.filter(c => c.cliente_id == props.clienteId)
  }
  return cuentasPorMoneda.value
})

const cuentaOrigenObj = computed(() => props.cuentas.find(c => c.id == props.cuentaOrigenId))

const advertenciaSaldo = computed(() => {
  if (!cuentaOrigenObj.value || !props.monto) return false
  if (!cuentaOrigenObj.value.titular_id) return false
  const saldo = parseFloat(cuentaOrigenObj.value.saldo_cache)
  const monto = parseFloat(props.monto)
  if (isNaN(saldo) || isNaN(monto)) return false
  return monto > saldo
})

const sugeridoComision = computed(() => {
  const m = parseFloat(props.monto) || 0
  return (m * 0.003).toFixed(2)
})

function labelCuenta(c) {
  const tipo = c.banco?.nombre || c.tipo || 'cuenta'
  return `${c.alias} · ${tipo} (${c.moneda?.codigo})`
}

function saldo(c) {
  if (c.saldo_cache === null || c.saldo_cache === undefined) return 'N/D'
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(c.saldo_cache))
}
</script>
