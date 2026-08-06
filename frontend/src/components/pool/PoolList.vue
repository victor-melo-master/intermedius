<template>
  <div class="bg-surface rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-edge">
      <thead class="bg-surface-soft">
        <tr>
          <th class="px-4 py-3 text-left text-sm font-medium text-ink-muted uppercase">#</th>
          <th class="px-4 py-3 text-left text-sm font-medium text-ink-muted uppercase">Cliente</th>
          <th class="px-4 py-3 text-left text-sm font-medium text-ink-muted uppercase">Monto</th>
          <th class="px-4 py-3 text-left text-sm font-medium text-ink-muted uppercase">Estado</th>
          <th class="px-4 py-3 text-left text-sm font-medium text-ink-muted uppercase">Tiempo en espera</th>
          <th class="px-4 py-3 text-left text-sm font-medium text-ink-muted uppercase">Transacciones</th>
          <th class="px-4 py-3 text-left text-sm font-medium text-ink-muted uppercase">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-edge">
        <tr
          v-for="op in operaciones"
          :key="op.id"
          :class="{
            'bg-warning-soft': op.estado === 'en_espera',
            'bg-info-soft': op.estado === 'en_proceso',
            'bg-success-soft': op.estado === 'concluida',
            'bg-danger-soft': op.estado === 'cancelada',
          }"
        >
          <td class="px-4 py-3 text-sm">{{ op.id }}</td>
          <td class="px-4 py-3 text-sm">{{ op.cliente?.nombre || '—' }}</td>
          <td class="px-4 py-3 text-sm font-mono">{{ formatMonto(op) }}</td>
          <td class="px-4 py-3 text-sm">
            <span :class="estadoBadge(op.estado)">
              {{ estadoLabel(op.estado) }}
            </span>
          </td>
          <td class="px-4 py-3 text-sm">
            <PoolTimer
              v-if="op.estado === 'en_espera' || op.estado === 'en_proceso'"
              :created-at="op.created_at"
              :estado="op.estado"
              :operacion-id="op.id"
            />
            <span v-else class="text-ink-muted">—</span>
          </td>
          <td class="px-4 py-3 text-sm">
            <span class="text-xs bg-surface-muted px-2 py-1 rounded">
              {{ op.transacciones?.length || 0 }} transacciones
            </span>
          </td>
          <td class="px-4 py-3 text-sm">
            <PoolActions
              :operacion="op"
              @tomar="$emit('tomar', op.id)"
              @soltar="$emit('soltar', op.id)"
              @pagar="$emit('pagar', op.id)"
              @cancelar="$emit('cancelar', op.id)"
            />
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="operaciones.length === 0" class="text-center py-12 text-ink-muted">
      <p>No hay operaciones en el pool.</p>
    </div>
  </div>
</template>

<script setup>
import PoolTimer from './PoolTimer.vue'
import PoolActions from './PoolActions.vue'

defineProps({
  operaciones: { type: Array, required: true },
})

defineEmits(['tomar', 'soltar', 'pagar', 'cancelar'])

const formatMonto = (op) => {
  const tx = op.transacciones?.[0]
  if (tx) return `${tx.monto} ${tx.moneda?.codigo || ''}`
  return '—'
}

const estadoLabel = (estado) => {
  const map = {
    en_espera: 'En espera',
    en_proceso: 'En proceso',
    concluida: 'Concluida',
    cancelada: 'Cancelada',
  }
  return map[estado] || estado
}

const estadoBadge = (estado) => {
  const map = {
    en_espera: 'bg-warning-soft text-warning-strong px-2 py-1 rounded-full text-xs',
    en_proceso: 'bg-info-soft text-info-strong px-2 py-1 rounded-full text-xs',
    concluida: 'bg-success-soft text-success-strong px-2 py-1 rounded-full text-xs',
    cancelada: 'bg-danger-soft text-danger-strong px-2 py-1 rounded-full text-xs',
  }
  return map[estado] || 'bg-surface-muted text-heading px-2 py-1 rounded-full text-xs'
}
</script>
