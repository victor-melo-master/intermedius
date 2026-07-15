<template>
  <div class="space-y-4">
    <TipoOperacionSelector
      :tipo="tipo"
      :fecha="fecha"
      :moneda="moneda"
      :quote-simbolo="quoteSimbolo"
      :today="today"
      @update:tipo="$emit('update:tipo', $event)"
      @update:fecha="$emit('update:fecha', $event)"
    />
    <ClienteSelector
      :model-value="cliente"
      @update:model-value="$emit('update:cliente', $event)"
      :cliente-tiene-cuentas="clienteTieneCuentas"
      @cuenta-agregada="$emit('cuenta-agregada')"
    />
  </div>
</template>

<script setup>
import ClienteSelector from '@/components/clientes/ClienteSelector.vue'
import TipoOperacionSelector from '@/components/configuracion/TipoOperacionSelector.vue'

defineProps({
  tipo: String,
  fecha: String,
  moneda: String,
  quoteSimbolo: String,
  today: String,
  clienteTieneCuentas: Boolean,
  cliente: Object,
})

defineEmits(['update:tipo', 'update:fecha', 'update:cliente', 'cuenta-agregada'])
</script>
