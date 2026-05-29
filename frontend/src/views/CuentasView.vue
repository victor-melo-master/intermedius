<template>
  <div class="space-y-4">
    <h2 class="text-xl font-bold text-gray-800">Cuentas</h2>

    <div v-if="loading" class="text-center py-12">
      <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>
    <div v-else-if="error" class="bg-red-50 text-red-600 p-4 rounded-xl">
      {{ error }}
      <button @click="fetchCuentas" class="underline ml-2">Reintentar</button>
    </div>
    <div v-else-if="cuentas.length === 0" class="text-center py-16">
      <span class="text-5xl block mb-4">🏦</span>
      <p class="text-gray-500">No hay cuentas registradas</p>
    </div>
    <div v-else class="space-y-2">
      <div v-for="c in cuentas" :key="c.id" class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-lg">🏦</div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm truncate">{{ c.nombre || `Cuenta #${c.id}` }}</p>
          <p v-if="c.titular?.nombre" class="text-xs text-gray-500">Titular: {{ c.titular.nombre }}</p>
          <p v-if="c.banco?.nombre" class="text-xs text-gray-400">Banco: {{ c.banco.nombre }}</p>
        </div>
        <div v-if="c.moneda?.codigo" class="bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1 rounded-full">
          {{ c.moneda.codigo }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios.js'

const cuentas = ref([])
const loading = ref(false)
const error = ref('')

async function fetchCuentas() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/cuentas')
    cuentas.value = data.data || []
  } catch (err) {
    error.value = err.response?.data?.message || err.message
  } finally {
    loading.value = false
  }
}

onMounted(fetchCuentas)
</script>
