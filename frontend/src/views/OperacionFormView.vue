<template>
  <div class="max-w-3xl mx-auto space-y-4 pb-10">
    <div class="flex items-center gap-3 mb-2">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">{{ titulo }}</h2>
    </div>

    <div v-if="successRef" class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center space-y-4">
      <div class="text-4xl">✅</div>
      <p class="text-green-700 font-semibold">Operación registrada {{ successRef }}</p>
      <div class="flex flex-col sm:flex-row gap-2 justify-center">
        <button @click="registrarOtra" class="px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">Registrar otra</button>
        <button @click="$router.push('/pool')" class="px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50">Ir al Pool de Pagadores</button>
      </div>
    </div>

    <form v-else @submit.prevent="submit" class="space-y-4">
      <TipoOperacionSelector v-model:tipo="form.tipo" v-model:fecha="form.fecha" :moneda="monedaSel" :quote-simbolo="quoteSimbolo" :today="today" />

      <ClienteSelector v-model="clienteSeleccionado" :cliente-tiene-cuentas="clienteTieneCuentas" @cuenta-agregada="recargarCuentas" />

      <CalculadoraBidireccional
        v-model:monto="form.monto_usd" v-model:bolivares="form.bolivares" v-model:tasa="form.tasa"
        :tipo="form.tipo" :moneda="monedaSel" :quote-codigo="quoteCodigo" :quote-simbolo="quoteSimbolo"
        :quote-nombre="quoteNombre" :par-str="parStr" :tasa-sugerida="tasaSugerida" :desfavorable="tasaDesfavorable" />

      <!-- Transacciones -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-700">Transacciones</h3>
          <button type="button" @click="agregarTransaccion" class="text-sm text-blue-600 hover:text-blue-700 font-medium">+ Agregar fila</button>
        </div>

        <AppLoadingSpinner v-if="loadingCuentas" />
        <div v-else-if="cuentas.length === 0" class="bg-amber-50 border border-amber-200 text-amber-700 text-sm p-4 rounded-lg">
          ⚠️ No hay cuentas configuradas.
        </div>

        <template v-else>
          <TransaccionRow
            v-for="(tx, i) in form.transacciones"
            :key="tx._key"
            :index="i"
            :monedas="monedas"
            :cuentas="cuentas"
            :cuenta-origen-id="tx.cuenta_origen_id"
            :cuenta-destino-id="tx.cuenta_destino_id"
            :moneda-id="tx.moneda_id"
            :monto="tx.monto"
            :comision-tipo="tx.comision_tipo"
            :comision-monto="tx.comision_monto"
            :cliente-id="clienteSeleccionado.id || null"
            :moneda-foreign-id="monedaForeignId"
            :moneda-quote-id="monedaQuoteId"
            @update:cuentaOrigenId="tx.cuenta_origen_id = $event"
            @update:cuentaDestinoId="tx.cuenta_destino_id = $event"
            @update:monedaId="tx.moneda_id = $event"
            @update:monto="tx.monto = $event"
            @update:comisionTipo="tx.comision_tipo = $event"
            @update:comisionMonto="tx.comision_monto = $event"
            @remove="eliminarTransaccion(i)"
          />
        </template>

        <div class="flex flex-wrap gap-2 pt-2">
          <button type="button" @click="distribuirMontos"
            class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition font-medium"
            :disabled="!form.monto_usd && !form.bolivares">Distribuir montos</button>
          <button type="button" @click="limpiarTransacciones"
            class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-red-100 text-gray-500 hover:text-red-600 rounded-lg transition font-medium">Limpiar filas</button>
        </div>

        <div v-if="resumenTransacciones.length" class="text-xs text-gray-500 space-y-0.5 pt-1 border-t border-gray-100">
          <p v-for="r in resumenTransacciones" :key="r.label" :class="r.ok ? 'text-gray-500' : 'text-red-500 font-medium'">
            {{ r.label }}: {{ r.total }} / {{ r.esperado }}
            <span v-if="r.ok">✅</span>
            <span v-else>⚠️ Diferencia: {{ r.diferencia }}</span>
          </p>
        </div>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <label class="block text-sm text-gray-600 mb-1">Descripción</label>
        <textarea v-model="form.descripcion" rows="2" placeholder="Notas opcionales"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
      </div>

      <ResumenOperacion v-if="resumenVisible" :items="resumenItems" />

      <AppErrorState v-if="error" :message="error" :retry="false" />

      <div v-if="!formularioValido && erroresValidacion.length" class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-700 space-y-1">
        <p class="font-medium">Completa los siguientes campos:</p>
        <ul class="list-disc list-inside">
          <li v-for="err in erroresValidacion" :key="err">{{ err }}</li>
        </ul>
      </div>

      <button type="submit" :disabled="saving || !formularioValido"
        class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
        <span v-if="saving" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
        {{ saving ? 'Registrando...' : 'Registrar operación' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useTasasStore } from '../stores/tasas.js'
import { useBancosStore } from '../stores/bancos.js'
import { useAuthStore } from '../stores/auth.js'
import { useOperacionesStore } from '../stores/operaciones.js'
import api from '../api/axios.js'
import ClienteSelector from '../components/ClienteSelector.vue'
import TipoOperacionSelector from '../components/TipoOperacionSelector.vue'
import CalculadoraBidireccional from '../components/CalculadoraBidireccional.vue'
import ResumenOperacion from '../components/ResumenOperacion.vue'
import AppLoadingSpinner from '../components/AppLoadingSpinner.vue'
import AppErrorState from '../components/AppErrorState.vue'
import TransaccionRow from '../components/operaciones/TransaccionRow.vue'

const route = useRoute()
const tasas = useTasasStore()
const bancosStore = useBancosStore()
const auth = useAuthStore()
const ops = useOperacionesStore()

const bancos = ref([])
const monedas = ref([])
const cuentas = ref([])
const loadingCuentas = ref(true)
const clienteSeleccionado = ref({ id: '', nombre: '' })
const saving = ref(false)
const error = ref('')
const successRef = ref('')
const today = new Date().toISOString().split('T')[0]

const SIMBOLOS = { USD: '$', USDT: '₮', EUR: '€', COP: '$', VES: 'Bs.' }
const NOMBRES = { USD: 'Dólar', USDT: 'Tether', EUR: 'Euro', COP: 'Peso', VES: 'Bolívar' }

const editId = computed(() => route.params.id || null)
const esEdicion = computed(() => !!editId.value)
const motivoEdicion = computed(() => route.query.motivo || '')
const monedaSel = computed(() => route.params.moneda || 'USD')
const quoteCodigo = computed(() => monedaSel.value === 'USD' ? 'VES' : 'USD')
const parStr = computed(() => `${monedaSel.value}/${quoteCodigo.value}`)
const quoteSimbolo = computed(() => SIMBOLOS[quoteCodigo.value] || 'Bs.')
const quoteNombre = computed(() => NOMBRES[quoteCodigo.value] || 'moneda cotizada')
const tipoCodigo = computed(() => (form.tipo === 'venta' ? 'venta_usd' : 'compra_usd'))

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

const monedaForeignId = computed(() => {
  const m = monedas.value.find(m => m.codigo === monedaSel.value)
  return m ? m.id : null
})
const monedaQuoteId = computed(() => {
  const m = monedas.value.find(m => m.codigo === quoteCodigo.value)
  return m ? m.id : null
})

let txCounter = 0
function nuevaTx() {
  return { _key: ++txCounter, cuenta_origen_id: null, cuenta_destino_id: null, moneda_id: null, monto: '', comision_tipo: 'manual', comision_monto: '' }
}

const form = reactive({
  tipo: 'compra', fecha: today, monto_usd: '', bolivares: '', tasa: '',
  descripcion: '',
  transacciones: [nuevaTx(), nuevaTx()],
})

const clienteTieneCuentas = computed(() => {
  if (!clienteSeleccionado.value.id) return false
  return cuentas.value.some(c => c.cliente_id === clienteSeleccionado.value.id)
})

const titulo = computed(() => {
  if (esEdicion.value) return `Editar operación #${editId.value}`
  return `Nueva ${form.tipo === 'venta' ? 'Venta' : 'Compra'} ${monedaSel.value}`
})

const resumenTransacciones = computed(() => {
  const agrupado = {}
  for (const tx of form.transacciones) {
    if (!tx.moneda_id || !tx.monto) continue
    agrupado[tx.moneda_id] = (agrupado[tx.moneda_id] || 0) + parseFloat(tx.monto)
  }
  const result = []
  for (const [monedaId, esperado, label] of [
    [monedaForeignId.value, parseFloat(form.monto_usd) || 0, `Total ${monedaSel.value}`],
    [monedaQuoteId.value, parseFloat(form.bolivares) || 0, `Total ${quoteCodigo.value}`],
  ]) {
    if (!monedaId) continue
    const total = agrupado[monedaId] || 0
    const diff = Math.abs(total - esperado)
    result.push({
      label,
      total: total.toFixed(2),
      esperado: esperado.toFixed(2),
      diferencia: diff.toFixed(2),
      ok: total > 0 && diff < 0.01,
    })
  }
  return result
})

const erroresValidacion = computed(() => {
  const errs = []
  if (!form.tasa || parseFloat(form.tasa) <= 0) errs.push('Ingresa una tasa de cambio')
  if (!clienteSeleccionado.value.id) errs.push('Selecciona un cliente')
  if (!parseFloat(form.monto_usd)) errs.push(`Ingresa el monto en ${monedaSel.value}`)
  if (!parseFloat(form.bolivares)) errs.push(`Ingresa el monto en ${quoteCodigo.value}`)
  const txCompleta = form.transacciones.some(tx => tx.cuenta_origen_id && tx.cuenta_destino_id && tx.moneda_id && parseFloat(tx.monto) > 0)
  if (!txCompleta) errs.push('Completa al menos una transacción (moneda, salida, entrada, monto)')
  else {
    const hasForeign = form.transacciones.some(tx => tx.moneda_id == monedaForeignId.value && parseFloat(tx.monto) > 0)
    const hasQuote = form.transacciones.some(tx => tx.moneda_id == monedaQuoteId.value && parseFloat(tx.monto) > 0)
    if (!hasForeign) errs.push(`Agrega al menos una transacción en ${monedaSel.value} (${quoteCodigo.value})`)
    if (!hasQuote) errs.push(`Agrega al menos una transacción en ${quoteCodigo.value} (${monedaSel.value})`)
    for (const tx of form.transacciones) {
      if (!tx.cuenta_origen_id) errs.push(`Transacción #${form.transacciones.indexOf(tx) + 1}: selecciona cuenta de salida`)
      if (!tx.cuenta_destino_id) errs.push(`Transacción #${form.transacciones.indexOf(tx) + 1}: selecciona cuenta de entrada`)
    }
  }
  if (resumenTransacciones.value.some(r => !r.ok)) {
    for (const r of resumenTransacciones.value) {
      if (!r.ok) errs.push(`${r.label}: los montos no cuadran (esperado ${r.esperado}, total ${r.total})`)
    }
  }
  if (tasaDesfavorable.value && !form.descripcion.trim()) errs.push('Agrega una descripción (la tasa es desfavorable)')
  return [...new Set(errs)]
})

const formularioValido = computed(() => erroresValidacion.value.length === 0)

const resumenVisible = computed(() => {
  return form.tasa && clienteSeleccionado.value.id && form.transacciones.some(tx => tx.cuenta_origen_id && tx.monto)
})

const resumenItems = computed(() => {
  const items = [
    { label: 'Tipo', value: form.tipo === 'venta' ? `Venta de ${monedaSel.value}` : `Compra de ${monedaSel.value}` },
    { label: 'Cliente', value: clienteSeleccionado.value.nombre || 'Sin cliente' },
    { label: 'Tasa', value: parseFloat(form.tasa).toFixed(2) },
    { label: 'Transacciones', value: `${form.transacciones.length} fila(s)` },
  ]
  const totalComision = form.transacciones.reduce((s, tx) => s + (Math.abs(parseFloat(tx.comision_monto)) || 0), 0)
  if (totalComision > 0) {
    items.push({ label: 'Comisión total', value: `${quoteSimbolo.value} ${formatMoney(totalComision)}` })
  }
  return items
})

function formatMoney(n) {
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(n) || 0)
}

