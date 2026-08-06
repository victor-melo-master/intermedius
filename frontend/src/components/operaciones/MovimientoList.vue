<template>
  <div>
    <div v-if="!transacciones.length" class="text-center py-8">
      <p class="text-ink-faint text-sm">No hay movimientos registrados</p>
    </div>
    <div v-else class="space-y-2">
      <div v-for="tx in transacciones" :key="tx.id"
        class="border border-edge rounded-xl p-4 space-y-2"
        :class="{ 'opacity-60': ['revertida', 'cancelada', 'fallido'].includes(tx.estado) }">
        <div class="flex items-center justify-between">
          <span class="text-xs text-ink-faint">#{{ tx.orden }}</span>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-medium" :class="estadoBadge(tx).clase">
            {{ estadoBadge(tx).label }}
          </span>
        </div>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div>
            <p class="text-xs text-ink-faint">Origen</p>
            <p class="font-medium text-ink">{{ tx.cuenta_origen?.alias || `Cuenta #${tx.cuenta_origen_id}` }}</p>
            <p v-if="tx.cuenta_origen?.titular_id" class="text-[11px] text-ink-faint">
              Saldo: {{ formatearSaldo(tx.cuenta_origen) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-ink-faint">Destino</p>
            <p class="font-medium text-ink">{{ tx.cuenta_destino?.alias || `Cuenta #${tx.cuenta_destino_id}` }}</p>
            <p v-if="tx.cuenta_destino?.titular_id" class="text-[11px] text-ink-faint">
              Saldo: {{ formatearSaldo(tx.cuenta_destino) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-ink-faint">Monto</p>
            <p class="font-semibold text-heading">{{ Number(tx.monto).toFixed(2) }} {{ tx.moneda?.codigo || '' }}</p>
          </div>
          <div>
            <p class="text-xs text-ink-faint">Método de pago</p>
            <p class="text-ink-muted">{{ tx.metodo_pago || '—' }}</p>
          </div>
        </div>
        <div v-if="tx.comprobante" class="text-xs text-ink-soft bg-surface-soft rounded-lg px-3 py-1">
          Comprobante: {{ tx.comprobante }}
        </div>
        <div v-if="tx.motivo_rechazo" class="text-xs text-danger bg-danger-soft rounded-lg px-3 py-1">
          Motivo: {{ tx.motivo_rechazo }}
        </div>
        <div v-if="!['solicitud', 'cerrada', 'cancelada'].includes(estado)" class="flex gap-2 pt-1 flex-wrap">
          <button v-if="tx.estado === 'pendiente'"
            @click="editarTx(tx)"
            class="text-xs px-3 py-1.5 bg-surface-muted hover:bg-surface-muted rounded-lg transition active:scale-[0.98]">
            Editar
          </button>
          <button v-if="tx.estado === 'pendiente'"
            @click="confirmarTx(tx)"
            class="text-xs px-3 py-1.5 bg-info-soft hover:bg-info-edge text-info-strong rounded-lg transition active:scale-[0.98]">
            Confirmar
          </button>
          <button v-if="tx.estado === 'pendiente'"
            @click="abrirFallar(tx)"
            class="text-xs px-3 py-1.5 bg-danger-soft hover:bg-danger-soft text-danger-strong rounded-lg transition active:scale-[0.98]">
            Fallar
          </button>
          <button v-if="tx.estado === 'pendiente'"
            @click="abrirCancelar(tx)"
            class="text-xs px-3 py-1.5 bg-warning-soft hover:bg-warning-soft text-warning-strong rounded-lg transition active:scale-[0.98]">
            Cancelar
          </button>
          <button v-if="tx.estado === 'pendiente'"
            @click="eliminarTx(tx)"
            class="text-xs px-3 py-1.5 bg-surface-muted hover:bg-surface-muted text-ink-muted rounded-lg transition active:scale-[0.98]">
            Eliminar
          </button>
          <button v-if="tx.estado === 'confirmada'"
            @click="mostrarRevertir = tx.id"
            class="text-xs px-3 py-1.5 bg-warning-soft hover:bg-warning-soft text-warning rounded-lg transition active:scale-[0.98]">
            Revertir
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Confirmar -->
    <ConfirmarMovimientoModal
      :visible="txAConfirmar !== null"
      :transaccion="txAConfirmar"
      :operacion-id="operacionId"
      @confirmado="onConfirmado"
      @cancel="onCancelarConfirmacion"
    />

    <!-- Modal: Editar -->
    <Teleport to="body">
      <AppFormModal v-model="mostrarEditando" title="Editar movimiento">
        <form @submit.prevent="guardarEdicion" class="space-y-4">
          <div>
            <label class="block text-xs text-ink-soft mb-1">Monto</label>
            <input v-model="editForm.monto" type="number" step="0.01" min="0" required
              class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm focus:ring-2 focus:ring-gold outline-none" />
          </div>
          <div>
            <label class="block text-xs text-ink-soft mb-1">Tasa aplicada</label>
            <input v-model="editForm.tasa_aplicada" type="number" step="0.01" min="0"
              class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm focus:ring-2 focus:ring-gold outline-none" />
          </div>
          <div>
            <label class="block text-xs text-ink-soft mb-1">Método de pago</label>
            <select v-model="editForm.metodo_pago" required
              class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm bg-white focus:ring-2 focus:ring-gold outline-none">
              <option value="">Seleccionar</option>
              <option value="efectivo">Efectivo</option>
              <option value="pagomovil">Pago móvil</option>
              <option value="transferencia">Transferencia</option>
              <option value="zelle">Zelle</option>
              <option value="binance">Binance</option>
              <option value="otro">Otro</option>
            </select>
          </div>
          <div v-if="editForm.metodo_pago && editForm.metodo_pago !== 'efectivo'">
            <label class="block text-xs text-ink-soft mb-1">Comprobante <span class="text-danger">*</span></label>
            <input v-model="editForm.comprobante" required
              placeholder="N° de referencia, voucher, hash..."
              class="w-full px-3 py-2 border border-edge-strong rounded-lg text-sm focus:ring-2 focus:ring-gold outline-none" />
          </div>
          <div v-if="editError" class="text-sm text-danger bg-danger-soft rounded-lg px-3 py-2">{{ editError }}</div>
          <div class="flex gap-3">
            <button type="button" @click="cerrarEdicion"
              class="flex-1 py-2.5 text-sm text-ink-muted bg-surface-muted hover:bg-surface-muted rounded-xl transition active:scale-[0.98]">Cancelar</button>
            <button type="submit" :disabled="editando || !editForm.monto"
              class="flex-1 py-2.5 bg-gold text-navy text-sm font-medium rounded-xl hover:bg-gold-dark disabled:opacity-50 transition active:scale-[0.98] flex items-center justify-center gap-2">
              <span v-if="editando" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ editando ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </AppFormModal>
    </Teleport>

    <!-- Modal: Revertir -->
    <Teleport to="body">
      <AppFormModal v-model="mostrarRevertirSeleccionado" title="Revertir movimiento">
        <form @submit.prevent="revertirTx" class="space-y-4">
          <p class="text-sm text-ink-soft">¿Estás seguro de revertir este movimiento? Se ajustará el saldo de las cuentas.</p>
          <textarea v-model="motivoRevertir" rows="3" required
            placeholder="Motivo de la reversión..."
            class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-warning outline-none resize-none"></textarea>
          <div class="flex gap-3">
            <button type="button" @click="mostrarRevertir = null"
              class="flex-1 py-2.5 text-sm text-ink-muted bg-surface-muted hover:bg-surface-muted rounded-xl transition active:scale-[0.98]">Volver</button>
            <button type="submit" :disabled="!motivoRevertir.trim() || revertiendo"
              class="flex-1 py-2.5 bg-orange-600 text-white text-sm font-medium rounded-xl hover:bg-orange-700 disabled:bg-orange-300 transition active:scale-[0.98]">
              {{ revertiendo ? 'Revirtiendo...' : 'Revertir movimiento' }}
            </button>
          </div>
        </form>
      </AppFormModal>
    </Teleport>

    <!-- Modal: Fallar -->
    <Teleport to="body">
      <AppFormModal v-model="mostrarFallarSeleccionado" title="Fallar movimiento">
        <form @submit.prevent="fallarTx" class="space-y-4">
          <p class="text-sm text-ink-soft">Indica la razón por la que este movimiento no pudo ejecutarse.</p>
          <textarea v-model="razonFallar" rows="3" required
            placeholder="Ej: saldo insuficiente, transferencia rechazada..."
            class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-danger outline-none resize-none"></textarea>
          <div class="flex gap-3">
            <button type="button" @click="cerrarFallar"
              class="flex-1 py-2.5 text-sm text-ink-muted bg-surface-muted hover:bg-surface-muted rounded-xl transition active:scale-[0.98]">Volver</button>
            <button type="submit" :disabled="!razonFallar.trim() || fallando"
              class="flex-1 py-2.5 bg-danger text-white dark:text-navy text-sm font-medium rounded-xl hover:bg-danger-strong disabled:opacity-50 transition active:scale-[0.98]">
              {{ fallando ? 'Procesando...' : 'Marcar como fallido' }}
            </button>
          </div>
        </form>
      </AppFormModal>
    </Teleport>

    <!-- Modal: Cancelar -->
    <Teleport to="body">
      <AppFormModal v-model="mostrarCancelarSeleccionado" title="Cancelar movimiento">
        <form @submit.prevent="cancelarTx" class="space-y-4">
          <p class="text-sm text-ink-soft">Indica por qué cancelas este movimiento.</p>
          <textarea v-model="razonCancelar" rows="3" required
            placeholder="Motivo de cancelación..."
            class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-warning outline-none resize-none"></textarea>
          <div class="flex gap-3">
            <button type="button" @click="cerrarCancelar"
              class="flex-1 py-2.5 text-sm text-ink-muted bg-surface-muted hover:bg-surface-muted rounded-xl transition active:scale-[0.98]">Volver</button>
            <button type="submit" :disabled="!razonCancelar.trim() || cancelando"
              class="flex-1 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-xl hover:bg-amber-700 disabled:bg-amber-300 transition active:scale-[0.98]">
              {{ cancelando ? 'Procesando...' : 'Cancelar movimiento' }}
            </button>
          </div>
        </form>
      </AppFormModal>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { useMovimientos } from '@/composables/useMovimientos'
import { useNotification } from '@/composables/useNotification'
import AppFormModal from '@/components/common/AppFormModal.vue'
import ConfirmarMovimientoModal from '@/components/operaciones/ConfirmarMovimientoModal.vue'

const props = defineProps({
  transacciones: { type: Array, default: () => [] },
  operacionId: { type: [String, Number], required: true },
  estado: { type: String, default: '' },
})

const emit = defineEmits(['refrescar'])

const txService = useMovimientos()

function formatearSaldo(cuenta) {
  if (!cuenta || cuenta.saldo_cache == null) return 'N/D'
  const cifra = new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(cuenta.saldo_cache))
  const simbolo = cuenta.moneda?.simbolo || ''
  return simbolo + cifra
}
const notifier = useNotification()

const mostrarRevertir = ref(null)
const motivoRevertir = ref('')
const revertiendo = ref(false)
const editando = ref(false)
const editError = ref('')
const editTx = ref(null)
const editForm = reactive({
  monto: '',
  tasa_aplicada: '',
  metodo_pago: '',
  comprobante: '',
})

// Fallar
const mostrarFallar = ref(null)
const razonFallar = ref('')
const fallando = ref(false)

function abrirFallar(tx) { mostrarFallar.value = tx.id; razonFallar.value = ''; fallando.value = false }
function cerrarFallar() { mostrarFallar.value = null; razonFallar.value = '' }

const mostrarFallarSeleccionado = computed(() => mostrarFallar.value !== null)

async function fallarTx() {
  if (!mostrarFallar.value || !razonFallar.value.trim()) return
  fallando.value = true
  try {
    await txService.fallar(props.operacionId, mostrarFallar.value, razonFallar.value.trim())
    notifier.success('Movimiento marcado como fallido')
    cerrarFallar()
    emit('refrescar')
  } catch (err) {
    notifier.error(err.response?.data?.message || 'Error al fallar el movimiento')
  }
  fallando.value = false
}

// Cancelar
const mostrarCancelar = ref(null)
const razonCancelar = ref('')
const cancelando = ref(false)

function abrirCancelar(tx) { mostrarCancelar.value = tx.id; razonCancelar.value = ''; cancelando.value = false }
function cerrarCancelar() { mostrarCancelar.value = null; razonCancelar.value = '' }

const mostrarCancelarSeleccionado = computed(() => mostrarCancelar.value !== null)

async function cancelarTx() {
  if (!mostrarCancelar.value || !razonCancelar.value.trim()) return
  cancelando.value = true
  try {
    await txService.cancelar(props.operacionId, mostrarCancelar.value, razonCancelar.value.trim())
    notifier.success('Movimiento cancelado')
    cerrarCancelar()
    emit('refrescar')
  } catch (err) {
    notifier.error(err.response?.data?.message || 'Error al cancelar el movimiento')
  }
  cancelando.value = false
}

// End shared

const mostrarEditando = computed(() => editTx.value !== null)

const mostrarRevertirSeleccionado = computed(() => mostrarRevertir.value !== null)

const txAConfirmar = ref(null)

function estadoBadge(tx) {
  const map = {
    pendiente:  { label: 'Pendiente',  clase: 'bg-warning-soft text-warning-strong' },
    confirmada: { label: 'Confirmada', clase: 'bg-success-soft text-success-strong' },
    revertida:  { label: 'Revertida',  clase: 'bg-warning-soft text-warning-strong' },
    cancelada:  { label: 'Cancelada',  clase: 'bg-danger-soft text-danger-strong' },
    fallido:    { label: 'Fallido',    clase: 'bg-danger-soft text-danger-strong' },
  }
  return map[tx.estado] || { label: tx.estado, clase: 'bg-surface-muted text-ink-muted' }
}

function editarTx(tx) {
  editTx.value = tx
  editForm.monto = String(tx.monto)
  editForm.tasa_aplicada = tx.tasa_aplicada ? String(tx.tasa_aplicada) : ''
  editForm.metodo_pago = tx.metodo_pago || ''
  editForm.comprobante = tx.comprobante || ''
  editError.value = ''
}

function cerrarEdicion() {
  editTx.value = null
}

async function guardarEdicion() {
  if (!editTx.value || !editForm.monto) return
  editando.value = true
  editError.value = ''
  try {
    const payload = { monto: parseFloat(editForm.monto) }
    if (editForm.tasa_aplicada) payload.tasa_aplicada = parseFloat(editForm.tasa_aplicada)
    if (editForm.metodo_pago) payload.metodo_pago = editForm.metodo_pago
    if (editForm.comprobante) payload.comprobante = editForm.comprobante
    await txService.editar(props.operacionId, editTx.value.id, payload)
    notifier.success('Movimiento actualizado')
    cerrarEdicion()
    emit('refrescar')
  } catch (err) {
    editError.value = err.response?.data?.message || err.message
  }
  editando.value = false
}

function confirmarTx(tx) {
  txAConfirmar.value = tx
}

function onConfirmado() {
  txAConfirmar.value = null
  emit('refrescar')
}

function onCancelarConfirmacion() {
  txAConfirmar.value = null
}

async function eliminarTx(tx) {
  if (!confirm('¿Eliminar este movimiento?')) return
  try {
    await txService.eliminar(props.operacionId, tx.id)
    notifier.success('Movimiento eliminado')
    emit('refrescar')
  } catch {
    notifier.error('Error al eliminar el movimiento')
  }
}

async function revertirTx() {
  if (!mostrarRevertir.value || !motivoRevertir.value.trim()) return
  revertiendo.value = true
  try {
    await txService.revertir(props.operacionId, mostrarRevertir.value, motivoRevertir.value.trim())
    notifier.success('Movimiento revertido')
    mostrarRevertir.value = null
    motivoRevertir.value = ''
    emit('refrescar')
  } catch {
    notifier.error('Error al revertir el movimiento')
  }
  revertiendo.value = false
}
</script>
