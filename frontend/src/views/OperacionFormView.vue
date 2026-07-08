<template>
  <div class="max-w-2xl mx-auto space-y-4 pb-10">
    <div class="flex items-center gap-3 mb-2">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">{{ titulo }}</h2>
    </div>

    <div v-if="successRef" class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center space-y-4">
      <div class="text-4xl">✅</div>
      <p class="text-green-700 font-semibold">Operación registrada {{ successRef }}</p>
      <div class="flex flex-col sm:flex-row gap-2 justify-center">
        <button @click="registrarOtra" class="px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">Registrar otra</button>
        <button @click="$router.push('/operaciones')" class="px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50">Ver operaciones</button>
      </div>
    </div>

    <form v-else @submit.prevent="submit" class="space-y-4">
      <TipoOperacionSelector v-model:tipo="form.tipo" v-model:fecha="form.fecha" :moneda="monedaSel" :quote-simbolo="quoteSimbolo" :today="today" />

      <ClienteSelector v-model="clienteSeleccionado" :cliente-tiene-cuentas="clienteTieneCuentas" @cuenta-agregada="recargarCuentas" />

      <CalculadoraBidireccional
        v-model:monto="form.monto_usd" v-model:bolivares="form.bolivares" v-model:tasa="form.tasa"
        :tipo="form.tipo" :moneda="monedaSel" :quote-codigo="quoteCodigo" :quote-simbolo="quoteSimbolo"
        :quote-nombre="quoteNombre" :par-str="parStr" :tasa-sugerida="tasaSugerida" :desfavorable="tasaDesfavorable" />

      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Cuentas involucradas</h3>
        <AppLoadingSpinner v-if="loadingCuentas" />
        <div v-else-if="cuentas.length === 0" class="bg-amber-50 border border-amber-200 text-amber-700 text-sm p-4 rounded-lg">
          ⚠️ No hay cuentas configuradas.
        </div>
        <template v-else>
          <CuentaSelector v-model="form.cuenta_usd_id" :label="`${form.tipo === 'venta' ? 'Cuenta ' + monedaSel + ' desde donde entregas' : 'Cuenta ' + monedaSel + ' donde recibes'}`" :placeholder="'Seleccionar cuenta ' + monedaSel" :cuentas="cuentasForeign" :empty-message="'No hay cuentas en ' + monedaSel" :cuenta-label="cuentaLabel" :bancos="bancos" />
          <CuentaSelector v-model="form.cuenta_ves_id" :label="`${form.tipo === 'venta' ? 'Cuenta ' + quoteCodigo + ' donde recibes' : 'Cuenta ' + quoteCodigo + ' desde donde pagas'}`" :placeholder="'Seleccionar cuenta ' + quoteCodigo" :cuentas="cuentasQuote" :empty-message="'No hay cuentas en ' + quoteCodigo" :cuenta-label="cuentaLabel" :bancos="bancos" />
          <div>
            <label class="block text-sm text-gray-600 mb-1">Estado de entrega</label>
            <select v-model="form.estado_entrega" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
              <option value="digital">Digital</option>
              <option value="efectivo_ok">Efectivo - entregado</option>
              <option value="efectivo_pendiente">Efectivo - pendiente</option>
            </select>
          </div>
        </template>
      </div>

      <ComisionToggle v-model:activa="form.genera_comision" v-model:tipo="form.tipo_comision" v-model:monto="form.monto_comision" :simbolo="quoteSimbolo" :monto-calculado="formatMoney(bolivares * 0.003)" />

      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <label class="block text-sm text-gray-600 mb-1">Descripción</label>
        <textarea v-model="form.descripcion" rows="2" placeholder="Notas opcionales" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
      </div>

      <ResumenOperacion v-if="resumenVisible" :items="resumenItems" />

      <AppErrorState v-if="error" :message="error" :retry="false" />

      <button type="submit" :disabled="saving || !formularioValido"
        class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
        <span v-if="saving" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
        {{ saving ? 'Registrando...' : 'Registrar operación' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useTasasStore } from '../stores/tasas.js'
import { useBancosStore } from '../stores/bancos.js'
import { useAuthStore } from '../stores/auth.js'
import { useOperacionesStore } from '../stores/operaciones.js'
import api from '../api/axios.js'
import ClienteSelector from '../components/ClienteSelector.vue'
import TipoOperacionSelector from '../components/TipoOperacionSelector.vue'
import CalculadoraBidireccional from '../components/CalculadoraBidireccional.vue'
import CuentaSelector from '../components/CuentaSelector.vue'
import ComisionToggle from '../components/ComisionToggle.vue'
import ResumenOperacion from '../components/ResumenOperacion.vue'
import AppLoadingSpinner from '../components/AppLoadingSpinner.vue'
import AppErrorState from '../components/AppErrorState.vue'

const route = useRoute()
const tasas = useTasasStore()
const bancosStore = useBancosStore()
const auth = useAuthStore()
const ops = useOperacionesStore()

const bancos = ref([])

const editId = computed(() => route.params.id || null)
const esEdicion = computed(() => !!editId.value)
const motivoEdicion = computed(() => route.query.motivo || '')

const saving = ref(false)
const error = ref('')
const successRef = ref('')
const cuentas = ref([])
const loadingCuentas = ref(true)
const clienteSeleccionado = ref({ id: '', nombre: '' })

const today = new Date().toISOString().split('T')[0]

const SIMBOLOS = { USD: '$', USDT: '₮', EUR: '€', COP: '$', VES: 'Bs.' }
const NOMBRES = { USD: 'Dólar', USDT: 'Tether', EUR: 'Euro', COP: 'Peso', VES: 'Bolívar' }

const monedaSel = computed(() => route.params.moneda || 'USD')
const quoteCodigo = computed(() => monedaSel.value === 'USD' ? 'VES' : 'USD')
const parStr = computed(() => `${monedaSel.value}/${quoteCodigo.value}`)
const simbolo = computed(() => SIMBOLOS[monedaSel.value] || '$')
const quoteSimbolo = computed(() => SIMBOLOS[quoteCodigo.value] || 'Bs.')
const quoteNombre = computed(() => NOMBRES[quoteCodigo.value] || 'moneda cotizada')

const form = reactive({
  tipo: 'compra', fecha: today, monto_usd: '', bolivares: '', tasa: '',
  cuenta_usd_id: '', cuenta_ves_id: '', estado_entrega: 'digital',
  genera_comision: false, tipo_comision: 'pago_movil', monto_comision: '', descripcion: '',
})

const tipoCodigo = computed(() => (form.tipo === 'venta' ? 'venta_usd' : 'compra_usd'))
const titulo = computed(() => {
  if (esEdicion.value) return `Editar operación #${editId.value}`
  return `Nueva ${form.tipo === 'venta' ? 'Venta' : 'Compra'} ${monedaSel.value}`
})

const tasaPar = computed(() => tasas.vigentes.find(t => t.par === parStr.value) || null)
const tasaSugerida = computed(() => {
  if (!tasaPar.value) return null
  return form.tipo === 'venta' ? tasaPar.value.tasa_venta : tasaPar.value.tasa_compra
})
const tasaDesfavorable = computed(() => {
  const sug = parseFloat(tasaSugerida.value)
  const t = parseFloat(form.tasa)
  if (!sug || !t) return false
  return form.tipo === 'compra' ? t > sug : t < sug
})

const bolivares = computed(() => parseFloat(form.bolivares) || 0)

const cuentasForeign = computed(() => cuentas.value.filter(c => c.moneda?.codigo === monedaSel.value))
const cuentasQuote = computed(() => {
  const pool = clienteSeleccionado.value.id ? cuentas.value.filter(c => c.cliente_id === clienteSeleccionado.value.id) : cuentas.value
  return pool.filter(c => c.moneda?.codigo === quoteCodigo.value)
})

const clienteTieneCuentas = computed(() => {
  if (!clienteSeleccionado.value.id) return false
  return cuentas.value.some(c => c.cliente_id === clienteSeleccionado.value.id)
})

const formularioValido = computed(() => {
  if (!form.monto_usd || parseFloat(form.monto_usd) <= 0) return false
  if (!form.tasa || parseFloat(form.tasa) <= 0) return false
  if (!form.bolivares || parseFloat(form.bolivares) <= 0) return false
  if (!form.cuenta_usd_id) return false
  if (!form.cuenta_ves_id) return false
  if (tasaDesfavorable.value && !form.descripcion.trim()) return false
  return true
})

const resumenVisible = computed(() => form.monto_usd && form.tasa && form.cuenta_ves_id && form.cuenta_usd_id)
const resumenItems = computed(() => {
  const items = [
    { label: 'Tipo', value: form.tipo === 'venta' ? `Venta de ${monedaSel.value}` : `Compra de ${monedaSel.value}` },
    { label: 'Cliente', value: clienteSeleccionado.value.nombre || 'Sin cliente' },
    { label: `Monto ${monedaSel.value}`, value: `${simbolo.value} ${formatMoney(form.monto_usd)}` },
    { label: 'Tasa', value: parseFloat(form.tasa).toFixed(2) },
    { label: quoteNombre.value, value: `${quoteSimbolo.value} ${formatMoney(form.bolivares)}` },
  ]
  if (form.genera_comision) items.push({ label: 'Comisión', value: `${quoteSimbolo.value} ${formatMoney(form.monto_comision)}` })
  if (form.cuenta_usd_id) items.push({ label: `Cuenta ${monedaSel.value}`, value: cuentaAlias(form.cuenta_usd_id) })
  items.push({ label: `Cuenta ${quoteCodigo.value}`, value: cuentaAlias(form.cuenta_ves_id) })
  return items
})

async function cargarOperacion() {
  if (!esEdicion.value) return
  await ops.fetchOne(editId.value)
  const op = ops.detail
  if (!op) return

  const codigo = op.tipo_operacion?.codigo
  form.tipo = codigo === 'venta_usd' ? 'venta' : 'compra'
  form.fecha = op.fecha
  form.tasa = parseFloat(op.tasa_aplicada).toFixed(2)

  const movs = op.movimientos || []
  const movUsd = movs.find(m => m.moneda?.codigo === monedaSel.value)
  const movVes = movs.find(m => m.moneda?.codigo === quoteCodigo.value)

  if (movUsd) {
    form.monto_usd = Math.abs(parseFloat(movUsd.monto)).toString()
    form.cuenta_usd_id = movUsd.cuenta_id.toString()
  }
  if (movVes) {
    form.bolivares = Math.abs(parseFloat(movVes.monto)).toString()
    form.cuenta_ves_id = movVes.cuenta_id.toString()
  }

  if (op.cliente) {
    clienteSeleccionado.value = {
      id: op.cliente.id,
      nombre: op.cliente.nombre,
      alias: op.cliente.alias,
    }
  }

  form.genera_comision = op.genera_comision || false
  form.tipo_comision = op.tipo_comision || 'pago_movil'
  form.monto_comision = op.monto_comision || ''
  form.descripcion = op.descripcion || ''
  form.estado_entrega = 'digital'
}

function formatMoney(n) { return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(n) || 0) }
function cuentaLabel(c) { const tipo = c.banco?.nombre || c.tipo || 'cuenta'; return `${c.alias} · ${tipo} (${c.moneda?.codigo})` }
function cuentaAlias(id) { const c = cuentas.value.find(x => x.id === Number(id)); return c ? c.alias : '-' }

