<template>
  <div class="max-w-2xl mx-auto space-y-4 pb-10">
    <div class="flex items-center gap-3 mb-2">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">Nueva venta</h2>
    </div>

    <form @submit.prevent="registrarVenta" class="space-y-4">
      <!-- Moneda -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <label class="block text-sm font-medium text-gray-600">Moneda a vender</label>
        <select v-model="moneda" required
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
          <option value="">Seleccionar moneda</option>
          <option value="USD">💵 USD — Dólar Estadounidense</option>
          <option value="USDT">₮ USDT — Tether</option>
          <option value="EUR">€ EUR — Euro</option>
          <option value="COP">$ COP — Peso Colombiano</option>
        </select>
      </div>

      <!-- Cliente -->
      <div v-if="moneda" class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <label class="block text-sm font-medium text-gray-600">Cliente</label>
        <ClienteSelector v-model="cliente" />
      </div>

      <!-- Monto, tasa, total -->
      <div v-if="moneda && cliente?.id" class="bg-white border border-gray-200 rounded-xl p-5">
        <CalculadoraBidireccional
          v-model:monto="montoDivisa"
          v-model:bolivares="montoVes"
          v-model:tasa="tasa"
          tipo="venta"
          :moneda="moneda"
          :quote-codigo="'VES'"
          :quote-simbolo="'Bs.'"
          :quote-nombre="'Bolívar'"
          :par-str="`${moneda}/VES`"
          :tasa-sugerida="tasaReferencia"
        />
      </div>

      <!-- Movimientos en Bs -->
      <div v-if="moneda && cliente?.id && montoVesNum > 0" class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Pago del cliente en Bs</h3>
        <div class="flex justify-between text-sm bg-gray-50 rounded-lg px-3 py-2">
          <span class="text-gray-500">Total:</span>
          <span class="font-bold text-gray-800">Bs. {{ fmt(montoVesNum) }}</span>
        </div>
        <div class="flex justify-between text-sm bg-gray-50 rounded-lg px-3 py-2">
          <span class="text-gray-500">Restante:</span>
          <span class="font-bold" :class="restanteVes === 0 ? 'text-green-600' : 'text-amber-600'">Bs. {{ fmt(restanteVes) }}</span>
        </div>

        <!-- Lista de movimientos confirmados -->
        <div v-if="movsVes.length" class="space-y-1.5">
          <div v-for="(tx, i) in movsVes" :key="i"
            class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2 text-sm">
            <span class="text-green-700">
              <span class="font-medium">✓</span>
              {{ tx._registroLabel }} · <span class="font-bold">Bs. {{ fmt(tx.monto) }}</span>
              <span class="text-gray-500 ml-1">· {{ tx.metodo_pago }}</span>
            </span>
            <div class="flex gap-2 ml-2 shrink-0">
              <button type="button" @click="editarMovVes(i)"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">Editar</button>
              <button type="button" @click="eliminarMovVes(i)"
                class="text-xs text-red-500 hover:text-red-700 font-medium">✕</button>
            </div>
          </div>
        </div>

        <!-- Formulario -->
        <div v-if="restanteVes > 0 || movVesEditandoIdx !== null" class="space-y-3 pt-2 border-t border-gray-100">
          <p v-if="movVesEditandoIdx !== null" class="text-xs text-blue-600 font-medium">Editando movimiento #{{ movVesEditandoIdx + 1 }}</p>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Método de pago</label>
            <select v-model="txVes.metodo_pago" required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar método</option>
              <option value="efectivo">Efectivo</option>
              <option value="pagomovil">Pago móvil</option>
              <option value="transferencia">Transferencia</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Monto</label>
            <input v-model="txVes.monto" type="number" step="0.01" min="0" :max="restanteVes"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <div v-if="txVes.metodo_pago && txVes.metodo_pago !== 'efectivo'">
            <label class="block text-xs text-gray-500 mb-1">Comprobante</label>
            <input v-model="txVes.comprobante" placeholder="N° de referencia..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <button type="button" @click="confirmarMovVes" :disabled="!txVesValido"
            class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white text-sm font-semibold rounded-xl transition">
            {{ movVesEditandoIdx !== null ? 'Actualizar movimiento' : 'Confirmar' }}
          </button>
        </div>
      </div>

      <!-- Movimientos en divisa -->
      <div v-if="moneda && cliente?.id && montoDivisaNum > 0 && restanteVes === 0 && movsVes.length > 0" class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Entrega de la casa en {{ moneda }}</h3>
        <div class="flex justify-between text-sm bg-gray-50 rounded-lg px-3 py-2">
          <span class="text-gray-500">Total:</span>
          <span class="font-bold text-gray-800">{{ fmt(montoDivisaNum) }} {{ moneda }}</span>
        </div>

        <!-- Estado confirmado -->
        <div v-if="movDivConfirmado" class="bg-green-50 border border-green-200 rounded-lg p-3 space-y-2">
          <div class="flex items-center justify-between text-sm">
            <span class="text-green-700 font-medium">✓ Confirmado</span>
            <div class="flex gap-2">
              <button type="button" @click="editarMovDiv"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">Editar</button>
              <button type="button" @click="eliminarMovDiv"
                class="text-xs text-red-500 hover:text-red-700 font-medium">Eliminar</button>
            </div>
          </div>
          <div class="text-sm text-gray-700 space-y-0.5">
            <p><span class="text-gray-500">Origen:</span> {{ movDivConfirmado._origen }}</p>
            <p><span class="text-gray-500">Destino:</span> {{ movDivConfirmado._destino }}</p>
            <p><span class="text-gray-500">Monto:</span> <span class="font-bold">{{ fmt(movDivConfirmado.monto) }} {{ moneda }}</span></p>
            <p><span class="text-gray-500">Método:</span> {{ movDivConfirmado.metodo_pago }}</p>
            <p v-if="movDivConfirmado.comprobante"><span class="text-gray-500">Comprobante:</span> {{ movDivConfirmado.comprobante }}</p>
          </div>
        </div>

        <!-- Formulario -->
        <div v-else class="space-y-3 pt-2 border-t border-gray-100">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Cuenta origen <span class="text-gray-400">(Intermedius)</span></label>
              <select v-model="txDiv.cuenta_origen_id" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Seleccionar</option>
                <option v-for="c in cuentasDivisaIntermedius" :key="c.id" :value="c.id">{{ labelCuenta(c) }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Cuenta destino <span class="text-gray-400">(Cliente)</span></label>
              <select v-model="txDiv.cuenta_destino_id" required :disabled="!txDiv.cuenta_origen_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 disabled:text-gray-400">
                <option value="">{{ txDiv.cuenta_origen_id ? 'Seleccionar' : 'Elegí origen primero' }}</option>
                <option v-for="c in cuentasDivisaDestinoFiltradas" :key="c.id" :value="c.id">{{ labelCuenta(c) }}</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Monto</label>
            <input v-model="txDiv.monto" type="number" step="0.01" min="0" :max="restanteDivisa"
              :placeholder="fmt(restanteDivisa)"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Método de pago</label>
            <select v-model="txDiv.metodo_pago" required :disabled="!txDiv.cuenta_origen_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 disabled:text-gray-400">
              <option value="">{{ txDiv.cuenta_origen_id ? 'Seleccionar' : 'Elegí origen primero' }}</option>
              <option v-for="m in metodosDivDisponibles" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
          </div>
          <div v-if="txDiv.metodo_pago && txDiv.metodo_pago !== 'efectivo'">
            <label class="block text-xs text-gray-500 mb-1">Comprobante</label>
            <input v-model="txDiv.comprobante" placeholder="N° de referencia..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <button type="button" @click="confirmarMovDiv" :disabled="!txDivValido"
            class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white text-sm font-semibold rounded-xl transition">
            Confirmar
          </button>
        </div>
      </div>

      <!-- Resumen + enviar -->
      <div v-if="moneda && cliente?.id && montoDivisaNum > 0 && montoVesNum > 0" class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Resumen</h3>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div class="bg-gray-50 rounded-lg px-3 py-2">
            <p class="text-gray-400">Monto divisa</p>
            <p class="font-bold text-gray-800">{{ fmt(montoDivisaNum) }} {{ moneda }}</p>
          </div>
          <div class="bg-gray-50 rounded-lg px-3 py-2">
            <p class="text-gray-400">Total Bs</p>
            <p class="font-bold text-gray-800">Bs. {{ fmt(montoVesNum) }}</p>
          </div>
          <div class="bg-gray-50 rounded-lg px-3 py-2">
            <p class="text-gray-400">Tasa</p>
            <p class="font-bold text-gray-800">{{ tasa || '—' }}</p>
          </div>
          <div class="bg-gray-50 rounded-lg px-3 py-2">
            <p class="text-gray-400">Movimientos</p>
            <p class="font-bold text-gray-800">{{ movsVes.length + (movDivConfirmado ? 1 : 0) }}</p>
          </div>
        </div>

        <div v-if="sumaValida"
          class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-700 text-center font-medium">
          ✅ Transacciones balanceadas
        </div>
        <div v-else class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-700 text-center">
          Faltan movimientos por registrar
        </div>

        <AppErrorState v-if="error" :message="error" :retry="false" />

        <button type="submit" :disabled="enviando || !sumaValida"
          class="w-full bg-green-600 hover:bg-green-700 disabled:bg-green-300 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
          <span v-if="enviando" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ enviando ? 'Registrando...' : 'Registrar Venta' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useOperacionesStore } from '../../stores/operaciones.js'
