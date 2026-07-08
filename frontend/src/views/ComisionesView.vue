<template>
  <div class="space-y-4">
    <AppPageHeader title="Comisiones" action-label="Nueva comisión" @action="openForm" />

    <AppLoadingSpinner v-if="loading" />
    <AppErrorState v-else-if="error" :message="error" @retry="fetchComisiones" />
    <AppEmptyState v-else-if="comisiones.length === 0" icon="💰" message="No hay comisiones registradas" />
    <div v-else class="space-y-2">
      <div v-for="c in comisiones" :key="c.id" class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-700 font-bold text-sm">%</div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm truncate">{{ c.nombre_metodo }}</p>
          <p class="text-xs text-gray-500">{{ c.descripcion }}</p>
          <p class="text-xs text-gray-400">
            {{ c.tipo_calculo === 'porcentaje' ? c.valor + '%' : 'Bs. ' + c.valor }}
            · {{ c.moneda?.codigo || '—' }}
            <span v-if="c.cuenta">· {{ c.cuenta.alias }}</span>
          </p>
        </div>
        <div class="text-right shrink-0">
          <span v-if="c.activa" class="text-[10px] bg-green-50 text-green-600 px-2 py-0.5 rounded-full">Activa</span>
          <span v-else class="text-[10px] bg-red-50 text-red-600 px-2 py-0.5 rounded-full">Inactiva</span>
          <p class="text-[10px] text-gray-400 mt-0.5">Desde: {{ c.vigente_desde }}</p>
        </div>
        <div class="flex gap-1">
          <button @click="editComision(c)" class="text-xs text-blue-600 hover:text-blue-800 font-medium px-2 py-1 border border-blue-200 rounded-lg hover:bg-blue-50">Editar</button>
          <button v-if="c.activa" @click="deactivateComision(c)" :disabled="savingId === c.id" class="text-xs text-red-600 hover:text-red-800 font-medium px-2 py-1 border border-red-200 rounded-lg hover:bg-red-50">
            {{ savingId === c.id ? '...' : 'Desactivar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showForm" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" @click.self="closeForm">
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-md p-6 relative z-10 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-lg">{{ editingId ? 'Editar comisión' : 'Nueva comisión' }}</h3>
          <button @click="closeForm" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="submit" class="space-y-3">
          <input v-model="form.nombre_metodo" required placeholder="Nombre del método *" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <input v-model="form.descripcion" required placeholder="Descripción *" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />

          <div>
            <label class="text-sm text-gray-600 mb-1 block">Tipo de cálculo</label>
            <select v-model="form.tipo_calculo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="porcentaje">Porcentaje</option>
              <option value="monto_fijo">Monto fijo</option>
            </select>
          </div>

          <input v-model="form.valor" type="number" step="0.0001" required :placeholder="form.tipo_calculo === 'porcentaje' ? 'Ej: 0.3' : 'Ej: 5.00'" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />

          <div>
            <label class="text-sm text-gray-600 mb-1 block">Moneda</label>
            <select v-model="form.moneda_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar moneda</option>
              <option v-for="m in monedas" :key="m.id" :value="m.id">{{ m.codigo }} — {{ m.nombre }}</option>
            </select>
          </div>

          <div>
            <label class="text-sm text-gray-600 mb-1 block">Cuenta (opcional)</label>
            <select v-model="form.cuenta_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Todas las cuentas</option>
              <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.alias }} ({{ c.moneda?.codigo }})</option>
            </select>
          </div>

          <div>
            <label class="text-sm text-gray-600 mb-1 block">Vigente desde</label>
            <input v-model="form.vigente_desde" type="date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>

          <div>
            <label class="text-sm text-gray-600 mb-1 block">Vigente hasta (opcional)</label>
            <input v-model="form.vigente_hasta" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>

          <label class="flex items-center gap-2 text-sm text-gray-600">
            <input v-model="form.activa" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
            Activa
          </label>

          <AppErrorState v-if="formError" :message="formError" :retry="false" />
          <button type="submit" :disabled="saving" class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 disabled:bg-blue-300 transition flex items-center justify-center gap-2">
            <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ saving ? 'Guardando...' : (editingId ? 'Guardar cambios' : 'Crear comisión') }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../api/axios.js'
