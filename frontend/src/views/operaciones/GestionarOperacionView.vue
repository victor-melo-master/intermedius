<template>
  <div class="max-w-4xl mx-auto space-y-4">
    <div class="flex items-center gap-3">
      <button @click="$router.back()" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-ink-muted hover:bg-surface-muted rounded-lg transition"><Iconoir name="arrow-left" class="w-4 h-4" /> Volver</button>
      <h2 class="text-xl font-bold text-heading">Gestionar operación #{{ store.detail?.id }}</h2>
    </div>

    <AppLoadingSpinner v-if="store.loading" />
    <AppErrorState v-else-if="store.error" :message="store.error" @retry="cargarOperacion" />
    <template v-else-if="store.detail">
      <!-- ════════ RESUMEN MONTO ════════ -->
      <div class="bg-surface border border-edge rounded-xl p-5">
        <div class="grid grid-cols-3 gap-4 text-center">
          <div>
            <p class="text-sm text-ink-muted mb-1">Monto divisa</p>
            <p class="text-xl font-bold text-heading">
              {{ formatMoney(montoDivisa) }} {{ monedaDivisa }}
            </p>
          </div>
          <div>
            <p class="text-sm text-ink-muted mb-1">Tasa</p>
            <p class="text-xl font-bold text-gold-dark">
              {{ formatRate(store.detail.tasa_aplicada) }}
            </p>
          </div>
          <div>
            <p class="text-sm text-ink-muted mb-1">Bolívares</p>
            <p class="text-xl font-bold text-success">
              {{ formatVes(montoBolivares) }}
            </p>
          </div>
        </div>
      </div>

      <!-- ════════ CABECERA ════════ -->
      <div class="bg-surface border border-edge rounded-xl p-5 space-y-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-sm text-ink-muted">#{{ store.detail.id }}</span>
            <span class="px-3 py-1 rounded-full text-xs font-bold" :class="badgeEstado.clase">{{ badgeEstado.label }}</span>
          </div>
          <span class="text-sm text-ink-muted">{{ formatDate(store.detail.fecha) }}</span>
        </div>
        <p class="font-semibold text-lg">{{ nombreOperacion }}</p>
        <p v-if="store.detail.cliente?.nombre" class="text-sm text-ink-muted">Cliente: {{ store.detail.cliente.nombre }}</p>
        <p v-if="store.detail.referencia" class="text-sm text-ink-muted">Ref: {{ store.detail.referencia }}</p>
        <p v-if="store.detail.descripcion" class="text-sm text-ink-muted bg-surface-soft p-3 rounded-lg">{{ store.detail.descripcion }}</p>
      </div>

      <!-- ════════ FLUJO PROGRESS ════════ -->
      <div class="bg-surface border border-edge rounded-xl p-5">
        <FlujoProgress :estado="store.detail.estado" :revertida="store.detail.estado === 'revertida'" />
      </div>

      <!-- ════════ GANANCIA ESTIMADA ════════ -->
      <div v-if="gananciaPreview && store.detail.estado === 'en_progreso'" class="bg-surface border border-edge rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-ink">Ganancia estimada</h3>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-ink-muted">Bruta</p>
            <p class="text-lg font-bold" :class="gananciaPreview.bruta_usd >= 0 ? 'text-success' : 'text-danger'">
              {{ formatMoney(gananciaPreview.bruta_usd) }} USD
            </p>
            <p class="text-sm text-ink-muted">Bs. {{ formatMoney(gananciaPreview.bruta_ves) }}</p>
          </div>
          <div>
            <p class="text-sm text-ink-muted">Neta</p>
            <p class="text-lg font-bold" :class="gananciaPreview.neta_usd >= 0 ? 'text-success' : 'text-danger'">
              {{ formatMoney(gananciaPreview.neta_usd) }} USD
            </p>
            <p class="text-sm text-ink-muted">Bs. {{ formatMoney(gananciaPreview.neta_ves) }}</p>
          </div>
        </div>
      </div>

      <!-- ════════ MOVIMIENTOS ════════ -->
      <div class="bg-surface border border-edge rounded-xl p-5 space-y-3">
        <h3 class="font-semibold text-ink">Movimientos</h3>
        <MovimientoList
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
          class="w-full bg-gold hover:bg-gold-dark disabled:opacity-50 text-navy font-semibold py-3 rounded-xl transition active:scale-[0.98] flex items-center justify-center gap-2">
          <span v-if="acting" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          <Iconoir v-if="!acting" name="rocket-launch" class="w-5 h-5" />
          {{ acting ? 'Iniciando...' : 'Iniciar operación' }}
        </button>

        <template v-if="store.detail.estado === 'en_progreso'">
          <div v-if="operacionBalanceada" class="bg-success-soft border border-success-edge rounded-xl px-4 py-3">
            <p class="text-success-strong text-sm font-medium text-center"><Iconoir name="check" class="w-4 h-4 text-success" /> Movimientos balanceados</p>
          </div>
          <button v-else
            @click="mostrarAgregarTx = true"
            class="w-full bg-gold hover:bg-gold-dark text-navy font-semibold py-3 rounded-xl transition active:scale-[0.98] flex items-center justify-center gap-2">
            + Agregar movimiento
          </button>

          <button
            @click="mostrarCerrar = true" :disabled="acting || !operacionBalanceada"