import { useNotification } from '@/composables/useNotification'
import { useFormatting } from '@/composables/useFormatting'
import { useTasasReferencia } from '@/composables/useTasasReferencia'
import { useTitulares } from '@/composables/useTitulares'
import ClienteSelector from '@/components/clientes/ClienteSelector.vue'
import CalculadoraBidireccional from '@/components/operaciones/CalculadoraBidireccional.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import api from '@/api/axios'

const router = useRouter()
const store = useOperacionesStore()
const notifier = useNotification()
const { formatMoney } = useFormatting()
const tasasRef = useTasasReferencia()
const titulares = useTitulares()

const moneda = ref('')
const cliente = ref(null)
const montoDivisa = ref('')
const montoVes = ref('')
const tasa = ref('')
const error = ref('')
const enviando = ref(false)

const movsVes = ref([])
const movVesEditandoIdx = ref(null)
const movDivConfirmado = ref(null)

const txVes = reactive({ monto: '', metodo_pago: '', comprobante: '' })
const txDiv = reactive({ cuenta_origen_id: '', cuenta_destino_id: '', monto: '', metodo_pago: '', comprobante: '' })

const allCuentas = ref([])
const cuentasIntermedius = ref([])
const cuentasCliente = ref([])
const registrosPago = ref([])
const monedas = ref([])