let calcGuard = false
function round2(n) { return (Math.round((n + Number.EPSILON) * 100) / 100).toString() }
async function runGuarded(mutate) { calcGuard = true; mutate(); await nextTick(); calcGuard = false }
watch(() => form.monto_usd, (v) => {
  if (calcGuard) return
  const m = parseFloat(v) || 0; const t = parseFloat(form.tasa) || 0
  runGuarded(() => { form.bolivares = (m && t) ? round2(m * t) : '' })
})
watch(() => form.bolivares, (v) => {
  if (calcGuard) return
  const b = parseFloat(v) || 0; const t = parseFloat(form.tasa) || 0
  runGuarded(() => { form.monto_usd = (b && t) ? round2(b / t) : '' })
})
watch(() => form.tasa, () => {
  if (calcGuard) return
  const m = parseFloat(form.monto_usd) || 0; const t = parseFloat(form.tasa) || 0
  if (m && t) runGuarded(() => { form.bolivares = round2(m * t) })
})

function recalcComision() {
  if (!form.genera_comision) return
  if (form.tipo_comision === 'mismo_banco') { form.monto_comision = '0'; return }
  if (['pago_movil', 'otros_bancos'].includes(form.tipo_comision)) form.monto_comision = round2(bolivares.value * 0.003)
}
watch(() => form.genera_comision, (on) => { if (on) recalcComision(); else form.monto_comision = '' })
watch(() => form.tipo_comision, () => recalcComision())
watch(bolivares, () => { if (form.genera_comision && ['pago_movil', 'otros_bancos'].includes(form.tipo_comision)) recalcComision() })

