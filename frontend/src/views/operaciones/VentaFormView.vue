<template>
  <div class="max-w-7xl mx-auto pb-6">
    <div class="flex items-center gap-3 mb-4">
      <button @click="$router.back()" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-ink-muted hover:bg-surface-muted rounded-lg transition"><Iconoir name="arrow-left" class="w-4 h-4" /> Volver</button>
      <h2 class="text-xl font-bold text-heading">Nueva venta</h2>
    </div>

    <form @submit.prevent="registrarVenta">
      <div class="bg-white dark:bg-surface rounded-xl border border-edge divide-y divide-edge">
        <!-- Moneda -->
        <div class="p-4 space-y-3">
          <label class="block text-sm font-semibold text-ink-muted uppercase tracking-wider">Moneda a vender</label>
          <div class="grid grid-cols-4 gap-2">
            <button type="button" v-for="m in monedasDisponibles" :key="m.codigo" @click="moneda = m.codigo"
              class="py-2.5 rounded-lg text-sm font-medium transition active:scale-[0.98] border-2"
              :class="moneda === m.codigo ? monedaColor(m.color) : 'bg-white dark:bg-surface-muted border-edge text-ink-muted hover:border-edge-strong'">
              <Iconoir :name="m.icono" class="w-5 h-5 mx-auto" :class="iconoColor[m.color] || 'text-ink-muted'" />
              <span class="text-xs">{{ m.codigo }}</span>
            </button>
          </div>
        </div>

        <!-- Cliente -->
        <div v-if="moneda" class="p-4">
          <label class="block text-sm font-semibold text-ink-muted uppercase tracking-wider mb-2">Cliente</label>
          <ClienteSelector v-model="cliente" flat />
        </div>

        <!-- Calculadora -->
        <div v-if="moneda && cliente?.id" class="p-4">
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
            flat
          />
        </div>

        <!-- Movimientos -->
        <div v-if="moneda && cliente?.id && montoDivisaNum > 0 && montoVesNum > 0" class="p-0">
          <div :class="['grid', mostrarDivisa ? 'lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x' : 'grid-cols-1', 'divide-edge']">

            <!-- Columna: Pago en Bs -->
            <div class="p-5 space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-ink">Pago del cliente en Bs</h3>
                <span class="text-sm font-medium" :class="restanteVes === 0 ? 'text-success' : 'text-warning'">
                  Bs. {{ fmt(restanteVes) }} restante
                </span>
              </div>

              <div v-if="movsVes.length" class="space-y-1.5">
                <div v-for="(tx, i) in movsVes" :key="i"
                  class="flex items-center justify-between bg-success-soft border border-success-edge rounded-lg px-3 py-2 text-sm">
                  <span class="text-success-strong truncate">
                    <Iconoir name="check" class="w-4 h-4 text-success inline" />
                    {{ tx._registroLabel }} · <span class="font-semibold">Bs. {{ fmt(tx.monto) }}</span>
                  </span>
                  <div class="flex gap-2 ml-1 shrink-0">
                    <button type="button" @click="editarMovVes(i)" class="text-gold-dark hover:text-gold-dark">Editar</button>
                    <button type="button" @click="eliminarMovVes(i)" class="text-danger hover:text-danger-strong"><Iconoir name="x-mark" class="w-4 h-4" /></button>
                  </div>
                </div>
              </div>

              <div v-if="restanteVes > 0 || movVesEditandoIdx !== null" class="space-y-3 pt-3 border-t border-edge">
                <div class="grid grid-cols-2 gap-3">
                  <select v-model="txVes.metodo_pago" required
                    class="px-3 py-2 border border-edge-strong rounded-lg text-sm bg-white dark:bg-surface-muted focus:ring-2 focus:ring-gold outline-none">
                    <option value="">Método</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="pagomovil">Pago móvil</option>
                    <option value="transferencia">Transferencia</option>
                  </select>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted text-sm">Bs.</span>
                    <input :value="fmt(txVes.monto)" @input="onMontoVesInput($event)"
                      type="text" inputmode="decimal" placeholder="0,00"
                      class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm focus:ring-2 outline-none"
                      :class="parseFloat(txVes.monto) > restanteVes + 0.01 ? 'border-danger-edge focus:ring-danger' : 'border-edge-strong focus:ring-gold'" />
                  </div>
                </div>
                <p v-if="parseFloat(txVes.monto) > restanteVes + 0.01" class="text-xs text-danger">
                  Excede el restante (Bs. {{ fmt(restanteVes) }})
                </p>
                <div v-if="txVes.metodo_pago && txVes.metodo_pago !== 'efectivo'">
                  <input v-model="txVes.comprobante" placeholder="N° de referencia..."
                    class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm focus:ring-2 focus:ring-gold outline-none" />
                </div>
                <button type="button" @click="confirmarMovVes" :disabled="!txVesValido"
                  class="w-full py-2 bg-gold hover:bg-gold-dark disabled:opacity-50 text-white text-sm font-semibold rounded-lg transition active:scale-[0.98]">
                  {{ movVesEditandoIdx !== null ? 'Actualizar' : 'Confirmar pago' }}
                </button>
              </div>
              <div v-else class="text-center py-2">
                <span class="text-sm text-success font-medium">✅ Completado</span>
              </div>
            </div>

            <!-- Columna: Entrega en divisa -->
            <div v-if="mostrarDivisa" class="p-5 space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-ink">Entrega en {{ moneda }}</h3>
                <span class="text-sm font-medium" :class="restanteDivisa === 0 ? 'text-success' : 'text-warning'">
                  {{ fmt(restanteDivisa) }} {{ moneda }} restante
                </span>
              </div>

              <div v-if="movsDiv.length" class="space-y-1.5">
                <div v-for="(tx, i) in movsDiv" :key="i"
                  class="flex items-center justify-between bg-success-soft border border-success-edge rounded-lg px-3 py-2 text-sm">
                  <span class="text-success-strong truncate">
                    <Iconoir name="check" class="w-4 h-4 text-success inline" />
                    {{ fmt(tx.monto) }} {{ moneda }} · {{ tx.metodo_pago }}
                  </span>
                  <div class="flex gap-2 ml-1 shrink-0">
                    <button type="button" @click="editarMovDiv(i)" class="text-gold-dark hover:text-gold-dark">Editar</button>
                    <button type="button" @click="eliminarMovDiv(i)" class="text-danger hover:text-danger-strong"><Iconoir name="x-mark" class="w-4 h-4" /></button>
                  </div>
                </div>
              </div>

              <div v-if="restanteDivisa > 0 || movDivEditandoIdx !== null" class="space-y-3 pt-3 border-t border-edge">
                <select v-model="txDiv.cuenta_origen_id" required
                  class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm bg-white dark:bg-surface-muted focus:ring-2 focus:ring-gold outline-none">
                  <option value="">Cuenta origen (Intermedius)</option>
                  <option v-for="c in cuentasDivisaIntermedius" :key="c.id" :value="c.id">{{ labelCuenta(c) }}</option>
                </select>
                <select v-model="txDiv.cuenta_destino_id" required :disabled="!txDiv.cuenta_origen_id"
                  class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm bg-white dark:bg-surface-muted focus:ring-2 focus:ring-gold outline-none disabled:bg-surface-muted disabled:text-ink-muted">
                  <option value="">{{ txDiv.cuenta_origen_id ? 'Cuenta destino (Cliente)' : 'Elegí origen primero' }}</option>
                  <option v-for="c in cuentasDivisaDestinoFiltradas" :key="c.id" :value="c.id">{{ labelCuenta(c) }}</option>
                </select>
                <div class="space-y-1.5">
                  <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wide">Monto a entregar (restante: {{ fmt(restanteDivisa) }} {{ moneda }})</label>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="relative">
                      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted text-sm">{{ moneda }}</span>
                      <input :value="fmt(txDiv.monto)" @input="onMontoDivInput($event)"
                        type="text" inputmode="decimal" :placeholder="fmt(restanteDivisa)"
                        class="w-full pl-10 pr-3 py-2 border rounded-lg text-sm focus:ring-2 outline-none"
                        :class="parseFloat(txDiv.monto) > restanteDivisa + 0.01 ? 'border-danger-edge focus:ring-danger' : 'border-edge-strong focus:ring-gold'" />
                    </div>
                    <select v-model="txDiv.metodo_pago" required :disabled="!txDiv.cuenta_origen_id"
                      class="px-3 py-2 border border-edge-strong rounded-lg text-sm bg-white dark:bg-surface-muted focus:ring-2 focus:ring-gold outline-none disabled:bg-surface-muted disabled:text-ink-muted">
                      <option value="">{{ txDiv.cuenta_origen_id ? 'Método' : 'Primero elige la cuenta origen' }}</option>
                      <option v-for="m in metodosDivDisponibles" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                  </div>
                </div>
                <p v-if="parseFloat(txDiv.monto) > restanteDivisa + 0.01" class="text-xs text-danger">
                  Excede el restante ({{ fmt(restanteDivisa) }} {{ moneda }})
                </p>
                <div v-if="txDiv.metodo_pago && txDiv.metodo_pago !== 'efectivo'">
                  <input v-model="txDiv.comprobante" placeholder="N° de referencia..."
                    class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm focus:ring-2 focus:ring-gold outline-none" />
                </div>
                <button type="button" @click="confirmarMovDiv" :disabled="!txDivValido"
                  class="w-full py-2 bg-gold hover:bg-gold-dark disabled:opacity-50 text-white text-sm font-semibold rounded-lg transition active:scale-[0.98]">
                  {{ movDivEditandoIdx !== null ? 'Actualizar' : 'Confirmar entrega' }}
                </button>
              </div>
              <div v-else class="text-center py-2">
                <span class="text-sm text-success font-medium">✅ Completado</span>
              </div>
            </div>

          </div>
        </div>

        <!-- Resumen + enviar -->
        <div v-if="moneda && cliente?.id && montoDivisaNum > 0 && montoVesNum > 0" class="p-5">
          <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4 text-sm">
              <div class="text-ink-muted">
                <span class="text-ink-muted">Divisa:</span>
                <span class="font-semibold text-heading ml-1">{{ fmt(montoDivisaNum) }} {{ moneda }}</span>
              </div>
              <div class="text-ink-muted">
                <span class="text-ink-muted">Bs:</span>
                <span class="font-semibold text-heading ml-1">Bs. {{ fmt(montoVesNum) }}</span>
              </div>
              <div class="text-ink-muted">
                <span class="text-ink-muted">Tasa:</span>
                <span class="font-semibold text-heading ml-1">{{ tasa || '—' }}</span>
              </div>
            </div>
            <div v-if="sumaValida" class="text-sm text-success font-medium flex items-center gap-1">
              <Iconoir name="check" class="w-4 h-4" /> Balanceado
            </div>
            <div v-else class="text-sm text-warning font-medium">
              ⚠ Pendiente
            </div>
          </div>

          <AppErrorState v-if="error" :message="error" :retry="false" />

          <button type="submit" :disabled="enviando || !sumaValida"
            class="w-full mt-3 bg-success hover:bg-success-strong disabled:opacity-50 text-white dark:text-navy font-semibold py-2.5 rounded-xl transition active:scale-[0.98] flex items-center justify-center gap-2 text-sm">
            <span v-if="enviando" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ enviando ? 'Registrando...' : 'Registrar Venta' }}
          </button>
        </div>
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
import Iconoir from '@/components/common/Iconoir.vue'
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
const movsDiv = ref([])
const movDivEditandoIdx = ref(null)