const montoVesNum = computed(() => parseFloat(montoVes.value) || 0)
const montoDivisaNum = computed(() => parseFloat(montoDivisa.value) || 0)
const tasaNum = computed(() => parseFloat(tasa.value) || 0)

const sumaVes = computed(() => movsVes.value.reduce((s, t) => s + t.monto, 0))
const sumaDivisa = computed(() => movDivConfirmado.value?.monto || 0)

const restanteVes = computed(() => Math.max(0, montoVesNum.value - sumaVes.value))
const restanteDivisa = computed(() => Math.max(0, montoDivisaNum.value - sumaDivisa.value))

const sumaValida = computed(() =>
  movsVes.value.length > 0 && movDivConfirmado.value
  && Math.abs(sumaVes.value - montoVesNum.value) <= 0.01
  && Math.abs(sumaDivisa.value - montoDivisaNum.value) <= 0.01
)

const tasaReferencia = computed(() => {
  if (!moneda.value) return null
  const ref = tasasRef.refTasaPorMoneda(moneda.value)
  return ref ? parseFloat(ref).toFixed(2) : null
})

const cuentaIntermediusVes = computed(() => {
  return cuentasIntermedius.value.find(c => c.moneda?.codigo === 'VES') || null
})

const txVesValido = computed(() =>
  txVes.metodo_pago && parseFloat(txVes.monto) > 0
)

function fmt(v) {
  return Number(v).toFixed(2)
}

function labelCuenta(c) {
  const tipo = c.banco?.nombre || c.tipo || 'cuenta'
  let saldo = ''
  if (c.titular_id && c.saldo_cache != null) {
    saldo = ` · Saldo: ${c.moneda?.simbolo || ''}${parseFloat(c.saldo_cache).toLocaleString('es-VE', { minimumFractionDigits: 2 })}`
  }
  return `${c.alias} · ${tipo}${saldo}`
}

const cuentasDivisaIntermedius = computed(() =>
  cuentasIntermedius.value.filter(c => c.moneda?.codigo === moneda.value)
)
const cuentasDivisaCliente = computed(() =>
  cuentasCliente.value.filter(c => c.cliente_id == cliente.value?.id && c.moneda?.codigo === moneda.value)
)

const origenDivisa = computed(() =>
  allCuentas.value.find(c => c.id == txDiv.cuenta_origen_id) || null
)
const esOrigenDivBanco = computed(() => origenDivisa.value?.tipo === 'banco')
const esOrigenDivEfectivo = computed(() => origenDivisa.value?.tipo === 'efectivo')

