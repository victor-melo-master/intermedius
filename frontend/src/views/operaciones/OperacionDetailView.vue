<template>
  <div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center gap-3">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">Operación #{{ ops.detail?.id }}</h2>
    </div>

    <AppLoadingSpinner v-if="ops.loading" />
    <AppErrorState v-else-if="ops.error" :message="ops.error" @retry="ops.fetchOne(route.params.id)" />
    <div v-else-if="ops.detail" class="space-y-4">
      <!-- Resumen de monto -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <div class="grid grid-cols-3 gap-4 text-center">
          <div>
            <p class="text-xs text-gray-400 mb-1">{{ esCompra ? 'Monto divisa' : 'Monto divisa' }}</p>
            <p class="text-xl font-bold text-gray-800">
              {{ formatMoney(montoDivisa) }} {{ monedaDivisa }}
            </p>
          </div>
          <div>
            <p class="text-xs text-gray-400 mb-1">Tasa</p>
            <p class="text-xl font-bold text-blue-600">
              {{ formatRate(ops.detail.tasa_aplicada) }}
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
              {{ esCompra ? formatMoney(montoDivisa) + ' ' + monedaDivisa : 'Bs. ' + formatMoney(montoBolivares) }}
            </p>
          </div>
          <div class="bg-green-50 rounded-xl px-4 py-3 text-center">
            <p class="text-[11px] text-green-500 mb-1">La casa entrega</p>
            <p class="text-lg font-bold" :class="esCompra ? 'text-blue-700' : 'text-green-700'">
              {{ esCompra ? 'Bs. ' + formatMoney(montoBolivares) : formatMoney(montoDivisa) + ' ' + monedaDivisa }}
            </p>
          </div>
        </div>
      </div>

      <!-- Datos generales -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-500">#{{ ops.detail.id }}</span>
          <div class="flex items-center gap-2">
            <span v-if="ops.detail.estado" class="px-3 py-1 rounded-full text-xs font-bold"
              :class="ops.detail.estado === 'cerrada' ? 'bg-green-50 text-green-700' :
                       ops.detail.estado === 'en_progreso' ? 'bg-blue-50 text-blue-700' :
                       ops.detail.estado === 'solicitud' ? 'bg-yellow-50 text-yellow-700' :
                       ops.detail.estado === 'cancelada' ? 'bg-red-50 text-red-700' :
                       'bg-gray-50 text-gray-700'">
              {{ ops.detail.estado?.replace('_', ' ') }}
            </span>
            <span v-if="!esFlujoMultipaso" class="px-3 py-1 rounded-full text-xs font-bold"
              :class="ops.detail.estatus === 'verificado' ? 'bg-green-50 text-green-700' :
                       ops.detail.estatus === 'en_verificacion' ? 'bg-blue-50 text-blue-700' :
                       ops.detail.estatus === 'en_revision' ? 'bg-orange-50 text-orange-700' :
                       'bg-gray-50 text-gray-700'">
              {{ ops.detail.estatus?.replace('_', ' ') }}
            </span>
          </div>
        </div>
        <p class="font-semibold text-lg">{{ nombreOperacion }}</p>
        <p v-if="ops.detail.cliente?.nombre" class="text-sm text-gray-500">Cliente: {{ ops.detail.cliente.nombre }}</p>
        <p class="text-sm text-gray-400">{{ formatDate(ops.detail.fecha) }}</p>
        <p v-if="ops.detail.referencia" class="text-sm text-gray-500">Ref: {{ ops.detail.referencia }}</p>
        <p v-if="ops.detail.descripcion" class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">{{ ops.detail.descripcion }}</p>
      </div>

      <!-- Movimientos -->
      <div v-if="ops.detail.movimientos?.length" class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Movimientos</h3>
        <div class="space-y-2">
          <div v-for="(par, idx) in movimientosPareados" :key="idx"
            class="flex items-center gap-3 text-sm bg-gray-50 rounded-lg px-4 py-3">
            <div class="flex-1 text-right">
              <p class="text-gray-500 text-xs">{{ par.salida.cuenta?.alias || `Cuenta #${par.salida.cuenta_id}` }}</p>
              <p class="font-medium text-red-600">{{ formatMoney(par.salida.monto) }} {{ par.salida.moneda?.codigo }}</p>
            </div>
            <span class="text-gray-400 text-lg shrink-0">→</span>
            <div class="flex-1">
              <p class="text-gray-500 text-xs">{{ par.entrada?.cuenta?.alias || `Cuenta #${par.entrada?.cuenta_id}` }}</p>
              <p class="font-medium text-green-600">{{ formatMoney(par.entrada?.monto) }} {{ par.entrada?.moneda?.codigo }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Ganancia -->
      <div v-if="ops.detail.estado === 'cerrada'" class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Ganancia</h3>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-500">Bruta</p>
            <p class="text-lg font-bold" :class="gananciaBrutaUsd >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ formatMoney(gananciaBrutaUsd) }} USD
            </p>
            <p class="text-xs text-gray-400">Bs. {{ formatMoney(gananciaBrutaVes) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Neta</p>
            <p class="text-lg font-bold" :class="gananciaNetaUsd >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ formatMoney(gananciaNetaUsd) }} USD
            </p>
            <p class="text-xs text-gray-400">Bs. {{ formatMoney(gananciaNetaVes) }}</p>
          </div>
        </div>
      </div>

      <!-- Botón: Gestionar transacciones (flujo multi-paso) -->
      <div v-if="ops.detail.estado && ['solicitud', 'en_progreso'].includes(ops.detail.estado)" class="space-y-2">
        <router-link :to="`/operaciones/${ops.detail.id}/gestionar`"
          class="block w-full bg-indigo-500 hover:bg-indigo-600 text-white font-semibold py-3 rounded-xl transition text-center">
          📋 Gestionar transacciones
        </router-link>
      </div>

      <!-- Botones de acción -->
      <div class="space-y-2">
        <button
          v-if="puedeEditar"
          @click="abrirEdicion"
          class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2"
        >
          ✏️ Editar operación
        </button>

        <button
          v-if="auth.isAdmin && !esFlujoMultipaso && ops.detail.estatus === 'sin_verificar'"
          @click="iniciarVerificacion"
          :disabled="verifying"
          class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2"
        >
          <span v-if="verifying" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ verifying ? 'Iniciando...' : 'Verificar transacciones' }}
        </button>

        <button
          v-if="auth.isAdmin && !esFlujoMultipaso && ops.detail.estatus === 'en_verificacion'"
          @click="irAVerificacion"
          class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2"
        >
          Continuar verificación
        </button>
      </div>
    </div>

    <!-- Modal motivo de edición -->
    <Teleport to="body">
      <AppFormModal v-model="showEditModal" :title="'Editar operación #' + (ops.detail?.id || '')">
        <form @submit.prevent="guardarEdicion" class="space-y-4">
          <p class="text-sm text-gray-500">Vas a modificar esta operación. ¿Cuál es el motivo del cambio?</p>
          <textarea v-model="motivoEdicion" rows="3" required placeholder="Ej: El cliente cambió el monto, o no hay fondos en la cuenta A..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
          <AppErrorState v-if="editError" :message="editError" :retry="false" />
        </form>
        <template #footer>
          <div class="flex gap-3">
            <button type="button" @click="showEditModal = false"
              class="flex-1 py-2.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">Cancelar</button>
            <button @click="guardarEdicion" :disabled="!motivoEdicion.trim()"
              class="flex-1 py-2.5 bg-amber-500 text-white text-sm font-medium rounded-xl hover:bg-amber-600 transition">
              Continuar a edición
            </button>
          </div>
        </template>
      </AppFormModal>
    </Teleport>
  </div>
</template>

<script setup>
/**
 * OperacionDetailView — Detalle individual de una operación.
 * Muestra datos generales, movimientos (cuentas, montos, tasas) y métricas.
 * Permite verificar (admin) y editar (con motivo) si la operación no está
 * verificada ni cancelada.
 */
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOperacionesStore } from '../../stores/operaciones.js'
import { useAuthStore } from '../../stores/auth.js'
import { useFormatting } from '@/composables/useFormatting'
import api from '@/api/axios'
import AppLoadingSpinner from '../../components/common/AppLoadingSpinner.vue'
import AppErrorState from '../../components/common/AppErrorState.vue'
import AppFormModal from '@/components/common/AppFormModal.vue'