const txVes = reactive({ monto: '', metodo_pago: '', comprobante: '' })
const txDiv = reactive({ cuenta_origen_id: '', cuenta_destino_id: '', monto: '', metodo_pago: '', comprobante: '' })

const allCuentas = ref([])
const cuentasIntermedius = ref([])
const cuentasCliente = ref([])
const registrosPago = ref([])
const monedas = ref([])

const monedasDisponibles = [
  { codigo: 'USD', nombre: 'Dólar Estadounidense', icono: 'banknotes', color: 'green' },
  { codigo: 'USDT', nombre: 'Tether', icono: 'currency-dollar', color: 'blue' },
  { codigo: 'EUR', nombre: 'Euro', icono: 'currency-euro', color: 'amber' },
  { codigo: 'COP', nombre: 'Peso Colombiano', icono: 'credit-card', color: 'purple' },
]

function monedaColor(c) {
  const map = { green: 'bg-success-soft border-success text-success-strong', blue: 'bg-info-soft border-info text-info-strong', purple: 'bg-violet-soft border-violet text-violet-strong', amber: 'bg-warning-soft border-warning text-warning-strong' }
  return map[c] || 'bg-info-soft border-info text-info-strong'
}
const iconoColor = { green: 'text-success', blue: 'text-gold-dark', purple: 'text-violet', amber: 'text-warning' }