function agregarTransaccion() { form.transacciones.push(nuevaTx()) }
function eliminarTransaccion(index) { if (form.transacciones.length <= 1) return; form.transacciones.splice(index, 1) }
function limpiarTransacciones() { form.transacciones = [nuevaTx(), nuevaTx()] }

function distribuirMontos() {
  const foreignId = monedaForeignId.value
  const quoteId = monedaQuoteId.value
  const montoUSD = parseFloat(form.monto_usd) || 0
  const montoVES = parseFloat(form.bolivares) || 0
  const txsForeign = form.transacciones.filter(tx => tx.moneda_id && tx.moneda_id == foreignId)
  const txsQuote = form.transacciones.filter(tx => tx.moneda_id && tx.moneda_id == quoteId)

  if (txsForeign.length > 0 && montoUSD > 0) {
    const base = Math.floor((montoUSD / txsForeign.length) * 100) / 100
    let resto = parseFloat((montoUSD - base * txsForeign.length).toFixed(2))
    txsForeign.forEach((tx, i) => { tx.monto = i === txsForeign.length - 1 ? (base + resto).toString() : base.toString() })
  }
  if (txsQuote.length > 0 && montoVES > 0) {
    const base = Math.floor((montoVES / txsQuote.length) * 100) / 100
    let resto = parseFloat((montoVES - base * txsQuote.length).toFixed(2))
    txsQuote.forEach((tx, i) => { tx.monto = i === txsQuote.length - 1 ? (base + resto).toString() : base.toString() })
  }
}

