<template>
  <div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiempo en espera</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transacciones</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <tr
          v-for="op in operaciones"
          :key="op.id"
          :class="{
            'bg-yellow-50': op.estado === 'en_espera',
            'bg-blue-50': op.estado === 'en_proceso',
            'bg-green-50': op.estado === 'concluida',
            'bg-red-50': op.estado === 'cancelada',
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
            <span v-else class="text-gray-400">—</span>
          </td>
          <td class="px-4 py-3 text-sm">
            <span class="text-xs bg-gray-100 px-2 py-1 rounded">
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

    <div v-if="operaciones.length === 0" class="text-center py-12 text-gray-500">
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
    en_espera: 'bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs',
    en_proceso: 'bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs',
    concluida: 'bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs',
    cancelada: 'bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs',
  }
  return map[estado] || 'bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs'
}
</script>