/** Ruta actual (contiene params.id) */
const route = useRoute()
/** Router para navegar a edición */
const router = useRouter()
/** Store de operaciones */
const ops = useOperacionesStore()
const { formatMoney, formatRate, formatDate } = useFormatting()
/** Store de autenticación (permisos) */
const auth = useAuthStore()
/** Indica si se está verificando la operación */
const verifying = ref(false)
/** Controla visibilidad del modal de motivo de edición */
const showEditModal = ref(false)
/** Motivo ingresado para la edición */
const motivoEdicion = ref('')
/** Error al mostrar el modal */
const editError = ref('')

/** Indica si la operación puede ser editada (no verificada ni cancelada) */
const esCompra = computed(() => {
  const codigo = ops.detail?.tipo_operacion?.codigo
  return codigo === 'compra_usd'
})

const nombreOperacion = computed(() => {
  const nombre = ops.detail?.tipo_operacion?.nombre
  if (!nombre) return 'Operación'
  const moneda = ops.detail?.moneda_operacion?.codigo
  if (!moneda) return nombre
  return nombre.replace('USD', moneda)
})

/** Operación usa el flujo multi-paso (no legacy) */
const esFlujoMultipaso = computed(() => {
  const estado = ops.detail?.estado
  return estado && estado !== 'en_espera'
})