const cuentasDivisaDestinoFiltradas = computed(() => {
  if (esOrigenDivEfectivo.value) {
    return cuentasDivisaCliente.value.filter(c => c.tipo === 'efectivo')
  }
  if (esOrigenDivBanco.value) {
    return cuentasDivisaCliente.value.filter(c => c.tipo === 'banco')
  }
  return []
})

const metodosDivDisponibles = computed(() => {
  if (esOrigenDivEfectivo.value) return [{ value: 'efectivo', label: 'Efectivo' }]
  if (esOrigenDivBanco.value) return [
    { value: 'transferencia', label: 'Transferencia' },
    { value: 'zelle', label: 'Zelle' },
    { value: 'otro', label: 'Otro' },
  ]
  return []
})

const txDivValido = computed(() =>
  txDiv.cuenta_origen_id && txDiv.cuenta_destino_id && parseFloat(txDiv.monto) > 0 && txDiv.metodo_pago
)

function resetTxForm(form) {
  form.monto = ''
  form.metodo_pago = ''
  form.comprobante = ''
}

function resetTxDiv(form) {
  form.cuenta_origen_id = ''
  form.cuenta_destino_id = ''
  form.monto = ''
  form.metodo_pago = ''
  form.comprobante = ''
}

function buildTxVes() {
  const metodo = txVes.metodo_pago
  const registro = registrosPago.value.find(r => r.metodo_pago === metodo)
  const esEfectivo = metodo === 'efectivo'
  const tipoCuenta = esEfectivo ? 'efectivo' : 'banco'

  const origenCliente = cuentasCliente.value.find(
    c => c.cliente_id == cliente.value?.id && c.moneda?.codigo === 'VES' && c.tipo === tipoCuenta
  )
  const destinoIntermedius = esEfectivo
    ? cuentasIntermedius.value.find(c => c.moneda?.codigo === 'VES' && c.tipo === 'efectivo')
    : cuentasIntermedius.value.find(c => c.moneda?.codigo === 'VES' && c.tipo === 'banco')

  return {
    cuenta_origen_id: origenCliente ? Number(origenCliente.id) : null,
    cuenta_destino_id: destinoIntermedius ? Number(destinoIntermedius.id) : null,
    moneda_id: monedas.value.find(m => m.codigo === 'VES')?.id,
    monto: parseFloat(txVes.monto),
    tasa_aplicada: tasaNum.value,
    metodo_pago: metodo,
    comprobante: txVes.comprobante || null,
    _registroLabel: registro?.alias || metodo,
    _origen: registro?.alias || metodo,
    _destino: destinoIntermedius?.alias || 'Intermedius',
  }
}

function buildTx(form, monedaCodigo) {
  return {
    cuenta_origen_id: form.cuenta_origen_id ? Number(form.cuenta_origen_id) : null,
    cuenta_destino_id: form.cuenta_destino_id ? Number(form.cuenta_destino_id) : null,
    moneda_id: monedas.value.find(m => m.codigo === monedaCodigo)?.id,
    monto: parseFloat(form.monto),
    tasa_aplicada: tasaNum.value,
    metodo_pago: form.metodo_pago,
    comprobante: form.comprobante || null,
    _origen: allCuentas.value.find(c => c.id == form.cuenta_origen_id)?.alias || '',
    _destino: allCuentas.value.find(c => c.id == form.cuenta_destino_id)?.alias || '',
  }
}

function confirmarMovVes() {
  if (!txVesValido.value) return
  const tx = buildTxVes()
  if (movVesEditandoIdx.value !== null) {
    movsVes.value[movVesEditandoIdx.value] = tx
    movVesEditandoIdx.value = null
  } else {
    movsVes.value.push(tx)
  }
  resetTxForm(txVes)
  txVes.monto = restanteVes.value > 0 ? restanteVes.value : ''
}

function confirmarMovDiv() {
  if (!txDivValido.value) return
  movDivConfirmado.value = buildTx(txDiv, moneda.value)
}

function editarMovVes(idx) {
  const tx = movsVes.value[idx]
  txVes.monto = tx.monto
  txVes.metodo_pago = tx.metodo_pago
  txVes.comprobante = tx.comprobante || ''
  movVesEditandoIdx.value = idx
}

function editarMovDiv() {
  const tx = movDivConfirmado.value
  txDiv.cuenta_origen_id = tx.cuenta_origen_id
  txDiv.cuenta_destino_id = tx.cuenta_destino_id
  txDiv.monto = tx.monto
  txDiv.metodo_pago = tx.metodo_pago
  txDiv.comprobante = tx.comprobante || ''
  movDivConfirmado.value = null
}

