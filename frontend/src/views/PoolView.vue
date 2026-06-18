<template>
  <div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center justify-between gap-3">
      <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
        <span>💸</span> Pool de pagos
      </h2>
      <button v-if="tab === 'pool'" @click="refrescarPool" :disabled="store.loadingPool"
        class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 flex items-center gap-1 active:scale-95 transition">
        <span :class="store.loadingPool ? 'animate-spin' : ''">🔄</span> Refrescar
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex bg-gray-100 rounded-xl p-1">
      <button @click="tab = 'pool'"
        class="flex-1 py-2.5 text-sm font-semibold rounded-lg transition"
        :class="tab === 'pool' ? 'bg-white text-blue-700 shadow' : 'text-gray-500'">
        Pool <span v-if="store.pool.length" class="ml-1 text-xs">({{ store.pool.length }})</span>
      </button>
      <button @click="tab = 'mias'"
        class="flex-1 py-2.5 text-sm font-semibold rounded-lg transition"
        :class="tab === 'mias' ? 'bg-white text-blue-700 shadow' : 'text-gray-500'">
        Mis órdenes <span v-if="store.misOrdenes.length" class="ml-1 text-xs">({{ store.misOrdenes.length }})</span>
      </button>
    </div>

    <!-- Toast -->
    <transition name="fade">
      <div v-if="toast.msg" :class="toast.error ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200'"
        class="border rounded-xl px-4 py-3 text-sm font-medium">
        {{ toast.msg }}
      </div>
    </transition>

    <!-- ═══════════ PESTAÑA POOL ═══════════ -->
    <div v-if="tab === 'pool'">
      <div v-if="store.loadingPool && !store.pool.length" class="text-center py-12">
        <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
      </div>
      <div v-else-if="store.pool.length === 0" class="text-center py-16">
        <span class="text-5xl block mb-4">📭</span>
        <p class="text-gray-500">No hay órdenes pendientes</p>
        <p class="text-sm text-gray-400 mt-1">Las nuevas órdenes aparecerán aquí automáticamente</p>
      </div>
      <div v-else class="space-y-3">
        <div v-for="op in store.pool" :key="op.id"
          class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
          <div class="flex items-center justify-between gap-2 mb-3">
            <span class="text-xs font-bold px-2.5 py-1 rounded-full" :class="tipoBadge(op).class">{{ tipoBadge(op).label }}</span>
            <span class="text-xs text-gray-400">{{ formatHora(op.created_at) }}</span>
          </div>

          <p v-if="op.cliente?.nombre" class="text-sm text-gray-500 mb-2">
            Cliente: <span class="font-medium text-gray-700">{{ op.cliente.nombre }}</span>
          </p>

          <!-- Monto destacado -->
          <div class="bg-blue-50 rounded-xl px-4 py-3 mb-3">
            <p class="text-xs text-blue-600 font-medium">Monto a pagar</p>
            <p class="text-2xl font-bold text-blue-800">{{ formatMoney(monto(op)) }} {{ monedaLabel(op) }}</p>
          </div>

          <!-- Cuenta destino -->
          <div class="space-y-1 text-sm mb-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Cuenta destino</p>
            <p class="text-gray-700"><span class="text-gray-400">Banco:</span> {{ banco(op) || '—' }}</p>
            <p class="text-gray-700"><span class="text-gray-400">Cuenta:</span> {{ numeroCuenta(op) || '—' }}</p>
            <p class="text-gray-700"><span class="text-gray-400">Titular:</span> {{ titular(op) || '—' }}</p>
          </div>

          <button @click="tomar(op)" :disabled="acting === op.id"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white font-semibold py-3 rounded-xl transition active:scale-[0.98] flex items-center justify-center gap-2">
            <span v-if="acting === op.id" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ acting === op.id ? 'Tomando...' : 'Tomar orden' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════ PESTAÑA MIS ÓRDENES ═══════════ -->
    <div v-else>
      <div v-if="store.loadingMias && !store.misOrdenes.length" class="text-center py-12">
        <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
      </div>
      <div v-else-if="store.misOrdenes.length === 0" class="text-center py-16">
        <span class="text-5xl block mb-4">🗂️</span>
        <p class="text-gray-500">No tienes órdenes asignadas</p>
        <p class="text-sm text-gray-400 mt-1">Toma una orden del pool para empezar</p>
      </div>
      <div v-else class="space-y-3">
        <div v-for="op in store.misOrdenes" :key="op.id"
          class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
          <div class="flex items-center justify-between gap-2 mb-3">
            <span class="text-xs font-bold px-2.5 py-1 rounded-full" :class="tipoBadge(op).class">{{ tipoBadge(op).label }}</span>
            <span class="text-xs text-gray-400">Tomada {{ formatHora(op.asignada_at) }}</span>
          </div>

          <p v-if="op.cliente?.nombre" class="text-sm text-gray-500 mb-2">
            Cliente: <span class="font-medium text-gray-700">{{ op.cliente.nombre }}</span>
          </p>

          <div class="bg-blue-50 rounded-xl px-4 py-3 mb-3">
            <p class="text-xs text-blue-600 font-medium">Monto a pagar</p>
            <p class="text-2xl font-bold text-blue-800">{{ formatMoney(monto(op)) }} {{ monedaLabel(op) }}</p>
          </div>

          <div class="space-y-1 text-sm mb-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Datos de pago</p>
            <p class="text-gray-700"><span class="text-gray-400">Banco:</span> {{ banco(op) || '—' }}</p>
            <p class="text-gray-700"><span class="text-gray-400">Cuenta:</span> {{ numeroCuenta(op) || '—' }}</p>
            <p class="text-gray-700"><span class="text-gray-400">Titular:</span> {{ titular(op) || '—' }}</p>
          </div>

          <!-- Copiar datos -->
          <button @click="copiar(op)"
            class="w-full mb-2 border-2 font-semibold py-3 rounded-xl transition active:scale-[0.98] flex items-center justify-center gap-2"
            :class="copiedId === op.id ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'">
            <span>{{ copiedId === op.id ? '✓' : '📋' }}</span>
            {{ copiedId === op.id ? '¡Copiado!' : 'Copiar datos' }}
          </button>

          <div class="grid grid-cols-2 gap-2">
            <button @click="soltar(op)" :disabled="acting === op.id"
              class="border border-gray-300 bg-white hover:bg-gray-50 disabled:opacity-50 text-gray-700 font-semibold py-3 rounded-xl transition active:scale-[0.98]">
              Soltar
            </button>
            <button @click="pagar(op)" :disabled="acting === op.id"
              class="bg-green-600 hover:bg-green-700 disabled:bg-green-300 text-white font-semibold py-3 rounded-xl transition active:scale-[0.98] flex items-center justify-center gap-2">
              <span v-if="acting === op.id" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ acting === op.id ? '...' : 'Pagada' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { usePoolStore } from '../stores/pool.js'

