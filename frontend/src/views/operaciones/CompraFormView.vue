<template>
  <div class="max-w-2xl mx-auto space-y-4 pb-10">
    <div class="flex items-center gap-3 mb-2">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500"><Iconoir name="arrow-left" class="w-5 h-5" /></button>
      <h2 class="text-xl font-bold text-gray-800">Nueva compra</h2>
    </div>

    <form @submit.prevent="registrarCompra" class="space-y-4">
      <!-- Paso 1: Moneda -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <label class="block text-sm font-medium text-gray-600">Moneda a recibir</label>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
          <button type="button" v-for="m in monedasDisponibles" :key="m.codigo" @click="moneda = m.codigo"
            class="py-3 rounded-xl text-sm font-medium transition border-2"
            :class="moneda === m.codigo ? 'bg-blue-50 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300'">
            <span class="block text-lg">{{ m.icono }}</span>
            {{ m.codigo }}
          </button>
        </div>
      </div>

      <!-- Paso 2: Cliente -->
      <div v-if="moneda" class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <label class="block text-sm font-medium text-gray-600">Cliente</label>
        <ClienteSelector v-model="cliente" />
      </div>

      <!-- Paso 3: Calculadora -->
      <div v-if="moneda && cliente?.id" class="bg-white border border-gray-200 rounded-xl p-5">
        <CalculadoraBidireccional
          v-model:monto="montoDivisa"
          v-model:bolivares="montoVes"
          v-model:tasa="tasa"
          tipo="compra"
          :moneda="moneda"
          :quote-codigo="'VES'"
          :quote-simbolo="'Bs.'"
          :quote-nombre="'Bolívar'"
          :par-str="`${moneda}/VES`"
          :tasa-sugerida="tasaReferencia"
        />
      </div>

      <!-- Paso 4: Recepción de divisa -->
      <div v-if="moneda && cliente?.id && montoDivisaNum > 0" class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Recepción de {{ moneda }}</h3>
        <p class="text-xs text-gray-500">El cliente entrega {{ moneda }} a la casa</p>
        <div class="flex justify-between text-sm bg-gray-50 rounded-lg px-3 py-2">
          <span class="text-gray-500">Total:</span>
          <span class="font-bold text-gray-800">{{ fmt(montoDivisaNum) }} {{ moneda }}</span>
        </div>
        <div class="flex justify-between text-sm bg-gray-50 rounded-lg px-3 py-2">
          <span class="text-gray-500">Restante:</span>
          <span class="font-bold" :class="restanteDivisa === 0 ? 'text-green-600' : 'text-amber-600'">{{ fmt(restanteDivisa) }} {{ moneda }}</span>
        </div>

        <div v-if="movsDivisa.length" class="space-y-1.5">
          <div v-for="(tx, i) in movsDivisa" :key="i"
            class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2 text-sm">
            <span class="text-green-700">
              <Iconoir name="check" class="w-4 h-4 text-green-500" />
              {{ tx._origen }} → {{ tx._destino }} · <span class="font-bold">{{ fmt(tx.monto) }} {{ moneda }}</span>
              <span class="text-gray-500 ml-1">· {{ tx.metodo_pago }}</span>
            </span>
            <div class="flex gap-2 ml-2 shrink-0">
              <button type="button" @click="editarMovDivisa(i)"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">Editar</button>
              <button type="button" @click="eliminarMovDivisa(i)"
                class="text-xs text-red-500 hover:text-red-700 font-medium"><Iconoir name="x-mark" class="w-4 h-4" /></button>
            </div>
          </div>
        </div>

        <div v-if="restanteDivisa > 0 || movDivisaEditandoIdx !== null" class="space-y-3 pt-2 border-t border-gray-100">
          <p v-if="movDivisaEditandoIdx !== null" class="text-xs text-blue-600 font-medium">Editando recepción #{{ movDivisaEditandoIdx + 1 }}</p>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Método de recepción</label>
            <select v-model="txDivisa.metodo_pago" required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar método</option>
              <option value="efectivo">Efectivo</option>
              <option value="transferencia">Transferencia</option>
              <option value="zelle">Zelle</option>
              <option value="binance">Binance</option>
              <option value="otro">Otro</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Monto</label>
            <input :value="fmt(txDivisa.monto)" @input="onMontoDivisaInput($event)"
              type="text" inputmode="decimal" :placeholder="fmt(restanteDivisa)"
              class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 outline-none"
              :class="parseFloat(txDivisa.monto) > restanteDivisa + 0.01 ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500'" />
            <p v-if="parseFloat(txDivisa.monto) > restanteDivisa + 0.01" class="text-xs text-red-500 mt-1">
              Excede el restante ({{ fmt(restanteDivisa) }} {{ moneda }})
            </p>
          </div>
          <div v-if="txDivisa.metodo_pago && txDivisa.metodo_pago !== 'efectivo'">
            <label class="block text-xs text-gray-500 mb-1">Comprobante</label>
            <input v-model="txDivisa.comprobante" placeholder="N° de referencia..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <button type="button" @click="confirmarMovDivisa" :disabled="!txDivisaValido"
            class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white text-sm font-semibold rounded-xl transition">
            {{ movDivisaEditandoIdx !== null ? 'Actualizar recepción' : 'Confirmar recepción' }}
          </button>
        </div>
      </div>

      <!-- Paso 5: Pago en Bs -->
      <div v-if="moneda && cliente?.id && montoDivisaNum > 0 && restanteDivisa === 0 && movsDivisa.length > 0" class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Pago al cliente en Bs</h3>
        <p class="text-xs text-gray-500">La casa entrega bolívares al cliente</p>
        <div class="flex justify-between text-sm bg-gray-50 rounded-lg px-3 py-2">
          <span class="text-gray-500">Total:</span>
          <span class="font-bold text-gray-800">Bs. {{ fmt(montoVesNum) }}</span>
        </div>
        <div class="flex justify-between text-sm bg-gray-50 rounded-lg px-3 py-2">
          <span class="text-gray-500">Restante:</span>
          <span class="font-bold" :class="restanteVes === 0 ? 'text-green-600' : 'text-amber-600'">Bs. {{ fmt(restanteVes) }}</span>
        </div>

        <div v-if="movsVes.length" class="space-y-1.5">
          <div v-for="(tx, i) in movsVes" :key="i"
            class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2 text-sm">
            <span class="text-green-700">
              <Iconoir name="check" class="w-4 h-4 text-green-500" />
              {{ tx._origen }} · <span class="font-bold">Bs. {{ fmt(tx.monto) }}</span>
              <span class="text-gray-500 ml-1">· {{ tx.metodo_pago }}</span>
            </span>
            <div class="flex gap-2 ml-2 shrink-0">
              <button type="button" @click="editarMovVes(i)"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">Editar</button>
              <button type="button" @click="eliminarMovVes(i)"
                class="text-xs text-red-500 hover:text-red-700 font-medium"><Iconoir name="x-mark" class="w-4 h-4" /></button>
            </div>
          </div>
        </div>

        <div v-if="restanteVes > 0 || movVesEditandoIdx !== null" class="space-y-3 pt-2 border-t border-gray-100">
          <p v-if="movVesEditandoIdx !== null" class="text-xs text-blue-600 font-medium">Editando pago #{{ movVesEditandoIdx + 1 }}</p>
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
            <input :value="fmt(txVes.monto)" @input="onMontoVesInput($event)"
              type="text" inputmode="decimal" :placeholder="fmt(restanteVes)"
              class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 outline-none"
              :class="parseFloat(txVes.monto) > restanteVes + 0.01 ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500'" />
            <p v-if="parseFloat(txVes.monto) > restanteVes + 0.01" class="text-xs text-red-500 mt-1">
              Excede el restante (Bs. {{ fmt(restanteVes) }})
            </p>
          </div>
          <div v-if="txVes.metodo_pago && txVes.metodo_pago !== 'efectivo'">
            <label class="block text-xs text-gray-500 mb-1">Comprobante</label>
            <input v-model="txVes.comprobante" placeholder="N° de referencia..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <button type="button" @click="confirmarMovVes" :disabled="!txVesValido"
            class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white text-sm font-semibold rounded-xl transition">
            {{ movVesEditandoIdx !== null ? 'Actualizar pago' : 'Confirmar pago' }}
          </button>
        </div>
      </div>

      <!-- Paso 6: Resumen + enviar -->
      <div v-if="moneda && cliente?.id && montoDivisaNum > 0 && montoVesNum > 0" class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Resumen</h3>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div class="bg-gray-50 rounded-lg px-3 py-2">
            <p class="text-gray-400">Recibir {{ moneda }}</p>
            <p class="font-bold text-gray-800">{{ fmt(montoDivisaNum) }} {{ moneda }}</p>
          </div>
          <div class="bg-gray-50 rounded-lg px-3 py-2">
            <p class="text-gray-400">Pagar en Bs</p>
            <p class="font-bold text-gray-800">Bs. {{ fmt(montoVesNum) }}</p>
          </div>
          <div class="bg-gray-50 rounded-lg px-3 py-2">
            <p class="text-gray-400">Tasa</p>
            <p class="font-bold text-gray-800">{{ tasa || '—' }}</p>
          </div>
          <div class="bg-gray-50 rounded-lg px-3 py-2">
            <p class="text-gray-400">Movimientos</p>
            <p class="font-bold text-gray-800">{{ movsDivisa.length + movsVes.length }}</p>
          </div>
        </div>

        <div v-if="sumaValida"
          class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-700 text-center font-medium">
          <Iconoir name="check" class="text-green-500" /> Transacciones balanceadas
        </div>
        <div v-else class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-700 text-center">
          Faltan movimientos por registrar
        </div>

        <AppErrorState v-if="error" :message="error" :retry="false" />

        <button type="submit" :disabled="enviando || !sumaValida"
          class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
          <span v-if="enviando" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ enviando ? 'Creando solicitud...' : 'Crear solicitud de compra' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOperacionesStore } from '../../stores/operaciones.js'
