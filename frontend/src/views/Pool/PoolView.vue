<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Pool de Pagadores</h1>
        <p class="text-sm text-gray-500">
          Gestión FIFO de operaciones en espera y en proceso
        </p>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-sm text-gray-500">
          Actualizado: {{ updatedAt }}
        </span>
        <button
          @click="store.fetchPool()"
          class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded"
        >
          ↻ Refrescar
        </button>
      </div>
    </div>

    <div class="grid grid-cols-4 gap-4 mb-6">
      <div class="bg-white p-4 rounded-lg shadow border-l-4 border-yellow-400">
        <div class="text-sm text-gray-500">En Espera</div>
        <div class="text-2xl font-bold">{{ store.enEspera.length }}</div>
      </div>
      <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-400">
        <div class="text-sm text-gray-500">En Proceso</div>
        <div class="text-2xl font-bold">{{ store.enProceso.length }}</div>
      </div>
      <div class="bg-white p-4 rounded-lg shadow border-l-4 border-green-400">
        <div class="text-sm text-gray-500">Concluidas</div>
        <div class="text-2xl font-bold">{{ store.concluidas.length }}</div>
      </div>
      <div class="bg-white p-4 rounded-lg shadow border-l-4 border-gray-400">
        <div class="text-sm text-gray-500">Total</div>
        <div class="text-2xl font-bold">{{ store.operaciones.length }}</div>
      </div>
    </div>

    <div v-if="store.loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="store.error" class="bg-red-50 text-red-700 p-4 rounded-lg">
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
