<template>
  <div class="space-y-4">
    <AppPageHeader
      title="Clientes"
      :action-label="(auth.user?.roles?.includes('admin') || auth.user?.roles?.includes('super_admin')) ? 'Nuevo cliente' : ''"
      @action="openCreate"
    />

    <div class="relative">
      <input v-model="search" @input="debounceSearch" placeholder="Buscar por nombre o alias..."
        class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
      <span class="absolute left-3 top-2.5 text-gray-400">🔍</span>
      <button v-if="search" @click="search = ''; clientes.fetchAll()" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">✕</button>
    </div>

    <AppLoadingSpinner v-if="clientes.loading" />
    <AppErrorState v-else-if="clientes.error" :message="clientes.error" @retry="clientes.fetchAll()" />
    <AppEmptyState
      v-else-if="clientes.list.length === 0"
      icon="👥"
      :message="search ? 'Sin resultados' : 'No hay clientes'"
    />
    <div v-else class="space-y-2">
      <div v-for="c in clientes.list" :key="c.id" @click="openDetail(c)" class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition">
        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-700 font-bold text-sm">{{ c.nombre.charAt(0).toUpperCase() }}</div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm truncate">{{ c.nombre }}</p>
          <p v-if="c.alias" class="text-xs text-gray-500 truncate">{{ c.alias }}</p>
          <p v-if="c.telefono" class="text-xs text-gray-400">{{ c.telefono }}</p>
        </div>
        <div class="text-right shrink-0">
          <p class="text-sm font-bold" :class="(c.saldo_cache_usd || 0) >= 0 ? 'text-green-600' : 'text-red-600'">${{ format(c.saldo_cache_usd) }}</p>
          <span v-if="!c.activo" class="text-[10px] bg-red-50 text-red-600 px-2 py-0.5 rounded-full">Inactivo</span>
          <button v-if="auth.user?.roles?.includes('admin') || auth.user?.roles?.includes('super_admin')" @click.stop="openEdit(c)" class="mt-1 text-xs text-blue-600 hover:text-blue-800 underline">✏️ Editar</button>
        </div>
      </div>
    </div>

    <!-- Modal crear/editar cliente -->
    <div v-if="showForm" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" @click.self="showForm = false">
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-md p-6 relative z-10 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-lg">{{ editingId ? 'Editar cliente' : 'Nuevo cliente' }}</h3>
          <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="submit" class="space-y-3">
          <input v-model="form.nombre" required placeholder="Nombre *" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <input v-model="form.alias" placeholder="Alias" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <input v-model="form.telefono" placeholder="Teléfono" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <input v-model="form.email" type="email" placeholder="Email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <textarea v-model="form.notas" rows="2" placeholder="Notas" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
          <AppErrorState v-if="formError" :message="formError" :retry="false" />
          <button type="submit" :disabled="saving" class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 disabled:bg-blue-300 transition flex items-center justify-center gap-2">
            <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ saving ? 'Guardando...' : (editingId ? 'Guardar cambios' : 'Crear cliente') }}
          </button>
        </form>
      </div>
    </div>

    <!-- Modal detalle del cliente -->
    <div v-if="showDetail" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" @click.self="showDetail = false">
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-md p-6 relative z-10 max-h-[90vh] overflow-y-auto flex flex-col">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-lg">{{ detailCliente?.nombre }}</h3>
          <button @click="showDetail = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <div class="space-y-3 mb-8">
          <p v-if="detailCliente?.alias" class="text-sm text-gray-500"><span class="font-medium text-gray-700">Alias:</span> {{ detailCliente.alias }}</p>
          <p v-if="detailCliente?.telefono" class="text-sm text-gray-500"><span class="font-medium text-gray-700">Teléfono:</span> {{ detailCliente.telefono }}</p>
          <p v-if="detailCliente?.email" class="text-sm text-gray-500"><span class="font-medium text-gray-700">Email:</span> {{ detailCliente.email }}</p>
          <p v-if="detailCliente?.notas" class="text-sm text-gray-500"><span class="font-medium text-gray-700">Notas:</span> {{ detailCliente.notas }}</p>
          <p class="text-sm text-gray-500"><span class="font-medium text-gray-700">Saldo:</span> <span :class="(detailCliente?.saldo_cache_usd || 0) >= 0 ? 'text-green-600' : 'text-red-600'">${{ format(detailCliente?.saldo_cache_usd) }}</span></p>
        </div>

        <!-- Cuentas bancarias -->
        <div class="border-t-2 border-gray-300 pt-6 pb-2">
          <div class="flex items-center justify-between mb-4">
            <h4 class="font-semibold text-gray-800 text-base">Cuentas bancarias</h4>
            <button v-if="auth.user?.roles?.includes('admin') || auth.user?.roles?.includes('super_admin')" @click="openCuentaForm" class="text-xs bg-blue-600 text-white px-2 py-1 rounded-lg hover:bg-blue-700">+ Agregar cuenta</button>
          </div>

          <AppLoadingSpinner v-if="loadingCuentas" />
          <AppEmptyState v-else-if="clienteCuentas.length === 0" icon="🏦" message="No hay cuentas registradas." />
          <div v-else class="space-y-3">
            <div v-for="cu in clienteCuentas" :key="cu.id" class="bg-gray-50 border border-gray-200 rounded-lg p-3">
              <p class="font-medium text-sm">{{ cu.alias }}</p>
              <p class="text-xs text-gray-500">{{ cu.banco?.nombre }} — {{ cu.moneda?.codigo }}</p>
              <p v-if="cu.numero_cuenta" class="text-xs text-gray-400">{{ cu.numero_cuenta }}</p>
            </div>
          </div>
        </div>

        <!-- Historial de transacciones -->
        <div class="border-t-2 border-gray-300 pt-6 pb-2 mt-4">
          <div class="flex items-center justify-between mb-4">
            <h4 class="font-semibold text-gray-800 text-base">Historial de transacciones</h4>
            <button @click="exportarPDF" :disabled="exportando" class="text-xs bg-red-600 text-white px-2 py-1 rounded-lg hover:bg-red-700">
              {{ exportando ? 'Generando...' : '📄 PDF' }}
            </button>
          </div>

          <!-- Filtros -->
          <div class="grid grid-cols-2 gap-2 mb-3">
            <div>
              <label class="text-[10px] text-gray-400">Desde</label>
              <input v-model="historialFiltros.fecha_desde" type="date" class="w-full px-2 py-1 text-xs border border-gray-300 rounded" />
            </div>
            <div>
              <label class="text-[10px] text-gray-400">Hasta</label>
              <input v-model="historialFiltros.fecha_hasta" type="date" class="w-full px-2 py-1 text-xs border border-gray-300 rounded" />
            </div>
          </div>
          <div class="mb-3">
            <label class="text-[10px] text-gray-400">Tipo</label>
            <select v-model="historialFiltros.tipo_codigo" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
              <option value="">Todos</option>
              <option value="compra_usd">Compra USD</option>
              <option value="venta_usd">Venta USD</option>
              <option value="intermediada">Intermediada</option>
            </select>
          </div>
          <button @click="cargarHistorial(1)" :disabled="loadingHistorial" class="w-full text-xs bg-blue-600 text-white py-1.5 rounded hover:bg-blue-700 mb-3">
            {{ loadingHistorial ? 'Cargando...' : 'Buscar' }}
          </button>

          <!-- Tabla de operaciones -->
          <div v-if="historial.length > 0" class="overflow-x-auto">
            <table class="w-full text-xs">
              <thead>
                <tr class="text-left text-gray-400 border-b">
                  <th class="py-1">ID</th>
                  <th class="py-1">Fecha</th>
                  <th class="py-1">Tipo</th>
                  <th class="py-1 text-right">USD</th>
                  <th class="py-1 text-right">VES</th>
                  <th class="py-1 text-right">Tasa</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="op in historial" :key="op.id" class="border-b border-gray-50">
                  <td class="py-1">#{{ op.id }}</td>
                  <td class="py-1">{{ formatFecha(op.fecha) }}</td>
                  <td class="py-1">{{ op.tipo_operacion?.nombre || '—' }}</td>
                  <td class="py-1 text-right">{{ formatMonto(op, 'USD') }}</td>
                  <td class="py-1 text-right">{{ formatMonto(op, 'VES') }}</td>
                  <td class="py-1 text-right">{{ op.tasa_aplicada ? parseFloat(op.tasa_aplicada).toFixed(2) : '—' }}</td>
                </tr>
              </tbody>
            </table>
            <!-- Paginación simple -->
            <div v-if="historialPaginacion.last_page > 1" class="flex justify-between items-center mt-2 text-xs">
              <button @click="cargarHistorial(historialPaginacion.current_page - 1)" :disabled="!historialPaginacion.prev_page_url" class="text-blue-600 disabled:text-gray-300">&lt; Anterior</button>
              <span class="text-gray-500">Pág {{ historialPaginacion.current_page }} / {{ historialPaginacion.last_page }}</span>
              <button @click="cargarHistorial(historialPaginacion.current_page + 1)" :disabled="!historialPaginacion.next_page_url" class="text-blue-600 disabled:text-gray-300">Siguiente &gt;</button>
            </div>
          </div>
          <div v-else-if="!loadingHistorial && historialCargado" class="text-xs text-gray-400 py-2">Sin operaciones.</div>
        </div>
      </div>
    </div>

    <!-- Modal crear cuenta para cliente -->
    <div v-if="showCuentaForm" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" @click.self="showCuentaForm = false">
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-md p-6 relative z-10 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-lg">Agregar cuenta para {{ detailCliente?.nombre }}</h3>
          <button @click="showCuentaForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="submitCuenta" class="space-y-3">
          <div>
            <label class="text-sm text-gray-600 mb-1 block">Banco</label>
            <select v-model="cuentaForm.banco_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar banco</option>
              <option v-for="b in bancos.list" :key="b.id" :value="b.id">{{ b.nombre }} ({{ b.codigo }})</option>
            </select>
          </div>
          <div>
            <label class="text-sm text-gray-600 mb-1 block">Moneda</label>
            <select v-model="cuentaForm.moneda_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar moneda</option>
              <option v-for="m in tasas.monedas" :key="m.id" :value="m.id">{{ m.codigo }} — {{ m.nombre }}</option>
            </select>
          </div>
          <input v-model="cuentaForm.alias" required placeholder="Alias * (ej: Banesco USD)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <select v-model="cuentaForm.tipo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="">Tipo de cuenta *</option>
            <option value="banco">Banco</option>
            <option value="zelle">Zelle</option>
            <option value="wallet">Wallet</option>
            <option value="efectivo">Efectivo</option>
            <option value="otro">Otro</option>
          </select>
          <input v-model="cuentaForm.numero_cuenta" placeholder="Número de cuenta (opcional)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <textarea v-model="cuentaForm.notas" rows="2" placeholder="Notas (opcional)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
          <label class="flex items-center gap-2 text-sm text-gray-600">
            <input v-model="cuentaForm.activa" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
            Activa
          </label>
          <AppErrorState v-if="cuentaFormError" :message="cuentaFormError" :retry="false" />
          <button type="submit" :disabled="savingCuenta" class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 disabled:bg-blue-300 transition flex items-center justify-center gap-2">
            <span v-if="savingCuenta" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ savingCuenta ? 'Guardando...' : 'Crear cuenta' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useClientesStore } from '../stores/clientes.js'