/** Movimientos agrupados en pares: salida → entrada */
const movimientosPareados = computed(() => {
  const movs = ops.detail?.movimientos || []
  if (!movs.length) return []
  const pares = []
  for (let i = 0; i < movs.length; i += 2) {
    const salida = movs[i]
    const entrada = movs[i + 1]
    if (salida && entrada) {
      pares.push({ salida, entrada })
    } else if (salida) {
      pares.push({ salida, entrada: null })
    }
  }
  return pares
})

/** Monto en divisa — usa monto_solicitado como fuente primaria */
const montoDivisa = computed(() => {
  const op = ops.detail
  if (!op) return 0
  return op.monto_solicitado ? Math.abs(parseFloat(op.monto_solicitado)) : 0
})

/** Código de la moneda divisa — usa moneda_operacion */
const monedaDivisa = computed(() => {
  return ops.detail?.moneda_operacion?.codigo || 'USD'
})

/** Monto en bolívares — calcula de monto_solicitado × tasa_aplicada */
const montoBolivares = computed(() => {
  const op = ops.detail
  if (!op) return 0
  const usd = montoDivisa.value
  const tasa = parseFloat(op.tasa_aplicada)
  return usd && tasa ? usd * tasa : 0
})

const puedeEditar = computed(() => {
  const op = ops.detail
  if (!op) return false
  if (op.estatus === 'verificado') return false
  if (op.estado_pool === 'cancelada') return false
  return true
})

const gananciaBrutaUsd = computed(() => parseFloat(ops.detail?.ganancia?.bruta_usd ?? ops.detail?.ganancia_bruta_usd ?? 0))
const gananciaBrutaVes = computed(() => parseFloat(ops.detail?.ganancia?.bruta_ves ?? ops.detail?.ganancia_bruta_ves ?? 0))
const gananciaNetaUsd = computed(() => parseFloat(ops.detail?.ganancia?.neta_usd ?? ops.detail?.ganancia_neta_usd ?? 0))
const gananciaNetaVes = computed(() => parseFloat(ops.detail?.ganancia?.neta_ves ?? ops.detail?.ganancia_neta_ves ?? 0))

/** Verifica la operación actual (cambia estatus a verificado) */
async function iniciarVerificacion() {
  verifying.value = true
  try {
    await api.post(`/operaciones/${route.params.id}/iniciar-verificacion`)
    router.push(`/operaciones/${route.params.id}/verificar`)
  } catch {}
  verifying.value = false
}

function irAVerificacion() {
  router.push(`/operaciones/${route.params.id}/verificar`)
}

/** Abre el modal para ingresar motivo de edición */
function abrirEdicion() {
  motivoEdicion.value = ''
  editError.value = ''
  showEditModal.value = true
}

/** Redirige a la URL de edición con el motivo como query param */
function guardarEdicion() {
  if (!motivoEdicion.value.trim()) return
  router.push({
    path: `/operaciones/${route.params.id}/editar`,
    query: { motivo: motivoEdicion.value.trim() },
  })
}

/** Carga la operación al montar el componente */
onMounted(() => ops.fetchOne(route.params.id))
</script>
