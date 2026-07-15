<template>
  <div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center gap-3">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">Operación #{{ ops.detail?.id }}</h2>
    </div>

    <AppLoadingSpinner v-if="ops.loading" />
    <AppErrorState v-else-if="ops.error" :message="ops.error" @retry="ops.fetchOne(route.params.id)" />
    <div v-else-if="ops.detail" class="space-y-4">
      <!-- Datos generales -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-500">#{{ ops.detail.id }}</span>
          <span class="px-3 py-1 rounded-full text-xs font-bold"
            :class="ops.detail.estatus === 'verificado' ? 'bg-green-50 text-green-700' :
                     ops.detail.estatus === 'en_revision' ? 'bg-orange-50 text-orange-700' :
                     'bg-gray-50 text-gray-700'">
            {{ ops.detail.estatus?.replace('_', ' ') }}
          </span>
        </div>
        <p class="font-semibold text-lg">{{ ops.detail.tipo_operacion?.nombre || 'Operación' }}</p>
        <p v-if="ops.detail.cliente?.nombre" class="text-sm text-gray-500">Cliente: {{ ops.detail.cliente.nombre }}</p>
        <p class="text-sm text-gray-400">{{ ops.detail.fecha }}</p>
        <p v-if="ops.detail.referencia" class="text-sm text-gray-500">Ref: {{ ops.detail.referencia }}</p>
        <p v-if="ops.detail.descripcion" class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">{{ ops.detail.descripcion }}</p>
      </div>

      <!-- Movimientos -->
      <div v-if="ops.detail.movimientos?.length" class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Movimientos</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
                <th class="py-2 font-medium">Cuenta</th>
                <th class="py-2 font-medium text-right">Monto</th>
                <th class="py-2 font-medium text-right">Tasa a USD</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in ops.detail.movimientos" :key="m.id" class="border-b border-gray-50 last:border-0">
                <td class="py-2 text-gray-700">{{ m.cuenta?.alias || `Cuenta #${m.cuenta_id}` }}</td>
                <td class="py-2 text-right font-medium" :class="m.monto >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ formatMoney(m.monto) }} {{ m.moneda?.codigo }}
                </td>
                <td class="py-2 text-right text-gray-500">{{ m.tasa_a_usd }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Métricas -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-gray-700">Métricas</h3>
        <div class="grid grid-cols-2 gap-4">
          <div><p class="text-xs text-gray-500">Ganancia neta USD</p><p class="text-lg font-bold" :class="(ops.detail.ganancia_neta_usd || 0) >= 0 ? 'text-green-600' : 'text-red-600'">${{ formatMoney(ops.detail.ganancia_neta_usd) }}</p></div>
          <div><p class="text-xs text-gray-500">Tasa aplicada</p><p class="text-lg font-bold text-blue-600">{{ ops.detail.tasa_aplicada }}</p></div>
        </div>
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
          v-if="auth.isAdmin && ops.detail.estatus !== 'verificado'"
          @click="verificar"
          :disabled="verifying"
          class="w-full bg-green-600 hover:bg-green-700 disabled:bg-green-300 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2"
        >
          <span v-if="verifying" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ verifying ? 'Verificando...' : 'Verificar operación' }}
        </button>
      </div>
    </div>

    <!-- Modal motivo de edición -->
    <Teleport to="body">
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showEditModal = false">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="bg-white rounded-2xl w-full max-w-md p-6 relative z-10">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">Editar operación #{{ ops.detail?.id }}</h3>
            <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
          </div>
          <form @submit.prevent="guardarEdicion" class="space-y-4">
            <p class="text-sm text-gray-500">Vas a modificar esta operación. ¿Cuál es el motivo del cambio?</p>
            <textarea v-model="motivoEdicion" rows="3" required placeholder="Ej: El cliente cambió el monto, o no hay fondos en la cuenta A..."
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
            <AppErrorState v-if="editError" :message="editError" :retry="false" />
            <div class="flex gap-3">
              <button type="button" @click="showEditModal = false"
                class="flex-1 py-2.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">Cancelar</button>
              <button type="submit" :disabled="!motivoEdicion.trim()"
                class="flex-1 py-2.5 bg-amber-500 text-white text-sm font-medium rounded-xl hover:bg-amber-600 transition">
                Continuar a edición
              </button>
            </div>
          </form>
        </div>
      </div>
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
import AppLoadingSpinner from '../../components/common/AppLoadingSpinner.vue'
import AppErrorState from '../../components/common/AppErrorState.vue'

/** Ruta actual (contiene params.id) */
const route = useRoute()
/** Router para navegar a edición */
const router = useRouter()
/** Store de operaciones */
const ops = useOperacionesStore()
const { formatMoney } = useFormatting()
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
const puedeEditar = computed(() => {
  const op = ops.detail
  if (!op) return false
  if (op.estatus === 'verificado') return false
  if (op.estado_pool === 'cancelada') return false
  return true
})

/** Verifica la operación actual (cambia estatus a verificado) */
async function verificar() {
  verifying.value = true
  try {
    await ops.verificar(route.params.id)
    await ops.fetchOne(route.params.id)
  } catch {}
  verifying.value = false
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