import { useAuthStore } from '../stores/auth.js'
import { useBancosStore } from '../stores/bancos.js'
import { useTasasStore } from '../stores/tasas.js'
import api from '../api/axios.js'
import AppPageHeader from '../components/AppPageHeader.vue'
import AppLoadingSpinner from '../components/AppLoadingSpinner.vue'
import AppErrorState from '../components/AppErrorState.vue'
import AppEmptyState from '../components/AppEmptyState.vue'

const clientes = useClientesStore()
const auth = useAuthStore()
const bancos = useBancosStore()
const tasas = useTasasStore()

const search = ref('')
const showForm = ref(false)
const saving = ref(false)
const formError = ref('')
const editingId = ref(null)
let debounce = null

const form = reactive({ nombre: '', alias: '', telefono: '', email: '', notas: '' })

const showDetail = ref(false)
const detailCliente = ref(null)
const clienteCuentas = ref([])
const loadingCuentas = ref(false)

const showCuentaForm = ref(false)
const savingCuenta = ref(false)
const cuentaFormError = ref('')
const cuentaForm = reactive({
  cliente_id: '',
  banco_id: '',
  moneda_id: '',
  alias: '',
  tipo: '',
  numero_cuenta: '',
  notas: '',
  activa: true,
})

function debounceSearch() {
  clearTimeout(debounce)
  debounce = setTimeout(() => clientes.fetchAll(search.value), 400)
}

