<template>
  <div class="max-w-4xl mx-auto space-y-4">
    <div class="flex items-center gap-3">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">Gestionar operación #{{ store.detail?.id }}</h2>
    </div>

    <AppLoadingSpinner v-if="store.loading" />
    <AppErrorState v-else-if="store.error" :message="store.error" @retry="cargarOperacion" />
    <template v-else-if="store.detail">
      <!-- ════════ RESUMEN MONTO ════════ -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <div class="grid grid-cols-3 gap-4 text-center">
          <div>
            <p class="text-xs text-gray-400 mb-1">Monto divisa</p>
            <p class="text-xl font-bold text-gray-800">
              {{ formatMoney(montoDivisa) }} {{ monedaDivisa }}
            </p>
          </div>
          <div>
            <p class="text-xs text-gray-400 mb-1">Tasa</p>
            <p class="text-xl font-bold text-blue-600">
              {{ formatRate(store.detail.tasa_aplicada) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-gray-400 mb-1">Bolívares</p>
            <p class="text-xl font-bold text-green-600">
              Bs. {{ formatMoney(montoBolivares) }}
            </p>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100">
          <div class="bg-blue-50 rounded-xl px-4 py-3 text-center">
            <p class="text-[11px] text-blue-500 mb-1">El cliente entrega</p>
            <p class="text-lg font-bold" :class="esCompra ? 'text-green-700' : 'text-blue-700'">
              {{ esCompra ? 'Bs. ' + formatMoney(montoBolivares) : formatMoney(montoDivisa) + ' ' + monedaDivisa }}
            </p>
          </div>
          <div class="bg-green-50 rounded-xl px-4 py-3 text-center">
            <p class="text-[11px] text-green-500 mb-1">La casa entrega</p>
            <p class="text-lg font-bold" :class="esCompra ? 'text-blue-700' : 'text-green-700'">
              {{ esCompra ? formatMoney(montoDivisa) + ' ' + monedaDivisa : 'Bs. ' + formatMoney(montoBolivares) }}
            </p>
          </div>
        </div>
      </div>

      <!-- ════════ CABECERA ════════ -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">#{{ store.detail.id }}</span>
            <span class="px-3 py-1 rounded-full text-xs font-bold" :class="badgeEstado.clase">{{ badgeEstado.label }}</span>
          </div>
          <span class="text-sm text-gray-400">{{ store.detail.fecha }}</span>
        </div>
        <p class="font-semibold text-lg">{{ store.detail.tipo_operacion?.nombre || 'Operación' }}</p>
        <p v-if="store.detail.cliente?.nombre" class="text-sm text-gray-500">Cliente: {{ store.detail.cliente.nombre }}</p>
        <p v-if="store.detail.referencia" class="text-sm text-gray-500">Ref: {{ store.detail.referencia }}</p>
        <p v-if="store.detail.descripcion" class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">{{ store.detail.descripcion }}</p>
      </div>

      <!-- ════════ FLUJO PROGRESS ════════ -->
      <div class="bg-white border border-gray-200 rounded-xl p-5">
        <FlujoProgress :estado="store.detail.estado" />
      </div>

      <!-- ════════ TRANSACCIONES ════════ -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Transacciones</h3>
        <TransaccionList
          :transacciones="store.detail.transacciones || []"
          :operacion-id="store.detail.id"
          :estado="store.detail.estado"
          @refrescar="cargarOperacion"
        />
      </div>

      <!-- ════════ BOTONES DE ACCIÓN ════════ -->
      <div class="space-y-2">
        <button v-if="store.detail.estado === 'solicitud'"
          @click="iniciarOperacion" :disabled="acting"
          class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
          <span v-if="acting" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ acting ? 'Iniciando...' : '🚀 Iniciar operación' }}
        </button>

        <button v-if="store.detail.estado === 'en_progreso'"
          @click="mostrarAgregarTx = true"
          class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
          + Agregar transacción
        </button>

        <button v-if="store.detail.estado === 'en_progreso'"
          @click="cerrarOperacion" :disabled="acting || !tieneTransaccionesConfirmadas"
          class="w-full bg-green-600 hover:bg-green-700 disabled:bg-green-300 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
          <span v-if="acting" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ acting ? 'Cerrando...' : '🔒 Cerrar operación' }}
        </button>

        <button v-if="store.detail.estado !== 'cerrada' && store.detail.estado !== 'cancelada'"
          @click="mostrarCancelar = true"
          class="w-full bg-red-50 hover:bg-red-100 text-red-600 font-semibold py-3 rounded-xl transition">
          Cancelar operación
        </button>
      </div>
    </template>

    <!-- Modal: Agregar transacción -->
    <Teleport to="body">
      <AppFormModal v-model="mostrarAgregarTx" title="Nueva transacción">
        <TransaccionForm
          :operacion-id="store.detail?.id"
          :cliente-id="store.detail?.cliente?.id"
          :cliente-nombre="store.detail?.cliente?.nombre || ''"
          :intermedius-titular-id="intermediusTitularId"
          :monedas-permitidas="monedasPermitidas"
          @saved="onTransaccionGuardada"
          @cancel="mostrarAgregarTx = false"
        />
      </AppFormModal>
    </Teleport>

    <!-- Modal: Cancelar operación -->
    <Teleport to="body">
      <AppFormModal v-model="mostrarCancelar" title="Cancelar operación">
        <form @submit.prevent="cancelarOperacion" class="space-y-4">
          <p class="text-sm text-gray-500">¿Estás seguro de cancelar esta operación?</p>
          <textarea v-model="motivoCancelacion" rows="3" required
            placeholder="Motivo de la cancelación..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 outline-none resize-none"></textarea>
          <div class="flex gap-3">
            <button type="button" @click="mostrarCancelar = false"
              class="flex-1 py-2.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">Volver</button>
            <button type="submit" :disabled="!motivoCancelacion.trim() || acting"
              class="flex-1 py-2.5 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700 disabled:bg-red-300 transition flex items-center justify-center gap-2">
              <span v-if="acting" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ acting ? 'Cancelando...' : 'Cancelar operación' }}
            </button>
          </div>
        </form>
      </AppFormModal>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useOperacionesStore } from '../../stores/operaciones.js'