const store = usePoolStore()
const tab = ref('pool')
const acting = ref(null)
const copiedId = ref(null)
const toast = ref({ msg: '', error: false })
let refreshTimer = null
let toastTimer = null
let copyTimer = null

// ── Toast helper ──
function showToast(msg, error = false) {
  toast.value = { msg, error }
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = { msg: '', error: false } }, 3000)
}

// ── Movimiento de pago (cuenta destino) ──
function pagoMov(op) {
  const movs = op.movimientos || []
  return movs.find(m => m.cuenta?.cliente)
      || movs.find(m => parseFloat(m.monto) < 0)
      || movs[0]
      || null
}

function banco(op) {
  return pagoMov(op)?.cuenta?.banco?.nombre || ''
}
function numeroCuenta(op) {
  return pagoMov(op)?.cuenta?.numero_cuenta || ''
}
function titular(op) {
  const c = pagoMov(op)?.cuenta
  return c?.cliente?.nombre || c?.titular?.nombre || c?.alias || ''
}
function monto(op) {
  const m = pagoMov(op)
  return m ? Math.abs(parseFloat(m.monto)) : 0
}
function monedaLabel(op) {
  const mo = pagoMov(op)?.moneda
  return mo?.simbolo || mo?.codigo || ''
}

// ── Badges ──
function tipoBadge(op) {
  const codigo = op.tipo_operacion?.codigo
  const map = {
    compra_usd: { label: 'Compra', class: 'bg-blue-100 text-blue-700' },
    venta_usd:  { label: 'Venta', class: 'bg-green-100 text-green-700' },
    cambio:     { label: 'Cambio', class: 'bg-orange-100 text-orange-700' },
  }
  return map[codigo] || { label: op.tipo_operacion?.nombre || 'Operación', class: 'bg-gray-100 text-gray-600' }
}

// ── Formatos ──
function formatMoney(n) {
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(n) || 0)
}
function formatHora(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('es-VE', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

// ── Acciones ──
async function tomar(op) {
  acting.value = op.id
  try {
    await store.tomar(op.id)
    showToast('Orden tomada. Está en "Mis órdenes".')
    await Promise.all([store.fetchPool(), store.fetchMisOrdenes()])
  } catch (err) {
    const msg = err.response?.data?.message || 'No se pudo tomar la orden'
    showToast(msg, true)
    await store.fetchPool()
  } finally {
    acting.value = null
  }
}

async function soltar(op) {
  acting.value = op.id
  try {
    await store.soltar(op.id)
    showToast('Orden devuelta al pool.')
    await Promise.all([store.fetchMisOrdenes(), store.fetchPool()])
  } catch (err) {
    showToast(err.response?.data?.message || 'No se pudo soltar la orden', true)
  } finally {
    acting.value = null
  }
}

async function pagar(op) {
  acting.value = op.id
  try {
    await store.pagar(op.id)
    showToast('Orden marcada como pagada.')
    await store.fetchMisOrdenes()
  } catch (err) {
    showToast(err.response?.data?.message || 'No se pudo marcar como pagada', true)
  } finally {
    acting.value = null
  }
}

// ── Copiar datos al portapapeles ──
async function copiar(op) {
  const texto = [
    `Banco: ${banco(op) || '—'}`,
    `Cuenta: ${numeroCuenta(op) || '—'}`,
    `Titular: ${titular(op) || '—'}`,
    `Monto: ${formatMoney(monto(op))} ${monedaLabel(op)}`,
  ].join('\n')

  try {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(texto)
    } else {
      const ta = document.createElement('textarea')
      ta.value = texto
      ta.style.position = 'fixed'
      ta.style.opacity = '0'
      document.body.appendChild(ta)
      ta.focus()
      ta.select()
      document.execCommand('copy')
      document.body.removeChild(ta)
    }
    copiedId.value = op.id
    clearTimeout(copyTimer)
    copyTimer = setTimeout(() => { copiedId.value = null }, 2000)
  } catch {
    showToast('No se pudo copiar', true)
  }
}

// ── Refresh manual + auto cada 30s ──
function refrescarPool() {
  store.fetchPool()
}

watch(tab, (t) => {
  if (t === 'mias') store.fetchMisOrdenes()
  else store.fetchPool()
})

onMounted(() => {
  store.fetchPool()
  store.fetchMisOrdenes()
  refreshTimer = setInterval(() => {
    if (tab.value === 'pool') store.fetchPool()
  }, 30000)
})

onUnmounted(() => {
  clearInterval(refreshTimer)
  clearTimeout(toastTimer)
  clearTimeout(copyTimer)
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