import { useNotification } from '@/composables/useNotification'
import { useFormatting } from '@/composables/useFormatting'
import { useTasasReferencia } from '@/composables/useTasasReferencia'
import { useTitulares } from '@/composables/useTitulares'
import { useAuthStore } from '@/stores/auth'
import ClienteSelector from '@/components/clientes/ClienteSelector.vue'
import CalculadoraBidireccional from '@/components/operaciones/CalculadoraBidireccional.vue'
import Iconoir from '@/components/common/Iconoir.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import api from '@/api/axios'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const store = useOperacionesStore()
const notifier = useNotification()
const { formatMoney } = useFormatting()
const tasasRef = useTasasReferencia()
const titulares = useTitulares()

const today = new Date().toISOString().slice(0, 10)
const moneda = ref('')
const cliente = ref(null)
const montoDivisa = ref('')
const montoVes = ref('')
const tasa = ref('')
const error = ref('')
const enviando = ref(false)

const movsDivisa = ref([])
const movDivisaEditandoIdx = ref(null)
const movsVes = ref([])
const movVesEditandoIdx = ref(null)

const txDivisa = reactive({ monto: '', metodo_pago: '', comprobante: '' })
const txVes = reactive({ monto: '', metodo_pago: '', comprobante: '' })

const allCuentas = ref([])
const cuentasIntermedius = ref([])
const cuentasCliente = ref([])
const monedas = ref([])

