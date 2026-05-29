<template>
  <div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center gap-3">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">Operación #{{ ops.detail?.id }}</h2>
    </div>

    <div v-if="ops.loading" class="text-center py-12">
      <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>
    <div v-else-if="ops.error" class="bg-red-50 text-red-600 p-4 rounded-xl">{{ ops.error }}</div>
    <div v-else-if="ops.detail" class="space-y-4">
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-500">#{{ ops.detail.id }}</span>
          <span class="px-3 py-1 rounded-full text-xs font-bold"
            :class="ops.detail.estatus === 'verificado' ? 'bg-green-50 text-green-700' :
                     ops.detail.estatus === 'en_revision' ? 'bg-orange-50 text-orange-700' :
                     'bg-gray-50 text-gray-700'">
            {{ ops.detail.estatus?.replace('_', ' ') }}
          </span>
        </div>
        <p class="font-semibold text-lg">{{ ops.detail.tipo_operacion?.nombre || 'Operación' }}</p>
        <p v-if="ops.detail.cliente?.nombre" class="text-sm text-gray-500">Cliente: {{ ops.detail.cliente.nombre }}</p>
        <p class="text-sm text-gray-400">{{ ops.detail.fecha }}</p>
        <p v-if="ops.detail.referencia" class="text-sm text-gray-500">Ref: {{ ops.detail.referencia }}</p>
        <p v-if="ops.detail.descripcion" class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">{{ ops.detail.descripcion }}</p>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Métricas</h3>
        <div class="grid grid-cols-2 gap-4">
          <div><p class="text-xs text-gray-500">Ganancia neta USD</p><p class="text-lg font-bold" :class="(ops.detail.ganancia_neta_usd || 0) >= 0 ? 'text-green-600' : 'text-red-600'">${{ format(ops.detail.ganancia_neta_usd) }}</p></div>
          <div><p class="text-xs text-gray-500">Tasa aplicada</p><p class="text-lg font-bold text-blue-600">{{ ops.detail.tasa_aplicada }}</p></div>
        </div>
      </div>

      <button v-if="auth.isAdmin && ops.detail.estatus !== 'verificado'" @click="verificar" :disabled="verifying"
        class="w-full bg-green-600 hover:bg-green-700 disabled:bg-green-300 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
        <span v-if="verifying" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
        {{ verifying ? 'Verificando...' : 'Verificar operación' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOperacionesStore } from '../stores/operaciones.js'
import { useAuthStore } from '../stores/auth.js'

const route = useRoute()
const router = useRouter()
const ops = useOperacionesStore()
const auth = useAuthStore()
const verifying = ref(false)

function format(n) {
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0)
}

async function verificar() {
  verifying.value = true
  try {
    await ops.verificar(route.params.id)
    await ops.fetchOne(route.params.id)
  } catch {}
  verifying.value = false
}

onMounted(() => ops.fetchOne(route.params.id))
</script>