watch(monedaSel, () => {
  form.monto_usd = ''; form.bolivares = ''; form.tasa = tasaSugerida.value ? parseFloat(tasaSugerida.value).toFixed(2) : ''
  form.cuenta_usd_id = ''; form.cuenta_ves_id = ''; form.estado_entrega = 'digital'
  form.genera_comision = false; form.monto_comision = ''
  tasas.fetchVigentes()
})

function buildMovimientos() {
  const montoForeign = parseFloat(form.monto_usd) || 0
  const montoQuote = parseFloat(form.bolivares) || 0
  const tasaAplicada = parseFloat(form.tasa) || 0
  const movimientos = []
  if (form.cuenta_ves_id) {
    const cuenta = cuentas.value.find(c => c.id === Number(form.cuenta_ves_id))
    movimientos.push({ cuenta_id: Number(form.cuenta_ves_id), monto: form.tipo === 'compra' ? -montoQuote : montoQuote, tasa_a_usd: cuenta?.moneda?.codigo === 'USD' ? 1 : (tasaAplicada ? 1 / tasaAplicada : 0) })
  }
  if (form.cuenta_usd_id) {
    const cuenta = cuentas.value.find(c => c.id === Number(form.cuenta_usd_id))
    movimientos.push({ cuenta_id: Number(form.cuenta_usd_id), monto: form.tipo === 'compra' ? montoForeign : -montoForeign, tasa_a_usd: cuenta?.moneda?.codigo === 'USD' ? 1 : (tasaAplicada || 0) })
  } else { error.value = `Debes seleccionar una cuenta en ${monedaSel.value}.`; return [] }
  return movimientos
}

