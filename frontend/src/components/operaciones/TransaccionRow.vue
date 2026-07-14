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
          <option v-for="m in monedas" :key="m.id" :value="m.id">{{ m.codigo }} — {{ m.nombre }}</option>
        </select>
      </div>

      <div>
        <label class="block text-xs text-gray-500 mb-1">
          <span class="inline-block w-2 h-2 bg-orange-400 rounded-full mr-1"></span>
          Salida (entrega)
        </label>
        <select
          :value="cuentaOrigenId"
          @change="$emit('update:cuentaOrigenId', $event.target.value)"
          class="w-full px-3 py-2 border border-orange-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 outline-none bg-white"
        >
          <option value="">Seleccionar</option>
          <option v-for="c in cuentasFiltradas" :key="c.id" :value="c.id">
            {{ labelCuenta(c) }} — Saldo: {{ saldo(c) }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs text-gray-500 mb-1">
          <span class="inline-block w-2 h-2 bg-emerald-400 rounded-full mr-1"></span>
          Entrada (recibe)
        </label>
        <select
          :value="cuentaDestinoId"
          @change="$emit('update:cuentaDestinoId', $event.target.value)"
          class="w-full px-3 py-2 border border-emerald-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-400 outline-none bg-white"
        >
          <option value="">Seleccionar</option>
          <option v-for="c in cuentasFiltradas" :key="c.id" :value="c.id">
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
})

defineEmits(['remove', 'update:cuentaOrigenId', 'update:cuentaDestinoId', 'update:monedaId', 'update:monto'])

const cuentasFiltradas = computed(() => {
  if (!props.monedaId) return []
  return props.cuentas.filter(c => c.moneda_id == props.monedaId)
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
  return `${c.alias} · ${tipo} (${c.moneda?.codigo})`
}

function saldo(c) {
  if (c.saldo_cache === null || c.saldo_cache === undefined) return 'N/D'
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(c.saldo_cache))
}
</script>
