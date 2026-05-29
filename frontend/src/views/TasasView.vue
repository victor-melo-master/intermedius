<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-xl font-bold text-gray-800">Tasas del día</h2>
      <div class="flex gap-2">
        <button @click="tasas.fetchVigentes()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50">🔄 Actualizar</button>
        <button v-if="auth.isAdmin" @click="showForm = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">+ Publicar tasa</button>
      </div>
    </div>

    <div v-if="tasas.loading" class="text-center py-12">
      <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>
    <div v-else-if="tasas.error" class="bg-red-50 text-red-600 p-4 rounded-xl">
      {{ tasas.error }}
      <button @click="tasas.fetchVigentes()" class="underline ml-2">Reintentar</button>
    </div>
    <div v-else-if="tasas.vigentes.length === 0" class="text-center py-16">
      <span class="text-5xl block mb-4">📈</span>
      <p class="text-gray-500">No hay tasas publicadas hoy</p>
      <p v-if="auth.isAdmin" class="text-sm text-gray-400 mt-1">Usa + para publicar la tasa del día</p>
    </div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="t in tasas.vigentes" :key="t.id" class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <span class="bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1 rounded-full">{{ t.moneda_base?.codigo }}/{{ t.moneda_cotizada?.codigo }}</span>
          <span v-if="!t.vigente_hasta" class="bg-green-50 text-green-700 text-xs font-bold px-2 py-1 rounded-full flex items-center gap-1">
            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Vigente
          </span>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-500 mb-1">Compra</p>
            <p class="text-xl font-bold text-teal-600">{{ format(t.tasa_compra) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Venta</p>
            <p class="text-xl font-bold text-blue-600">{{ format(t.tasa_venta) }}</p>
          </div>
        </div>
        <p v-if="t.notas" class="text-xs text-gray-400 mt-3 pt-3 border-t border-gray-100">{{ t.notas }}</p>
        <p class="text-[11px] text-gray-400 mt-2">Desde: {{ t.vigente_desde }}</p>
      </div>
    </div>

    <!-- Publicar tasa modal -->
    <div v-if="showForm" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" @click.self="showForm = false">
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-md p-6 relative z-10">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-lg">Publicar tasa del día</h3>
          <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="submitTasa" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <select v-model="tasaForm.moneda_base_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Base</option>
              <option v-for="m in tasas.monedas" :key="m.id" :value="m.id">{{ m.codigo }}</option>
            </select>
            <select v-model="tasaForm.moneda_cotizada_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Cotizada</option>
              <option v-for="m in tasas.monedas" :key="m.id" :value="m.id">{{ m.codigo }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <input v-model="tasaForm.tasa_compra" type="number" step="0.0001" required placeholder="Tasa compra" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
            <input v-model="tasaForm.tasa_venta" type="number" step="0.0001" required placeholder="Tasa venta" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <input v-model="tasaForm.notas" placeholder="Notas (opcional)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <div v-if="formError" class="bg-red-50 text-red-600 text-sm p-3 rounded-lg">{{ formError }}</div>
          <button type="submit" :disabled="saving" class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 disabled:bg-blue-300 transition flex items-center justify-center gap-2">
            <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ saving ? 'Publicando...' : 'Publicar tasa' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useTasasStore } from '../stores/tasas.js'
import { useAuthStore } from '../stores/auth.js'

const tasas = useTasasStore()
const auth = useAuthStore()
const showForm = ref(false)
const saving = ref(false)
const formError = ref('')

const tasaForm = reactive({
  moneda_base_id: '',
  moneda_cotizada_id: '',
  tasa_compra: '',
  tasa_venta: '',
  notas: '',
})

function format(n) {
  return new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 4 }).format(n)
}

async function submitTasa() {
  formError.value = ''
  saving.value = true
  try {
    await tasas.publicar({
      moneda_base_id: Number(tasaForm.moneda_base_id),
      moneda_cotizada_id: Number(tasaForm.moneda_cotizada_id),
      tasa_compra: parseFloat(tasaForm.tasa_compra),
      tasa_venta: parseFloat(tasaForm.tasa_venta),
      ...(tasaForm.notas ? { notas: tasaForm.notas } : {}),
    })
    showForm.value = false
    tasas.fetchVigentes()
  } catch (err) {
    formError.value = err.response?.data?.message || err.message
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  tasas.fetchVigentes()
  tasas.fetchMonedas()
})
</script>