const monedasDisponibles = [
  { codigo: 'USD', nombre: 'Dólar Estadounidense', icono: '$' },
  { codigo: 'USDT', nombre: 'Tether', icono: '₮' },
  { codigo: 'EUR', nombre: 'Euro', icono: '€' },
  { codigo: 'COP', nombre: 'Peso Colombiano', icono: '$' },
]

const montoVesNum = computed(() => parseFloat(montoVes.value) || 0)
const montoDivisaNum = computed(() => parseFloat(montoDivisa.value) || 0)
const tasaNum = computed(() => parseFloat(tasa.value) || 0)

const sumaDivisa = computed(() => movsDivisa.value.reduce((s, t) => s + t.monto, 0))
const sumaVes = computed(() => movsVes.value.reduce((s, t) => s + t.monto, 0))

const restanteDivisa = computed(() => Math.max(0, montoDivisaNum.value - sumaDivisa.value))
const restanteVes = computed(() => Math.max(0, montoVesNum.value - sumaVes.value))

const sumaValida = computed(() =>
  movsDivisa.value.length > 0 && movsVes.value.length > 0
  && Math.abs(sumaDivisa.value - montoDivisaNum.value) <= 0.01
  && Math.abs(sumaVes.value - montoVesNum.value) <= 0.01
)

