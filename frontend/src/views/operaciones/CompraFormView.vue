<template>
  <div class="max-w-7xl mx-auto pb-6">
    <div class="flex items-center gap-3 mb-4">
      <button @click="$router.back()" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-ink-muted hover:bg-surface-muted rounded-lg transition"><Iconoir name="arrow-left" class="w-4 h-4" /> Volver</button>
      <h2 class="text-xl font-bold text-heading">Nueva compra</h2>
    </div>

    <form @submit.prevent="registrarCompra">
      <div class="bg-white dark:bg-surface rounded-xl border border-edge divide-y divide-edge">
        <!-- Moneda -->
        <div class="p-4 space-y-3">
          <label class="block text-sm font-semibold text-ink-muted uppercase tracking-wider">Moneda a recibir</label>
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
            tipo="compra"
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
          <div :class="['grid', mostrarVes ? 'lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x' : 'grid-cols-1', 'divide-edge']">

            <!-- Columna: Recepción de divisa -->
            <div class="p-5 space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-ink">Recepción de {{ moneda }}</h3>
                <span class="text-sm font-medium" :class="restanteDivisa === 0 ? 'text-success' : 'text-warning'">
                  {{ fmt(restanteDivisa) }} {{ moneda }} restante
                </span>
              </div>

              <div v-if="movsDivisa.length" class="space-y-1.5">
                <div v-for="(tx, i) in movsDivisa" :key="i"
                  class="flex items-center justify-between bg-success-soft border border-success-edge rounded-lg px-3 py-2 text-sm">
                  <span class="text-success-strong truncate">
                    <Iconoir name="check" class="w-4 h-4 text-success inline" />
                    {{ fmt(tx.monto) }} {{ moneda }} · {{ tx.metodo_pago }}
                  </span>
                  <div class="flex gap-2 ml-1 shrink-0">
                    <button type="button" @click="editarMovDivisa(i)" class="text-gold-dark hover:text-gold-dark">Editar</button>
                    <button type="button" @click="eliminarMovDivisa(i)" class="text-danger hover:text-danger-strong"><Iconoir name="x-mark" class="w-4 h-4" /></button>
                  </div>
                </div>
              </div>

              <div v-if="restanteDivisa > 0 || movDivisaEditandoIdx !== null" class="space-y-3 pt-3 border-t border-edge">
                <div class="grid grid-cols-2 gap-3">
                  <select v-model="txDivisa.metodo_pago" required
                    class="px-3 py-2 border border-edge-strong rounded-lg text-sm bg-white dark:bg-surface-muted focus:ring-2 focus:ring-gold outline-none">
                    <option value="">Método</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="zelle">Zelle</option>
                    <option value="binance">Binance</option>
                    <option value="otro">Otro</option>
                  </select>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted text-sm">{{ moneda }}</span>
                    <input :value="fmt(txDivisa.monto)" @input="onMontoDivisaInput($event)"
                      type="text" inputmode="decimal" :placeholder="fmt(restanteDivisa)"
                      class="w-full pl-10 pr-3 py-2 border rounded-lg text-sm focus:ring-2 outline-none"
                      :class="parseFloat(txDivisa.monto) > restanteDivisa + 0.01 ? 'border-danger-edge focus:ring-danger' : 'border-edge-strong focus:ring-gold'" />
                  </div>
                </div>
                <p v-if="parseFloat(txDivisa.monto) > restanteDivisa + 0.01" class="text-xs text-danger">
                  Excede el restante ({{ fmt(restanteDivisa) }} {{ moneda }})
                </p>
                <div v-if="txDivisa.metodo_pago && txDivisa.metodo_pago !== 'efectivo'">
                  <input v-model="txDivisa.comprobante" placeholder="N° de referencia..."
                    class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm focus:ring-2 focus:ring-gold outline-none" />
                </div>
                <button type="button" @click="confirmarMovDivisa" :disabled="!txDivisaValido"
                  class="w-full py-2 bg-gold hover:bg-gold-dark disabled:opacity-50 text-white text-sm font-semibold rounded-lg transition active:scale-[0.98]">
                  {{ movDivisaEditandoIdx !== null ? 'Actualizar' : 'Confirmar recepción' }}
                </button>
              </div>
              <div v-else class="text-center py-2">
                <span class="text-sm text-success font-medium">✅ Completado</span>
              </div>
            </div>

            <!-- Columna: Pago en Bs -->
            <div v-if="mostrarVes" class="p-5 space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-ink">Pago al cliente en Bs</h3>
                <span class="text-sm font-medium" :class="restanteVes === 0 ? 'text-success' : 'text-warning'">
                  Bs. {{ fmt(restanteVes) }} restante
                </span>
              </div>

              <div v-if="movsVes.length" class="space-y-1.5">
                <div v-for="(tx, i) in movsVes" :key="i"
                  class="flex items-center justify-between bg-success-soft border border-success-edge rounded-lg px-3 py-2 text-sm">
                  <span class="text-success-strong truncate">
                    <Iconoir name="check" class="w-4 h-4 text-success inline" />
                    Bs. {{ fmt(tx.monto) }} · {{ tx.metodo_pago }}
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
                      type="text" inputmode="decimal" :placeholder="fmt(restanteVes)"
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

          </div>
        </div>

        <!-- Resumen + enviar -->
        <div v-if="moneda && cliente?.id && montoDivisaNum > 0 && montoVesNum > 0" class="p-5">
          <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4 text-sm">
              <div class="text-ink-muted">
                <span class="text-ink-muted">Recibir:</span>
                <span class="font-semibold text-heading ml-1">{{ fmt(montoDivisaNum) }} {{ moneda }}</span>
              </div>
              <div class="text-ink-muted">
                <span class="text-ink-muted">Pagar:</span>
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
            {{ enviando ? 'Creando solicitud...' : 'Crear solicitud de compra' }}
          </button>
        </div>
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

const mostrarVes = computed(() =>
  restanteDivisa.value === 0 && movsDivisa.value.length > 0
)

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