function format(n) {
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0)
}

function openCreate() {
  editingId.value = null
  Object.assign(form, { nombre: '', alias: '', telefono: '', email: '', notas: '' })
  formError.value = ''
  showForm.value = true
}

function openEdit(c) {
  editingId.value = c.id
  Object.assign(form, {
    nombre: c.nombre || '',
    alias: c.alias || '',
    telefono: c.telefono || '',
    email: c.email || '',
    notas: c.notas || '',
  })
  formError.value = ''
  showForm.value = true
}

async function submit() {
  formError.value = ''
  saving.value = true
  try {
    const body = { nombre: form.nombre }
    if (form.alias) body.alias = form.alias
    if (form.telefono) body.telefono = form.telefono
    if (form.email) body.email = form.email
    if (form.notas) body.notas = form.notas
    if (editingId.value) {
      await clientes.update(editingId.value, body)
    } else {
      await clientes.create(body)
    }
    showForm.value = false
    clientes.fetchAll(search.value)
    Object.assign(form, { nombre: '', alias: '', telefono: '', email: '', notas: '' })
  } catch (err) {
    formError.value = err.response?.data?.message || err.message
  } finally {
    saving.value = false
  }
}

async function openDetail(c) {
  detailCliente.value = c
  showDetail.value = true
  loadingCuentas.value = true
  try {
    const { data } = await api.get(`/clientes/${c.id}/cuentas`)
    clienteCuentas.value = Array.isArray(data) ? data : (data.data || [])
  } catch {
    clienteCuentas.value = []
  } finally {
    loadingCuentas.value = false
  }
}

