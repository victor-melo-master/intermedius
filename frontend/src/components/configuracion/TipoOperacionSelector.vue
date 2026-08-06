<template>
  <div class="bg-surface border border-edge rounded-xl p-5 space-y-4">
    <h3 class="font-semibold text-ink">Tipo de operación *</h3>
    <div class="grid grid-cols-2 gap-3">
      <button type="button" @click="$emit('update:tipo', 'compra')"
        class="py-3 rounded-xl border-2 font-semibold text-sm transition"
        :class="tipo === 'compra' ? 'border-info bg-info-soft text-info-strong' : 'border-edge text-ink-muted hover:border-edge-strong'">
        Compra de {{ moneda }}
        <span class="block text-xs font-normal mt-0.5">La casa compra {{ moneda }}, paga {{ quoteSimbolo }}.</span>
      </button>
      <button type="button" @click="$emit('update:tipo', 'venta')"
        class="py-3 rounded-xl border-2 font-semibold text-sm transition"
        :class="tipo === 'venta' ? 'border-success bg-success-soft text-success-strong' : 'border-edge text-ink-muted hover:border-edge-strong'">
        Venta de {{ moneda }}
        <span class="block text-xs font-normal mt-0.5">La casa vende {{ moneda }}, recibe {{ quoteSimbolo }}.</span>
      </button>
    </div>
    <div>
      <label class="block text-sm text-ink-muted mb-1">Fecha *</label>
      <input :value="fecha" @input="$emit('update:fecha', $event.target.value)" type="date" required :max="today"
        class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-gold outline-none" />
    </div>
  </div>
</template>

<script setup>
/**
 * Componente selector de tipo de operación.
 * Permite elegir entre compra/venta y seleccionar una fecha.
 *
 * @component
 * @prop {string} tipo - Tipo de operación ('compra' | 'venta')
 * @prop {string} fecha - Fecha de la operación (formato YYYY-MM-DD)
 * @prop {string} moneda - Código de la moneda
 * @prop {string} quoteSimbolo - Símbolo de la moneda quote
 * @prop {string} today - Fecha máxima permitida (hoy)
 * @emit {string} update:tipo - Actualiza el tipo de operación
 * @emit {string} update:fecha - Actualiza la fecha de operación
 */
defineProps({
  /** @type {string} - Tipo de operación ('compra' | 'venta') */
  tipo: String,
  /** @type {string} - Fecha de la operación */
  fecha: String,
  /** @type {string} - Código de moneda */
  moneda: String,
  /** @type {string} - Símbolo de moneda quote */
  quoteSimbolo: String,
  /** @type {string} - Fecha máxima (hoy) */
  today: String,
})
defineEmits(['update:tipo', 'update:fecha'])
</script>