async function cargarOperacion() {
  if (!esEdicion.value) return
  await ops.fetchOne(editId.value)
  const op = ops.detail
  if (!op) return

  const codigo = op.tipo_operacion?.codigo
  form.tipo = codigo === 'venta_usd' ? 'venta' : 'compra'
  form.fecha = op.fecha
  form.tasa = parseFloat(op.tasa_aplicada).toFixed(2)

  if (op.cliente) {
    clienteSeleccionado.value = { id: op.cliente.id, nombre: op.cliente.nombre, alias: op.cliente.alias }
  }

  form.descripcion = op.descripcion || ''

  const movs = op.movimientos || []
  const movsForeign = movs.filter(m => m.moneda?.codigo === monedaSel.value)
  const movsQuote = movs.filter(m => m.moneda?.codigo === quoteCodigo.value)

  form.monto_usd = movsForeign.reduce((s, m) => s + Math.abs(parseFloat(m.monto)), 0).toFixed(2)
  form.bolivares = movsQuote.reduce((s, m) => s + Math.abs(parseFloat(m.monto)), 0).toFixed(2)

  form.transacciones = []
  for (const mov of movs) {
    const signo = parseFloat(mov.monto) >= 0 ? 'destino' : 'origen'
    form.transacciones.push({
      _key: ++txCounter,
      cuenta_origen_id: signo === 'origen' ? mov.cuenta_id : null,
      cuenta_destino_id: signo === 'destino' ? mov.cuenta_id : null,
      moneda_id: mov.moneda_id,
      monto: Math.abs(parseFloat(mov.monto)).toString(),
      comision_monto: '',
    })
  }
  if (form.transacciones.length === 0) form.transacciones = [nuevaTx(), nuevaTx()]
}