import AppPageHeader from '../components/AppPageHeader.vue'
import AppLoadingSpinner from '../components/AppLoadingSpinner.vue'
import AppErrorState from '../components/AppErrorState.vue'
import AppEmptyState from '../components/AppEmptyState.vue'

const comisiones = ref([])
const monedas = ref([])
const cuentas = ref([])
const loading = ref(false)
const error = ref('')
const showForm = ref(false)
const saving = ref(false)
const formError = ref('')
const editingId = ref(null)
const savingId = ref(null)

const form = reactive({
  nombre_metodo: '',
  descripcion: '',
  tipo_calculo: 'porcentaje',
  valor: '',
  moneda_id: '',
  cuenta_id: '',
  vigente_desde: new Date().toISOString().split('T')[0],
  vigente_hasta: '',
  activa: true,
})

async function fetchComisiones() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/configuracion/comisiones-metodo-pago')
    // El backend devuelve { data: { current_page, data: [...], ... } }
    const paginated = data.data || data
    comisiones.value = paginated.data || []  // array de items
  } catch (err) {
    error.value = err.response?.data?.message || err.message
  } finally {
    loading.value = false
  }
}

async function fetchCatalogos() {
  try {
    const [monedasRes, cuentasRes] = await Promise.all([
      api.get('/monedas'),
      api.get('/cuentas'),
    ])
    monedas.value = Array.isArray(monedasRes.data) ? monedasRes.data : (monedasRes.data.data || [])
    cuentas.value = Array.isArray(cuentasRes.data) ? cuentasRes.data : (cuentasRes.data.data || [])
  } catch {}
}

function openForm() {
  editingId.value = null
  Object.assign(form, {
    nombre_metodo: '',
    descripcion: '',
    tipo_calculo: 'porcentaje',
    valor: '',
    moneda_id: '',
    cuenta_id: '',
    vigente_desde: new Date().toISOString().split('T')[0],
    vigente_hasta: '',
    activa: true,
  })
  formError.value = ''
  showForm.value = true
}

function editComision(c) {
  editingId.value = c.id
  Object.assign(form, {
    nombre_metodo: c.nombre_metodo || '',
    descripcion: c.descripcion || '',
    tipo_calculo: c.tipo_calculo || 'porcentaje',
    valor: c.valor || '',
    moneda_id: c.moneda_id || '',
    cuenta_id: c.cuenta_id || '',
    vigente_desde: c.vigente_desde || '',
    vigente_hasta: c.vigente_hasta || '',
    activa: c.activa ?? true,
  })
  formError.value = ''
  showForm.value = true
}

function closeForm() {
  showForm.value = false
}

async function submit() {
  formError.value = ''
  saving.value = true
  try {
    const body = {
      nombre_metodo: form.nombre_metodo,
      descripcion: form.descripcion,
      tipo_calculo: form.tipo_calculo,
      valor: parseFloat(form.valor),
      moneda_id: Number(form.moneda_id),
      cuenta_id: form.cuenta_id ? Number(form.cuenta_id) : null,
      vigente_desde: form.vigente_desde,
      vigente_hasta: form.vigente_hasta || null,
      activa: form.activa,
    }
    if (editingId.value) {
      await api.put(`/configuracion/comisiones-metodo-pago/${editingId.value}`, body)
    } else {
      await api.post('/configuracion/comisiones-metodo-pago', body)
    }
    closeForm()
    fetchComisiones()
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) {
      formError.value = Object.values(data.errors).flat().join('\n')
    } else {
      formError.value = data?.message || err.message
    }
  } finally {
    saving.value = false
  }
}

async function deactivateComision(c) {
  if (!confirm(`¿Desactivar la comisión "${c.nombre_metodo}"?`)) return
  savingId.value = c.id
  try {
    await api.delete(`/configuracion/comisiones-metodo-pago/${c.id}`)
    fetchComisiones()
  } catch (err) {
    alert(err.response?.data?.message || 'Error al desactivar')
  } finally {
    savingId.value = null
  }
}

onMounted(() => {
  fetchComisiones()
  fetchCatalogos()
})
</script>
