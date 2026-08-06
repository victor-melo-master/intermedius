<template>
  <div class="max-w-7xl mx-auto pb-6">
    <div class="mb-4">
      <button @click="$router.back()" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-ink-muted hover:bg-surface-muted rounded-lg transition"><Iconoir name="arrow-left" class="w-4 h-4" /> Volver</button>
    </div>

    <AppLoadingSpinner v-if="ops.loading" />
    <AppErrorState v-else-if="ops.error" :message="ops.error" @retry="ops.fetchOne(route.params.id)" />

    <div v-else-if="ops.detail">
      <div class="bg-white dark:bg-surface-alt rounded-xl border border-edge divide-y divide-edge">

        <!-- Header: resumen + datos -->
        <div class="p-5">
          <div class="flex items-center justify-between mb-4">
            <p class="font-bold text-lg text-heading">{{ nombreOperacion }}</p>
            <div class="flex items-center gap-2">
              <span v-if="ops.detail.estado" class="px-3 py-1 rounded-full text-xs font-bold"
                :class="ops.detail.estado === 'cerrada' ? 'bg-success-soft text-success-strong' :
                         ops.detail.estado === 'en_progreso' ? 'bg-info-soft text-info-strong' :
                         ops.detail.estado === 'solicitud' ? 'bg-warning-soft text-warning-strong' :
                         ops.detail.estado === 'cancelada' ? 'bg-danger-soft text-danger-strong' :
                         ops.detail.estado === 'revertida' ? 'bg-warning-soft text-warning-strong' :
                         'bg-surface-soft text-ink'">
                {{ ops.detail.estado?.replace('_', ' ') }}
              </span>
              <span v-if="!esFlujoMultipaso" class="px-3 py-1 rounded-full text-xs font-bold"
                :class="ops.detail.estatus === 'verificado' ? 'bg-success-soft text-success-strong' :
                         ops.detail.estatus === 'en_verificacion' ? 'bg-info-soft text-info-strong' :
                         ops.detail.estatus === 'en_revision' ? 'bg-warning-soft text-warning-strong' :
                         'bg-surface-soft text-ink'">
                {{ ops.detail.estatus?.replace('_', ' ') }}
              </span>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Montos -->
            <div>
              <div class="grid grid-cols-2 gap-3">
                <div class="bg-gold-soft rounded-xl px-4 py-3 text-center">
                  <p class="text-xs text-gold-dark mb-1">El cliente entrega</p>
                  <p class="text-lg font-bold" :class="esCompra ? 'text-success-strong' : 'text-info-strong'">
                    {{ esCompra ? formatMoney(montoDivisa) + ' ' + monedaDivisa : formatVes(montoBolivares) }}
                  </p>
                </div>
                <div class="bg-success-soft rounded-xl px-4 py-3 text-center">
                  <p class="text-xs text-success mb-1">La casa entrega</p>
                  <p class="text-lg font-bold" :class="esCompra ? 'text-info-strong' : 'text-success-strong'">
                    {{ esCompra ? formatVes(montoBolivares) : formatMoney(montoDivisa) + ' ' + monedaDivisa }}
                  </p>
                </div>
              </div>
              <div class="text-center mt-2">
                <span class="text-sm text-ink-soft">Tasa:</span>
                <span class="ml-1 text-lg font-bold text-gold-dark">{{ formatRate(ops.detail.tasa_aplicada) }}</span>
              </div>
            </div>

            <!-- Datos -->
            <div class="grid grid-cols-2 gap-3">
              <div class="bg-surface-soft rounded-xl px-4 py-3">
                <p class="text-xs text-ink-soft flex items-center gap-1 mb-0.5">
                  <Iconoir name="identification" class="w-3.5 h-3.5" /> Número de operación
                </p>
                <p class="text-lg font-bold text-heading">{{ ops.detail.id }}</p>
              </div>
              <div v-if="ops.detail.cliente?.nombre" class="bg-surface-soft rounded-xl px-4 py-3">
                <p class="text-xs text-ink-soft flex items-center gap-1 mb-0.5">
                  <Iconoir name="users" class="w-3.5 h-3.5" /> Cliente
                </p>
                <p class="text-lg font-bold text-heading truncate">{{ ops.detail.cliente.nombre }}</p>
              </div>
              <div class="bg-surface-soft rounded-xl px-4 py-3">
                <p class="text-xs text-ink-soft flex items-center gap-1 mb-0.5">
                  <Iconoir name="document-text" class="w-3.5 h-3.5" /> Fecha
                </p>
                <p class="text-lg font-bold text-heading">{{ formatDate(ops.detail.fecha) }}</p>
              </div>
              <div v-if="ops.detail.moneda_operacion" class="bg-surface-soft rounded-xl px-4 py-3">
                <p class="text-xs text-ink-soft flex items-center gap-1 mb-0.5">
                  <Iconoir name="currency-dollar" class="w-3.5 h-3.5" /> Moneda
                </p>
                <p class="text-lg font-bold text-heading">{{ ops.detail.moneda_operacion.codigo }}</p>
              </div>
              <div v-if="ops.detail.referencia" class="bg-surface-soft rounded-xl px-4 py-3">
                <p class="text-xs text-ink-soft flex items-center gap-1 mb-0.5">
                  <Iconoir name="link" class="w-3.5 h-3.5" /> Referencia
                </p>
                <p class="text-lg font-bold text-heading truncate">{{ ops.detail.referencia }}</p>
              </div>
            </div>
          </div>

          <!-- Alertas -->
          <p v-if="ops.detail.descripcion" class="mt-4 text-sm text-ink-muted bg-surface-soft p-3 rounded-lg">{{ ops.detail.descripcion }}</p>
          <p v-if="ops.detail.motivo_reversion" class="mt-3 text-sm text-warning-strong bg-warning-soft p-3 rounded-lg">
            <Iconoir name="arrow-uturn-left" class="w-4 h-4 inline" /> Reversión: {{ ops.detail.motivo_reversion }}
          </p>
          <p v-if="ops.detail.motivo_cancelacion" class="mt-3 text-sm text-danger bg-danger-soft p-3 rounded-lg">
            <Iconoir name="x-mark" class="w-4 h-4 inline" /> Cancelación: {{ ops.detail.motivo_cancelacion }}
          </p>
        </div>

        <!-- Movimientos -->
        <div v-if="ops.detail.transacciones?.length" class="p-5">
          <h3 class="text-base font-semibold text-ink mb-3">Movimientos</h3>
          <div class="space-y-2">
            <div v-for="tx in ops.detail.transacciones" :key="tx.id"
              class="bg-surface-soft rounded-lg px-4 py-3 space-y-1.5"
              :class="{ 'opacity-50': ['revertida', 'cancelada', 'fallido'].includes(tx.estado) }">
              <div class="flex justify-end">
                <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="txEstadoBadge(tx).clase">
                  {{ txEstadoBadge(tx).label }}
                </span>
              </div>
              <div class="flex items-center gap-3">
                <div class="flex-1 text-right">
                  <p class="text-ink-soft text-sm">{{ tx.cuenta_origen?.alias || txLabelMetodo(tx) }}</p>
                  <p class="font-semibold text-danger">{{ formatMoney(tx.monto, tx.moneda?.codigo) }}</p>
                </div>
                <Iconoir name="arrow-right" class="w-5 h-5 shrink-0 text-ink-faint" />
                <div class="flex-1">
                  <p class="text-ink-soft text-sm">{{ tx.cuenta_destino?.alias || `Cuenta #${tx.cuenta_destino_id}` }}</p>
                  <p class="font-semibold text-success">{{ formatMoney(tx.monto, tx.moneda?.codigo) }}</p>
                </div>
              </div>
            </div>
            <div v-for="tx in ops.detail.transacciones" :key="'motivo-'+tx.id">
              <p v-if="tx.motivo_rechazo" class="text-sm text-danger bg-danger-soft rounded-lg px-3 py-1 ml-12">
                Motivo: {{ tx.motivo_rechazo }}
              </p>
            </div>
          </div>
        </div>

        <!-- Historial -->
        <div class="p-5">
          <h3 class="text-base font-semibold text-ink mb-3">Historial</h3>
          <div class="grid gap-3" :class="historial.length <= 4 ? 'grid-cols-2 sm:grid-cols-4' : 'grid-cols-2 sm:grid-cols-3'">
            <div v-for="ev in historial" :key="ev.label" class="flex items-center gap-3 bg-surface-soft rounded-lg px-4 py-3">
              <span class="w-3 h-3 rounded-full shrink-0" :class="ev.color"></span>
              <div>
                <p class="text-ink font-semibold">{{ ev.label }}</p>
                <p class="text-ink-soft text-sm">{{ ev.fecha }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Ganancia -->
        <div v-if="ops.detail.estado === 'cerrada'" class="p-5">
          <h3 class="text-base font-semibold text-ink mb-3">Ganancia</h3>
          <div class="grid grid-cols-2 gap-4 max-w-sm">
            <div>
              <p class="text-xs text-ink-soft">Bruta</p>
              <p class="text-lg font-bold" :class="gananciaBrutaUsd >= 0 ? 'text-success' : 'text-danger'">
                {{ formatMoney(gananciaBrutaUsd) }} USD
              </p>
              <p class="text-xs text-ink-faint">{{ formatVes(gananciaBrutaVes) }}</p>
            </div>
            <div>
              <p class="text-xs text-ink-soft">Neta</p>
              <p class="text-lg font-bold" :class="gananciaNetaUsd >= 0 ? 'text-success' : 'text-danger'">
                {{ formatMoney(gananciaNetaUsd) }} USD
              </p>
              <p class="text-xs text-ink-faint">{{ formatVes(gananciaNetaVes) }}</p>
            </div>
          </div>
        </div>

      </div>

      <!-- Acciones -->
      <div class="mt-4 space-y-2">
        <router-link v-if="ops.detail.estado && ['solicitud', 'en_progreso'].includes(ops.detail.estado)"
          :to="`/operaciones/${ops.detail.id}/gestionar`"
          class="block w-full bg-gold hover:bg-gold-dark text-navy font-semibold py-3 rounded-xl transition text-center">
          Gestionar movimientos
        </router-link>

        <button v-if="puedeRevertir" @click="mostrarRevertirOp = true"
          class="w-full bg-danger hover:bg-danger-strong text-white dark:text-navy font-semibold py-3 rounded-xl transition active:scale-[0.98] flex items-center justify-center gap-2">
          <Iconoir name="arrow-uturn-left" class="w-5 h-5" /> Revertir venta
        </button>

        <button v-if="puedeEditar" @click="abrirEdicion"
          class="w-full bg-gold hover:bg-gold-dark text-navy font-semibold py-3 rounded-xl transition active:scale-[0.98] flex items-center justify-center gap-2">
          <Iconoir name="pencil-square" class="w-5 h-5" /> Editar operación
        </button>

        <button v-if="auth.isAdmin && !esFlujoMultipaso && ops.detail.estatus === 'sin_verificar'"
          @click="iniciarVerificacion" :disabled="verifying"
          class="w-full bg-gold hover:bg-gold-dark disabled:opacity-50 text-navy font-semibold py-3 rounded-xl transition active:scale-[0.98] flex items-center justify-center gap-2">
          <span v-if="verifying" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ verifying ? 'Iniciando...' : 'Verificar transacciones' }}
        </button>

        <button v-if="auth.isAdmin && !esFlujoMultipaso && ops.detail.estatus === 'en_verificacion'"
          @click="irAVerificacion"
          class="w-full bg-success hover:bg-success-strong text-white dark:text-navy font-semibold py-3 rounded-xl transition active:scale-[0.98] flex items-center justify-center gap-2">
          Continuar verificación
        </button>
      </div>
    </div>

    <!-- Modal: Revertir venta -->
    <Teleport to="body">
      <AppFormModal v-model="mostrarRevertirOp" title="Revertir venta">
        <form @submit.prevent="confirmarRevertir" class="space-y-4">
          <p class="text-sm text-ink-soft">¿Estás seguro de revertir esta venta? Se generarán movimientos inversos.</p>
          <textarea v-model="motivoRevertirOp" rows="3" required
            placeholder="Motivo de la reversión..."
            class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-warning outline-none resize-none"></textarea>
          <div v-if="errorRevertir" class="text-sm text-danger bg-danger-soft rounded-lg px-3 py-2">{{ errorRevertir }}</div>
          <div class="flex gap-3">
            <button type="button" @click="mostrarRevertirOp = false"
              class="flex-1 py-2.5 text-sm text-ink-muted bg-surface-muted hover:bg-surface-muted rounded-xl transition active:scale-[0.98]">Cancelar</button>
            <button type="submit" :disabled="!motivoRevertirOp.trim() || revertiendoOp"
              class="flex-1 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-xl hover:bg-amber-700 disabled:bg-amber-300 transition active:scale-[0.98] flex items-center justify-center gap-2">
              <span v-if="revertiendoOp" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ revertiendoOp ? 'Revirtiendo...' : 'Revertir venta' }}
            </button>
          </div>
        </form>
      </AppFormModal>
    </Teleport>

    <!-- Modal motivo de edición -->
    <Teleport to="body">
      <AppFormModal v-model="showEditModal" :title="'Editar operación #' + (ops.detail?.id || '')">
        <form @submit.prevent="guardarEdicion" class="space-y-4">
          <p class="text-sm text-ink-soft">Vas a modificar esta operación. ¿Cuál es el motivo del cambio?</p>
          <textarea v-model="motivoEdicion" rows="3" required placeholder="Ej: El cliente cambió el monto, o no hay fondos en la cuenta A..."
            class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-gold outline-none resize-none"></textarea>
          <AppErrorState v-if="editError" :message="editError" :retry="false" />
        </form>
        <template #footer>
          <div class="flex gap-3">
            <button type="button" @click="showEditModal = false"
              class="flex-1 py-2.5 text-sm text-ink-muted bg-surface-muted hover:bg-surface-muted rounded-xl transition active:scale-[0.98]">Cancelar</button>
            <button @click="guardarEdicion" :disabled="!motivoEdicion.trim()"
              class="flex-1 py-2.5 bg-warning-soft0 text-white text-sm font-medium rounded-xl hover:bg-amber-600 transition active:scale-[0.98]">
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
import { useNotification } from '@/composables/useNotification'
import api from '@/api/axios'
import AppLoadingSpinner from '../../components/common/AppLoadingSpinner.vue'
import AppErrorState from '../../components/common/AppErrorState.vue'
import Iconoir from '@/components/common/Iconoir.vue'
import AppFormModal from '@/components/common/AppFormModal.vue'