function eliminarMovVes(idx) {
  movsVes.value.splice(idx, 1)
  if (movVesEditandoIdx.value === idx) {
    movVesEditandoIdx.value = null
    resetTxForm(txVes)
  } else if (movVesEditandoIdx.value !== null && movVesEditandoIdx.value > idx) {
    movVesEditandoIdx.value--
  }
}

function eliminarMovDiv() {
  movDivConfirmado.value = null
  resetTxDiv(txDiv)
}

async function cargarCuentas() {
  try {
    const { data } = await api.get('/cuentas')
    const list = Array.isArray(data) ? data : (data.data || [])
    allCuentas.value = list
    if (titulares.intermediusTitularId) {
      cuentasIntermedius.value = list.filter(c => c.titular_id == titulares.intermediusTitularId)
    } else {
      cuentasIntermedius.value = list.filter(c => c.titular_id)
    }
    cuentasCliente.value = list.filter(c => c.cliente_id)
  } catch { }
}

async function cargarRegistrosPago() {
  if (!cliente.value?.id) { registrosPago.value = []; return }
  try {
    const { data } = await api.get(`/clientes/${cliente.value.id}/registros-pago`)
    registrosPago.value = Array.isArray(data) ? data : (data.data || [])
  } catch { registrosPago.value = [] }
}

async function cargarMonedas() {
  try {
    const { data } = await api.get('/monedas')
    monedas.value = Array.isArray(data) ? data : (data.data || [])
  } catch { }
}

async function registrarVenta() {
  error.value = ''
  enviando.value = true
  try {
    const payload = {
      moneda_codigo: moneda.value,
      tasa_aplicada: tasaNum.value,
      tasa_mercado_snapshot: tasaReferencia.value || tasaNum.value,
      cliente_id: cliente.value?.id,
      monto_solicitado: montoDivisaNum.value,
      transacciones: [...movsVes.value, movDivConfirmado.value].map(tx => ({
        cuenta_origen_id: tx.cuenta_origen_id,
        cuenta_destino_id: tx.cuenta_destino_id,
        moneda_id: tx.moneda_id,
        monto: tx.monto,
        tasa_aplicada: tx.tasa_aplicada,
        metodo_pago: tx.metodo_pago,
        comprobante: tx.comprobante || null,
      })),
    }
    await store.crearVenta(payload)
    notifier.success('Venta registrada exitosamente')
    const op = store.detail
    router.push(op?.id ? `/operaciones/${op.id}` : '/operaciones')
  } catch (err) {
    error.value = err.response?.data?.message || err.message
    notifier.error(error.value)
  }
  enviando.value = false
}

watch(() => txVes.metodo_pago, () => {
  if (movVesEditandoIdx.value === null) {
    txVes.monto = restanteVes.value > 0 ? restanteVes.value : ''
  }
})

watch(() => cliente.value?.id, () => {
  registrosPago.value = []
  movsVes.value = []
  movVesEditandoIdx.value = null
  resetTxForm(txVes)
  cargarRegistrosPago()
})

watch(() => txDiv.cuenta_origen_id, () => {
  txDiv.cuenta_destino_id = ''
  txDiv.metodo_pago = ''
  txDiv.comprobante = ''
  if (esOrigenDivEfectivo.value) {
    txDiv.metodo_pago = 'efectivo'
  }
})
watch(() => txDiv.cuenta_destino_id, () => autoDetectarMetodo(txDiv))

function autoDetectarMetodo(form) {
  if (!form.cuenta_origen_id || !form.cuenta_destino_id || form.metodo_pago) return
  const origen = allCuentas.value.find(c => c.id == form.cuenta_origen_id)
  const destino = allCuentas.value.find(c => c.id == form.cuenta_destino_id)
  if (!origen || !destino) return
  if (origen.tipo === 'efectivo' && destino.tipo === 'efectivo') {
    form.metodo_pago = 'efectivo'
  } else if (origen.tipo === 'banco' && destino.tipo === 'banco') {
    form.metodo_pago = 'transferencia'
  }
}

watch(restanteDivisa, (val) => {
  if (val > 0 && !movDivConfirmado.value && !txDiv.monto) {
    txDiv.monto = val
  }
}, { immediate: true })

watch(moneda, () => {
  movsVes.value = []
  movVesEditandoIdx.value = null
  movDivConfirmado.value = null
  resetTxForm(txVes)
  resetTxDiv(txDiv)
})

onMounted(async () => {
  await Promise.all([tasasRef.fetch(), titulares.fetchAll(), cargarMonedas()])
  await cargarCuentas()
  await cargarRegistrosPago()
})
</script>