const montoVesNum = computed(() => parseFloat(montoVes.value) || 0)
const montoDivisaNum = computed(() => parseFloat(montoDivisa.value) || 0)
const tasaNum = computed(() => parseFloat(tasa.value) || 0)

const sumaVes = computed(() => movsVes.value.reduce((s, t) => s + t.monto, 0))
const sumaDivisa = computed(() => movsDiv.value.reduce((s, t) => s + t.monto, 0))

const restanteVes = computed(() => Math.max(0, montoVesNum.value - sumaVes.value))
const restanteDivisa = computed(() => Math.max(0, montoDivisaNum.value - sumaDivisa.value))

const sumaValida = computed(() =>
  movsVes.value.length > 0 && movsDiv.value.length > 0
  && Math.abs(sumaVes.value - montoVesNum.value) <= 0.01
  && Math.abs(sumaDivisa.value - montoDivisaNum.value) <= 0.01
)

const mostrarDivisa = computed(() =>
  restanteVes.value === 0 && movsVes.value.length > 0
)

const tasaReferencia = computed(() => {
  if (!moneda.value) return null
  const ref = tasasRef.refTasaPorMoneda(moneda.value)
  return ref ? parseFloat(ref).toFixed(2) : null
})

const cuentaIntermediusVes = computed(() => {
  return cuentasIntermedius.value.find(c => c.moneda?.codigo === 'VES') || null
})