/** Ruta actual (contiene params.id) */
const route = useRoute()
/** Router para navegar a edición */
const router = useRouter()
/** Store de operaciones */
const ops = useOperacionesStore()
const { formatMoney, formatVes, formatRate, formatDate } = useFormatting()
const notifier = useNotification()
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

/** Revertir venta */
const mostrarRevertirOp = ref(false)
const motivoRevertirOp = ref('')
const revertiendoOp = ref(false)
const errorRevertir = ref('')

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

const puedeRevertir = computed(() => {
  const op = ops.detail
  if (!op) return false
  if (!auth.isAdmin && !auth.isSuperAdmin) return false
  if (op.tipo_operacion?.codigo !== 'venta_usd') return false
  if (op.estado !== 'cerrada') return false
  return !op.revertida_at
})

const puedeEditar = computed(() => {
  const op = ops.detail
  if (!op) return false
  if (op.estatus === 'verificado') return false
  if (op.estado_pool === 'cancelada') return false
  return true
})

const historial = computed(() => {
  const op = ops.detail
  if (!op) return []
  const eventos = []
  if (op.created_at) eventos.push({ label: 'Creada', fecha: formatDate2(op.created_at), color: 'bg-ink-faint' })
  if (op.en_progreso_at) eventos.push({ label: 'Iniciada', fecha: formatDate2(op.en_progreso_at), color: 'bg-gold' })
  if (op.verificado_at) eventos.push({ label: 'Verificada', fecha: formatDate2(op.verificado_at), color: 'bg-success-soft0' })
  if (op.cancelada_at) eventos.push({ label: 'Cancelada', fecha: formatDate2(op.cancelada_at), color: 'bg-danger-soft0' })
  if (op.revertida_at) eventos.push({ label: 'Revertida', fecha: formatDate2(op.revertida_at), color: 'bg-warning-soft0' })
  return eventos
})

