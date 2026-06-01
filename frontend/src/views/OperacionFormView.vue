<template>
  <div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center gap-3 mb-2">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">Nueva Operación</h2>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <!-- Tipo y Fecha -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Tipo y fecha</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Tipo de operación *</label>
            <select v-model="form.tipo_codigo" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Selecciona un tipo</option>
              <option value="venta_usd">Venta de USD</option>
              <option value="compra_usd">Compra de USD</option>
              <option value="cambio">Cambio de moneda</option>
              <option value="gasto">Gasto operativo</option>
              <option value="comision">Comisión</option>
              <option value="traslado">Traslado interno</option>
              <option value="ajuste">Ajuste contable</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Fecha *</label>
            <input v-model="form.fecha" type="date" required :max="today"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
        </div>
      </div>

      <!-- Tasa y referencia -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Tasa y referencia</h3>
        <div v-if="tasas.vigentes.length" class="bg-blue-50 rounded-lg p-3 flex flex-wrap gap-4">
          <div v-for="t in tasas.vigentes" :key="t.id" class="text-sm text-blue-700">
            <span class="font-medium">{{ t.moneda_base?.codigo }}/{{ t.moneda_cotizada?.codigo }}:</span>
            C: {{ t.tasa_compra }} · V: {{ t.tasa_venta }}
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Tasa aplicada</label>
            <input v-model="form.tasa_aplicada" type="number" step="0.0001" placeholder="Ej: 36.5000"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Referencia</label>
            <input v-model="form.referencia" placeholder="Ej: TRF-001"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
        </div>
        <div>
          <label class="block text-sm text-gray-600 mb-1">Descripción</label>
          <textarea v-model="form.descripcion" rows="2" placeholder="Descripción opcional"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
        </div>
      </div>

      <!-- Cliente -->
      <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h3 class="font-semibold text-gray-700 mb-3">Cliente (opcional)</h3>
        <select v-model="form.cliente_id"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
          <option value="">Sin cliente</option>
          <option v-for="c in clientes.list" :key="c.id" :value="c.id">{{ c.nombre }}</option>
        </select>
      </div>

      <!-- Movimientos contables -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="font-semibold text-gray-700">Movimientos contables *</h3>
            <p class="text-xs text-gray-400 mt-0.5">Monto negativo = débito (salida). Positivo = crédito (entrada).</p>
          </div>
          <button type="button" @click="addMovimiento"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium px-3 py-1.5 border border-blue-200 rounded-lg hover:bg-blue-50">
            + Agregar
          </button>
        </div>

        <div v-if="loadingCuentas" class="text-center py-6 text-gray-400 text-sm">Cargando cuentas...</div>
        <div v-else-if="cuentas.length === 0" class="bg-amber-50 border border-amber-200 text-amber-700 text-sm p-4 rounded-lg">
          ⚠️ No hay cuentas configuradas. Crea al menos una cuenta en la sección <strong>Cuentas</strong> antes de registrar operaciones.
        </div>

        <div v-else class="space-y-3">
          <div v-for="(mov, i) in form.movimientos" :key="i"
            class="border border-gray-200 rounded-lg p-3 bg-gray-50 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Movimiento {{ i + 1 }}</span>
              <button type="button" v-if="form.movimientos.length > 1" @click="removeMovimiento(i)"
                class="text-xs text-red-400 hover:text-red-600 font-medium">✕ Quitar</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
              <div>
                <label class="block text-xs text-gray-500 mb-1">Cuenta *</label>
                <select v-model="mov.cuenta_id" required
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                  <option value="">Seleccionar</option>
                  <option v-for="c in cuentas" :key="c.id" :value="c.id">
                    {{ c.alias }} ({{ c.moneda?.codigo }})
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Monto *</label>
                <input v-model="mov.monto" type="number" step="0.01" required placeholder="-100.00"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Tasa a USD *</label>
                <input v-model="mov.tasa_a_usd" type="number" step="0.0001" required placeholder="1.0000"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 text-sm p-4 rounded-xl whitespace-pre-line">{{ error }}</div>

      <button type="submit" :disabled="saving || cuentas.length === 0"
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
import { useAuthStore } from '../stores/auth.js'
import api from '../api/axios.js'

const router = useRouter()
const ops = useOperacionesStore()
const tasas = useTasasStore()
const clientes = useClientesStore()
const auth = useAuthStore()

const saving = ref(false)
const error = ref('')
const cuentas = ref([])
const loadingCuentas = ref(true)

const today = new Date().toISOString().split('T')[0]

const form = reactive({
  fecha: today,
  tipo_codigo: '',
  cliente_id: '',
  tasa_aplicada: '',
  referencia: '',
  descripcion: '',
  movimientos: [{ cuenta_id: '', monto: '', tasa_a_usd: '' }],
})

function addMovimiento() {
  form.movimientos.push({ cuenta_id: '', monto: '', tasa_a_usd: '' })
}

function removeMovimiento(i) {
  form.movimientos.splice(i, 1)
}

async function submit() {
  error.value = ''
  saving.value = true
  try {
    const body = {
      fecha: form.fecha,
      tipo_codigo: form.tipo_codigo,
      operador_id: auth.user.id,
      movimientos: form.movimientos.map(m => ({
        cuenta_id: Number(m.cuenta_id),
        monto: parseFloat(m.monto),
        tasa_a_usd: parseFloat(m.tasa_a_usd),
      })),
    }
    if (form.cliente_id) body.cliente_id = Number(form.cliente_id)
    if (form.tasa_aplicada) body.tasa_aplicada = parseFloat(form.tasa_aplicada)
    if (form.referencia) body.referencia = form.referencia
    if (form.descripcion) body.descripcion = form.descripcion

    await ops.create(body)
    router.push('/operaciones')
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) {
      error.value = Object.values(data.errors).flat().join('\n')
    } else {
      error.value = data?.message || err.message
    }
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  tasas.fetchVigentes()
  clientes.fetchAll()
  try {
    const { data } = await api.get('/cuentas')
    cuentas.value = Array.isArray(data) ? data : (data.data || [])
  } catch {
    cuentas.value = []
  } finally {
    loadingCuentas.value = false
  }
})
</script>
