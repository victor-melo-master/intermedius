<template>
  <div class="bg-white border border-edge rounded-xl p-5 space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-semibold text-ink">Transacciones</h3>
      <button type="button" @click="$emit('agregar')" class="text-sm text-gold-dark hover:text-gold-dark font-medium">
        + Agregar fila
      </button>
    </div>

    <AppLoadingSpinner v-if="loading" />

    <div v-else-if="cuentas.length === 0" class="bg-warning-soft border border-warning-edge text-warning-strong text-sm p-4 rounded-lg">
      <Iconoir name="exclamation-triangle" class="w-4 h-4 inline text-warning" /> No hay cuentas configuradas.
    </div>

    <template v-else>
      <MovimientoRow
        v-for="(tx, i) in transacciones"
        :key="tx._key"
        :index="i"
        :monedas="monedas"
        :cuentas="cuentas"
        :cuenta-origen-id="tx.cuenta_origen_id"
        :cuenta-destino-id="tx.cuenta_destino_id"
        :moneda-id="tx.moneda_id"
        :monto="tx.monto"
        :tipo-operacion="tipoOperacion"
        :cliente-id="clienteId"
        :intermedius-titular-id="intermediusTitularId"
        :moneda-foreign-id="monedaForeignId"
        :moneda-quote-id="monedaQuoteId"
        :comision-tipo="tx.comision_tipo"
        :comision-monto="tx.comision_monto"
        @update:cuentaOrigenId="tx.cuenta_origen_id = $event"
        @update:cuentaDestinoId="tx.cuenta_destino_id = $event"
        @update:monedaId="tx.moneda_id = $event"
        @update:monto="tx.monto = $event"
        @update:comisionTipo="tx.comision_tipo = $event"
        @update:comisionMonto="tx.comision_monto = $event"
        @remove="eliminarTransaccion(i)"
      />
    </template>

    <div class="flex flex-wrap gap-2 pt-2">
      <button
        type="button"
        @click="$emit('distribuir')"
        class="text-xs px-3 py-1.5 bg-surface-muted hover:bg-surface-muted text-ink-muted rounded-lg transition font-medium"
        :disabled="!montoUSD && !montoVES"
      >
        Distribuir montos
      </button>
      <button
        type="button"
        @click="$emit('limpiar')"
        class="text-xs px-3 py-1.5 bg-surface-muted hover:bg-danger-soft text-ink-soft hover:text-danger rounded-lg transition font-medium"
      >
        Limpiar filas
      </button>
    </div>

    <div v-if="resumen.length" class="text-xs text-ink-soft space-y-0.5 pt-1 border-t border-edge">
      <p v-for="r in resumen" :key="r.label" :class="r.ok ? 'text-ink-soft' : 'text-danger font-medium'">
        {{ r.label }}: {{ r.total }} / {{ r.esperado }}
        <Iconoir v-if="r.ok" name="check" class="w-3.5 h-3.5 inline text-success" />
        <span v-else><Iconoir name="exclamation-triangle" class="w-3.5 h-3.5 inline text-danger" /> Diferencia: {{ r.diferencia }}</span>
      </p>
    </div>
  </div>
</template>

<script setup>
import MovimientoRow from '@/components/operaciones/MovimientoRow.vue'
import AppLoadingSpinner from '@/components/common/AppLoadingSpinner.vue'
import Iconoir from '@/components/common/Iconoir.vue'

const props = defineProps({
  transacciones: Array,
  monedas: Array,
  cuentas: Array,
  loading: { type: Boolean, default: false },
  montoUSD: [String, Number],
  montoVES: [String, Number],
  resumen: Array,
  tipoOperacion: String,
  clienteId: [String, Number],
  intermediusTitularId: [String, Number],
  monedaForeignId: [String, Number],
  monedaQuoteId: [String, Number],
})

const emit = defineEmits(['agregar', 'eliminar', 'distribuir', 'limpiar'])

const eliminarTransaccion = (index) => {
  if (props.transacciones.length <= 1) return
  emit('eliminar', index)
}
</script>