const tasaReferencia = computed(() => {
  if (!moneda.value) return null
  const ref = tasasRef.refTasaPorMoneda(moneda.value)
  return ref ? parseFloat(ref).toFixed(2) : null
})

function fmt(v) {
  if (v === '' || v == null) return ''
  const s = String(v).replace(/,/g, '')
  const n = parseFloat(s)
  if (isNaN(n)) return s
  const parts = s.split('.')
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  return parts.join('.')
}

function labelCuenta(c) {
  const tipo = c.banco?.nombre || c.tipo || 'cuenta'
  let saldo = ''
  if (c.titular_id && c.saldo_cache != null) {
    saldo = ` · Saldo: ${c.moneda?.simbolo || ''}${parseFloat(c.saldo_cache).toLocaleString('es-VE', { minimumFractionDigits: 2 })}`
  }
  return `${c.alias} · ${tipo}${saldo}`
}

function cuentaIntermediusPorTipo(tipo, monedaCodigo) {
  return cuentasIntermedius.value.find(c => c.moneda?.codigo === monedaCodigo && c.tipo === tipo) || null
}

const cuentaIntermediusVesEfectivo = computed(() => cuentaIntermediusPorTipo('efectivo', 'VES'))
const cuentaIntermediusVesBanco = computed(() => cuentaIntermediusPorTipo('banco', 'VES'))
const cuentaClienteVesEfectivo = computed(() =>
  cuentasCliente.value.find(c => c.cliente_id == cliente.value?.id && c.moneda?.codigo === 'VES' && c.tipo === 'efectivo') || null
)

const cuentaClienteDivisa = computed(() => {
  if (!txDivisa.metodo_pago) return null
  const map = {
    efectivo: 'efectivo',
    transferencia: 'banco',
    zelle: 'zelle',
    binance: 'plataforma',
  }
  const tipo = map[txDivisa.metodo_pago]
  if (tipo) return cuentasCliente.value.find(c => c.cliente_id == cliente.value?.id && c.moneda?.codigo === moneda.value && c.tipo === tipo) || null
  return cuentasCliente.value.find(c => c.cliente_id == cliente.value?.id && c.moneda?.codigo === moneda.value) || null
})

const cuentaDestinoDivisa = computed(() => {
  if (!txDivisa.metodo_pago) return null
  const map = {
    efectivo: 'efectivo',
    transferencia: 'banco',
    zelle: 'zelle',
    binance: 'plataforma',
  }
  const tipo = map[txDivisa.metodo_pago]
  if (tipo) return cuentaIntermediusPorTipo(tipo, moneda.value)
  return cuentasIntermedius.value.find(c => c.moneda?.codigo === moneda.value) || null
})

