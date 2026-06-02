<template>
  <div class="max-w-2xl mx-auto space-y-4 pb-10">
    <div class="flex items-center gap-3 mb-2">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">{{ titulo }}</h2>
    </div>

    <!-- Éxito -->
    <div v-if="successRef" class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center space-y-4">
      <div class="text-4xl">✅</div>
      <p class="text-green-700 font-semibold">Operación registrada {{ successRef }}</p>
      <div class="flex flex-col sm:flex-row gap-2 justify-center">
        <button @click="registrarOtra" class="px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">Registrar otra</button>
        <button @click="$router.push('/operaciones')" class="px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50">Ver operaciones</button>
      </div>
    </div>

    <form v-else @submit.prevent="submit" class="space-y-4">
      <!-- Tipo -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Tipo de operación *</h3>
        <div class="grid grid-cols-2 gap-3">
          <button type="button" @click="setTipo('compra')"
            class="py-3 rounded-xl border-2 font-semibold text-sm transition"
            :class="form.tipo === 'compra' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'">
            Compra de {{ monedaSel }}
            <span class="block text-[11px] font-normal mt-0.5">La casa compra {{ monedaSel }}, paga {{ quoteSimbolo }}.</span>
          </button>
          <button type="button" @click="setTipo('venta')"
            class="py-3 rounded-xl border-2 font-semibold text-sm transition"
            :class="form.tipo === 'venta' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'">
            Venta de {{ monedaSel }}
            <span class="block text-[11px] font-normal mt-0.5">La casa vende {{ monedaSel }}, recibe {{ quoteSimbolo }}.</span>
          </button>
        </div>
        <div>
          <label class="block text-sm text-gray-600 mb-1">Fecha *</label>
          <input v-model="form.fecha" type="date" required :max="today"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
        </div>
      </div>

      <!-- Cliente -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Cliente</h3>
        <div v-if="form.cliente_id" class="flex items-center justify-between bg-blue-50 rounded-lg px-3 py-2">
          <span class="text-sm text-blue-700 font-medium">{{ form.cliente_nombre }}</span>
          <button type="button" @click="clearCliente" class="text-xs text-blue-500 hover:text-blue-700">Cambiar</button>
        </div>
        <div v-else class="relative">
          <input v-model="clienteSearch" @input="onClienteSearch" type="text" placeholder="Buscar cliente por nombre..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
          <div v-if="clienteSearch && (clienteResults.length || !searchingCliente)"
            class="absolute z-20 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
            <button v-for="c in clienteResults" :key="c.id" type="button" @click="selectCliente(c)"
              class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 border-b border-gray-100 last:border-0">
              {{ c.nombre }} <span v-if="c.alias" class="text-gray-400">· {{ c.alias }}</span>
            </button>
            <button type="button" @click="crearClienteInline"
              class="w-full text-left px-4 py-2.5 text-sm text-blue-600 font-medium hover:bg-blue-50">
              + Crear cliente "{{ clienteSearch }}"
            </button>
          </div>
        </div>
      </div>

      <!-- Monto y tasa -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Monto y tasa</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Monto {{ monedaSel }} *</label>
            <input v-model="form.monto_usd" type="number" step="0.01" inputmode="decimal" required placeholder="100.00"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Tasa {{ form.tipo === 'venta' ? 'de venta' : 'de compra' }} ({{ parStr }}) *</label>
            <input v-model="form.tasa" type="number" step="0.0001" inputmode="decimal" required placeholder="36.5000"
              class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 outline-none"
              :class="tasaDesfavorable ? 'border-amber-400 focus:ring-amber-400' : 'border-gray-300 focus:ring-blue-500'" />
            <p v-if="tasaSugerida" class="text-xs text-gray-400 mt-1">Sugerida del día: <span class="font-medium text-gray-600">{{ tasaSugerida }}</span></p>
            <p v-else class="text-xs text-amber-500 mt-1">No hay tasa {{ parStr }} publicada hoy.</p>
          </div>
        </div>
        <div v-if="tasaDesfavorable" class="bg-amber-50 border border-amber-200 text-amber-700 text-sm p-3 rounded-lg">
          ⚠️ La tasa es desfavorable para la casa (sugerida {{ form.tipo === 'venta' ? '≥' : '≤' }} {{ tasaSugerida }}). El backend podría rechazarla salvo aprobación especial.
        </div>
        <div class="bg-gray-50 rounded-lg p-3 flex items-center justify-between">
          <span class="text-sm text-gray-500">{{ quoteNombre }} {{ form.tipo === 'venta' ? 'a recibir' : 'a pagar' }}</span>
          <span class="text-lg font-bold text-gray-800">{{ quoteSimbolo }} {{ formatMoney(bolivares) }}</span>
        </div>
      </div>

      <!-- Cuentas -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Cuentas involucradas</h3>
        <div v-if="loadingCuentas" class="text-center py-4 text-gray-400 text-sm">Cargando cuentas...</div>
        <div v-else-if="cuentas.length === 0" class="bg-amber-50 border border-amber-200 text-amber-700 text-sm p-4 rounded-lg">
          ⚠️ No hay cuentas configuradas. Crea al menos una cuenta {{ monedaSel }} y una {{ quoteCodigo }} en <strong>Cuentas</strong>.
        </div>
        <template v-else>
          <div>
            <label class="block text-sm text-gray-600 mb-1">
              {{ form.tipo === 'venta' ? `Cuenta ${monedaSel} desde donde entregas` : `Cuenta ${monedaSel} donde recibes` }}
              <span v-if="!cuentaForeignRequerida" class="text-gray-400 font-normal">(opcional para efectivo)</span>
            </label>
            <select v-model="form.cuenta_usd_id" :required="cuentaForeignRequerida"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
              <option value="">Seleccionar cuenta {{ monedaSel }}</option>
              <option v-for="c in cuentasForeign" :key="c.id" :value="c.id">{{ cuentaLabel(c) }}</option>
            </select>
            <p v-if="cuentasForeign.length === 0" class="text-xs text-amber-500 mt-1">No hay cuentas en {{ monedaSel }}.</p>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">
              {{ form.tipo === 'venta' ? `Cuenta ${quoteCodigo} donde recibes` : `Cuenta ${quoteCodigo} desde donde pagas` }} *
            </label>
            <select v-model="form.cuenta_ves_id" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
              <option value="">Seleccionar cuenta {{ quoteCodigo }}</option>
              <option v-for="c in cuentasQuote" :key="c.id" :value="c.id">{{ cuentaLabel(c) }}</option>
            </select>
            <p v-if="cuentasQuote.length === 0" class="text-xs text-amber-500 mt-1">No hay cuentas en {{ quoteCodigo }}.</p>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Estado de entrega</label>
            <select v-model="form.estado_entrega"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
              <option value="digital">Digital - ya {{ form.tipo === 'venta' ? 'enviado' : 'recibido' }}</option>
              <option value="efectivo_ok">Efectivo - ya {{ form.tipo === 'venta' ? 'enviado' : 'recibido' }}</option>
              <option value="efectivo_pendiente">Efectivo - pendiente de entrega</option>
            </select>
          </div>
        </template>
      </div>

      <!-- Referencia y descripción -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <div>
          <label class="block text-sm text-gray-600 mb-1">Referencia (comprobante)</label>
          <input v-model="form.referencia" placeholder="Ej: TRF-001"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
        </div>
        <div>
          <label class="block text-sm text-gray-600 mb-1">Descripción</label>
          <textarea v-model="form.descripcion" rows="2" placeholder="Notas opcionales"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
        </div>
      </div>

      <!-- Resumen -->
      <div v-if="resumenVisible" class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-2 text-sm">
        <h3 class="font-semibold text-gray-700 mb-2">Resumen</h3>
        <div class="flex justify-between"><span class="text-gray-500">Tipo</span><span class="font-medium">{{ form.tipo === 'venta' ? `Venta de ${monedaSel}` : `Compra de ${monedaSel}` }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Cliente</span><span class="font-medium">{{ form.cliente_nombre || 'Sin cliente' }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Monto {{ monedaSel }}</span><span class="font-medium">{{ simbolo }} {{ formatMoney(form.monto_usd) }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Tasa</span><span class="font-medium">{{ form.tasa }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">{{ quoteNombre }}</span><span class="font-medium">{{ quoteSimbolo }} {{ formatMoney(bolivares) }}</span></div>
        <div v-if="form.cuenta_usd_id" class="flex justify-between"><span class="text-gray-500">Cuenta {{ monedaSel }}</span><span class="font-medium">{{ cuentaAlias(form.cuenta_usd_id) }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Cuenta {{ quoteCodigo }}</span><span class="font-medium">{{ cuentaAlias(form.cuenta_ves_id) }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Entrega</span><span class="font-medium">{{ estadoEntregaLabel }}</span></div>
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
import { reactive, ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useTasasStore } from '../stores/tasas.js'
import { useAuthStore } from '../stores/auth.js'
import { useOperacionesStore } from '../stores/operaciones.js'
import api from '../api/axios.js'

const route = useRoute()
const tasas = useTasasStore()
const auth = useAuthStore()
const ops = useOperacionesStore()

const saving = ref(false)
const error = ref('')
const successRef = ref('')
const cuentas = ref([])
const loadingCuentas = ref(true)

// Cliente autocomplete
const clienteSearch = ref('')
const clienteResults = ref([])
const searchingCliente = ref(false)
let clienteDebounce = null

const today = new Date().toISOString().split('T')[0]

// ── Moneda y cotización dinámicas ──
const SIMBOLOS = { USD: '$', USDT: '₮', EUR: '€', COP: '$', VES: 'Bs.' }
const NOMBRES = { USD: 'Dólar', USDT: 'Tether', EUR: 'Euro', COP: 'Peso', VES: 'Bolívar' }

const monedaSel = computed(() => route.params.moneda || 'USD')
const quoteCodigo = computed(() => monedaSel.value === 'USD' ? 'VES' : 'USD')
const parStr = computed(() => `${monedaSel.value}/${quoteCodigo.value}`)

const simbolo = computed(() => SIMBOLOS[monedaSel.value] || '$')
const quoteSimbolo = computed(() => SIMBOLOS[quoteCodigo.value] || 'Bs.')
const quoteNombre = computed(() => NOMBRES[quoteCodigo.value] || 'moneda cotizada')

const form = reactive({
  tipo: 'compra',
  fecha: today,
  cliente_id: '',
  cliente_nombre: '',
  monto_usd: '',
  tasa: '',
  cuenta_usd_id: '',
  cuenta_ves_id: '',
  estado_entrega: 'digital',
  referencia: '',
  descripcion: '',
})

// ── Cuenta extranjera opcional para efectivo ──
const cuentaForeignRequerida = computed(() => {
  return !form.estado_entrega.startsWith('efectivo')
})

// ── Computeds ─────────────────────────────────────────────
const tipoCodigo = computed(() => (form.tipo === 'venta' ? 'venta_usd' : 'compra_usd'))

const titulo = computed(() => {
  const accion = form.tipo === 'venta' ? 'Venta' : 'Compra'
  return `Nueva ${accion} ${monedaSel.value}`
})

const tasaPar = computed(() =>
  tasas.vigentes.find(t => t.par === parStr.value) || null
)

const tasaSugerida = computed(() => {
  if (!tasaPar.value) return null
  return form.tipo === 'venta' ? tasaPar.value.tasa_venta : tasaPar.value.tasa_compra
})

const bolivares = computed(() => {
  const m = parseFloat(form.monto_usd) || 0
  const t = parseFloat(form.tasa) || 0
  return m * t
})

const tasaDesfavorable = computed(() => {
  const sug = parseFloat(tasaSugerida.value)
  const t = parseFloat(form.tasa)
  if (!sug || !t) return false
  // Compra: desfavorable si pagas MÁS que la sugerida. Venta: si cobras MENOS.
  return form.tipo === 'compra' ? t > sug : t < sug
})

const cuentasForeign = computed(() => cuentas.value.filter(c => c.moneda?.codigo === monedaSel.value))
const cuentasQuote = computed(() => cuentas.value.filter(c => c.moneda?.codigo === quoteCodigo.value))

const estadoEntregaLabel = computed(() => ({
  digital: `Digital - ya ${form.tipo === 'venta' ? 'enviado' : 'recibido'}`,
  efectivo_ok: `Efectivo - ya ${form.tipo === 'venta' ? 'enviado' : 'recibido'}`,
  efectivo_pendiente: 'Efectivo - pendiente de entrega',
}[form.estado_entrega]))

const resumenVisible = computed(() =>
  form.monto_usd && form.tasa && form.cuenta_ves_id && (cuentaForeignRequerida.value ? form.cuenta_usd_id : true)
)

// ── Helpers ───────────────────────────────────────────────
function formatMoney(n) {
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(n) || 0)
}

function cuentaLabel(c) {
  const tipo = c.banco?.nombre || c.tipo || 'cuenta'
  return `${c.alias} · ${tipo} (${c.moneda?.codigo})`
}

function cuentaAlias(id) {
  const c = cuentas.value.find(x => x.id === Number(id))
  return c ? c.alias : '-'
}

function setTipo(tipo) {
  form.tipo = tipo
  if (tasaSugerida.value) form.tasa = tasaSugerida.value
}

// ── Watcher para resetear al cambiar moneda ──
watch(monedaSel, () => {
  form.monto_usd = ''
  form.tasa = tasaSugerida.value || ''
  form.cuenta_usd_id = ''
  form.cuenta_ves_id = ''
  form.estado_entrega = 'digital'
  form.referencia = ''
  form.descripcion = ''
  tasas.fetchVigentes()
}, { immediate: false })

// ── Cliente autocomplete ──────────────────────────────────
function onClienteSearch() {
  clearTimeout(clienteDebounce)
  searchingCliente.value = true
  clienteDebounce = setTimeout(async () => {
    const q = clienteSearch.value.trim()
    if (!q) { clienteResults.value = []; searchingCliente.value = false; return }
    try {
      const { data } = await api.get('/clientes', { params: { q } })
      clienteResults.value = Array.isArray(data) ? data : (data.data || [])
    } catch {
      clienteResults.value = []
    } finally {
      searchingCliente.value = false
    }
  }, 300)
}

function selectCliente(c) {
  form.cliente_id = c.id
  form.cliente_nombre = c.nombre
  clienteSearch.value = ''
  clienteResults.value = []
}

function clearCliente() {
  form.cliente_id = ''
  form.cliente_nombre = ''
}

async function crearClienteInline() {
  const nombre = clienteSearch.value.trim()
  if (!nombre) return
  try {
    const { data } = await api.post('/clientes', { nombre })
    const cliente = data.data || data
    selectCliente(cliente)
  } catch (err) {
    error.value = err.response?.data?.message || err.message
  }
}

// ── Submit ────────────────────────────────────────────────
function buildMovimientos() {
  const montoForeign = parseFloat(form.monto_usd)
  const montoQuote = bolivares.value
  const movimientos = []

  // Pata de moneda cotizada (siempre requerida)
  if (form.cuenta_ves_id) {
    movimientos.push({ cuenta_id: Number(form.cuenta_ves_id), monto: form.tipo === 'compra' ? -montoQuote : montoQuote })
  }

  // Pata de moneda extranjera (opcional para efectivo)
  if (form.cuenta_usd_id) {
    movimientos.push({ cuenta_id: Number(form.cuenta_usd_id), monto: form.tipo === 'compra' ? montoForeign : -montoForeign })
  }

  return movimientos
}

async function submit() {
  error.value = ''
  saving.value = true
  try {
    const descripcionFinal = [form.descripcion, `[Entrega: ${estadoEntregaLabel.value}]`]
      .filter(Boolean).join(' ')

    const body = {
      fecha: form.fecha,
      tipo_codigo: tipoCodigo.value,
      operador_id: Number(auth.user.id),
      tasa_aplicada: parseFloat(form.tasa),
      descripcion: descripcionFinal,
      movimientos: buildMovimientos(),
    }
    if (form.cliente_id) body.cliente_id = Number(form.cliente_id)
    if (form.referencia) body.referencia = form.referencia

    const created = await ops.create(body)
    const op = created.data || created
    successRef.value = op.referencia ? `(${op.referencia})` : `#${op.id || ''}`
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

function registrarOtra() {
  successRef.value = ''
  error.value = ''
  Object.assign(form, {
    cliente_id: '', cliente_nombre: '', monto_usd: '', tasa: tasaSugerida.value || '',
    cuenta_usd_id: '', cuenta_ves_id: '', estado_entrega: 'digital', referencia: '', descripcion: '',
  })
  tasas.fetchVigentes()
}

onMounted(async () => {
  await tasas.fetchVigentes()
  if (tasaSugerida.value) form.tasa = tasaSugerida.value
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