function openCuentaForm() {
  cuentaFormError.value = ''
  Object.assign(cuentaForm, {
    cliente_id: detailCliente.value.id,
    banco_id: '',
    moneda_id: '',
    alias: '',
    tipo: '',
    numero_cuenta: '',
    notas: '',
    activa: true,
  })
  bancos.fetchAll()
  tasas.fetchMonedas()
  showCuentaForm.value = true
}

async function submitCuenta() {
  cuentaFormError.value = ''
  savingCuenta.value = true
  try {
    const body = {
      cliente_id: Number(cuentaForm.cliente_id),
      banco_id: Number(cuentaForm.banco_id),
      moneda_id: Number(cuentaForm.moneda_id),
      alias: cuentaForm.alias,
      tipo: cuentaForm.tipo,
      activa: cuentaForm.activa,
    }
    if (cuentaForm.numero_cuenta) body.numero_cuenta = cuentaForm.numero_cuenta
    if (cuentaForm.notas) body.notas = cuentaForm.notas
    await api.post('/cuentas', body)
    showCuentaForm.value = false
    const { data } = await api.get(`/clientes/${detailCliente.value.id}/cuentas`)
    clienteCuentas.value = Array.isArray(data) ? data : (data.data || [])
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) {
      cuentaFormError.value = Object.values(data.errors).flat().join('\n')
    } else {
      cuentaFormError.value = data?.message || err.message
    }
  } finally {
    savingCuenta.value = false
  }
}

// ── Historial de transacciones ──
const historial = ref([])
const historialPaginacion = ref({})
const historialCargado = ref(false)
const loadingHistorial = ref(false)
const exportando = ref(false)
const historialFiltros = reactive({
  fecha_desde: '',
  fecha_hasta: '',
  tipo_codigo: '',
})

async function cargarHistorial(page = 1) {
  if (!detailCliente.value) return
  loadingHistorial.value = true
  try {
    const params = { page }
    if (historialFiltros.fecha_desde) params.fecha_desde = historialFiltros.fecha_desde
    if (historialFiltros.fecha_hasta) params.fecha_hasta = historialFiltros.fecha_hasta
    if (historialFiltros.tipo_codigo) params.tipo_codigo = historialFiltros.tipo_codigo

    const { data } = await api.get(`/clientes/${detailCliente.value.id}/operaciones`, { params })
    historial.value = data.data || []
    historialPaginacion.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      prev_page_url: data.prev_page_url,
      next_page_url: data.next_page_url,
    }
    historialCargado.value = true
  } catch {
    historial.value = []
  } finally {
    loadingHistorial.value = false
  }
}

function formatFecha(fecha) {
  if (!fecha) return '—'
  const d = new Date(fecha)
  return d.toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatMonto(op, moneda) {
  const mov = op.movimientos?.find(m => m.moneda?.codigo === moneda)
  if (!mov) return '—'
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Math.abs(parseFloat(mov.monto)))
}

async function exportarPDF() {
  if (!detailCliente.value) return
  exportando.value = true
  try {
    const token = localStorage.getItem('token')
    const params = {}
    if (historialFiltros.fecha_desde) params.fecha_desde = historialFiltros.fecha_desde
    if (historialFiltros.fecha_hasta) params.fecha_hasta = historialFiltros.fecha_hasta
    if (historialFiltros.tipo_codigo) params.tipo_codigo = historialFiltros.tipo_codigo

    // Usar axios directamente (no la instancia api) para evitar interceptores que rompan el blob
    const axios = (await import('axios')).default
    const response = await axios.post(
      `${import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'}/clientes/${detailCliente.value.id}/operaciones/exportar`,
      params,
      {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/pdf',
        },
        responseType: 'blob',
      }
    )

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `operaciones_${detailCliente.value.nombre}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Error al exportar PDF:', err)
    alert('Error al generar el PDF. Intenta de nuevo.')
  } finally {
    exportando.value = false
  }
}

watch(showDetail, (val) => {
  if (val && detailCliente.value) {
    cargarHistorial()
  }
})

onMounted(() => clientes.fetchAll())
</script>