const txVesValido = computed(() => {
  const monto = parseFloat(txVes.monto)
  if (!txVes.metodo_pago || isNaN(monto) || monto <= 0) return false
  const max = movVesEditandoIdx.value !== null
    ? restanteVes.value + movsVes.value[movVesEditandoIdx.value]?.monto
    : restanteVes.value
  return monto <= max + 0.01
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

const txDivValido = computed(() => {
  const monto = parseFloat(txDiv.monto)
  if (!txDiv.cuenta_origen_id || !txDiv.cuenta_destino_id || isNaN(monto) || monto <= 0 || !txDiv.metodo_pago) return false
  const max = movDivEditandoIdx.value !== null
    ? restanteDivisa.value + movsDiv.value[movDivEditandoIdx.value]?.monto
    : restanteDivisa.value
  return monto <= max + 0.01
})

function resetTxForm(form) {
  form.monto = ''
  form.metodo_pago = ''
  form.comprobante = ''
}

function onMontoVesInput(e) {
  txVes.monto = String(e.target.value).replace(/,/g, '')
}

function onMontoDivInput(e) {
  txDiv.monto = String(e.target.value).replace(/,/g, '')
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

  const destinoIntermedius = esEfectivo
    ? cuentasIntermedius.value.find(c => c.moneda?.codigo === 'VES' && c.tipo === 'efectivo')
    : cuentasIntermedius.value.find(c => c.moneda?.codigo === 'VES' && c.tipo === 'banco')

  return {
    cuenta_origen_id: null,
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

function buildTxDiv() {
  return {
    cuenta_origen_id: txDiv.cuenta_origen_id ? Number(txDiv.cuenta_origen_id) : null,
    cuenta_destino_id: txDiv.cuenta_destino_id ? Number(txDiv.cuenta_destino_id) : null,
    moneda_id: monedas.value.find(m => m.codigo === moneda.value)?.id,
    monto: parseFloat(txDiv.monto),
    tasa_aplicada: tasaNum.value,
    metodo_pago: txDiv.metodo_pago,
    comprobante: txDiv.comprobante || null,
    _origen: allCuentas.value.find(c => c.id == txDiv.cuenta_origen_id)?.alias || '',
    _destino: allCuentas.value.find(c => c.id == txDiv.cuenta_destino_id)?.alias || '',
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
  const tx = buildTxDiv()
  if (movDivEditandoIdx.value !== null) {
    movsDiv.value[movDivEditandoIdx.value] = tx
    movDivEditandoIdx.value = null
  } else {
    movsDiv.value.push(tx)
  }
  resetTxDiv(txDiv)
  txDiv.monto = restanteDivisa.value > 0 ? restanteDivisa.value : ''
}

function editarMovVes(idx) {
  const tx = movsVes.value[idx]
  txVes.monto = tx.monto
  txVes.metodo_pago = tx.metodo_pago
  txVes.comprobante = tx.comprobante || ''
  movVesEditandoIdx.value = idx
}

function editarMovDiv(idx) {
  const tx = movsDiv.value[idx]
  txDiv.cuenta_origen_id = tx.cuenta_origen_id || ''
  txDiv.cuenta_destino_id = tx.cuenta_destino_id || ''
  txDiv.monto = tx.monto
  txDiv.metodo_pago = tx.metodo_pago
  txDiv.comprobante = tx.comprobante || ''
  movDivEditandoIdx.value = idx
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

function eliminarMovDiv(idx) {
  movsDiv.value.splice(idx, 1)
  if (movDivEditandoIdx.value === idx) {
    movDivEditandoIdx.value = null
    resetTxDiv(txDiv)
  } else if (movDivEditandoIdx.value !== null && movDivEditandoIdx.value > idx) {
    movDivEditandoIdx.value--
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
      transacciones: [...movsVes.value, ...movsDiv.value].map(tx => ({
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
  movsDiv.value = []
  movDivEditandoIdx.value = null
  resetTxForm(txVes)
  resetTxDiv(txDiv)
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
  if (movDivEditandoIdx.value !== null) return
  txDiv.monto = val > 0 ? val : ''
}, { immediate: true })

watch(moneda, () => {
  movsVes.value = []
  movVesEditandoIdx.value = null
  movsDiv.value = []
  movDivEditandoIdx.value = null
  resetTxForm(txVes)
  resetTxDiv(txDiv)
})

onMounted(async () => {
  await Promise.all([tasasRef.fetch(), titulares.fetchAll(), cargarMonedas()])
  await cargarCuentas()
  await cargarRegistrosPago()
})
</script>
