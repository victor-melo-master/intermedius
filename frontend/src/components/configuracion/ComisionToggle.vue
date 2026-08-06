<template>
  <div class="bg-white border border-edge rounded-xl p-5 space-y-4">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h3 class="font-semibold text-ink">Comisión</h3>
        <p class="text-xs text-ink-faint">Comisión bancaria sobre el monto en {{ simbolo }} que sale.</p>
      </div>
      <button type="button" @click="$emit('update:activa', !activa)"
        class="relative w-12 h-6 rounded-full transition shrink-0" :class="activa ? 'bg-gold' : 'bg-surface-muted'">
        <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
          :class="activa ? 'translate-x-6' : ''"></span>
      </button>
    </div>

    <template v-if="activa">
      <div>
        <label class="block text-sm text-ink-muted mb-1">Tipo de comisión</label>
        <select :value="tipo" @change="$emit('update:tipo', $event.target.value)"
          class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-gold outline-none bg-white">
          <option value="pago_movil">Pago móvil (0.3%)</option>
          <option value="otros_bancos">Transferencia otros bancos (0.3%)</option>
          <option value="mismo_banco">Transferencia mismo banco (0%)</option>
          <option value="manual">Manual (monto libre)</option>
        </select>
      </div>
      <div>
        <label class="block text-sm text-ink-muted mb-1">Monto de comisión ({{ simbolo }})</label>
        <input :value="monto" @input="$emit('update:monto', $event.target.value)" type="number" step="0.01" placeholder="0.00"
          :disabled="tipo === 'mismo_banco'"
          class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-gold outline-none disabled:bg-surface-muted disabled:text-ink-faint" />
        <p v-if="['pago_movil', 'otros_bancos'].includes(tipo)" class="text-xs text-ink-faint mt-1">
          Calculado: 0.3% de {{ simbolo }} {{ montoCalculado }}. Puedes ajustarlo.
        </p>
        <p v-else-if="tipo === 'mismo_banco'" class="text-xs text-ink-faint mt-1">Sin comisión para el mismo banco.</p>
      </div>
    </template>
  </div>
</template>

<script setup>
/**
 * Componente toggle de comisión.
 * Permite activar/desactivar comisión, seleccionar tipo y editar el monto.
 *
 * @component
 * @prop {boolean} activa - Indica si la comisión está activa
 * @prop {string} tipo - Tipo de comisión (pago_movil, otros_bancos, mismo_banco, manual)
 * @prop {string|number} monto - Monto de la comisión
 * @prop {string} simbolo - Símbolo de la moneda
 * @prop {string|number} montoCalculado - Monto calculado automáticamente
 * @emit {boolean} update:activa - Actualiza el estado activo
 * @emit {string} update:tipo - Actualiza el tipo de comisión
 * @emit {string|number} update:monto - Actualiza el monto de comisión
 */
defineProps({
  /** @type {boolean} - Indica si la comisión está activa */
  activa: Boolean,
  /** @type {string} - Tipo de comisión */
  tipo: String,
  /** @type {string|number} - Monto de la comisión */
  monto: [String, Number],
  /** @type {string} - Símbolo de la moneda */
  simbolo: String,
  /** @type {string|number} - Monto calculado automáticamente */
  montoCalculado: [String, Number],
})
defineEmits(['update:activa', 'update:tipo', 'update:monto'])
</script>