const cuentaOrigenVes = computed(() => {
  if (!txVes.metodo_pago) return null
  if (txVes.metodo_pago === 'efectivo') return cuentaIntermediusVesEfectivo.value
  return cuentaIntermediusVesBanco.value
})

const txDivisaValido = computed(() => {
  const monto = parseFloat(txDivisa.monto)
  if (!txDivisa.metodo_pago || isNaN(monto) || monto <= 0) return false
  const max = movDivisaEditandoIdx.value !== null
    ? restanteDivisa.value + movsDivisa.value[movDivisaEditandoIdx.value]?.monto
    : restanteDivisa.value
  return monto <= max + 0.01
})

const txVesValido = computed(() => {
  const monto = parseFloat(txVes.monto)
  if (!txVes.metodo_pago || isNaN(monto) || monto <= 0) return false
  const max = movVesEditandoIdx.value !== null
    ? restanteVes.value + movsVes.value[movVesEditandoIdx.value]?.monto
    : restanteVes.value
  return monto <= max + 0.01
})

function onMontoDivisaInput(e) {
  txDivisa.monto = String(e.target.value).replace(/,/g, '')
}

function onMontoVesInput(e) {
  txVes.monto = String(e.target.value).replace(/,/g, '')
}

function buildTxDivisa() {
  const origen = cuentaClienteDivisa.value
  const destino = cuentaDestinoDivisa.value
  return {
    cuenta_origen_id: origen ? Number(origen.id) : null,
    cuenta_destino_id: destino ? Number(destino.id) : null,
    moneda_id: monedas.value.find(m => m.codigo === moneda.value)?.id,
    monto: parseFloat(txDivisa.monto),
    tasa_aplicada: tasaNum.value,
    metodo_pago: txDivisa.metodo_pago,
    comprobante: txDivisa.comprobante || null,
    _origen: origen?.alias || cliente.value?.nombre || 'Cliente',
    _destino: destino?.alias || 'Intermedius',
  }
}

function buildTxVes() {
  const origen = cuentaOrigenVes.value
  const destino = cuentaClienteVesEfectivo.value
  return {
    cuenta_origen_id: origen ? Number(origen.id) : null,
    cuenta_destino_id: destino ? Number(destino.id) : null,
    moneda_id: monedas.value.find(m => m.codigo === 'VES')?.id,
    monto: parseFloat(txVes.monto),
    tasa_aplicada: tasaNum.value,
    metodo_pago: txVes.metodo_pago,
    comprobante: txVes.comprobante || null,
    _origen: origen?.alias || 'Intermedius',
    _destino: destino?.alias || cliente.value?.nombre || 'Cliente',
  }
}

function confirmarMovDivisa() {
  if (!txDivisaValido.value) return
  const tx = buildTxDivisa()
  if (movDivisaEditandoIdx.value !== null) {
    movsDivisa.value[movDivisaEditandoIdx.value] = tx
    movDivisaEditandoIdx.value = null
  } else {
    movsDivisa.value.push(tx)
  }
  txDivisa.monto = ''
  txDivisa.metodo_pago = ''
  txDivisa.comprobante = ''
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
  resetTxVes()
}

function resetTxVes() {
  txVes.monto = ''
  txVes.metodo_pago = ''
  txVes.comprobante = ''
}

function editarMovDivisa(idx) {
  const tx = movsDivisa.value[idx]
  txDivisa.monto = tx.monto
  txDivisa.metodo_pago = tx.metodo_pago
  txDivisa.comprobante = tx.comprobante || ''
  movDivisaEditandoIdx.value = idx
}

function editarMovVes(idx) {
  const tx = movsVes.value[idx]
  txVes.monto = tx.monto
  txVes.metodo_pago = tx.metodo_pago
  txVes.comprobante = tx.comprobante || ''
  movVesEditandoIdx.value = idx
}

