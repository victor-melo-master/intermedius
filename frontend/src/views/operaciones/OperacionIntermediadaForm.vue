<template>
  <div class="max-w-2xl mx-auto space-y-4 pb-10">
    <div class="flex items-center gap-3 mb-2">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">Nueva Intermediada</h2>
    </div>

    <div v-if="successRef" class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center space-y-4">
      <div class="text-4xl">✅</div>
      <p class="text-green-700 font-semibold">Operación registrada {{ successRef }}</p>
      <button @click="$router.push('/operaciones')" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm">Ver operaciones</button>
    </div>

    <form v-else @submit.prevent="submit" class="space-y-4">
      <!-- Cliente Emisor (vende) -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Cliente Emisor (vende {{ moneda }})</h3>
        <ClienteSelector v-model="clienteEmisor" />
      </div>

      <!-- Cuentas Emisor -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Cuentas Emisor</h3>
        <CuentaSelector v-model="cuentaEmisorDivisa" :label="'Cuenta ' + moneda + ' del emisor (recibe divisa)'" :placeholder="'Seleccionar cuenta ' + moneda" :cuentas="cuentasDivisa" :empty-message="'No hay cuentas en ' + moneda" :cuenta-label="cuentaLabel" :bancos="bancos" />
        <CuentaSelector v-model="cuentaEmisorVes" label="Cuenta VES del emisor (paga al cliente)" placeholder="Seleccionar cuenta VES" :cuentas="cuentasVes" empty-message="No hay cuentas en VES" :cuenta-label="cuentaLabel" :bancos="bancos" />
      </div>

      <!-- Cliente Receptor (compra) -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Cliente Receptor (compra {{ moneda }})</h3>
        <ClienteSelector v-model="clienteReceptor" />
      </div>

      <!-- Cuentas Receptor -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Cuentas Receptor</h3>
        <CuentaSelector v-model="cuentaReceptorDivisa" :label="'Cuenta ' + moneda + ' del receptor (entrega divisa)'" :placeholder="'Seleccionar cuenta ' + moneda" :cuentas="cuentasDivisa" :empty-message="'No hay cuentas en ' + moneda" :cuenta-label="cuentaLabel" :bancos="bancos" />
        <CuentaSelector v-model="cuentaReceptorVes" label="Cuenta VES del receptor (recibe del cliente)" placeholder="Seleccionar cuenta VES" :cuentas="cuentasVes" empty-message="No hay cuentas en VES" :cuenta-label="cuentaLabel" :bancos="bancos" />
      </div>

      <!-- Monto -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Monto</h3>
        <div>
          <label class="block text-sm text-gray-600 mb-1">Monto {{ moneda }} *</label>
          <input v-model="form.monto" type="number" step="0.01" required placeholder="100.00"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
        </div>
      </div>

      <!-- Tasas -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <h3 class="font-semibold text-gray-700">Tasas</h3>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Tasa de compra (al emisor) *</label>
            <input v-model="form.tasa_compra" type="number" step="0.01" required placeholder="36.00"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Tasa de venta (al receptor) *</label>
            <input v-model="form.tasa_venta" type="number" step="0.01" required placeholder="37.00"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
        </div>
        <div v-if="tasaVenta <= tasaCompra" class="bg-amber-50 border border-amber-200 text-amber-700 text-sm p-3 rounded-lg">
          ⚠️ La tasa de venta debe ser mayor que la de compra.
        </div>
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm p-3 rounded-lg">
          💰 Ganancia estimada: {{ gananciaEstimada }} Bs ({{ moneda }} {{ form.monto }} × {{ spread }})
        </div>
      </div>

      <!-- Descripción -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <label class="block text-sm text-gray-600 mb-1">Descripción</label>
        <textarea v-model="form.descripcion" rows="2" placeholder="Notas opcionales"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
      </div>

      <AppErrorState v-if="error" :message="error" :retry="false" />

      <button type="submit" :disabled="saving || !formularioValido"
        class="w-full bg-purple-600 hover:bg-purple-700 disabled:bg-purple-300 text-white font-semibold py-3 rounded-xl transition">
        {{ saving ? 'Registrando...' : 'Registrar intermediada' }}
      </button>
    </form>
  </div>
</template>

<script setup>
/**
 * OperacionIntermediaForm — Formulario para crear una operación intermediada.
 * Conecta un cliente emisor (vende divisa) con un cliente receptor (compra divisa),
 * con tasas de compra y venta diferenciadas, 4 movimientos y ganancia estimada.
 */
import { reactive, ref, computed, onMounted } from 'vue'
import { useApiError } from '@/composables/useApiError'
import { useFormatting } from '@/composables/useFormatting'
import { useAuthStore } from '../../stores/auth.js'
import { useBancosStore } from '../../stores/bancos.js'
import { useOperacionesStore } from '../../stores/operaciones.js'
import ClienteSelector from '../../components/clientes/ClienteSelector.vue'
import CuentaSelector from '../../components/cuentas/CuentaSelector.vue'
import AppErrorState from '../../components/common/AppErrorState.vue'
import api from '../../api/axios.js'

