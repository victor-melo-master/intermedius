<template>
  <div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center gap-3 mb-2">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">Nueva Operación</h2>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Tipo de operación</h3>
        <select v-model="form.tipo_operacion_id" required
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
          <option value="">Selecciona un tipo</option>
          <option value="1">Venta USD</option>
          <option value="2">Compra USD</option>
          <option value="3">Cambio multimoneda</option>
          <option value="4">Gasto</option>
        </select>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Cliente</h3>
        <select v-model="form.cliente_id"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
          <option value="">Sin cliente</option>
          <option v-for="c in clientes.list" :key="c.id" :value="c.id">{{ c.nombre }}</option>
        </select>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Tasa aplicada</h3>
        <div v-if="tasas.vigentes.length" class="bg-blue-50 rounded-lg p-4 space-y-2">
          <p class="text-sm font-medium text-blue-800">Tasas del día</p>
          <div v-for="t in tasas.vigentes" :key="t.id" class="flex gap-4 text-sm text-blue-700">
            <span>{{ t.moneda_base?.codigo }}/{{ t.moneda_cotizada?.codigo }}:</span>
            <span>C: {{ t.tasa_compra }}</span>
            <span class="font-semibold">V: {{ t.tasa_venta }}</span>
          </div>
        </div>
        <div v-else class="bg-red-50 text-red-700 text-sm p-4 rounded-lg">
          Sin tasa publicada hoy. Se usará la última conocida.
        </div>
        <input v-model="form.tasa_aplicada" type="number" step="0.0001" required placeholder="Tasa a aplicar"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
        <p v-if="tasas.vigentes.length && !form.tasa_aplicada" class="text-xs text-gray-500">
          Sugerida: {{ tasas.vigentes[0].tasa_venta }}
        </p>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Detalles adicionales</h3>
        <input v-model="form.referencia" placeholder="Referencia (opcional)"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
        <textarea v-model="form.descripcion" rows="3" placeholder="Descripción (opcional)"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
      </div>

      <div v-if="error" class="bg-red-50 text-red-600 text-sm p-4 rounded-xl">{{ error }}</div>

      <button type="submit" :disabled="saving"
        class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
        <span v-if="saving" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
        {{ saving ? 'Registrando...' : 'Registrar operación' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useOperacionesStore } from '../stores/operaciones.js'
import { useTasasStore } from '../stores/tasas.js'
import { useClientesStore } from '../stores/clientes.js'

const router = useRouter()
const ops = useOperacionesStore()
const tasas = useTasasStore()
const clientes = useClientesStore()
const saving = ref(false)
const error = ref('')

const form = reactive({
  tipo_operacion_id: '',
  cliente_id: '',
  tasa_aplicada: '',
  referencia: '',
  descripcion: '',
})

async function submit() {
  error.value = ''
  saving.value = true
  try {
    const body = {
      tipo_operacion_id: Number(form.tipo_operacion_id),
      tasa_aplicada: parseFloat(form.tasa_aplicada),
    }
    if (form.cliente_id) body.cliente_id = Number(form.cliente_id)
    if (form.referencia) body.referencia = form.referencia
    if (form.descripcion) body.descripcion = form.descripcion

    await ops.create(body)
    router.push('/operaciones')
  } catch (err) {
    error.value = err.response?.data?.message || err.message
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  tasas.fetchVigentes()
  clientes.fetchAll()
})
</script>