class="w-full bg-success hover:bg-success-strong disabled:opacity-50 text-white dark:text-navy font-semibold py-3 rounded-xl transition active:scale-[0.98] flex items-center justify-center gap-2">
          <span v-if="acting" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          <Iconoir v-if="!acting" name="lock-closed" class="w-5 h-5" />
          {{ acting ? 'Cerrando...' : 'Cerrar operación' }}
          </button>
          <p v-if="!operacionBalanceada" class="text-sm text-ink-muted text-center">
            Confirma todos los movimientos para cerrar la operación
          </p>
        </template>

        <button v-if="store.detail.estado !== 'cerrada' && store.detail.estado !== 'cancelada'"
          @click="mostrarCancelar = true"
          class="w-full bg-danger-soft hover:bg-danger-soft text-danger font-semibold py-3 rounded-xl transition active:scale-[0.98]">
          Cancelar operación
        </button>
      </div>
    </template>

    <!-- Modal: Agregar movimiento -->
    <Teleport to="body">
      <AppFormModal v-model="mostrarAgregarTx" title="Nuevo movimiento">
        <MovimientoForm
          :operacion-id="store.detail?.id"
          :cliente-id="store.detail?.cliente?.id"
          :cliente-nombre="store.detail?.cliente?.nombre || ''"
          :intermedius-titular-id="intermediusTitularId"
          :monedas-permitidas="monedasPermitidas"
          :es-compra="esCompra"
          :tasa-operacion="store.detail?.tasa_aplicada"
          :monto-solicitado="store.detail?.monto_solicitado"
          :movimientos-existentes="store.detail?.transacciones || []"
          @saved="onMovimientoGuardada"
          @cancel="mostrarAgregarTx = false"
        />
      </AppFormModal>
    </Teleport>

    <!-- Modal: Cerrar operación -->
    <Teleport to="body">
      <AppFormModal v-model="mostrarCerrar" title="Cerrar operación">
        <form @submit.prevent="cerrarOperacion" class="space-y-4">
          <p class="text-sm text-ink-muted">Confirma el cierre de esta operación. Se generarán los movimientos contables.</p>
          <div>
            <label class="block text-sm font-medium text-ink mb-1">Tasa de mercado ({{ fuenteTasaLabel }}) *</label>
            <input v-model.number="tasaMercadoCierre" type="number" step="0.01" min="0" required
              placeholder="Ej: 64.00"
              class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-success outline-none" />
            <p class="text-sm text-ink-muted mt-1">Tasa de referencia para el cálculo de ganancia</p>
          </div>
          <div v-if="tasaMercadoCierre" class="bg-surface-soft rounded-xl p-3 text-sm text-ink-muted">
            <p>Ganancia estimada:             <span class="font-semibold" :class="gananciaPreview?.bruta_usd >= 0 ? 'text-success' : 'text-danger'">$ {{ formatRate(gananciaPreview?.bruta_usd || 0) }} USD</span></p>
          </div>
          <div class="flex gap-3">
            <button type="button" @click="mostrarCerrar = false"
              class="flex-1 py-2.5 text-sm text-ink-muted bg-surface-muted hover:bg-surface-muted rounded-xl transition active:scale-[0.98]">Volver</button>
            <button type="submit" :disabled="acting || !tasaMercadoCierre"
              class="flex-1 py-2.5 bg-success text-white dark:text-navy text-sm font-medium rounded-xl hover:bg-success-strong disabled:opacity-50 transition active:scale-[0.98] flex items-center justify-center gap-2">
              <span v-if="acting" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ acting ? 'Cerrando...' : 'Cerrar operación' }}
            </button>
          </div>
        </form>
      </AppFormModal>
    </Teleport>

    <!-- Modal: Cancelar operación -->
    <Teleport to="body">
      <AppFormModal v-model="mostrarCancelar" title="Cancelar operación">
        <form @submit.prevent="cancelarOperacion" class="space-y-4">
          <p class="text-sm text-ink-muted">¿Estás seguro de cancelar esta operación?</p>
          <textarea v-model="motivoCancelacion" rows="3" required
            placeholder="Motivo de la cancelación..."
            class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-danger outline-none resize-none"></textarea>
          <div class="flex gap-3">
            <button type="button" @click="mostrarCancelar = false"
              class="flex-1 py-2.5 text-sm text-ink-muted bg-surface-muted hover:bg-surface-muted rounded-xl transition active:scale-[0.98]">Volver</button>
            <button type="submit" :disabled="!motivoCancelacion.trim() || acting"
              class="flex-1 py-2.5 bg-danger text-white dark:text-navy text-sm font-medium rounded-xl hover:bg-danger-strong disabled:opacity-50 transition active:scale-[0.98] flex items-center justify-center gap-2">
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
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOperacionesStore } from '../../stores/operaciones.js'
import { useNotification } from '@/composables/useNotification'
import { useFormatting } from '@/composables/useFormatting'
import { useTitulares } from '@/composables/useTitulares'
import FlujoProgress from '@/components/operaciones/FlujoProgress.vue'
import MovimientoList from '@/components/operaciones/MovimientoList.vue'
import MovimientoForm from '@/components/operaciones/MovimientoForm.vue'
import AppLoadingSpinner from '@/components/common/AppLoadingSpinner.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import AppFormModal from '@/components/common/AppFormModal.vue'
import { useTasasReferencia } from '@/composables/useTasasReferencia'
import { useSaldoCuenta } from '@/composables/useSaldoCuenta'
import Iconoir from '@/components/common/Iconoir.vue'