async function recargarCuentas() {
  try { const { data } = await api.get('/cuentas'); cuentas.value = Array.isArray(data) ? data : (data.data || []) } catch {}
}

async function submit() {
  error.value = ''
  if (!form.cuenta_ves_id) { error.value = `Selecciona una cuenta en ${quoteCodigo.value}.`; return }
  if (!form.cuenta_usd_id) { error.value = `Selecciona una cuenta en ${monedaSel.value}.`; return }
  const movimientos = buildMovimientos()
  if (movimientos.length !== 2) { if (!error.value) error.value = 'Se requieren 2 movimientos.'; return }
  saving.value = true
  try {
    const body = {
      fecha: form.fecha,
      tipo_codigo: tipoCodigo.value,
      operador_id: Number(auth.user.id),
      tasa_aplicada: parseFloat(form.tasa),
      descripcion: [form.descripcion, `[Entrega: ${form.estado_entrega}]`].filter(Boolean).join(' '),
      movimientos,
    }
    if (clienteSeleccionado.value.id) body.cliente_id = Number(clienteSeleccionado.value.id)
    body.genera_comision = form.genera_comision
    if (form.genera_comision) { body.monto_comision = parseFloat(form.monto_comision) || 0; body.tipo_comision = form.tipo_comision }

    if (esEdicion.value) {
      body.motivo_edicion = motivoEdicion.value
      await ops.update(editId.value, body)
    } else {
      await ops.create(body)
    }

    const op = ops.detail
    successRef.value = op?.referencia ? `(${op.referencia})` : `#${op?.id || ''}`
  } catch (err) {
    const data = err.response?.data
    error.value = data?.errors ? Object.values(data.errors).flat().join('\n') : data?.message || err.message
  } finally { saving.value = false }
}

function registrarOtra() {
  successRef.value = ''; error.value = ''; clienteSeleccionado.value = { id: '', nombre: '' }
  Object.assign(form, { monto_usd: '', bolivares: '', tasa: tasaSugerida.value || '', cuenta_usd_id: '', cuenta_ves_id: '', estado_entrega: 'digital', genera_comision: false, tipo_comision: 'pago_movil', monto_comision: '', descripcion: '' })
  tasas.fetchVigentes()
}

onMounted(async () => {
  await tasas.fetchVigentes()
  await bancosStore.fetchAll()
  bancos.value = bancosStore.list
  try { const { data } = await api.get('/cuentas'); cuentas.value = Array.isArray(data) ? data : (data.data || []) } catch { cuentas.value = [] }
  finally { loadingCuentas.value = false }

  if (esEdicion.value) {
    await cargarOperacion()
  } else if (tasaSugerida.value) {
    form.tasa = parseFloat(tasaSugerida.value).toFixed(2)
  }
})
</script>