/** Store de autenticación */
const auth = useAuthStore()
/** Store de bancos */
const bancosStore = useBancosStore()
/** Store de operaciones */
const ops = useOperacionesStore()
const { parseError } = useApiError()
const { roundTo } = useFormatting()

/** Lista de bancos */
const bancos = ref([])

/** Moneda de la operación (USD por defecto) */
const moneda = ref('USD')
/** Indica guardado en curso */
const saving = ref(false)
/** Mensaje de error */
const error = ref('')
/** Referencia de la operación creada */
const successRef = ref('')
/** Cliente emisor seleccionado */
const clienteEmisor = ref({ id: '', nombre: '' })
/** Cliente receptor seleccionado */
const clienteReceptor = ref({ id: '', nombre: '' })

/** Cuentas seleccionadas para cada lado */
const cuentasEmisorDivisa = ref('')
const cuentasEmisorVes = ref('')
const cuentasReceptorDivisa = ref('')
const cuentasReceptorVes = ref('')

/** Lista de todas las cuentas disponibles */
const cuentas = ref([])
/** Cuentas filtradas por moneda (divisa) */
const cuentasDivisa = computed(() => cuentas.value.filter(c => c.moneda?.codigo === moneda.value))
/** Cuentas filtradas por moneda VES */
const cuentasVes = computed(() => cuentas.value.filter(c => c.moneda?.codigo === 'VES'))

/**
 * Genera etiqueta descriptiva de una cuenta.
 * @param {Object} c - Objeto de cuenta
 * @returns {string}
 */
function cuentaLabel(c) {
  const tipo = c.banco?.nombre || c.tipo || 'cuenta'
  return `${c.alias} · ${tipo} (${c.moneda?.codigo})`
}

/** Datos del formulario */
const form = reactive({
  monto: '',
  tasa_compra: '',
  tasa_venta: '',
  descripcion: '',
})

/** Tasa de compra como número */
const tasaCompra = computed(() => parseFloat(form.tasa_compra) || 0)
/** Tasa de venta como número */
const tasaVenta = computed(() => parseFloat(form.tasa_venta) || 0)
/** Diferencia entre tasa venta y compra */
const spread = computed(() => (tasaVenta.value - tasaCompra.value).toFixed(2))
/** Ganancia estimada en VES */
const gananciaEstimada = computed(() => {
  const monto = parseFloat(form.monto) || 0
  return (monto * (tasaVenta.value - tasaCompra.value)).toFixed(2)
})

/** Valida que el formulario esté completo y consistente */
const formularioValido = computed(() => {
  if (!form.monto || parseFloat(form.monto) <= 0) return false
  if (!form.tasa_compra || parseFloat(form.tasa_compra) <= 0) return false
  if (!form.tasa_venta || parseFloat(form.tasa_venta) <= 0) return false
  if (tasaVenta.value <= tasaCompra.value) return false
  if (!clienteEmisor.value.id) return false
  if (!clienteReceptor.value.id) return false
  if (clienteEmisor.value.id === clienteReceptor.value.id) return false
  if (!cuentaEmisorDivisa.value || !cuentaEmisorVes.value || !cuentaReceptorDivisa.value || !cuentaReceptorVes.value) return false
  return true
})

/**
 * Envía el formulario para registrar la operación intermediada.
 * Construye 4 movimientos: emisor entrega divisa, casa paga VES, receptor recibe divisa, receptor paga VES.
 * @returns {Promise<void>}
 */
async function submit() {
  if (!formularioValido.value) return
  saving.value = true
  error.value = ''
  try {
    const monto = roundTo(parseFloat(form.monto))
    const body = {
      fecha: new Date().toISOString().split('T')[0],
      tipo_codigo: 'intermediada',
      operador_id: Number(auth.user.id),
      cliente_emisor_id: Number(clienteEmisor.value.id),
      cliente_receptor_id: Number(clienteReceptor.value.id),
      tasa_compra: roundTo(parseFloat(form.tasa_compra)),
      tasa_venta: roundTo(parseFloat(form.tasa_venta)),
      descripcion: form.descripcion,
      movimientos: [
        { cuenta_id: Number(cuentaEmisorDivisa.value), monto: -monto, tasa_a_usd: 1 },
        { cuenta_id: Number(cuentaEmisorVes.value), monto: roundTo(monto * tasaCompra.value), tasa_a_usd: roundTo(1 / tasaCompra.value, 8) },
        { cuenta_id: Number(cuentaReceptorDivisa.value), monto: monto, tasa_a_usd: 1 },
        { cuenta_id: Number(cuentaReceptorVes.value), monto: roundTo(-(monto * tasaVenta.value)), tasa_a_usd: roundTo(1 / tasaVenta.value, 8) },
      ],
    }

    const created = await ops.create(body)
    const op = created.data || created
    successRef.value = `#${op.id || ''}`
  } catch (err) {
    error.value = parseError(err)
  } finally {
    saving.value = false
  }
}

/** Carga bancos y cuentas al montar */
onMounted(async () => {
  await bancosStore.fetchAll()
  bancos.value = bancosStore.list
  try {
    const { data } = await api.get('/cuentas')
    cuentas.value = Array.isArray(data) ? data : (data.data || [])
  } catch { cuentas.value = [] }
})
</script>
