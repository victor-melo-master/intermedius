<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-heading">Pool de Pagadores</h1>
        <p class="text-sm text-ink-soft">
          Gestión FIFO de operaciones en espera y en proceso
        </p>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-sm text-ink-soft">
          Actualizado: {{ updatedAt }}
        </span>
        <button
          @click="store.fetchPool()"
          class="px-3 py-1 text-sm bg-surface-muted hover:bg-surface-muted rounded"
        >
          ↻ Refrescar
        </button>
      </div>
    </div>

    <div class="grid grid-cols-4 gap-4 mb-6">
      <div class="bg-surface p-4 rounded-lg shadow border-l-4 border-warning">
        <div class="text-sm text-ink-soft">En Espera</div>
        <div class="text-2xl font-bold">{{ store.enEspera.length }}</div>
      </div>
      <div class="bg-surface p-4 rounded-lg shadow border-l-4 border-info">
        <div class="text-sm text-ink-soft">En Proceso</div>
        <div class="text-2xl font-bold">{{ store.enProceso.length }}</div>
      </div>
      <div class="bg-surface p-4 rounded-lg shadow border-l-4 border-success">
        <div class="text-sm text-ink-soft">Concluidas</div>
        <div class="text-2xl font-bold">{{ store.concluidas.length }}</div>
      </div>
      <div class="bg-surface p-4 rounded-lg shadow border-l-4 border-edge-strong">
        <div class="text-sm text-ink-soft">Total</div>
        <div class="text-2xl font-bold">{{ store.operaciones.length }}</div>
      </div>
    </div>

    <div v-if="store.loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gold"></div>
    </div>

    <div v-else-if="store.error" class="bg-danger-soft text-danger-strong p-4 rounded-lg">
      {{ store.error }}
    </div>

    <PoolList
      v-else
      :operaciones="store.operaciones"
      @tomar="store.tomar"
      @soltar="store.soltar"
      @pagar="store.pagar"
      @cancelar="store.cancelar"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePoolStore } from '@/stores/pool'
import PoolList from '@/components/pool/PoolList.vue'

const store = usePoolStore()

const updatedAt = computed(() => {
  return new Date().toLocaleTimeString()
})

onMounted(() => {
  store.init()
})
</script>