async function recargarCuentas() {
  try { const { data } = await api.get('/cuentas'); cuentas.value = Array.isArray(data) ? data : (data.data || []) } catch { cuentas.value = [] }
}

async function submit() {
  error.value = ''
  saving.value = true
  try {
    const movimientos = []
    let totalComision = 0
    for (const tx of form.transacciones) {
      if (!tx.cuenta_origen_id || !tx.cuenta_destino_id || !tx.moneda_id || !parseFloat(tx.monto)) continue
      const monto = Math.abs(parseFloat(tx.monto))
      const comision = Math.abs(parseFloat(tx.comision_monto)) || 0
      movimientos.push({ cuenta_id: Number(tx.cuenta_origen_id), monto: -(monto + comision) })
      movimientos.push({ cuenta_id: Number(tx.cuenta_destino_id), monto })
      totalComision += comision
    }

    if (movimientos.length < 2) { error.value = 'Debes configurar al menos una transacción completa.'; return }

    const body = {
      fecha: form.fecha,
      tipo_codigo: tipoCodigo.value,
      operador_id: Number(auth.user.id),
      tasa_aplicada: parseFloat(form.tasa),
      descripcion: form.descripcion.trim() || null,
      movimientos,
    }

    if (clienteSeleccionado.value.id) body.cliente_id = Number(clienteSeleccionado.value.id)

    if (totalComision > 0) {
      body.genera_comision = true
      body.monto_comision = totalComision
    } else {
      body.genera_comision = false
      body.monto_comision = 0
    }

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
  Object.assign(form, {
    monto_usd: '', bolivares: '', tasa: tasaSugerida.value || '',
    descripcion: '',
    transacciones: [nuevaTx(), nuevaTx()],
  })
  tasas.fetchVigentes()
}

onMounted(async () => {
  await tasas.fetchVigentes()
  await bancosStore.fetchAll()
  bancos.value = bancosStore.list
  try {
    const { data: cuentasData } = await api.get('/cuentas')
    cuentas.value = Array.isArray(cuentasData) ? cuentasData : (cuentasData.data || [])
  } catch { cuentas.value = [] }
  try {
    const { data: monedasData } = await api.get('/monedas')
    monedas.value = Array.isArray(monedasData) ? monedasData : (monedasData.data || [])
  } catch { monedas.value = [] }
  finally { loadingCuentas.value = false }

  if (esEdicion.value) {
    await cargarOperacion()
  } else if (tasaSugerida.value) {
    form.tasa = parseFloat(tasaSugerida.value).toFixed(2)
  }
})
</script>