function formatDate2(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  } catch { return iso }
}

function txEstadoBadge(tx) {
  const map = {
    pendiente:  { label: 'Pendiente',  clase: 'bg-warning-soft text-warning-strong' },
    confirmada: { label: 'Confirmada', clase: 'bg-success-soft text-success-strong' },
    revertida:  { label: 'Revertida',  clase: 'bg-warning-soft text-warning-strong' },
    cancelada:  { label: 'Cancelada',  clase: 'bg-danger-soft text-danger-strong' },
    fallido:    { label: 'Fallido',    clase: 'bg-danger-soft text-danger-strong' },
  }
  return map[tx.estado] || { label: tx.estado, clase: 'bg-surface-muted text-ink-muted' }
}

function txLabelMetodo(tx) {
  const map = {
    efectivo:      'Efectivo',
    pagomovil:     'Pago móvil',
    transferencia: 'Transferencia',
  }
  return map[tx.metodo_pago] || tx.metodo_pago || `Cuenta #${tx.cuenta_origen_id}`
}

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

/** Confirma la reversión de la venta */
async function confirmarRevertir() {
  if (!motivoRevertirOp.value.trim()) return
  revertiendoOp.value = true
  errorRevertir.value = ''
  try {
    await store.revertirOperacion(route.params.id, motivoRevertirOp.value.trim())
    notifier.success('Venta revertida exitosamente')
    mostrarRevertirOp.value = false
    motivoRevertirOp.value = ''
    await ops.fetchOne(route.params.id)
  } catch (err) {
    errorRevertir.value = err.response?.data?.message || err.message
  }
  revertiendoOp.value = false
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