function eliminarMovDivisa(idx) {
  movsDivisa.value.splice(idx, 1)
  if (movDivisaEditandoIdx.value === idx) {
    movDivisaEditandoIdx.value = null
    txDivisa.monto = ''
    txDivisa.metodo_pago = ''
    txDivisa.comprobante = ''
  } else if (movDivisaEditandoIdx.value !== null && movDivisaEditandoIdx.value > idx) {
    movDivisaEditandoIdx.value--
  }
}

function eliminarMovVes(idx) {
  movsVes.value.splice(idx, 1)
  if (movVesEditandoIdx.value === idx) {
    movVesEditandoIdx.value = null
    resetTxVes()
  } else if (movVesEditandoIdx.value !== null && movVesEditandoIdx.value > idx) {
    movVesEditandoIdx.value--
  }
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

async function cargarMonedas() {
  try {
    const { data } = await api.get('/monedas')
    monedas.value = Array.isArray(data) ? data : (data.data || [])
  } catch { }
}

async function registrarCompra() {
  error.value = ''
  enviando.value = true
  try {
    const payload = {
      fecha: today,
      tipo_codigo: 'compra_usd',
      moneda_codigo: moneda.value,
      operador_id: Number(auth.user?.id),
      tasa_aplicada: tasaNum.value,
      tasa_mercado_snapshot: tasaReferencia.value || tasaNum.value,
      cliente_id: cliente.value?.id,
      monto_solicitado: montoDivisaNum.value,
      transacciones: [...movsDivisa.value, ...movsVes.value].map(tx => ({
        cuenta_origen_id: tx.cuenta_origen_id,
        cuenta_destino_id: tx.cuenta_destino_id,
        moneda_id: tx.moneda_id,
        monto: tx.monto,
        tasa_aplicada: tx.tasa_aplicada,
        metodo_pago: tx.metodo_pago,
        comprobante: tx.comprobante || null,
      })),
    }
    await store.solicitar(payload)
    notifier.success('Solicitud de compra creada exitosamente')
    const op = store.detail
    router.push(op?.id ? `/operaciones/${op.id}/gestionar` : '/operaciones')
  } catch (err) {
    error.value = err.response?.data?.message || err.message
    notifier.error(error.value)
  }
  enviando.value = false
}

watch(() => txDivisa.metodo_pago, () => {
  if (movDivisaEditandoIdx.value === null && restanteDivisa.value > 0) {
    txDivisa.monto = restanteDivisa.value
  }
})

watch(() => txVes.metodo_pago, () => {
  if (movVesEditandoIdx.value === null && restanteVes.value > 0) {
    txVes.monto = restanteVes.value
  }
})

watch(() => cliente.value?.id, () => {
  movsDivisa.value = []
  movDivisaEditandoIdx.value = null
  movsVes.value = []
  movVesEditandoIdx.value = null
  txDivisa.monto = ''
  txDivisa.metodo_pago = ''
  txDivisa.comprobante = ''
  resetTxVes()
})

watch(moneda, () => {
  movsDivisa.value = []
  movDivisaEditandoIdx.value = null
  movsVes.value = []
  movVesEditandoIdx.value = null
  txDivisa.monto = ''
  txDivisa.metodo_pago = ''
  txDivisa.comprobante = ''
  resetTxVes()
})

watch(restanteDivisa, (val) => {
  if (val > 0 && movDivisaEditandoIdx.value === null && !txDivisa.monto) {
    txDivisa.monto = val
  }
}, { immediate: true })

onMounted(async () => {
  if (route.params.moneda) {
    const val = route.params.moneda
    if (monedasDisponibles.some(m => m.codigo === val)) {
      moneda.value = val
    }
  }
  await Promise.all([tasasRef.fetch(), titulares.fetchAll(), cargarMonedas()])
  await cargarCuentas()
})
</script>
