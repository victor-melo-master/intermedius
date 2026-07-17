<template>
  <div class="max-w-3xl mx-auto space-y-4">
    <!-- Spinner -->
    <template v-if="loading">
      <div class="flex items-center gap-3 mb-4">
        <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
        <h2 class="text-xl font-bold text-gray-800">Verificación</h2>
      </div>
      <div class="flex justify-center py-12">
        <AppLoadingSpinner />
      </div>
    </template>

    <!-- Error -->
    <template v-else-if="error">
      <div class="flex items-center gap-3 mb-4">
        <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
        <h2 class="text-xl font-bold text-gray-800">Verificación</h2>
      </div>
      <AppErrorState :message="error" @retry="cargar" />
    </template>

    <!-- Sin datos -->
    <template v-else-if="!operacion">
      <div class="flex items-center gap-3 mb-4">
        <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
        <h2 class="text-xl font-bold text-gray-800">Verificación</h2>
      </div>
      <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700">
        No se encontraron datos de operación.
      </div>
    </template>

    <!-- Contenido -->
    <template v-else>
      <!-- Header -->
      <div class="flex items-center gap-3">
        <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
        <div class="flex-1">
          <h2 class="text-xl font-bold text-gray-800">Verificación #{{ operacion.id }}</h2>
          <p class="text-sm text-gray-500">{{ tipoNombre }}</p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">En verificación</span>
      </div>

      <!-- Detalles de la operación -->
      <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-2 text-sm">
        <div v-if="operacion.cliente" class="flex justify-between">
          <span class="text-gray-400">Cliente</span>
          <span class="text-gray-700 font-medium">{{ operacion.cliente.nombre }}</span>
        </div>
        <div v-if="operacion.operador" class="flex justify-between">
          <span class="text-gray-400">Operador</span>
          <span class="text-gray-700 font-medium">{{ operacion.operador.name }}</span>
        </div>
        <div v-if="operacion.tasa_aplicada" class="flex justify-between">
          <span class="text-gray-400">Tasa aplicada</span>
          <span class="text-gray-700 font-medium">{{ formatRate(operacion.tasa_aplicada) }}</span>
        </div>
        <div v-if="operacion.descripcion" class="flex justify-between">
          <span class="text-gray-400">Descripción</span>
          <span class="text-gray-700 font-medium text-right max-w-[60%]">{{ operacion.descripcion }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-400">Fecha</span>
          <span class="text-gray-700 font-medium">{{ formatDate(operacion.fecha) }}</span>
        </div>
      </div>

      <!-- Barra de progreso -->
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-700">Progreso de verificación</span>
          <span class="text-sm text-gray-500">{{ movimientosValidados }}/{{ totalMovimientos }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5">
          <div class="bg-green-600 h-2.5 rounded-full transition-all duration-300"
            :style="{ width: porcentajeProgreso + '%' }"></div>
        </div>
      </div>

      <!-- Saldos de cuentas -->
      <div v-if="tieneSaldos" class="bg-white border border-gray-200 rounded-xl p-4 space-y-2">
        <h3 class="font-semibold text-gray-700 text-sm">Saldos de cuentas</h3>
        <div class="grid grid-cols-2 gap-2">
          <div v-for="(saldo, cuentaId) in saldos" :key="cuentaId"
            class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500">{{ saldo.alias }}</p>
            <p class="text-sm font-bold text-gray-800">{{ formatMoney(saldo.saldo, saldo.moneda) }}</p>
          </div>
        </div>
      </div>

      <!-- Movimientos -->
      <div class="space-y-3">
        <h3 class="font-semibold text-gray-700">Movimientos</h3>

        <div v-if="movimientos.length === 0" class="bg-white border border-gray-200 rounded-xl p-6 text-center text-gray-400">
          No hay movimientos en esta operación
        </div>

        <div v-for="mov in movimientos" :key="mov.id"
          class="bg-white border rounded-xl p-4 space-y-3"
          :class="mov.estado === 'validada' ? 'border-green-200 bg-green-50/30' :
                   mov.estado === 'rechazada' ? 'border-red-200 bg-red-50/30' :
                   'border-gray-200'">
          <!-- Estado y acciones -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                :class="mov.estado === 'validada' ? 'bg-green-100 text-green-700' :
                         mov.estado === 'rechazada' ? 'bg-red-100 text-red-700' :
                         'bg-yellow-100 text-yellow-700'">
                {{ mov.estado === 'validada' ? 'Validado' : mov.estado === 'rechazada' ? 'Rechazado' : 'Pendiente' }}
              </span>
              <span v-if="mov.orden" class="text-xs text-gray-400">#{{ mov.orden }}</span>
            </div>
            <div v-if="mov.estado === 'pendiente'" class="flex gap-2">
              <button @click="validar(mov)"
                class="text-xs text-green-600 hover:text-green-800 font-medium px-2 py-1 rounded-lg hover:bg-green-50">
                Validar
              </button>
              <button @click="abrirRechazo(mov)"
                class="text-xs text-red-600 hover:text-red-800 font-medium px-2 py-1 rounded-lg hover:bg-red-50">
                Rechazar
              </button>
            </div>
          </div>

          <!-- Detalles del movimiento -->
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <p class="text-xs text-gray-400">Cuenta</p>
              <p class="text-gray-700">{{ mov.cuenta?.alias || `Cuenta #${mov.cuenta_id}` }}</p>
              <p v-if="mov.cuenta?.banco?.nombre" class="text-xs text-gray-400">{{ mov.cuenta.banco.nombre }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Moneda</p>
              <p class="text-gray-700">{{ mov.moneda?.codigo }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Monto</p>
              <p class="font-bold" :class="parseFloat(mov.monto) >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ formatMoney(mov.monto, mov.moneda?.codigo) }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Equivalente USD</p>
              <p class="text-gray-700">{{ formatMoney(mov.monto_usd_equivalente, 'USD') }}</p>
            </div>
          </div>

          <!-- Motivo de rechazo -->
          <div v-if="mov.estado === 'rechazada' && mov.motivo_rechazo"
            class="bg-red-50 border border-red-200 rounded-lg p-3">
            <p class="text-xs font-medium text-red-700 mb-1">Motivo de rechazo:</p>
            <p class="text-sm text-red-600">{{ mov.motivo_rechazo }}</p>
          </div>

          <!-- Validado por -->
          <div v-if="mov.estado === 'validada' && mov.validada_por"
            class="text-xs text-gray-400">
            Validado por {{ mov.validada_por.name }} el {{ formatDateTime(mov.validada_en) }}
          </div>
        </div>
      </div>

      <!-- Botón cerrar verificación -->
      <div class="pt-2 pb-8">
        <button @click="cerrar" :disabled="!todasValidados || cerrando"
          class="w-full py-3 rounded-xl font-semibold transition flex items-center justify-center gap-2"
          :class="todasValidados
            ? 'bg-green-600 hover:bg-green-700 text-white'
            : 'bg-gray-200 text-gray-400 cursor-not-allowed'">
          <span v-if="cerrando" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ cerrando ? 'Cerrando...' : todasValidados ? 'Cerrar verificación' : 'Todos los movimientos deben estar validados' }}
        </button>
      </div>
    </template>

    <!-- Modal: Rechazar movimiento -->
    <Teleport to="body">
      <AppFormModal v-model="showRechazoModal" title="Rechazar movimiento">
        <form @submit.prevent="confirmarRechazo" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Motivo del rechazo</label>
            <textarea v-model="rechazoMotivo" rows="3" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 outline-none"
              placeholder="Explique por qué rechaza este movimiento..."></textarea>
          </div>
        </form>
        <template #footer>
          <div class="flex gap-3">
            <button type="button" @click="showRechazoModal = false"
              class="flex-1 py-2.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">Cancelar</button>
            <button @click="confirmarRechazo" :disabled="!rechazoMotivo.trim() || savingRechazo"
              class="flex-1 py-2.5 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700 transition">
              {{ savingRechazo ? 'Rechazando...' : 'Rechazar' }}
            </button>
          </div>
        </template>
      </AppFormModal>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVerificacion } from '@/composables/useVerificacion'
import { useFormatting } from '@/composables/useFormatting'
import { useNotification } from '@/composables/useNotification'
import { useApiError } from '@/composables/useApiError'

import AppLoadingSpinner from '@/components/common/AppLoadingSpinner.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import AppFormModal from '@/components/common/AppFormModal.vue'

const route = useRoute()
const router = useRouter()
const { formatMoney, formatDateTime, formatRate, formatDate } = useFormatting()
const notify = useNotification()
const { parseError } = useApiError()

const {
  loading, error, operacion, movimientos, saldos,
  totalMovimientos, movimientosValidados, todasValidados,
  fetchVerificacion, validarMovimiento, rechazarMovimiento, cerrarVerificacion
} = useVerificacion()

const tipoNombre = computed(() => {
  return operacion.value?.tipo_operacion?.nombre || operacion.value?.tipoOperacion?.nombre || 'Operación'
})

const tieneSaldos = computed(() => {
  const s = saldos.value
  return s && typeof s === 'object' && Object.keys(s).length > 0
})

const porcentajeProgreso = computed(() => {
  if (!totalMovimientos.value) return 0
  return Math.round((movimientosValidados.value / totalMovimientos.value) * 100)
})

const cerrando = ref(false)
const showRechazoModal = ref(false)
const rechazoMovimiento = ref(null)
const rechazoMotivo = ref('')
const savingRechazo = ref(false)

async function cargar() {
  await fetchVerificacion(route.params.id)
}

function abrirRechazo(mov) {
  rechazoMovimiento.value = mov
  rechazoMotivo.value = ''
  showRechazoModal.value = true
}

async function validar(mov) {
  try {
    await validarMovimiento(route.params.id, mov.id)
    notify.success('Movimiento validado')
    await fetchVerificacion(route.params.id)
  } catch (err) {
    notify.error(parseError(err))
  }
}

async function confirmarRechazo() {
  if (!rechazoMovimiento.value || !rechazoMotivo.value.trim()) return
  savingRechazo.value = true
  try {
    await rechazarMovimiento(route.params.id, rechazoMovimiento.value.id, rechazoMotivo.value.trim())
    showRechazoModal.value = false
    notify.success('Movimiento rechazado')
    await fetchVerificacion(route.params.id)
  } catch (err) {
    notify.error(parseError(err))
  } finally {
    savingRechazo.value = false
  }
}

async function cerrar() {
  cerrando.value = true
  try {
    await cerrarVerificacion(route.params.id)
    notify.success('Verificación completada')
    router.push(`/operaciones/${route.params.id}`)
  } catch (err) {
    notify.error(parseError(err))
  } finally {
    cerrando.value = false
  }
}

onMounted(cargar)
</script>