import { useNotification } from '@/composables/useNotification'
import { useFormatting } from '@/composables/useFormatting'
import { useTitulares } from '@/composables/useTitulares'
import FlujoProgress from '@/components/operaciones/FlujoProgress.vue'
import TransaccionList from '@/components/operaciones/TransaccionList.vue'
import TransaccionForm from '@/components/operaciones/TransaccionForm.vue'
import AppLoadingSpinner from '@/components/common/AppLoadingSpinner.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import AppFormModal from '@/components/common/AppFormModal.vue'

const route = useRoute()
const store = useOperacionesStore()
const notifier = useNotification()
const { formatMoney, formatRate } = useFormatting()
const titulares = useTitulares()

const acting = ref(false)
const mostrarAgregarTx = ref(false)
const mostrarCancelar = ref(false)
const motivoCancelacion = ref('')
const intermediusTitularId = ref(null)

const tieneTransaccionesConfirmadas = computed(() =>
  (store.detail?.transacciones || []).some(t => t.estado === 'confirmada')
)

const monedasPermitidas = computed(() => {
  const codigo = store.detail?.tipo_operacion?.codigo
  if (!codigo) return []
  if (['compra_usd', 'venta_usd'].includes(codigo)) return ['USD', 'VES']
  return []
})

const esCompra = computed(() => store.detail?.tipo_operacion?.codigo === 'compra_usd')

const badgeEstado = computed(() => {
  const map = {
    solicitud:   { label: 'Solicitud',   clase: 'bg-yellow-100 text-yellow-700' },
    en_progreso: { label: 'En Progreso', clase: 'bg-blue-100 text-blue-700' },
    cerrada:     { label: 'Cerrada',     clase: 'bg-green-100 text-green-700' },
    cancelada:   { label: 'Cancelada',   clase: 'bg-red-100 text-red-700' },
  }
  return map[store.detail?.estado] || { label: store.detail?.estado || '—', clase: 'bg-gray-100 text-gray-600' }
})

const montoDivisa = computed(() => {
  const op = store.detail
  if (!op) return 0
  const mov = (op.movimientos || []).find(m => m.moneda?.codigo !== 'VES')
  if (mov) return Math.abs(parseFloat(mov.monto))
  const tx = (op.transacciones || []).find(t => t.moneda?.codigo !== 'VES')
  if (tx) return Math.abs(parseFloat(tx.monto))
  return op.monto_solicitado ? Math.abs(parseFloat(op.monto_solicitado)) : 0
})

const monedaDivisa = computed(() => {
  const op = store.detail
  if (!op) return ''
  const mov = (op.movimientos || []).find(m => m.moneda?.codigo !== 'VES')
  if (mov) return mov.moneda?.codigo || ''
  const tx = (op.transacciones || []).find(t => t.moneda?.codigo !== 'VES')
  if (tx) return tx.moneda?.codigo || ''
  return 'USD'
})

const montoBolivares = computed(() => {
  const op = store.detail
  if (!op) return 0
  const mov = (op.movimientos || []).find(m => m.moneda?.codigo === 'VES')
  if (mov) return Math.abs(parseFloat(mov.monto))
  const tx = (op.transacciones || []).find(t => t.moneda?.codigo === 'VES')
  if (tx) return Math.abs(parseFloat(tx.monto))
  const usd = montoDivisa.value
  const tasa = parseFloat(op.tasa_aplicada)
  return usd && tasa ? usd * tasa : 0
})

async function cargarOperacion() {
  await store.fetchOne(route.params.id)
}

async function iniciarOperacion() {
  acting.value = true
  try {
    await store.iniciar(route.params.id)
    notifier.success('Operación iniciada')
  } catch {
    notifier.error('Error al iniciar la operación')
  }
  acting.value = false
}

async function cerrarOperacion() {
  acting.value = true
  try {
    await store.cerrar(route.params.id)
    notifier.success('Operación cerrada — movimientos generados')
  } catch {
    notifier.error('Error al cerrar la operación')
  }
  acting.value = false
}

async function cancelarOperacion() {
  acting.value = true
  try {
    await store.cancelar(route.params.id, motivoCancelacion.value.trim())
    notifier.success('Operación cancelada')
    mostrarCancelar.value = false
  } catch {
    notifier.error('Error al cancelar la operación')
  }
  acting.value = false
}

function onTransaccionGuardada() {
  mostrarAgregarTx.value = false
  cargarOperacion()
}

onMounted(async () => {
  await titulares.fetchAll()
  const intermedius = titulares.getIntermedius()
  intermediusTitularId.value = intermedius ? intermedius.id : null
  await cargarOperacion()
})
</script>