const route = useRoute()
const router = useRouter()
const store = useOperacionesStore()
const notifier = useNotification()
const { formatMoney, formatVes, formatRate, formatDate } = useFormatting()
const titulares = useTitulares()
const tasasRef = useTasasReferencia()

const saldoCuenta = useSaldoCuenta()
const acting = ref(false)
const mostrarAgregarTx = ref(false)
const mostrarCancelar = ref(false)
const mostrarCerrar = ref(false)
const motivoCancelacion = ref('')
const intermediusTitularId = ref(null)
const gananciaPreview = ref(null)
const tasaMercadoCierre = ref(null)

const tieneMovimientosConfirmados = computed(() =>
  (store.detail?.transacciones || []).some(tx => tx.estado === 'confirmada')
)

const operacionBalanceada = computed(() => {
  if (!tieneMovimientosConfirmados.value) return false
  const op = store.detail
  if (!op) return false
  const monedaOp = op.moneda_operacion?.codigo
  if (!monedaOp) return false

  const monto = parseFloat(op.monto_solicitado || 0)
  const tasa = parseFloat(op.tasa_aplicada || 0)
  const expectedVes = monto * tasa

  let totalDivisa = 0
  let totalVes = 0
  for (const tx of (op.transacciones || [])) {
    if (tx.estado !== 'confirmada') continue
    if (tx.moneda?.codigo === monedaOp) totalDivisa += Math.abs(parseFloat(tx.monto))
    else if (tx.moneda?.codigo === 'VES') totalVes += Math.abs(parseFloat(tx.monto))
  }

  return Math.abs(totalDivisa - monto) <= 0.01 && Math.abs(totalVes - expectedVes) <= 0.01
})

const monedasPermitidas = computed(() => {
  const codigo = store.detail?.tipo_operacion?.codigo
  const monedaOp = store.detail?.moneda_operacion?.codigo
  if (!codigo) return []
  if (['compra_usd', 'venta_usd'].includes(codigo)) {
    if (monedaOp) return [monedaOp, 'VES']
    return ['USD', 'VES']
  }
  return []
})

const esCompra = computed(() => store.detail?.tipo_operacion?.codigo === 'compra_usd')

const nombreOperacion = computed(() => {
  const nombre = store.detail?.tipo_operacion?.nombre
  if (!nombre) return 'Operación'
  const moneda = store.detail?.moneda_operacion?.codigo
  if (!moneda) return nombre
  return nombre.replace('USD', moneda)
})

const badgeEstado = computed(() => {
  const map = {
    solicitud:   { label: 'Solicitud',   clase: 'bg-warning-soft text-warning-strong' },
    en_progreso: { label: 'En Progreso', clase: 'bg-info-soft text-info-strong' },
    cerrada:     { label: 'Cerrada',     clase: 'bg-success-soft text-success-strong' },
    cancelada:   { label: 'Cancelada',   clase: 'bg-danger-soft text-danger-strong' },
  }
  return map[store.detail?.estado] || { label: store.detail?.estado || '—', clase: 'bg-surface-muted text-ink-muted' }
})

const montoDivisa = computed(() => {
  const op = store.detail
  if (!op) return 0
  return op.monto_solicitado ? Math.abs(parseFloat(op.monto_solicitado)) : 0
})

const monedaDivisa = computed(() => {
  return store.detail?.moneda_operacion?.codigo || 'USD'
})

const fuenteTasaLabel = computed(() => {
  const map = { USD: 'BCV', EUR: 'BCV', USDT: 'Binance' }
  return map[monedaDivisa.value] || 'BCV'
})

const montoBolivares = computed(() => {
  const op = store.detail
  if (!op) return 0
  const usd = montoDivisa.value
  const tasa = parseFloat(op.tasa_aplicada)
  return usd && tasa ? usd * tasa : 0
})

async function cargarOperacion() {
  saldoCuenta.invalidateCache()
  await store.fetchOne(route.params.id)
}

async function iniciarOperacion() {
  acting.value = true
  try {
    await store.iniciar(route.params.id)
    await cargarOperacion()
    notifier.success('Operación iniciada')
  } catch {
    notifier.error('Error al iniciar la operación')
  }
  acting.value = false
}

async function cerrarOperacion() {
  acting.value = true
  try {
    const payload = {}
    if (tasaMercadoCierre.value) {
      payload.tasa_mercado_snapshot = tasaMercadoCierre.value
      const fuenteMap = { USD: 'bcv', EUR: 'bcv_eur', USDT: 'binance_p2p' }
      payload.fuente_tasa_mercado = fuenteMap[monedaDivisa.value] || 'bcv'
    }
    await store.cerrar(route.params.id, payload)
    notifier.success('Operación cerrada — movimientos generados')
    mostrarCerrar.value = false
    router.push('/operaciones')
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

async function onMovimientoGuardada() {
  mostrarAgregarTx.value = false
  saldoCuenta.invalidateCache()
  await cargarOperacion()
  await cargarGananciaPreview()
}

async function cargarGananciaPreview() {
  if (!store.detail?.id || store.detail?.estado !== 'en_progreso') {
    gananciaPreview.value = null
    return
  }
  try {
    const codigo = monedaDivisa.value
    await tasasRef.fetch()
    const ref = tasasRef.refTasaPorMoneda(codigo)
    gananciaPreview.value = await store.fetchGananciaPreview(store.detail.id, ref || null)
  } catch {
    gananciaPreview.value = null
  }
}

watch(mostrarCerrar, async (abierto) => {
  if (!abierto) return
  const codigo = monedaDivisa.value
  await tasasRef.fetch()
  const ref = tasasRef.refTasaPorMoneda(codigo)
  if (ref) {
    tasaMercadoCierre.value = Math.round(parseFloat(ref) * 100) / 100
  }
})

watch(tasaMercadoCierre, async (nueva) => {
  if (!store.detail?.id || store.detail?.estado !== 'en_progreso') return
  try {
    gananciaPreview.value = await store.fetchGananciaPreview(store.detail.id, nueva || null)
  } catch {
    // keep existing preview
  }
})

onMounted(async () => {
  await titulares.fetchAll()
  const intermedius = titulares.getIntermedius()
  intermediusTitularId.value = intermedius ? intermedius.id : null
  await cargarOperacion()
  tasasRef.start()
  await cargarGananciaPreview()
})
</script>
